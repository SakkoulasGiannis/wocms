@extends(app(\App\Services\ThemeManager::class)->getLayout())

@section('title', $title ?? 'Rental Properties')

@section('content')
    {{-- Page Header with Filters --}}
    <section class="bg-slate-50 border-b border-slate-200">
        <div class="mx-auto max-w-8xl px-4 py-10 sm:px-6 lg:px-8">
            <div class="mb-6">
                <h1 class="text-3xl font-bold text-slate-900 md:text-4xl">{{ $title ?? 'Rental Properties' }}</h1>
                <p class="mt-1 text-sm text-slate-500">
                    Found <span class="font-semibold text-slate-900">{{ $properties->total() }}</span>
                    {{ \Illuminate\Support\Str::plural('rental', $properties->total()) }}
                </p>
            </div>

            <form
                method="get"
                action="{{ url()->current() }}"
                x-data="{ advanced: {{ empty($filters['min_price']) && empty($filters['max_price']) && empty($filters['bedrooms']) && empty($filters['bathrooms']) ? 'false' : 'true' }} }"
                class="rounded-2xl bg-white p-4 shadow-sm ring-1 ring-slate-200 sm:p-6"
            >
                <div class="grid grid-cols-1 gap-4 md:grid-cols-2 lg:grid-cols-4">
                    <div>
                        <label class="mb-1.5 block text-xs font-semibold uppercase tracking-wide text-slate-600">Type</label>
                        <select name="type" class="w-full rounded-lg border-slate-300 bg-white px-3 py-2.5 text-sm shadow-sm focus:border-brand focus:ring-brand">
                            <option value="">All Types</option>
                            @foreach($propertyTypes as $value => $label)
                                <option value="{{ $value }}" {{ ($filters['type'] ?? '') === $value ? 'selected' : '' }}>{{ $label }}</option>
                            @endforeach
                        </select>
                    </div>
                    <div>
                        <label class="mb-1.5 block text-xs font-semibold uppercase tracking-wide text-slate-600">Location</label>
                        <input type="text" name="city" value="{{ $filters['city'] ?? '' }}" placeholder="Search location" class="w-full rounded-lg border-slate-300 bg-white px-3 py-2.5 text-sm shadow-sm focus:border-brand focus:ring-brand">
                    </div>
                    <div>
                        <label class="mb-1.5 block text-xs font-semibold uppercase tracking-wide text-slate-600">Keyword</label>
                        <input type="text" name="search" value="{{ $filters['search'] ?? '' }}" placeholder="Search keyword" class="w-full rounded-lg border-slate-300 bg-white px-3 py-2.5 text-sm shadow-sm focus:border-brand focus:ring-brand">
                    </div>
                    <div class="flex items-end gap-2">
                        <button type="submit" class="flex-1 rounded-lg bg-brand px-4 py-2.5 text-sm font-semibold text-white shadow-sm transition-colors hover:bg-brand-dark">
                            Search
                        </button>
                        <button type="button" @click="advanced = !advanced" class="flex items-center gap-1.5 rounded-lg border border-slate-300 px-3 py-2.5 text-sm text-slate-700 hover:bg-slate-50" :aria-expanded="advanced">
                            <svg class="h-4 w-4" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M3 4h18M6 12h12M10 20h4" /></svg>
                            <span class="hidden sm:inline">Advanced</span>
                        </button>
                    </div>
                </div>

                <div x-show="advanced" x-collapse class="mt-4 border-t border-slate-100 pt-4">
                    <div class="grid grid-cols-2 gap-4 md:grid-cols-4">
                        <div>
                            <label class="mb-1.5 block text-xs font-semibold uppercase tracking-wide text-slate-600">Min Price</label>
                            <input type="number" name="min_price" value="{{ $filters['min_price'] ?? '' }}" placeholder="Min" class="w-full rounded-lg border-slate-300 px-3 py-2 text-sm shadow-sm focus:border-brand focus:ring-brand">
                        </div>
                        <div>
                            <label class="mb-1.5 block text-xs font-semibold uppercase tracking-wide text-slate-600">Max Price</label>
                            <input type="number" name="max_price" value="{{ $filters['max_price'] ?? '' }}" placeholder="Max" class="w-full rounded-lg border-slate-300 px-3 py-2 text-sm shadow-sm focus:border-brand focus:ring-brand">
                        </div>
                        <div>
                            <label class="mb-1.5 block text-xs font-semibold uppercase tracking-wide text-slate-600">Beds (min)</label>
                            <input type="number" name="bedrooms" value="{{ $filters['bedrooms'] ?? '' }}" placeholder="Any" class="w-full rounded-lg border-slate-300 px-3 py-2 text-sm shadow-sm focus:border-brand focus:ring-brand">
                        </div>
                        <div>
                            <label class="mb-1.5 block text-xs font-semibold uppercase tracking-wide text-slate-600">Baths (min)</label>
                            <input type="number" name="bathrooms" value="{{ $filters['bathrooms'] ?? '' }}" placeholder="Any" class="w-full rounded-lg border-slate-300 px-3 py-2 text-sm shadow-sm focus:border-brand focus:ring-brand">
                        </div>
                    </div>
                </div>
            </form>
        </div>
    </section>

    {{-- Results --}}
    <section class="py-10" x-data="{ view: '{{ request('view', 'grid') }}' }">
        <div class="mx-auto max-w-8xl px-4 sm:px-6 lg:px-8">
            <div class="mb-6 flex flex-wrap items-center justify-between gap-3">
                <form method="get" action="{{ url()->current() }}" class="flex items-center gap-2">
                    @foreach($filters ?? [] as $k => $v)
                        @if($k !== 'sort' && $v !== null && $v !== '')
                            <input type="hidden" name="{{ $k }}" value="{{ $v }}">
                        @endif
                    @endforeach
                    <label for="sort" class="text-sm text-slate-600">Sort:</label>
                    <select id="sort" name="sort" onchange="this.form.submit()" class="rounded-lg border-slate-300 bg-white py-2 pl-3 pr-8 text-sm focus:border-brand focus:ring-brand">
                        <option value="newest" {{ ($filters['sort'] ?? 'newest') === 'newest' ? 'selected' : '' }}>Newest</option>
                        <option value="oldest" {{ ($filters['sort'] ?? '') === 'oldest' ? 'selected' : '' }}>Oldest</option>
                        <option value="price_asc" {{ ($filters['sort'] ?? '') === 'price_asc' ? 'selected' : '' }}>Price: Low to High</option>
                        <option value="price_desc" {{ ($filters['sort'] ?? '') === 'price_desc' ? 'selected' : '' }}>Price: High to Low</option>
                    </select>
                </form>

                <div class="inline-flex rounded-lg border border-slate-300 bg-white p-1">
                    <button type="button" @click="view = 'grid'" :class="view === 'grid' ? 'bg-brand text-white shadow-sm' : 'text-slate-600 hover:bg-slate-50'" class="rounded-md px-3 py-1.5 text-sm transition-colors">
                        <svg class="h-4 w-4" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M4 6a2 2 0 012-2h2a2 2 0 012 2v2a2 2 0 01-2 2H6a2 2 0 01-2-2V6zM14 6a2 2 0 012-2h2a2 2 0 012 2v2a2 2 0 01-2 2h-2a2 2 0 01-2-2V6zM4 16a2 2 0 012-2h2a2 2 0 012 2v2a2 2 0 01-2 2H6a2 2 0 01-2-2v-2zM14 16a2 2 0 012-2h2a2 2 0 012 2v2a2 2 0 01-2 2h-2a2 2 0 01-2-2v-2z" /></svg>
                    </button>
                    <button type="button" @click="view = 'list'" :class="view === 'list' ? 'bg-brand text-white shadow-sm' : 'text-slate-600 hover:bg-slate-50'" class="rounded-md px-3 py-1.5 text-sm transition-colors">
                        <svg class="h-4 w-4" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M4 6h16M4 12h16M4 18h16" /></svg>
                    </button>
                </div>
            </div>

            <div x-show="view === 'grid'" class="grid grid-cols-1 gap-6 sm:grid-cols-2 lg:grid-cols-3 xl:grid-cols-4">
                @forelse($properties as $property)
                    @include('themes.ketw.templates.properties._card-grid', ['property' => $property, 'isRental' => true])
                @empty
                    <div class="col-span-full rounded-2xl bg-slate-50 p-12 text-center">
                        <p class="text-slate-600">No rentals match your search criteria.</p>
                    </div>
                @endforelse
            </div>

            <div x-show="view === 'list'" class="grid grid-cols-1 gap-6" style="display:none">
                @foreach($properties as $property)
                    @include('themes.ketw.templates.properties._card-list', ['property' => $property, 'isRental' => true])
                @endforeach
            </div>

            @if($properties->hasPages())
                <div class="mt-10">
                    {{ $properties->links() }}
                </div>
            @endif
        </div>
    </section>
@endsection
