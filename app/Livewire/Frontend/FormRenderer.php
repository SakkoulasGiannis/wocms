<?php

namespace App\Livewire\Frontend;

use App\Mail\FormAutoReply;
use App\Mail\FormSubmissionNotification;
use App\Models\Form;
use App\Models\FormSubmission;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\Storage;
use Livewire\Component;
use Livewire\WithFileUploads;

class FormRenderer extends Component
{
    use WithFileUploads;

    public $form;
    public $formData = [];
    public $fileUploads = [];
    public $honeypot = '';
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
    }

    public function submit()
    {
        // Honeypot check - if filled, it's spam
        if (!empty($this->honeypot)) {
            $this->showSuccessMessage = true;
            $this->successMessage = $this->form->success_message ?? 'Thank you for your submission!';
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

    protected function buildValidationRules(): array
    {
        $rules = [];

        foreach ($this->form->fields as $field) {
            if ($field->type === 'file') {
                // File fields use separate property
                $fieldRules = $field->getValidationRules();
                if (!empty($fieldRules)) {
                    $rules['fileUploads.' . $field->name] = $fieldRules;
                }
            } else {
                $fieldRules = $field->getValidationRules();
                if (!empty($fieldRules)) {
                    $rules['formData.' . $field->name] = $fieldRules;
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
                    $path = $file->store('form-submissions/' . $this->form->slug, 'public');
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

            if (!empty($recipients)) {
                try {
                    Mail::to($recipients)->send(
                        new FormSubmissionNotification($this->form, $data, $submission)
                    );
                } catch (\Exception $e) {
                    \Log::error('Failed to send form notification: ' . $e->getMessage());
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
                    \Log::error('Failed to send auto-reply: ' . $e->getMessage());
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
        $this->honeypot = '';

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
        return view('livewire.frontend.form-renderer');
    }
}
