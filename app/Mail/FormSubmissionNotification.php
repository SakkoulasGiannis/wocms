<?php

namespace App\Mail;

use App\Models\Form;
use App\Models\FormSubmission;
use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;
use Illuminate\Queue\SerializesModels;

class FormSubmissionNotification extends Mailable
{
    use Queueable, SerializesModels;

    public $form;

    public $submissionData;

    public $submission;

    public $formattedData;

    public $replyToEmail;

    /**
     * Create a new message instance.
     */
    public function __construct(Form $form, array $submissionData, ?FormSubmission $submission = null)
    {
        $this->form = $form;
        $this->submissionData = $submissionData;
        $this->submission = $submission;

        // Format data for display
        $this->formattedData = $this->formatSubmissionData();

        // Set reply-to email if available
        $this->replyToEmail = $this->extractReplyToEmail();
    }

    /**
     * Get the message envelope.
     */
    public function envelope(): Envelope
    {
        $envelope = (new Envelope)->from(config('mail.from.address'))
            ->subject($this->form->notification_subject ?? 'New Form Submission: '.$this->form->name);

        // Add reply-to if available
        if ($this->replyToEmail) {
            $envelope->replyTo($this->replyToEmail);
        }

        return $envelope;
    }

    /**
     * Get the message content definition.
     */
    public function content(): Content
    {
        return new Content(
            view: 'emails.form-submission',
        );
    }

    /**
     * Get the attachments for the message.
     *
     * @return array<int, \Illuminate\Mail\Mailables\Attachment>
     */
    public function attachments(): array
    {
        return [];
    }

    /**
     * Format submission data for display
     */
    protected function formatSubmissionData(): array
    {
        $formatted = [];

        foreach ($this->submissionData as $key => $value) {
            $field = $this->form->fields()->where('name', $key)->first();
            $label = $field ? $field->label : ucfirst(str_replace('_', ' ', $key));
            $isFileField = $field && $field->type === 'file';

            // Handle different value types. File values are rendered as HTML links, so
            // every path must be escaped before it reaches the `{!! !!}` view output -
            // a crafted filename/stored path must never break out of the anchor tag.
            if ($isFileField && $value) {
                $paths = is_array($value) ? $value : [$value];
                $links = [];
                foreach ($paths as $path) {
                    $links[] = '<a href="'.e(asset('storage/'.$path)).'" target="_blank">View File</a>';
                }
                $displayValue = implode(', ', $links);
            } elseif (is_array($value)) {
                $displayValue = implode(', ', $value);
            } else {
                $displayValue = $value;
            }

            $formatted[] = [
                'label' => $label,
                'value' => $displayValue,
                'is_html' => $isFileField && (bool) $value,
            ];
        }

        return $formatted;
    }

    /**
     * Extract reply-to email from submission data
     */
    protected function extractReplyToEmail(): ?string
    {
        // Try to find an email field in the submission
        foreach ($this->submissionData as $key => $value) {
            $field = $this->form->fields()->where('name', $key)->first();

            if ($field && $field->type === 'email' && filter_var($value, FILTER_VALIDATE_EMAIL)) {
                return $value;
            }
        }

        return null;
    }
}
