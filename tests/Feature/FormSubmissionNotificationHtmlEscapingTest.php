<?php

namespace Tests\Feature;

use App\Mail\FormSubmissionNotification;
use App\Models\Form;
use App\Models\FormField;
use App\Models\FormSubmission;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/**
 * SECURITY REGRESSION: the admin form-submission notification email is sent
 * with is_html=true. A crafted filename (or field value) must never break out
 * of its HTML context - it must be escaped, not raw-echoed.
 */
class FormSubmissionNotificationHtmlEscapingTest extends TestCase
{
    use RefreshDatabase;

    protected const HOSTILE = '"><img src=x onerror=alert(1)>.pdf';

    /** @test */
    public function a_hostile_uploaded_file_path_is_escaped_in_the_rendered_email(): void
    {
        $form = Form::create([
            'name' => 'Contact Form',
            'is_active' => true,
        ]);

        FormField::create([
            'form_id' => $form->id,
            'name' => 'attachment',
            'label' => 'Attachment',
            'type' => 'file',
            'order' => 1,
        ]);

        $submission = FormSubmission::create([
            'form_id' => $form->id,
            'data' => ['attachment' => self::HOSTILE],
            'ip_address' => '127.0.0.1',
            'is_read' => false,
            'is_spam' => false,
        ]);

        $html = (new FormSubmissionNotification($form, ['attachment' => self::HOSTILE], $submission))->render();

        $this->assertStringNotContainsString('<img src=x onerror=alert(1)>', $html);
        $this->assertStringContainsString(
            htmlspecialchars('<img src=x onerror=alert(1)>', ENT_QUOTES, 'UTF-8'),
            $html
        );
    }

    /** @test */
    public function multiple_hostile_uploaded_file_paths_are_each_escaped(): void
    {
        $form = Form::create([
            'name' => 'Contact Form',
            'is_active' => true,
        ]);

        FormField::create([
            'form_id' => $form->id,
            'name' => 'attachments',
            'label' => 'Attachments',
            'type' => 'file',
            'order' => 1,
        ]);

        $paths = ['clean-file.pdf', self::HOSTILE];

        $html = (new FormSubmissionNotification($form, ['attachments' => $paths]))->render();

        $this->assertStringNotContainsString('<img src=x onerror=alert(1)>', $html);
        $this->assertStringContainsString(
            htmlspecialchars('<img src=x onerror=alert(1)>', ENT_QUOTES, 'UTF-8'),
            $html
        );
    }

    /** @test */
    public function a_hostile_submitted_field_value_is_escaped_in_the_rendered_email(): void
    {
        $form = Form::create([
            'name' => 'Contact Form',
            'is_active' => true,
        ]);

        FormField::create([
            'form_id' => $form->id,
            'name' => 'message',
            'label' => 'Message',
            'type' => 'textarea',
            'order' => 1,
        ]);

        $hostileValue = '<img src=x onerror=alert(1)>';

        $html = (new FormSubmissionNotification($form, ['message' => $hostileValue]))->render();

        $this->assertStringNotContainsString('<img src=x onerror=alert(1)>', $html);
        $this->assertStringContainsString(
            htmlspecialchars($hostileValue, ENT_QUOTES, 'UTF-8'),
            $html
        );
    }

    /** @test */
    public function a_hostile_referer_and_user_agent_are_escaped_in_the_rendered_email(): void
    {
        $form = Form::create([
            'name' => 'Contact Form',
            'is_active' => true,
        ]);

        $hostile = '"><img src=x onerror=alert(1)>';

        $submission = FormSubmission::create([
            'form_id' => $form->id,
            'data' => [],
            'ip_address' => '127.0.0.1',
            'user_agent' => $hostile,
            'referer' => $hostile,
            'is_read' => false,
            'is_spam' => false,
        ]);

        $html = (new FormSubmissionNotification($form, [], $submission))->render();

        $this->assertStringNotContainsString('<img src=x onerror=alert(1)>', $html);
    }
}
