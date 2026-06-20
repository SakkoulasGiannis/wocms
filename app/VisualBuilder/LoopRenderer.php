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
        if (stripos($html, 'data-vb-loop') === false) {
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
        $loops = $xpath->query('//*[@data-vb-loop]');
        if ($loops === false) {
            return $html;
        }

        foreach (iterator_to_array($loops) as $node) {
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

        return $out !== '' ? $out : $html;
    }
}
