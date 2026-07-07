<?php

namespace App\Livewire\Frontend;

use App\Mail\FormAutoReply;
use App\Mail\FormSubmissionNotification;
use App\Models\Form;
use App\Models\FormSubmission;
use App\Models\Setting;
use Illuminate\Http\Client\ConnectionException;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\Storage;
use Livewire\Attributes\Locked;
use Livewire\Component;
use Livewire\WithFileUploads;

class FormRenderer extends Component
{
    use WithFileUploads;

    /** Minimum seconds a human plausibly needs between render and submit. */
    protected const MIN_SECONDS_TO_SUBMIT = 3;

    public $form;

    public $formData = [];

    public $fileUploads = [];

    /** Honeypot field — must stay empty; bots love filling "website_url". */
    public $website_url = '';

    /** Google reCAPTCHA v3 token, set client-side just before submit. */
    public string $recaptchaToken = '';

    #[Locked]
    public int $formLoadedAt = 0;

    public $showSuccessMessage = false;

    public $successMessage = '';

    protected $listeners = ['resetForm'];

    public function mount($slug = null, $formId = null)
    {
        // Load form by slug or ID
        if ($slug) {
            $this->form = Form::where('slug', $slug)
                ->where('is_active', true)
                ->with('fields')
                ->firstOrFail();
        } elseif ($formId) {
            $this->form = Form::where('id', $formId)
                ->where('is_active', true)
                ->with('fields')
                ->firstOrFail();
        } else {
            abort(404, 'Form not found');
        }

        // Initialize form data with default values
        foreach ($this->form->fields as $field) {
            if ($field->type !== 'file' && $field->default_value) {
                $this->formData[$field->name] = $field->default_value;
            }
        }

        // Stamp render time for the spam time-gate
        $this->formLoadedAt = now()->timestamp;
    }

    public function submit()
    {
        // Spam check (honeypot + time-gate): silently pretend success,
        // never store the submission and never send mail.
        if ($this->isSpamSubmission()) {
            $this->handleSuccess();

            return;
        }

        // reCAPTCHA v3 check (only when enabled + keys configured):
        // same silent-reject pattern as the honeypot.
        if ($this->failsRecaptcha()) {
            $this->handleSuccess();

            return;
        }

        // Build validation rules
        $rules = $this->buildValidationRules();

        // Validate
        $this->validate($rules);

        // Process file uploads
        $submissionData = $this->processFileUploads();

        // Merge form data with file paths
        $submissionData = array_merge($this->formData, $submissionData);

        // Store submission if enabled
        $submission = null;
        if ($this->form->store_submissions) {
            $submission = $this->storeSubmission($submissionData);
        }

        // Send email notifications
        $this->sendNotifications($submissionData, $submission);

        // Handle success
        $this->handleSuccess();
    }

    protected function isSpamSubmission(): bool
    {
        if (! empty($this->website_url)) {
            Log::info('Form honeypot triggered', [
                'form_id' => $this->form->id,
                'form_slug' => $this->form->slug,
                'reason' => 'honeypot_filled',
                'ip' => request()->ip(),
            ]);

            return true;
        }

        if ($this->formLoadedAt > 0 && (now()->timestamp - $this->formLoadedAt) < self::MIN_SECONDS_TO_SUBMIT) {
            Log::info('Form honeypot triggered', [
                'form_id' => $this->form->id,
                'form_slug' => $this->form->slug,
                'reason' => 'submitted_too_fast',
                'elapsed_seconds' => now()->timestamp - $this->formLoadedAt,
                'ip' => request()->ip(),
            ]);

            return true;
        }

        return false;
    }

    /**
     * reCAPTCHA v3 is active only when the admin enabled it AND both keys exist.
     */
    public static function recaptchaIsActive(): bool
    {
        return (bool) Setting::get('recaptcha_enabled', false)
            && Setting::get('recaptcha_site_key', '') !== ''
            && Setting::get('recaptcha_secret_key', '') !== '';
    }

    /**
     * Verify the reCAPTCHA v3 token with Google. Returns true when the
     * submission must be silently rejected. Fails OPEN on network/API
     * errors so the form never breaks if Google is unreachable.
     */
    protected function failsRecaptcha(): bool
    {
        if (! static::recaptchaIsActive()) {
            return false;
        }

        try {
            $response = Http::asForm()->post('https://www.google.com/recaptcha/api/siteverify', [
                'secret' => Setting::get('recaptcha_secret_key', ''),
                'response' => $this->recaptchaToken,
                'remoteip' => request()->ip(),
            ]);
        } catch (ConnectionException $e) {
            Log::warning('Form recaptcha verification unavailable — allowing submission (fail-open)', [
                'form_id' => $this->form->id,
                'form_slug' => $this->form->slug,
                'error' => $e->getMessage(),
                'ip' => request()->ip(),
            ]);

            return false;
        }

        if ($response->failed()) {
            Log::warning('Form recaptcha verification unavailable — allowing submission (fail-open)', [
                'form_id' => $this->form->id,
                'form_slug' => $this->form->slug,
                'status' => $response->status(),
                'ip' => request()->ip(),
            ]);

            return false;
        }

        $result = $response->json();
        $minScore = (float) Setting::get('recaptcha_min_score', 0.5);
        $score = (float) ($result['score'] ?? 0);

        if (($result['success'] ?? false) === true && $score >= $minScore) {
            return false;
        }

        Log::info('Form recaptcha failed', [
            'form_id' => $this->form->id,
            'form_slug' => $this->form->slug,
            'success' => $result['success'] ?? false,
            'score' => $result['score'] ?? null,
            'min_score' => $minScore,
            'error_codes' => $result['error-codes'] ?? [],
            'ip' => request()->ip(),
        ]);

        return true;
    }

    protected function buildValidationRules(): array
    {
        $rules = [];

        foreach ($this->form->fields as $field) {
            if ($field->type === 'file') {
                // File fields use separate property
                $fieldRules = $field->getValidationRules();
                if (! empty($fieldRules)) {
                    $rules['fileUploads.'.$field->name] = $fieldRules;
                }
            } else {
                $fieldRules = $field->getValidationRules();
                if (! empty($fieldRules)) {
                    $rules['formData.'.$field->name] = $fieldRules;
                }
            }
        }

        return $rules;
    }

    protected function processFileUploads(): array
    {
        $uploadedFiles = [];

        foreach ($this->form->fields as $field) {
            if ($field->type === 'file' && isset($this->fileUploads[$field->name])) {
                $file = $this->fileUploads[$field->name];

                if ($file) {
                    // Store file in public storage
                    $path = $file->store('form-submissions/'.$this->form->slug, 'public');
                    $uploadedFiles[$field->name] = $path;
                }
            }
        }

        return $uploadedFiles;
    }

    protected function storeSubmission(array $data): FormSubmission
    {
        return FormSubmission::create([
            'form_id' => $this->form->id,
            'data' => $data,
            'ip_address' => request()->ip(),
            'user_agent' => request()->userAgent(),
            'referer' => request()->header('referer'),
            'is_read' => false,
            'is_spam' => false,
        ]);
    }

    protected function sendNotifications(array $data, ?FormSubmission $submission): void
    {
        // Send admin notification
        if ($this->form->send_email_notification) {
            $recipients = $this->form->getRecipientsArray();

            if (! empty($recipients)) {
                try {
                    Mail::to($recipients)->send(
                        new FormSubmissionNotification($this->form, $data, $submission)
                    );
                } catch (\Exception $e) {
                    \Log::error('Failed to send form notification: '.$e->getMessage());
                }
            }
        }

        // Send auto-reply
        if ($this->form->send_auto_reply && $this->form->auto_reply_email_field) {
            $emailFieldName = $this->form->auto_reply_email_field;
            $userEmail = $data[$emailFieldName] ?? null;

            if ($userEmail && filter_var($userEmail, FILTER_VALIDATE_EMAIL)) {
                try {
                    Mail::to($userEmail)->send(
                        new FormAutoReply($this->form)
                    );
                } catch (\Exception $e) {
                    \Log::error('Failed to send auto-reply: '.$e->getMessage());
                }
            }
        }
    }

    protected function handleSuccess(): void
    {
        // Check if redirect is set
        if ($this->form->redirect_url) {
            redirect($this->form->redirect_url);

            return;
        }

        // Show success message
        $this->showSuccessMessage = true;
        $this->successMessage = $this->form->success_message ?? 'Thank you for your submission!';

        // Reset form
        $this->resetForm();
    }

    public function resetForm(): void
    {
        $this->formData = [];
        $this->fileUploads = [];
        $this->website_url = '';
        $this->recaptchaToken = '';
        $this->formLoadedAt = now()->timestamp;

        // Reset with default values
        foreach ($this->form->fields as $field) {
            if ($field->type !== 'file' && $field->default_value) {
                $this->formData[$field->name] = $field->default_value;
            }
        }

        $this->resetValidation();
    }

    public function render()
    {
        return view('livewire.frontend.form-renderer', [
            'recaptchaSiteKey' => static::recaptchaIsActive()
                ? Setting::get('recaptcha_site_key', '')
                : null,
        ]);
    }
}
