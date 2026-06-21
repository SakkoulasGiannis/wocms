<?php

namespace App\VisualBuilder;

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
            $table = (new $modelClass)->getTable();
            $q = $modelClass::query();

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
            $limit = (int) ($query['limit'] ?? 12);
            if ($limit > 0) {
                $q->limit($limit);
            }

            return $q->get()
                ->map(fn ($entity): string => (string) $this->resolver->resolve($itemHtml, $entity))
                ->all();
        } catch (\Throwable $e) {
            \Log::warning('LoopRenderer query failed for '.$source.': '.$e->getMessage());

            return [];
        }
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
        if (! $hasLoop && ! $hasForm) {
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

        return $out;
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
