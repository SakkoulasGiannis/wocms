@props(['content' => [], 'settings' => []])

@php
    $style = $settings['style'] ?? $content['style'] ?? 'overlay';  // overlay (on hero) or standalone
    $types = [];
    // Load property types dynamically if module exists
    if (class_exists(\Modules\Properties\Models\Property::class)) {
        try { $types = \Modules\Properties\Models\Property::getPropertyTypes(); } catch (\Throwable $e) {}
    }
    if (empty($types)) {
        $types = ['villa' => 'Villa', 'apartment' => 'Apartment', 'house' => 'House', 'land' => 'Land'];
    }
@endphp

<div
    class="{{ $style === 'overlay' ? 'absolute bottom-0 left-0 right-0 z-20 pb-8' : 'py-8 bg-slate-50' }}"
    x-data="{
        activeTab: 'rent',
        advanced: false,
        getAction() {
            return this.activeTab === 'sale' ? '{{ route('properties.index') }}' : '{{ route('rental-properties.index') }}';
        }
    }"
>
    <div class="mx-auto max-w-5xl px-4 sm:px-6">
        {{-- Tabs --}}
        <div class="flex justify-center mb-0">
            <button type="button"
                    @click="activeTab = 'rent'"
                    :class="activeTab === 'rent' ? 'bg-brand text-white' : 'bg-white/80 text-slate-700 hover:bg-white'"
                    class="rounded-tl-xl px-6 py-2.5 text-sm font-semibold backdrop-blur-sm transition-colors">
                For Rent
            </button>
            <button type="button"
                    @click="activeTab = 'sale'"
                    :class="activeTab === 'sale' ? 'bg-brand text-white' : 'bg-white/80 text-slate-700 hover:bg-white'"
                    class="rounded-tr-xl px-6 py-2.5 text-sm font-semibold backdrop-blur-sm transition-colors">
                For Sale
            </button>
        </div>

        {{-- Search Form --}}
        <form method="get" :action="getAction()"
              class="rounded-2xl rounded-t-none bg-white/95 p-4 shadow-xl backdrop-blur-sm ring-1 ring-black/5 sm:p-6">

            <div class="grid grid-cols-1 gap-3 sm:grid-cols-2 lg:grid-cols-4">
                {{-- Type --}}
                <div>
                    <label class="mb-1 block text-xs font-semibold uppercase tracking-wide text-slate-500">Type</label>
                    <select name="type" class="w-full rounded-lg border-slate-300 bg-white px-3 py-2.5 text-sm shadow-sm focus:border-brand focus:ring-brand">
                        <option value="">All Types</option>
                        @foreach($types as $value => $label)
                            <option value="{{ $value }}">{{ $label }}</option>
                        @endforeach
                    </select>
                </div>

                {{-- Location --}}
                <div>
                    <label class="mb-1 block text-xs font-semibold uppercase tracking-wide text-slate-500">Location</label>
                    <input type="text" name="city" placeholder="City or area"
                           class="w-full rounded-lg border-slate-300 bg-white px-3 py-2.5 text-sm shadow-sm focus:border-brand focus:ring-brand">
                </div>

                {{-- Keyword --}}
                <div>
                    <label class="mb-1 block text-xs font-semibold uppercase tracking-wide text-slate-500">Keyword</label>
                    <input type="text" name="search" placeholder="Search..."
                           class="w-full rounded-lg border-slate-300 bg-white px-3 py-2.5 text-sm shadow-sm focus:border-brand focus:ring-brand">
                </div>

                {{-- Buttons --}}
                <div class="flex items-end gap-2">
                    <button type="submit" class="flex-1 rounded-lg bg-brand px-4 py-2.5 text-sm font-semibold text-white shadow-sm transition-colors hover:bg-brand-dark">
                        <svg class="inline-block mr-1 h-4 w-4" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z" /></svg>
                        Search
                    </button>
                    <button type="button" @click="advanced = !advanced"
                            class="flex items-center justify-center rounded-lg border border-slate-300 bg-white p-2.5 text-slate-600 hover:bg-slate-50"
                            :aria-expanded="advanced">
                        <svg class="h-5 w-5" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M3 4h18M6 12h12M10 20h4" /></svg>
                    </button>
                </div>
            </div>

            {{-- Advanced Filters --}}
            <div x-show="advanced" x-collapse class="mt-4 border-t border-slate-100 pt-4">
                <div class="grid grid-cols-2 gap-3 md:grid-cols-4">
                    <div>
                        <label class="mb-1 block text-xs font-semibold uppercase tracking-wide text-slate-500">Min Price</label>
                        <input type="number" name="min_price" placeholder="€ Min"
                               class="w-full rounded-lg border-slate-300 px-3 py-2 text-sm shadow-sm focus:border-brand focus:ring-brand">
                    </div>
                    <div>
                        <label class="mb-1 block text-xs font-semibold uppercase tracking-wide text-slate-500">Max Price</label>
                        <input type="number" name="max_price" placeholder="€ Max"
                               class="w-full rounded-lg border-slate-300 px-3 py-2 text-sm shadow-sm focus:border-brand focus:ring-brand">
                    </div>
                    <div>
                        <label class="mb-1 block text-xs font-semibold uppercase tracking-wide text-slate-500">Bedrooms</label>
                        <select name="bedrooms" class="w-full rounded-lg border-slate-300 px-3 py-2 text-sm shadow-sm focus:border-brand focus:ring-brand">
                            <option value="">Any</option>
                            @for($i = 1; $i <= 10; $i++)
                                <option value="{{ $i }}">{{ $i }}+</option>
                            @endfor
                        </select>
                    </div>
                    <div>
                        <label class="mb-1 block text-xs font-semibold uppercase tracking-wide text-slate-500">Bathrooms</label>
                        <select name="bathrooms" class="w-full rounded-lg border-slate-300 px-3 py-2 text-sm shadow-sm focus:border-brand focus:ring-brand">
                            <option value="">Any</option>
                            @for($i = 1; $i <= 5; $i++)
                                <option value="{{ $i }}">{{ $i }}+</option>
                            @endfor
                        </select>
                    </div>
                </div>
            </div>
        </form>
    </div>
</div>
