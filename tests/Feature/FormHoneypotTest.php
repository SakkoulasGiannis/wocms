<?php

namespace Tests\Feature;

use App\Livewire\Frontend\FormRenderer;
use App\Mail\FormSubmissionNotification;
use App\Models\Form;
use App\Models\FormField;
use App\Models\FormSubmission;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Mail;
use Livewire\Livewire;
use Tests\TestCase;

/**
 * Covers the spam protection built into {@see FormRenderer}:
 *
 *   1. A legitimate submission (empty honeypot, human-plausible timing)
 *      is stored and the admin notification mail is sent.
 *   2. A filled honeypot field ("website_url") silently fakes success:
 *      nothing is stored, no mail goes out.
 *   3. A submission completed faster than the time-gate threshold is
 *      likewise silently discarded.
 */
class FormHoneypotTest extends TestCase
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

        FormField::create([
            'form_id' => $form->id,
            'name' => 'email',
            'label' => 'Email',
            'type' => 'email',
            'is_required' => true,
            'order' => 2,
        ]);

        return $form;
    }

    public function test_normal_submission_is_stored_and_notification_sent(): void
    {
        Mail::fake();
        $form = $this->makeForm();

        $component = Livewire::test(FormRenderer::class, ['slug' => 'contact-form']);

        $this->travel(10)->seconds();

        $component
            ->set('formData.name', 'Giannis')
            ->set('formData.email', 'giannis@example.com')
            ->call('submit')
            ->assertHasNoErrors()
            ->assertSet('showSuccessMessage', true);

        $this->assertSame(1, FormSubmission::count());
        $this->assertSame('Giannis', FormSubmission::first()->data['name']);

        Mail::assertSent(FormSubmissionNotification::class);
    }

    public function test_filled_honeypot_fakes_success_and_stores_nothing(): void
    {
        Mail::fake();
        $this->makeForm();

        $component = Livewire::test(FormRenderer::class, ['slug' => 'contact-form']);

        $this->travel(10)->seconds();

        $component
            ->set('formData.name', 'Spam Bot')
            ->set('formData.email', 'bot@spam.example')
            ->set('website_url', 'https://spam.example.com')
            ->call('submit')
            ->assertSet('showSuccessMessage', true)
            ->assertSee('Thank you for your submission!');

        $this->assertSame(0, FormSubmission::count());

        Mail::assertNothingOutgoing();
    }

    public function test_too_fast_submission_fakes_success_and_stores_nothing(): void
    {
        Mail::fake();
        $this->makeForm();

        Livewire::test(FormRenderer::class, ['slug' => 'contact-form'])
            ->set('formData.name', 'Fast Bot')
            ->set('formData.email', 'bot@spam.example')
            ->call('submit')
            ->assertSet('showSuccessMessage', true);

        $this->assertSame(0, FormSubmission::count());

        Mail::assertNothingOutgoing();
    }

    public function test_honeypot_field_is_rendered_hidden(): void
    {
        $this->makeForm();

        Livewire::test(FormRenderer::class, ['slug' => 'contact-form'])
            ->assertSeeHtml('name="website_url"')
            ->assertSeeHtml('left:-9999px');
    }
}
