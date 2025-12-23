<?php

namespace App\Livewire\Admin\Forms;

use App\Models\Form;
use App\Models\FormField;
use Livewire\Component;
use Illuminate\Support\Str;

class FormBuilder extends Component
{
    public $formId;
    public $form;

    // Form basic settings
    public $name = '';
    public $slug = '';
    public $description = '';
    public $is_active = true;
    public $store_submissions = true;

    // Submit button settings
    public $submit_button_text = 'Submit';
    public $success_message = 'Thank you! Your submission has been received.';
    public $redirect_url = '';

    // Email notification settings
    public $send_email_notification = false;
    public $notification_recipients = [];
    public $notification_recipients_string = ''; // For input field
    public $notification_subject = 'New Form Submission';
    public $notification_message = 'You have received a new form submission.';

    // Auto-reply settings
    public $send_auto_reply = false;
    public $auto_reply_email_field = '';
    public $auto_reply_subject = 'Thank you for your submission';
    public $auto_reply_message = 'Thank you for contacting us. We will get back to you soon.';

    // Fields
    public $fields = [];
    public $newFieldType = '';
    public $showAddField = false;

    public function mount($formId = null)
    {
        $this->formId = $formId;

        if ($formId) {
            // Edit mode
            $this->form = Form::with('fields')->findOrFail($formId);

            // Load form basic settings
            $this->name = $this->form->name;
            $this->slug = $this->form->slug;
            $this->description = $this->form->description;
            $this->is_active = $this->form->is_active;
            $this->store_submissions = $this->form->store_submissions;

            // Submit button settings
            $this->submit_button_text = $this->form->submit_button_text;
            $this->success_message = $this->form->success_message;
            $this->redirect_url = $this->form->redirect_url;

            // Email notification settings
            $this->send_email_notification = $this->form->send_email_notification;
            $this->notification_recipients = $this->form->notification_recipients ?? [];
            $this->notification_recipients_string = implode(', ', $this->notification_recipients);
            $this->notification_subject = $this->form->notification_subject;
            $this->notification_message = $this->form->notification_message;

            // Auto-reply settings
            $this->send_auto_reply = $this->form->send_auto_reply;
            $this->auto_reply_email_field = $this->form->auto_reply_email_field;
            $this->auto_reply_subject = $this->form->auto_reply_subject;
            $this->auto_reply_message = $this->form->auto_reply_message;

            // Load fields
            $this->fields = $this->form->fields->map(function ($field) {
                return [
                    'id' => $field->id,
                    'name' => $field->name,
                    'label' => $field->label,
                    'type' => $field->type,
                    'placeholder' => $field->placeholder,
                    'default_value' => $field->default_value,
                    'help_text' => $field->help_text,
                    'is_required' => $field->is_required,
                    'options' => $field->options ?? [],
                    'options_string' => is_array($field->options) ? implode("\n", $field->options) : '',
                    'validation_rules' => $field->validation_rules ?? [],
                    'validation_rules_string' => is_array($field->validation_rules) ? implode(', ', $field->validation_rules) : '',
                    'order' => $field->order,
                ];
            })->toArray();
        }
    }

    public function updatedName()
    {
        if (!$this->formId) {
            $this->slug = Str::slug($this->name);
        }
    }

    public function addField()
    {
        if (empty($this->newFieldType)) {
            session()->flash('error', 'Please select a field type.');
            return;
        }

        $fieldName = 'field_' . (count($this->fields) + 1);
        $fieldLabel = 'Field ' . (count($this->fields) + 1);

        $this->fields[] = [
            'id' => null,
            'name' => $fieldName,
            'label' => $fieldLabel,
            'type' => $this->newFieldType,
            'placeholder' => '',
            'default_value' => '',
            'help_text' => '',
            'is_required' => false,
            'options' => [],
            'options_string' => '',
            'validation_rules' => [],
            'validation_rules_string' => '',
            'order' => count($this->fields),
        ];

        $this->newFieldType = '';
        $this->showAddField = false;
    }

    public function removeField($index)
    {
        unset($this->fields[$index]);
        $this->fields = array_values($this->fields); // Re-index array
    }

    public function moveFieldUp($index)
    {
        if ($index > 0) {
            $temp = $this->fields[$index - 1];
            $this->fields[$index - 1] = $this->fields[$index];
            $this->fields[$index] = $temp;
        }
    }

    public function moveFieldDown($index)
    {
        if ($index < count($this->fields) - 1) {
            $temp = $this->fields[$index + 1];
            $this->fields[$index + 1] = $this->fields[$index];
            $this->fields[$index] = $temp;
        }
    }

    public function save()
    {
        // Validate basic form settings
        $this->validate([
            'name' => 'required|string|max:255',
            'slug' => 'required|string|max:255|unique:forms,slug,' . ($this->formId ?? 'NULL'),
            'submit_button_text' => 'required|string|max:255',
            'success_message' => 'required|string',
        ]);

        // Parse notification recipients
        if (!empty($this->notification_recipients_string)) {
            $this->notification_recipients = array_map('trim', explode(',', $this->notification_recipients_string));
        } else {
            $this->notification_recipients = [];
        }

        // Prepare form data
        $formData = [
            'name' => $this->name,
            'slug' => $this->slug,
            'description' => $this->description,
            'is_active' => $this->is_active,
            'store_submissions' => $this->store_submissions,
            'submit_button_text' => $this->submit_button_text,
            'success_message' => $this->success_message,
            'redirect_url' => $this->redirect_url,
            'send_email_notification' => $this->send_email_notification,
            'notification_recipients' => $this->notification_recipients,
            'notification_subject' => $this->notification_subject,
            'notification_message' => $this->notification_message,
            'send_auto_reply' => $this->send_auto_reply,
            'auto_reply_email_field' => $this->auto_reply_email_field,
            'auto_reply_subject' => $this->auto_reply_subject,
            'auto_reply_message' => $this->auto_reply_message,
        ];

        if ($this->formId) {
            // Update existing form
            $this->form->update($formData);

            // Update fields
            $this->updateFields();

            session()->flash('success', 'Form updated successfully!');
        } else {
            // Create new form
            $this->form = Form::create($formData);
            $this->formId = $this->form->id;

            // Create fields
            $this->updateFields();

            session()->flash('success', 'Form created successfully!');
        }

        return redirect()->route('admin.forms.edit', $this->form->id);
    }

    protected function updateFields()
    {
        // Get existing field IDs
        $existingFieldIds = collect($this->fields)
            ->pluck('id')
            ->filter()
            ->toArray();

        // Delete removed fields
        FormField::where('form_id', $this->form->id)
            ->whereNotIn('id', $existingFieldIds)
            ->delete();

        // Update or create fields
        foreach ($this->fields as $index => $fieldData) {
            // Parse options
            $options = [];
            if (in_array($fieldData['type'], ['select', 'radio', 'checkbox']) && !empty($fieldData['options_string'])) {
                $options = array_map('trim', explode("\n", $fieldData['options_string']));
            }

            // Parse validation rules
            $validationRules = [];
            if (!empty($fieldData['validation_rules_string'])) {
                $validationRules = array_map('trim', explode(',', $fieldData['validation_rules_string']));
            }

            $data = [
                'form_id' => $this->form->id,
                'name' => $fieldData['name'],
                'label' => $fieldData['label'],
                'type' => $fieldData['type'],
                'placeholder' => $fieldData['placeholder'],
                'default_value' => $fieldData['default_value'],
                'help_text' => $fieldData['help_text'],
                'is_required' => $fieldData['is_required'],
                'options' => $options,
                'validation_rules' => $validationRules,
                'order' => $index,
            ];

            if ($fieldData['id']) {
                // Update existing field
                FormField::where('id', $fieldData['id'])->update($data);
            } else {
                // Create new field
                FormField::create($data);
            }
        }
    }

    public function render()
    {
        $fieldTypes = Form::getFieldTypes();
        $emailFields = collect($this->fields)->where('type', 'email')->pluck('label', 'name')->toArray();

        return view('livewire.admin.forms.form-builder', [
            'fieldTypes' => $fieldTypes,
            'emailFields' => $emailFields,
        ])->layout('layouts.admin-clean');
    }
}
