@section('page-title', ($formId ? 'Edit' : 'New') . ' Form')

<div>
    @if (session()->has('success'))
        <div class="mb-6 bg-green-100 border border-green-400 text-green-700 px-4 py-3 rounded relative">
            {{ session('success') }}
        </div>
    @endif

    @if (session()->has('error'))
        <div class="mb-6 bg-red-100 border border-red-400 text-red-700 px-4 py-3 rounded relative">
            {{ session('error') }}
        </div>
    @endif

    <form wire:submit.prevent="save">
        <div class="space-y-6">

            <!-- Actions Bar -->
            <div class="flex items-center justify-between">
                <a href="{{ route('admin.forms.index') }}"
                   class="inline-flex items-center text-sm text-gray-600 hover:text-gray-900">
                    <svg class="w-5 h-5 mr-1" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 19l-7-7 7-7"/>
                    </svg>
                    Back to Forms
                </a>
                <button type="submit"
                        class="inline-flex items-center px-4 py-2 bg-blue-600 text-white rounded-lg hover:bg-blue-700 transition">
                    <svg class="w-5 h-5 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"/>
                    </svg>
                    {{ $formId ? 'Update' : 'Create' }} Form
                </button>
            </div>

            <!-- Basic Settings -->
            <div class="bg-white rounded-lg shadow p-6">
                <h3 class="text-lg font-semibold text-gray-900 mb-4">Basic Settings</h3>
                <div class="space-y-4">
                    <div class="grid grid-cols-2 gap-4">
                        <div>
                            <label class="block text-sm font-medium text-gray-700 mb-2">
                                Form Name <span class="text-red-500">*</span>
                            </label>
                            <input type="text"
                                   wire:model.live="name"
                                   class="w-full rounded-lg border-gray-300 shadow-sm focus:border-blue-500 focus:ring-blue-500 p-2 border"
                                   placeholder="Contact Form">
                            @error('name')
                                <p class="mt-1 text-sm text-red-600">{{ $message }}</p>
                            @enderror
                        </div>
                        <div>
                            <label class="block text-sm font-medium text-gray-700 mb-2">
                                Slug <span class="text-red-500">*</span>
                            </label>
                            <input type="text"
                                   wire:model="slug"
                                   class="w-full rounded-lg border-gray-300 shadow-sm focus:border-blue-500 focus:ring-blue-500 p-2 border"
                                   placeholder="contact-form">
                            @error('slug')
                                <p class="mt-1 text-sm text-red-600">{{ $message }}</p>
                            @enderror
                        </div>
                    </div>

                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-2">
                            Description
                        </label>
                        <textarea wire:model="description"
                                  rows="3"
                                  class="w-full rounded-lg border-gray-300 shadow-sm focus:border-blue-500 focus:ring-blue-500 p-2 border"
                                  placeholder="Brief description of this form"></textarea>
                    </div>

                    <div class="flex items-center space-x-6">
                        <label class="flex items-center">
                            <input type="checkbox"
                                   wire:model="is_active"
                                   class="rounded border-gray-300 text-blue-600 shadow-sm focus:border-blue-500 focus:ring-blue-500">
                            <span class="ml-2 text-sm text-gray-700">Active (visible on frontend)</span>
                        </label>

                        <label class="flex items-center">
                            <input type="checkbox"
                                   wire:model="store_submissions"
                                   class="rounded border-gray-300 text-blue-600 shadow-sm focus:border-blue-500 focus:ring-blue-500">
                            <span class="ml-2 text-sm text-gray-700">Store submissions in database</span>
                        </label>
                    </div>
                </div>
            </div>

            <!-- Submit Button Settings -->
            <div class="bg-white rounded-lg shadow p-6">
                <h3 class="text-lg font-semibold text-gray-900 mb-4">Submit Button & Success Message</h3>
                <div class="space-y-4">
                    <div class="grid grid-cols-2 gap-4">
                        <div>
                            <label class="block text-sm font-medium text-gray-700 mb-2">
                                Submit Button Text <span class="text-red-500">*</span>
                            </label>
                            <input type="text"
                                   wire:model="submit_button_text"
                                   class="w-full rounded-lg border-gray-300 shadow-sm focus:border-blue-500 focus:ring-blue-500 p-2 border"
                                   placeholder="Submit">
                            @error('submit_button_text')
                                <p class="mt-1 text-sm text-red-600">{{ $message }}</p>
                            @enderror
                        </div>
                        <div>
                            <label class="block text-sm font-medium text-gray-700 mb-2">
                                Redirect URL (optional)
                            </label>
                            <input type="url"
                                   wire:model="redirect_url"
                                   class="w-full rounded-lg border-gray-300 shadow-sm focus:border-blue-500 focus:ring-blue-500 p-2 border"
                                   placeholder="/thank-you">
                            <p class="mt-1 text-xs text-gray-500">Redirect after submission (leave empty to show message)</p>
                        </div>
                    </div>

                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-2">
                            Success Message <span class="text-red-500">*</span>
                        </label>
                        <textarea wire:model="success_message"
                                  rows="3"
                                  class="w-full rounded-lg border-gray-300 shadow-sm focus:border-blue-500 focus:ring-blue-500 p-2 border"
                                  placeholder="Thank you! Your submission has been received."></textarea>
                        @error('success_message')
                            <p class="mt-1 text-sm text-red-600">{{ $message }}</p>
                        @enderror
                    </div>
                </div>
            </div>

            <!-- Email Notification Settings -->
            <div class="bg-white rounded-lg shadow p-6">
                <div class="flex items-center justify-between mb-4">
                    <h3 class="text-lg font-semibold text-gray-900">Email Notifications</h3>
                    <label class="flex items-center">
                        <input type="checkbox"
                               wire:model.live="send_email_notification"
                               class="rounded border-gray-300 text-blue-600 shadow-sm focus:border-blue-500 focus:ring-blue-500">
                        <span class="ml-2 text-sm text-gray-700">Enable email notifications</span>
                    </label>
                </div>

                @if($send_email_notification)
                    <div class="space-y-4 border-t pt-4">
                        <div>
                            <label class="block text-sm font-medium text-gray-700 mb-2">
                                Notification Recipients
                            </label>
                            <input type="text"
                                   wire:model="notification_recipients_string"
                                   class="w-full rounded-lg border-gray-300 shadow-sm focus:border-blue-500 focus:ring-blue-500 p-2 border"
                                   placeholder="admin@example.com, sales@example.com">
                            <p class="mt-1 text-xs text-gray-500">Comma-separated email addresses</p>
                        </div>

                        <div>
                            <label class="block text-sm font-medium text-gray-700 mb-2">
                                Email Subject
                            </label>
                            <input type="text"
                                   wire:model="notification_subject"
                                   class="w-full rounded-lg border-gray-300 shadow-sm focus:border-blue-500 focus:ring-blue-500 p-2 border"
                                   placeholder="New Form Submission">
                        </div>

                        <div>
                            <label class="block text-sm font-medium text-gray-700 mb-2">
                                Email Message
                            </label>
                            <textarea wire:model="notification_message"
                                      rows="4"
                                      class="w-full rounded-lg border-gray-300 shadow-sm focus:border-blue-500 focus:ring-blue-500 p-2 border"
                                      placeholder="You have received a new form submission."></textarea>
                            <p class="mt-1 text-xs text-gray-500">The submission data will be included automatically</p>
                        </div>
                    </div>
                @endif
            </div>

            <!-- Auto-Reply Settings -->
            <div class="bg-white rounded-lg shadow p-6">
                <div class="flex items-center justify-between mb-4">
                    <h3 class="text-lg font-semibold text-gray-900">Auto-Reply</h3>
                    <label class="flex items-center">
                        <input type="checkbox"
                               wire:model.live="send_auto_reply"
                               class="rounded border-gray-300 text-blue-600 shadow-sm focus:border-blue-500 focus:ring-blue-500">
                        <span class="ml-2 text-sm text-gray-700">Enable auto-reply</span>
                    </label>
                </div>

                @if($send_auto_reply)
                    <div class="space-y-4 border-t pt-4">
                        <div>
                            <label class="block text-sm font-medium text-gray-700 mb-2">
                                Email Field for Auto-Reply
                            </label>
                            <select wire:model="auto_reply_email_field"
                                    class="w-full rounded-lg border-gray-300 shadow-sm focus:border-blue-500 focus:ring-blue-500 p-2 border">
                                <option value="">-- Select Email Field --</option>
                                @foreach($emailFields as $fieldName => $fieldLabel)
                                    <option value="{{ $fieldName }}">{{ $fieldLabel }}</option>
                                @endforeach
                            </select>
                            <p class="mt-1 text-xs text-gray-500">Select which email field to send the auto-reply to</p>
                        </div>

                        <div>
                            <label class="block text-sm font-medium text-gray-700 mb-2">
                                Auto-Reply Subject
                            </label>
                            <input type="text"
                                   wire:model="auto_reply_subject"
                                   class="w-full rounded-lg border-gray-300 shadow-sm focus:border-blue-500 focus:ring-blue-500 p-2 border"
                                   placeholder="Thank you for your submission">
                        </div>

                        <div>
                            <label class="block text-sm font-medium text-gray-700 mb-2">
                                Auto-Reply Message
                            </label>
                            <textarea wire:model="auto_reply_message"
                                      rows="4"
                                      class="w-full rounded-lg border-gray-300 shadow-sm focus:border-blue-500 focus:ring-blue-500 p-2 border"
                                      placeholder="Thank you for contacting us. We will get back to you soon."></textarea>
                        </div>
                    </div>
                @endif
            </div>

            <!-- Form Fields Builder -->
            <div class="bg-white rounded-lg shadow p-6">
                <div class="flex items-center justify-between mb-4">
                    <h3 class="text-lg font-semibold text-gray-900">Form Fields</h3>
                    <button type="button"
                            wire:click="$set('showAddField', true)"
                            class="inline-flex items-center px-3 py-2 bg-green-600 text-white text-sm rounded-lg hover:bg-green-700">
                        <svg class="w-4 h-4 mr-1" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4"/>
                        </svg>
                        Add Field
                    </button>
                </div>

                <!-- Add Field Form -->
                @if($showAddField)
                    <div class="mb-4 p-4 bg-blue-50 border border-blue-200 rounded-lg">
                        <div class="flex items-end space-x-3">
                            <div class="flex-1">
                                <label class="block text-sm font-medium text-gray-700 mb-2">
                                    Select Field Type
                                </label>
                                <select wire:model="newFieldType"
                                        class="w-full rounded-lg border-gray-300 shadow-sm focus:border-blue-500 focus:ring-blue-500 p-2 border">
                                    <option value="">-- Select Type --</option>
                                    @foreach($fieldTypes as $type => $label)
                                        <option value="{{ $type }}">{{ $label }}</option>
                                    @endforeach
                                </select>
                            </div>
                            <button type="button"
                                    wire:click="addField"
                                    class="px-4 py-2 bg-blue-600 text-white rounded-lg hover:bg-blue-700">
                                Add
                            </button>
                            <button type="button"
                                    wire:click="$set('showAddField', false)"
                                    class="px-4 py-2 bg-gray-300 text-gray-700 rounded-lg hover:bg-gray-400">
                                Cancel
                            </button>
                        </div>
                    </div>
                @endif

                <!-- Fields List -->
                @if(count($fields) > 0)
                    <div class="space-y-4">
                        @foreach($fields as $index => $field)
                            <div class="border border-gray-200 rounded-lg p-4 bg-gray-50">
                                <div class="flex items-start justify-between mb-3">
                                    <div class="flex items-center space-x-2">
                                        <span class="text-sm font-medium text-gray-700">Field #{{ $index + 1 }}</span>
                                        <span class="text-xs bg-blue-100 text-blue-800 px-2 py-1 rounded">
                                            {{ $fieldTypes[$field['type']] ?? $field['type'] }}
                                        </span>
                                    </div>
                                    <div class="flex items-center space-x-2">
                                        @if($index > 0)
                                            <button type="button"
                                                    wire:click="moveFieldUp({{ $index }})"
                                                    class="text-gray-500 hover:text-gray-700">
                                                <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 15l7-7 7 7"/>
                                                </svg>
                                            </button>
                                        @endif
                                        @if($index < count($fields) - 1)
                                            <button type="button"
                                                    wire:click="moveFieldDown({{ $index }})"
                                                    class="text-gray-500 hover:text-gray-700">
                                                <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 9l-7 7-7-7"/>
                                                </svg>
                                            </button>
                                        @endif
                                        <button type="button"
                                                wire:click="removeField({{ $index }})"
                                                class="text-red-600 hover:text-red-800">
                                            <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16"/>
                                            </svg>
                                        </button>
                                    </div>
                                </div>

                                <div class="grid grid-cols-2 gap-3">
                                    <div>
                                        <label class="block text-xs font-medium text-gray-700 mb-1">Field Name</label>
                                        <input type="text"
                                               wire:model="fields.{{ $index }}.name"
                                               class="w-full rounded border-gray-300 shadow-sm focus:border-blue-500 focus:ring-blue-500 p-2 border text-sm"
                                               placeholder="field_name">
                                    </div>
                                    <div>
                                        <label class="block text-xs font-medium text-gray-700 mb-1">Label</label>
                                        <input type="text"
                                               wire:model="fields.{{ $index }}.label"
                                               class="w-full rounded border-gray-300 shadow-sm focus:border-blue-500 focus:ring-blue-500 p-2 border text-sm"
                                               placeholder="Field Label">
                                    </div>
                                    <div>
                                        <label class="block text-xs font-medium text-gray-700 mb-1">Placeholder</label>
                                        <input type="text"
                                               wire:model="fields.{{ $index }}.placeholder"
                                               class="w-full rounded border-gray-300 shadow-sm focus:border-blue-500 focus:ring-blue-500 p-2 border text-sm"
                                               placeholder="Enter placeholder text">
                                    </div>
                                    <div>
                                        <label class="block text-xs font-medium text-gray-700 mb-1">Default Value</label>
                                        <input type="text"
                                               wire:model="fields.{{ $index }}.default_value"
                                               class="w-full rounded border-gray-300 shadow-sm focus:border-blue-500 focus:ring-blue-500 p-2 border text-sm"
                                               placeholder="Default value">
                                    </div>
                                    <div class="col-span-2">
                                        <label class="block text-xs font-medium text-gray-700 mb-1">Help Text</label>
                                        <input type="text"
                                               wire:model="fields.{{ $index }}.help_text"
                                               class="w-full rounded border-gray-300 shadow-sm focus:border-blue-500 focus:ring-blue-500 p-2 border text-sm"
                                               placeholder="Additional help text">
                                    </div>

                                    @if(in_array($field['type'], ['select', 'radio', 'checkbox']))
                                        <div class="col-span-2">
                                            <label class="block text-xs font-medium text-gray-700 mb-1">Options (one per line)</label>
                                            <textarea wire:model="fields.{{ $index }}.options_string"
                                                      rows="3"
                                                      class="w-full rounded border-gray-300 shadow-sm focus:border-blue-500 focus:ring-blue-500 p-2 border text-sm"
                                                      placeholder="Option 1&#10;Option 2&#10;Option 3"></textarea>
                                        </div>
                                    @endif

                                    <div class="col-span-2">
                                        <label class="block text-xs font-medium text-gray-700 mb-1">Custom Validation Rules (comma-separated)</label>
                                        <input type="text"
                                               wire:model="fields.{{ $index }}.validation_rules_string"
                                               class="w-full rounded border-gray-300 shadow-sm focus:border-blue-500 focus:ring-blue-500 p-2 border text-sm"
                                               placeholder="max:255, min:3">
                                        <p class="mt-1 text-xs text-gray-500">Example: max:255, min:3, alpha_dash</p>
                                    </div>

                                    <div class="col-span-2">
                                        <label class="flex items-center">
                                            <input type="checkbox"
                                                   wire:model="fields.{{ $index }}.is_required"
                                                   class="rounded border-gray-300 text-blue-600 shadow-sm focus:border-blue-500 focus:ring-blue-500">
                                            <span class="ml-2 text-sm text-gray-700">Required field</span>
                                        </label>
                                    </div>
                                </div>
                            </div>
                        @endforeach
                    </div>
                @else
                    <div class="text-center py-8 text-gray-500">
                        <p>No fields added yet. Click "Add Field" to get started.</p>
                    </div>
                @endif
            </div>

            <!-- Bottom Actions -->
            <div class="flex items-center justify-between">
                <a href="{{ route('admin.forms.index') }}"
                   class="text-sm text-gray-600 hover:text-gray-900">
                    Cancel
                </a>
                <button type="submit"
                        class="inline-flex items-center px-6 py-3 bg-blue-600 text-white rounded-lg hover:bg-blue-700 transition">
                    <svg class="w-5 h-5 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"/>
                    </svg>
                    {{ $formId ? 'Update' : 'Create' }} Form
                </button>
            </div>

        </div>
    </form>
</div>
