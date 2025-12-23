<div class="w-full max-w-2xl mx-auto">
    @if($showSuccessMessage)
        <div class="bg-green-50 border border-green-200 text-green-800 rounded-lg p-4 mb-6">
            <div class="flex items-start">
                <svg class="w-5 h-5 text-green-500 mt-0.5 mr-3 flex-shrink-0" fill="currentColor" viewBox="0 0 20 20">
                    <path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zm3.707-9.293a1 1 0 00-1.414-1.414L9 10.586 7.707 9.293a1 1 0 00-1.414 1.414l2 2a1 1 0 001.414 0l4-4z" clip-rule="evenodd"/>
                </svg>
                <div>
                    <p class="font-medium">{{ $successMessage }}</p>
                </div>
            </div>
        </div>
    @else
        <div class="bg-white rounded-lg shadow-md p-6 md:p-8">
            @if($form->description)
                <div class="mb-6">
                    <p class="text-gray-600">{{ $form->description }}</p>
                </div>
            @endif

            <form wire:submit.prevent="submit" class="space-y-6">
                @foreach($form->fields as $field)
                    @if($field->type === 'hidden')
                        <input type="hidden" wire:model="formData.{{ $field->name }}" value="{{ $field->default_value }}">
                    @else
                        <div class="form-field">
                            <label for="field-{{ $field->name }}" class="block text-sm font-medium text-gray-700 mb-2">
                                {{ $field->label }}
                                @if($field->is_required)
                                    <span class="text-red-500">*</span>
                                @endif
                            </label>

                            @switch($field->type)
                                @case('text')
                                @case('email')
                                @case('tel')
                                @case('url')
                                @case('number')
                                    <input
                                        type="{{ $field->type }}"
                                        id="field-{{ $field->name }}"
                                        wire:model="formData.{{ $field->name }}"
                                        placeholder="{{ $field->placeholder }}"
                                        class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-transparent transition @error('formData.' . $field->name) border-red-500 @enderror"
                                        @if($field->is_required) required @endif
                                    >
                                    @break

                                @case('textarea')
                                    <textarea
                                        id="field-{{ $field->name }}"
                                        wire:model="formData.{{ $field->name }}"
                                        placeholder="{{ $field->placeholder }}"
                                        rows="{{ $field->settings['rows'] ?? 4 }}"
                                        class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-transparent transition @error('formData.' . $field->name) border-red-500 @enderror"
                                        @if($field->is_required) required @endif
                                    ></textarea>
                                    @break

                                @case('select')
                                    <select
                                        id="field-{{ $field->name }}"
                                        wire:model="formData.{{ $field->name }}"
                                        class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-transparent transition @error('formData.' . $field->name) border-red-500 @enderror"
                                        @if($field->is_required) required @endif
                                    >
                                        <option value="">{{ $field->placeholder ?: 'Select an option' }}</option>
                                        @if($field->options)
                                            @foreach($field->options as $option)
                                                <option value="{{ $option }}">{{ $option }}</option>
                                            @endforeach
                                        @endif
                                    </select>
                                    @break

                                @case('radio')
                                    <div class="space-y-2">
                                        @if($field->options)
                                            @foreach($field->options as $option)
                                                <label class="flex items-center">
                                                    <input
                                                        type="radio"
                                                        wire:model="formData.{{ $field->name }}"
                                                        value="{{ $option }}"
                                                        class="w-4 h-4 text-blue-600 border-gray-300 focus:ring-blue-500"
                                                        @if($field->is_required) required @endif
                                                    >
                                                    <span class="ml-2 text-gray-700">{{ $option }}</span>
                                                </label>
                                            @endforeach
                                        @endif
                                    </div>
                                    @break

                                @case('checkbox')
                                    <div class="space-y-2">
                                        @if($field->options)
                                            @foreach($field->options as $option)
                                                <label class="flex items-center">
                                                    <input
                                                        type="checkbox"
                                                        wire:model="formData.{{ $field->name }}"
                                                        value="{{ $option }}"
                                                        class="w-4 h-4 text-blue-600 border-gray-300 rounded focus:ring-blue-500"
                                                    >
                                                    <span class="ml-2 text-gray-700">{{ $option }}</span>
                                                </label>
                                            @endforeach
                                        @endif
                                    </div>
                                    @break

                                @case('file')
                                    <input
                                        type="file"
                                        id="field-{{ $field->name }}"
                                        wire:model="fileUploads.{{ $field->name }}"
                                        class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-transparent transition @error('fileUploads.' . $field->name) border-red-500 @enderror"
                                        @if($field->is_required) required @endif
                                    >
                                    @if(isset($fileUploads[$field->name]))
                                        <div class="mt-2 text-sm text-gray-600">
                                            <span class="font-medium">Selected:</span> {{ $fileUploads[$field->name]->getClientOriginalName() }}
                                        </div>
                                    @endif
                                    <div wire:loading wire:target="fileUploads.{{ $field->name }}" class="mt-2 text-sm text-blue-600">
                                        Uploading...
                                    </div>
                                    @break

                                @case('date')
                                    <input
                                        type="date"
                                        id="field-{{ $field->name }}"
                                        wire:model="formData.{{ $field->name }}"
                                        class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-transparent transition @error('formData.' . $field->name) border-red-500 @enderror"
                                        @if($field->is_required) required @endif
                                    >
                                    @break

                                @case('time')
                                    <input
                                        type="time"
                                        id="field-{{ $field->name }}"
                                        wire:model="formData.{{ $field->name }}"
                                        class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-transparent transition @error('formData.' . $field->name) border-red-500 @enderror"
                                        @if($field->is_required) required @endif
                                    >
                                    @break

                                @case('datetime')
                                    <input
                                        type="datetime-local"
                                        id="field-{{ $field->name }}"
                                        wire:model="formData.{{ $field->name }}"
                                        class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-transparent transition @error('formData.' . $field->name) border-red-500 @enderror"
                                        @if($field->is_required) required @endif
                                    >
                                    @break
                            @endswitch

                            @if($field->help_text)
                                <p class="mt-1 text-sm text-gray-500">{{ $field->help_text }}</p>
                            @endif

                            @error('formData.' . $field->name)
                                <p class="mt-1 text-sm text-red-600">{{ $message }}</p>
                            @enderror

                            @error('fileUploads.' . $field->name)
                                <p class="mt-1 text-sm text-red-600">{{ $message }}</p>
                            @enderror
                        </div>
                    @endif
                @endforeach

                {{-- Honeypot field for spam protection (hidden) --}}
                <div class="hidden" aria-hidden="true">
                    <label for="honeypot">Leave this field blank</label>
                    <input type="text" id="honeypot" wire:model="honeypot" tabindex="-1" autocomplete="off">
                </div>

                <div class="pt-4">
                    <button
                        type="submit"
                        class="w-full bg-blue-600 hover:bg-blue-700 text-white font-semibold py-3 px-6 rounded-lg transition duration-200 ease-in-out transform hover:scale-105 focus:outline-none focus:ring-2 focus:ring-blue-500 focus:ring-offset-2 disabled:opacity-50 disabled:cursor-not-allowed"
                        wire:loading.attr="disabled"
                    >
                        <span wire:loading.remove>{{ $form->submit_button_text ?? 'Submit' }}</span>
                        <span wire:loading>
                            <svg class="animate-spin inline-block w-5 h-5 mr-2" fill="none" viewBox="0 0 24 24">
                                <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
                                <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path>
                            </svg>
                            Processing...
                        </span>
                    </button>
                </div>
            </form>
        </div>
    @endif
</div>
