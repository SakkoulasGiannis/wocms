@props(['pageType' => 'home'])

@php
    $sections = \App\Models\PageSection::getPageSections($pageType, true);
@endphp

@foreach($sections as $section)
    @php
        $componentName = 'sections.' . str_replace('_', '-', $section->section_type);
    @endphp
    <x-dynamic-component
        :component="$componentName"
        :content="$section->content"
        :settings="$section->settings"
    />
@endforeach
