{{-- Dynamic Loop section (new-builder) — renders content['item_html'] once per
     entity of the source Template, resolving {tokens} per entity. --}}
@php
    $c = isset($content) && is_array($content) ? $content : (is_array($section->content) ? $section->content : []);
    $itemHtml   = (string) ($c['item_html'] ?? '');
    $sourceSlug = (string) ($c['source'] ?? '');
    $heading    = trim((string) ($c['heading'] ?? ''));
    $limit      = (int) ($c['limit'] ?? 12);
    $orderBy    = $c['order_by'] ?? 'created_at';
    $orderDir   = ($c['order_dir'] ?? 'desc') === 'asc' ? 'asc' : 'desc';
    $columns    = (int) ($c['columns'] ?? 3);
    if (! in_array($columns, [1, 2, 3, 4], true)) {
        $columns = 3;
    }
    $gridClass = match ($columns) {
        1 => 'grid-cols-1',
        2 => 'grid-cols-1 sm:grid-cols-2',
        4 => 'grid-cols-1 sm:grid-cols-2 lg:grid-cols-4',
        default => 'grid-cols-1 sm:grid-cols-2 lg:grid-cols-3',
    };

    $entries = collect();
    $sourceTemplate = $sourceSlug
        ? \App\Models\Template::where('slug', $sourceSlug)->first()
        : null;

    if ($sourceTemplate && $sourceTemplate->model_class) {
        $modelClass = str_contains($sourceTemplate->model_class, '\\')
            ? $sourceTemplate->model_class
            : 'App\\Models\\'.$sourceTemplate->model_class;
        try {
            if (class_exists($modelClass)) {
                $table = (new $modelClass)->getTable();
                $query = $modelClass::query();
                if (method_exists($modelClass, 'scopeActive')) {
                    try { $query->active(); } catch (\Throwable $e) {}
                }
                $isSortable = (bool) ($sourceTemplate->settings['sortable'] ?? false);
                if ($isSortable && \Illuminate\Support\Facades\Schema::hasColumn($table, 'sort_order')) {
                    $query->orderBy('sort_order')->orderBy('id');
                } else {
                    $col = \Illuminate\Support\Facades\Schema::hasColumn($table, $orderBy) ? $orderBy : 'created_at';
                    $query->orderBy($col, $orderDir);
                }
                if ($limit > 0) {
                    $query->limit($limit);
                }
                $entries = $query->get();
            }
        } catch (\Throwable $e) {
            \Log::warning("nb_loop query failed for {$sourceSlug}: ".$e->getMessage());
        }
    }

    $resolver = app(\App\Services\TokenResolver::class);
@endphp

<section class="py-16 px-4">
    <div class="mx-auto max-w-7xl">
        @if($heading !== '')
            <h2 class="text-3xl font-bold text-center mb-10">{{ $heading }}</h2>
        @endif

        @if($entries->isEmpty())
            <p class="text-center text-gray-500">
                @if(! $sourceTemplate)
                    Pick a source for this loop in the builder.
                @else
                    No entries to show from “{{ $sourceTemplate->name }}” yet.
                @endif
            </p>
        @else
            <div class="grid {{ $gridClass }} gap-6">
                @foreach($entries as $entry)
                    {!! $resolver->resolve($itemHtml, $entry) !!}
                @endforeach
            </div>
        @endif
    </div>
</section>
