<?php

namespace App\VisualBuilder;

use App\Models\ContentNode;
use App\Models\Template;
use App\Services\TokenResolver;
use Illuminate\Support\Facades\Schema;

/**
 * Expands "repeater" regions for the visual builder. A repeater is any element
 * carrying a data-vb-loop="{json query}" attribute; its inner HTML is the
 * per-item template (with {tokens}). This renders the template once per entity
 * of the query, resolving tokens against each entity.
 *
 * Used both for the live builder preview (sample endpoint) and the public
 * frontend (expandHtml on saved section HTML).
 */
class LoopRenderer
{
    public function __construct(private readonly TokenResolver $resolver) {}

    /**
     * Render the item template once per entity of the query.
     *
     * @param  array{limit?:int, order_by?:string, order_dir?:string, offset?:int, filter_field?:string, filter_value?:string}  $query
     * @return array<int, string> resolved HTML per entity
     */
    public function renderItems(string $source, array $query, string $itemHtml): array
    {
        $template = Template::query()->where('slug', $source)->first();
        if (! $template || ! $template->model_class || trim($itemHtml) === '') {
            return [];
        }

        $modelClass = str_contains($template->model_class, '\\')
            ? $template->model_class
            : 'App\\Models\\'.$template->model_class;

        if (! class_exists($modelClass)) {
            return [];
        }

        try {
            $model = new $modelClass;
            $table = $model->getTable();
            $pk = $model->getKeyName();
            $q = $modelClass::query();
            $limit = (int) ($query['limit'] ?? 12);

            // Explicit selection: specific picked entries, or "children of" a node
            // (resolved through the content tree). Wins over the source-wide filter.
            $explicitIds = $this->pickedIds($query, $modelClass);

            if ($explicitIds !== null) {
                $q->whereIn($pk, count($explicitIds) ? $explicitIds : [0]);
            } else {
                if (method_exists($modelClass, 'scopeActive')) {
                    try {
                        $q->active();
                    } catch (\Throwable $e) {
                        // model has no usable active scope; ignore
                    }
                }

                $field = $query['filter_field'] ?? null;
                $value = $query['filter_value'] ?? null;
                if ($field && $value !== null && $value !== '' && Schema::hasColumn($table, $field)) {
                    $q->where($field, $value);
                }

                $orderBy = $query['order_by'] ?? 'created_at';
                $orderDir = ($query['order_dir'] ?? 'desc') === 'asc' ? 'asc' : 'desc';
                $q->orderBy(Schema::hasColumn($table, $orderBy) ? $orderBy : 'created_at', $orderDir);

                $offset = max(0, (int) ($query['offset'] ?? 0));
                if ($offset > 0) {
                    $q->skip($offset);
                }
                if ($limit > 0) {
                    $q->limit($limit);
                }
            }

            $results = $q->get();

            if ($explicitIds !== null && count($explicitIds)) {
                // Preserve the picked / tree order, then cap to the limit.
                $pos = array_flip(array_values($explicitIds));
                $results = $results->sortBy(fn ($e) => $pos[$e->getKey()] ?? PHP_INT_MAX)->values();
                if ($limit > 0) {
                    $results = $results->take($limit)->values();
                }
            }

            return $results
                ->map(fn ($entity): string => (string) $this->resolver->resolve($itemHtml, $entity))
                ->all();
        } catch (\Throwable $e) {
            \Log::warning('LoopRenderer query failed for '.$source.': '.$e->getMessage());

            return [];
        }
    }

    /**
     * Resolve an explicit list of entry ids for the loop, in the desired order:
     * either the user-picked `ids`, or the children of a `parent` content node
     * (matching the source model). Returns null when neither is set.
     *
     * @return array<int, int>|null
     */
    private function pickedIds(array $query, string $modelClass): ?array
    {
        $ids = $query['ids'] ?? null;
        if (is_array($ids) && count($ids)) {
            return array_values(array_filter(array_map('intval', $ids)));
        }

        $parent = $query['parent'] ?? null;
        if ($parent !== null && $parent !== '') {
            return ContentNode::query()
                ->where('parent_id', (int) $parent)
                ->where('content_type', $modelClass)
                ->whereNotNull('content_id')
                ->orderBy('sort_order')
                ->pluck('content_id')
                ->map(fn ($v): int => (int) $v)
                ->all();
        }

        return null;
    }

    /**
     * Expand every data-vb-loop element in a chunk of HTML: replace its inner
     * template with the rendered items. Returns the HTML unchanged if it has no
     * repeaters.
     */
    public function expandHtml(string $html): string
    {
        $hasLoop = stripos($html, 'data-vb-loop') !== false;
        $hasForm = stripos($html, 'data-vb-form') !== false;
        $hasSlider = stripos($html, 'data-vb-slider') !== false;
        if (! $hasLoop && ! $hasForm && ! $hasSlider) {
            return $html;
        }

        $doc = new \DOMDocument;
        libxml_use_internal_errors(true);
        $doc->loadHTML(
            '<?xml encoding="utf-8"?><div id="__vb_root">'.$html.'</div>',
            LIBXML_HTML_NOIMPLIED | LIBXML_HTML_NODEFDTD
        );
        libxml_clear_errors();

        $xpath = new \DOMXPath($doc);

        $formMap = $hasForm ? $this->stubForms($doc, $xpath) : [];
        $sliderMap = $hasSlider ? $this->stubSliders($doc, $xpath) : [];

        $loops = $hasLoop ? $xpath->query('//*[@data-vb-loop]') : false;

        foreach ($loops ? iterator_to_array($loops) : [] as $node) {
            /** @var \DOMElement $node */
            $query = json_decode((string) $node->getAttribute('data-vb-loop'), true);
            if (! is_array($query) || empty($query['source'])) {
                continue;
            }

            // Inner HTML = item template.
            $itemHtml = '';
            foreach (iterator_to_array($node->childNodes) as $child) {
                $itemHtml .= $doc->saveHTML($child);
            }

            $rendered = implode("\n", $this->renderItems($query['source'], $query, $itemHtml));

            // Replace children with the rendered items.
            while ($node->firstChild) {
                $node->removeChild($node->firstChild);
            }
            $node->removeAttribute('data-vb-loop');
            if ($rendered !== '') {
                $tmp = new \DOMDocument;
                libxml_use_internal_errors(true);
                $tmp->loadHTML('<?xml encoding="utf-8"?><div>'.$rendered.'</div>', LIBXML_HTML_NOIMPLIED | LIBXML_HTML_NODEFDTD);
                libxml_clear_errors();
                $tmpRoot = $tmp->getElementsByTagName('div')->item(0);
                if ($tmpRoot) {
                    foreach (iterator_to_array($tmpRoot->childNodes) as $imported) {
                        $node->appendChild($doc->importNode($imported, true));
                    }
                }
            }
        }

        $root = $doc->getElementById('__vb_root');
        $out = '';
        if ($root) {
            foreach (iterator_to_array($root->childNodes) as $child) {
                $out .= $doc->saveHTML($child);
            }
        }

        if ($out === '') {
            return $html;
        }

        // Swap each form placeholder for the live <x-form> component HTML. Done on
        // the serialized string so DOMDocument never mangles the Livewire snapshot.
        foreach ($formMap as $token => $slug) {
            $out = str_replace($token, $this->renderForm($slug), $out);
        }
        foreach ($sliderMap as $token => $id) {
            $out = str_replace($token, $this->renderSlider($id), $out);
        }

        return $out;
    }

    /**
     * Replace each [data-vb-slider] element's content with a unique text
     * placeholder and return a map of placeholder => slider id.
     *
     * @return array<string, string>
     */
    protected function stubSliders(\DOMDocument $doc, \DOMXPath $xpath): array
    {
        $sliders = $xpath->query('//*[@data-vb-slider]');
        if ($sliders === false) {
            return [];
        }

        $map = [];
        $i = 0;
        foreach (iterator_to_array($sliders) as $node) {
            /** @var \DOMElement $node */
            $id = trim((string) $node->getAttribute('data-vb-slider'));
            $node->removeAttribute('data-vb-slider');
            while ($node->firstChild) {
                $node->removeChild($node->firstChild);
            }
            if ($id === '') {
                continue;
            }
            $token = 'VBSLIDERPLACEHOLDER'.($i++).'ENDVBSLIDER';
            $map[$token] = $id;
            $node->appendChild($doc->createTextNode($token));
        }

        return $map;
    }

    /**
     * Render a slider by id to its frontend HTML via the hero-slider component.
     */
    public function renderSlider(string $id): string
    {
        try {
            return \Illuminate\Support\Facades\Blade::render(
                '<x-sections.hero-slider :content="$content" />',
                ['content' => ['slider_id' => (int) $id]]
            );
        } catch (\Throwable $e) {
            \Log::warning('LoopRenderer slider render failed for '.$id.': '.$e->getMessage());

            return '';
        }
    }

    /**
     * Replace each [data-vb-form] element's content with a unique text
     * placeholder and return a map of placeholder => form slug.
     *
     * @return array<string, string>
     */
    protected function stubForms(\DOMDocument $doc, \DOMXPath $xpath): array
    {
        $forms = $xpath->query('//*[@data-vb-form]');
        if ($forms === false) {
            return [];
        }

        $map = [];
        $i = 0;
        foreach (iterator_to_array($forms) as $node) {
            /** @var \DOMElement $node */
            $slug = trim((string) $node->getAttribute('data-vb-form'));
            $node->removeAttribute('data-vb-form');
            while ($node->firstChild) {
                $node->removeChild($node->firstChild);
            }
            if ($slug === '') {
                continue;
            }
            $token = 'VBFORMPLACEHOLDER'.($i++).'ENDVBFORM';
            $map[$token] = $slug;
            $node->appendChild($doc->createTextNode($token));
        }

        return $map;
    }

    /**
     * Render a form by slug to its live (Livewire) HTML.
     */
    protected function renderForm(string $slug): string
    {
        try {
            return \Illuminate\Support\Facades\Blade::render('<x-form :slug="$slug" />', ['slug' => $slug]);
        } catch (\Throwable $e) {
            \Log::warning('LoopRenderer form render failed for '.$slug.': '.$e->getMessage());

            return '';
        }
    }
}
