<?php

namespace Tests\Feature;

use App\Livewire\Frontend\FormRenderer;
use App\Mail\FormSubmissionNotification;
use App\Models\Form;
use App\Models\FormField;
use App\Models\FormSubmission;
use App\Models\Setting;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Mail;
use Livewire\Livewire;
use Tests\TestCase;

/**
 * Covers the admin-toggleable Google reCAPTCHA v3 protection in
 * {@see FormRenderer}:
 *
 *   1. Disabled (default): submissions are stored normally and NO request
 *      is ever made to Google — zero behavioral change.
 *   2. Enabled + high score: the submission passes and is stored.
 *   3. Enabled + low score: silent reject — fake success, nothing stored,
 *      no mail (same pattern as the honeypot).
 *   4. Google API down / erroring: fail-OPEN — the submission is stored so
 *      the form never breaks because of Google.
 *   5. The api.js script tag is only rendered while reCAPTCHA is active.
 */
class FormRecaptchaTest extends TestCase
{
    use RefreshDatabase;

    protected function makeForm(): Form
    {
        $form = Form::create([
            'name' => 'Contact Form',
            'slug' => 'contact-form',
            'is_active' => true,
            'store_submissions' => true,
            'send_email_notification' => true,
            'notification_recipients' => ['admin@example.com'],
            'success_message' => 'Thank you for your submission!',
        ]);

        FormField::create([
            'form_id' => $form->id,
            'name' => 'name',
            'label' => 'Name',
            'type' => 'text',
            'is_required' => true,
            'order' => 1,
        ]);

        return $form;
    }

    protected function enableRecaptcha(string $minScore = '0.5'): void
    {
        Setting::set('recaptcha_enabled', '1', 'integrations');
        Setting::set('recaptcha_site_key', 'test-site-key', 'integrations');
        Setting::set('recaptcha_secret_key', 'test-secret-key', 'integrations');
        Setting::set('recaptcha_min_score', $minScore, 'integrations');
    }

    protected function submitForm(): \Livewire\Features\SupportTesting\Testable
    {
        $component = Livewire::test(FormRenderer::class, ['slug' => 'contact-form']);

        $this->travel(10)->seconds();

        return $component
            ->set('formData.name', 'Giannis')
            ->set('recaptchaToken', 'dummy-token')
            ->call('submit');
    }

    public function test_disabled_recaptcha_stores_normally_without_calling_google(): void
    {
        Mail::fake();
        Http::fake();
        $this->makeForm();

        $this->submitForm()
            ->assertHasNoErrors()
            ->assertSet('showSuccessMessage', true);

        $this->assertSame(1, FormSubmission::count());
        Http::assertNothingSent();
        Mail::assertSent(FormSubmissionNotification::class);
    }

    public function test_enabled_with_high_score_stores_submission(): void
    {
        Mail::fake();
        Http::fake([
            'www.google.com/recaptcha/api/siteverify' => Http::response([
                'success' => true,
                'score' => 0.9,
                'action' => 'form_submit',
            ]),
        ]);
        $this->makeForm();
        $this->enableRecaptcha();

        $this->submitForm()
            ->assertHasNoErrors()
            ->assertSet('showSuccessMessage', true);

        $this->assertSame(1, FormSubmission::count());
        Http::assertSent(function ($request) {
            return str_contains($request->url(), 'siteverify')
                && $request['secret'] === 'test-secret-key'
                && $request['response'] === 'dummy-token';
        });
        Mail::assertSent(FormSubmissionNotification::class);
    }

    public function test_enabled_with_low_score_silently_rejects(): void
    {
        Mail::fake();
        Http::fake([
            'www.google.com/recaptcha/api/siteverify' => Http::response([
                'success' => true,
                'score' => 0.2,
                'action' => 'form_submit',
            ]),
        ]);
        $this->makeForm();
        $this->enableRecaptcha();

        $this->submitForm()
            ->assertSet('showSuccessMessage', true)
            ->assertSee('Thank you for your submission!');

        $this->assertSame(0, FormSubmission::count());
        Mail::assertNothingOutgoing();
    }

    public function test_enabled_with_failed_verification_silently_rejects(): void
    {
        Mail::fake();
        Http::fake([
            'www.google.com/recaptcha/api/siteverify' => Http::response([
                'success' => false,
                'error-codes' => ['invalid-input-response'],
            ]),
        ]);
        $this->makeForm();
        $this->enableRecaptcha();

        $this->submitForm()->assertSet('showSuccessMessage', true);

        $this->assertSame(0, FormSubmission::count());
        Mail::assertNothingOutgoing();
    }

    public function test_google_api_error_fails_open_and_stores_submission(): void
    {
        Mail::fake();
        Http::fake([
            'www.google.com/recaptcha/api/siteverify' => Http::response('Server Error', 500),
        ]);
        $this->makeForm();
        $this->enableRecaptcha();

        $this->submitForm()
            ->assertHasNoErrors()
            ->assertSet('showSuccessMessage', true);

        $this->assertSame(1, FormSubmission::count());
        Mail::assertSent(FormSubmissionNotification::class);
    }

    public function test_enabled_but_missing_keys_stays_inactive(): void
    {
        Mail::fake();
        Http::fake();
        $this->makeForm();
        Setting::set('recaptcha_enabled', '1', 'integrations');

        $this->submitForm()->assertSet('showSuccessMessage', true);

        $this->assertSame(1, FormSubmission::count());
        Http::assertNothingSent();
    }

    public function test_script_tag_rendered_only_when_active(): void
    {
        $this->makeForm();

        Livewire::test(FormRenderer::class, ['slug' => 'contact-form'])
            ->assertDontSeeHtml('www.google.com/recaptcha/api.js');

        $this->enableRecaptcha();

        Livewire::test(FormRenderer::class, ['slug' => 'contact-form'])
            ->assertSeeHtml('www.google.com/recaptcha/api.js?render=test-site-key')
            ->assertSeeHtml('recaptchaToken');
    }
}
