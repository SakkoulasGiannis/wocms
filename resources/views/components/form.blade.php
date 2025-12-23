@props(['slug' => null, 'id' => null])

@if($slug || $id)
    <div class="form-container">
        @if($slug)
            <livewire:frontend.form-renderer :slug="$slug" />
        @else
            <livewire:frontend.form-renderer :formId="$id" />
        @endif
    </div>
@else
    <div class="p-4 bg-red-50 border border-red-200 rounded text-red-800">
        Error: Please provide either 'slug' or 'id' attribute for the form component.
    </div>
@endif
