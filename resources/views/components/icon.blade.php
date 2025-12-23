@props(['name' => '', 'class' => 'w-6 h-6', 'type' => 'auto'])

@php
    // Auto-detect icon type
    $iconType = $type;

    if ($type === 'auto' && !empty($name)) {
        // If starts with M or contains path commands, it's SVG
        if (preg_match('/^[Mm]\d/', $name) || strlen($name) > 20) {
            $iconType = 'svg';
        }
        // If it's 1-4 characters and contains emoji-like chars, it's emoji
        elseif (mb_strlen($name) <= 4 && preg_match('/[\x{1F300}-\x{1F9FF}\x{2600}-\x{26FF}\x{2700}-\x{27BF}]/u', $name)) {
            $iconType = 'emoji';
        }
        // Otherwise treat as emoji
        else {
            $iconType = 'emoji';
        }
    }
@endphp

@if($iconType === 'svg' && !empty($name))
    {{-- Heroicons SVG Path --}}
    <svg {{ $attributes->merge(['class' => $class]) }} fill="none" stroke="currentColor" viewBox="0 0 24 24" xmlns="http://www.w3.org/2000/svg">
        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="{{ $name }}"></path>
    </svg>
@elseif(!empty($name))
    {{-- Emoji or text icon --}}
    <span {{ $attributes->merge(['class' => 'inline-flex items-center justify-center']) }} style="font-size: 1.5em; line-height: 1;">
        {{ $name }}
    </span>
@endif
