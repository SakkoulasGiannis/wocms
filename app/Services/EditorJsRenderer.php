<?php

namespace App\Services;

/**
 * Renders EditorJS JSON output to clean HTML.
 * Also handles legacy HTML content (Trix editor output).
 */
class EditorJsRenderer
{
    /**
     * Convert EditorJS JSON (or legacy HTML) to HTML string.
     */
    public function toHtml(?string $content): string
    {
        if (empty($content)) {
            return '';
        }

        $content = trim($content);

        // Try to parse as EditorJS JSON
        if (str_starts_with($content, '{') || str_starts_with($content, '[')) {
            $data = json_decode($content, true);
            if (json_last_error() === JSON_ERROR_NONE && isset($data['blocks'])) {
                return $this->renderBlocks($data['blocks']);
            }
        }

        // Legacy HTML (Trix, raw HTML, etc.) — return as-is
        return $content;
    }

    /**
     * Render all EditorJS blocks to HTML.
     *
     * @param  array<int, array<string, mixed>>  $blocks
     */
    protected function renderBlocks(array $blocks): string
    {
        $html = '';

        foreach ($blocks as $block) {
            $html .= $this->renderBlock($block);
        }

        return $html;
    }

    /**
     * Render a single EditorJS block.
     *
     * @param  array<string, mixed>  $block
     */
    protected function renderBlock(array $block): string
    {
        $type = $block['type'] ?? '';
        $data = $block['data'] ?? [];
        $tunes = $block['tunes'] ?? [];

        // Text alignment from tunes
        $alignment = $tunes['textAlignment']['alignment'] ?? $data['alignment'] ?? '';
        $alignStyle = $alignment ? " style=\"text-align:{$alignment};\"" : '';
        $alignClass = $alignment ? " text-{$alignment}" : '';

        // Per-block custom classes tune (Tailwind or any CSS)
        $extraClasses = trim($tunes['blockClasses']['classes'] ?? '');

        $html = match ($type) {
            'paragraph' => $this->renderParagraph($data, $alignStyle),
            'header' => $this->renderHeader($data, $alignStyle),
            'list' => $this->renderList($data),
            'nestedList' => $this->renderNestedList($data),
            'quote' => $this->renderQuote($data),
            'code' => $this->renderCode($data),
            'delimiter' => '<hr class="editorjs-delimiter my-8"/>',
            'image' => $this->renderImage($data, $tunes),
            'simpleImage' => $this->renderSimpleImage($data, $tunes),
            'embed' => $this->renderEmbed($data),
            'table' => $this->renderTable($data),
            'raw' => $data['html'] ?? '',
            'liveHtml' => $data['html'] ?? '',
            'checklist' => $this->renderChecklist($data),
            'warning' => $this->renderWarning($data),
            'attaches' => $this->renderAttachment($data),
            'linkTool' => $this->renderLinkTool($data),
            'personality' => $this->renderPersonality($data),
            'columns' => $this->renderColumns($data),
            'container' => $this->renderContainer($data),
            'space' => $this->renderSpace($data),
            'sectionEmbed' => $this->renderSectionEmbed($data),
            default => '',
        };

        // Apply per-block classes tune to the rendered primary element
        if ($extraClasses !== '' && $html !== '') {
            $html = $this->injectClasses($html, $extraClasses);
        }

        return $html;
    }

    /**
     * Inject extra classes into the first HTML element of the rendered block.
     * Appends to existing class attribute, or adds one if missing.
     */
    protected function injectClasses(string $html, string $classes): string
    {
        $classes = htmlspecialchars($classes, ENT_QUOTES);

        // Match first opening tag
        return preg_replace_callback(
            '/<([a-zA-Z][a-zA-Z0-9]*)\b([^>]*)>/',
            function ($m) use ($classes) {
                $tag = $m[1];
                $attrs = $m[2];
                if (preg_match('/\bclass\s*=\s*"([^"]*)"/', $attrs)) {
                    $attrs = preg_replace('/\bclass\s*=\s*"([^"]*)"/', 'class="$1 '.$classes.'"', $attrs);
                } else {
                    $attrs .= ' class="'.$classes.'"';
                }

                return '<'.$tag.$attrs.'>';
            },
            $html,
            1
        );
    }

    protected function renderContainer(array $data): string
    {
        $widths = [
            'full' => 'max-w-full', '8xl' => 'max-w-8xl', '7xl' => 'max-w-7xl', '6xl' => 'max-w-6xl',
            '5xl' => 'max-w-5xl', '4xl' => 'max-w-4xl', '3xl' => 'max-w-3xl', '2xl' => 'max-w-2xl',
            'xl' => 'max-w-xl', 'prose' => 'max-w-prose',
        ];

        $mobile = $widths[$data['mobile'] ?? 'full'] ?? 'max-w-full';
        $tablet = $widths[$data['tablet'] ?? 'full'] ?? 'max-w-full';
        $desktop = $widths[$data['desktop'] ?? '7xl'] ?? 'max-w-7xl';

        // Build responsive width classes: base=mobile, md:=tablet, lg:=desktop
        $widthClasses = trim(
            $mobile.
            ($tablet !== $mobile ? ' md:'.$tablet : '').
            ($desktop !== $tablet ? ' lg:'.$desktop : '')
        );

        $wrapperClass = trim($data['wrapperClass'] ?? '');
        $innerClass = trim($data['innerClass'] ?? 'mx-auto px-4 sm:px-6 lg:px-8');

        // Render nested content recursively
        $content = $data['content'] ?? ['blocks' => []];
        $innerHtml = '';
        if (is_array($content) && isset($content['blocks']) && is_array($content['blocks'])) {
            foreach ($content['blocks'] as $block) {
                $innerHtml .= $this->renderBlock($block);
            }
        }

        $wrapperAttrs = $wrapperClass ? ' class="'.e($wrapperClass).'"' : '';

        return '<div'.$wrapperAttrs.'><div class="'.e($widthClasses.' '.$innerClass).'">'.$innerHtml.'</div></div>';
    }

    protected function renderColumns(array $data): string
    {
        $cols = (int) ($data['cols'] ?? 2);
        $cols = max(1, min(6, $cols));
        $columns = $data['columns'] ?? [];
        if (! is_array($columns)) {
            return '';
        }

        $gridClass = match ($cols) {
            2 => 'grid-cols-1 md:grid-cols-2',
            3 => 'grid-cols-1 md:grid-cols-3',
            4 => 'grid-cols-1 md:grid-cols-2 lg:grid-cols-4',
            5 => 'grid-cols-1 md:grid-cols-3 lg:grid-cols-5',
            6 => 'grid-cols-1 md:grid-cols-3 lg:grid-cols-6',
            default => 'grid-cols-1',
        };

        $html = '<div class="grid '.$gridClass.' gap-6 my-6">';
        foreach ($columns as $col) {
            // Column content can be:
            //  - An EditorJS data object {blocks: [...]} (preferred, allows nested blocks with images)
            //  - A plain HTML string (legacy)
            //  - An empty value
            if (is_array($col) && isset($col['blocks']) && is_array($col['blocks'])) {
                // Recursively render nested EditorJS blocks (supports images, headings, lists per column)
                $colHtml = '';
                foreach ($col['blocks'] as $block) {
                    $colHtml .= $this->renderBlock($block);
                }
                $html .= '<div>'.$colHtml.'</div>';
            } else {
                $html .= '<div>'.(is_string($col) ? $col : '').'</div>';
            }
        }
        $html .= '</div>';

        return $html;
    }

    /**
     * Boost inline `style="color: …"` (and bg/font-weight) on ANY tag to
     * `!important` so theme stylesheets / Tailwind preflight can't override the
     * user's choice. Also normalises the legacy `<font color="…">` form (which
     * contentEditable in some browsers still emits via execCommand) into a
     * `<span style="color:…">` so it gets the same treatment.
     *
     * Idempotent — won't double-add !important if already present.
     */
    protected function boostInlineStyles(string $html): string
    {
        if ($html === '') {
            return $html;
        }

        // 1) Normalise <font color="…"> → <span style="color:…"> (and </font>).
        if (stripos($html, '<font') !== false) {
            $html = preg_replace_callback(
                '/<font\b([^>]*)>/i',
                function ($m) {
                    $attrs = $m[1];
                    $color = null;
                    if (preg_match('/\bcolor\s*=\s*"([^"]+)"/i', $attrs, $c)) {
                        $color = $c[1];
                    } elseif (preg_match("/\bcolor\s*=\s*'([^']+)'/i", $attrs, $c)) {
                        $color = $c[1];
                    } elseif (preg_match('/\bcolor\s*=\s*([^\s>]+)/i', $attrs, $c)) {
                        $color = $c[1];
                    }

                    return $color !== null
                        ? '<span style="color: '.htmlspecialchars($color, ENT_QUOTES).' !important;">'
                        : '<span>';
                },
                $html
            ) ?? $html;
            $html = str_ireplace('</font>', '</span>', $html);
        }

        if (stripos($html, 'style=') === false) {
            return $html;
        }

        // 2) For ANY tag with a style="…" attr, boost color/bg/font-weight to !important.
        return preg_replace_callback(
            '/(<[a-zA-Z][a-zA-Z0-9]*\b[^>]*\bstyle\s*=\s*")([^"]*)(")/i',
            function ($m) {
                $styleStr = $m[2];
                $newStyle = preg_replace_callback(
                    '/([a-zA-Z\-]+)\s*:\s*([^;]+?)(\s*!important)?\s*(;|$)/',
                    function ($p) {
                        $prop = trim($p[1]);
                        $val = trim($p[2]);
                        $boostTargets = ['color', 'background-color', 'font-weight'];
                        if (in_array(strtolower($prop), $boostTargets, true) && empty($p[3])) {
                            return "{$prop}: {$val} !important;";
                        }

                        return "{$prop}: {$val}".($p[3] ?? '').';';
                    },
                    $styleStr
                );

                return $m[1].$newStyle.$m[3];
            },
            $html
        ) ?? $html;
    }

    protected function renderParagraph(array $data, string $alignStyle): string
    {
        $text = $this->boostInlineStyles($data['text'] ?? '');

        return "<p{$alignStyle}>{$text}</p>\n";
    }

    protected function renderHeader(array $data, string $alignStyle): string
    {
        $level = min(max((int) ($data['level'] ?? 2), 1), 6);
        $text = $this->boostInlineStyles($data['text'] ?? '');

        return "<h{$level}{$alignStyle}>{$text}</h{$level}>\n";
    }

    protected function renderList(array $data): string
    {
        $style = ($data['style'] ?? 'unordered') === 'ordered' ? 'ol' : 'ul';
        $items = $data['items'] ?? [];
        $listClass = $style === 'ol' ? 'list-decimal' : 'list-disc';
        $html = "<{$style} class=\"{$listClass} pl-6 my-4\">\n";

        foreach ($items as $item) {
            $text = is_array($item) ? ($item['content'] ?? '') : $item;
            $html .= '  <li>'.$this->boostInlineStyles($text)."</li>\n";
        }

        return $html."</{$style}>\n";
    }

    protected function renderNestedList(array $data): string
    {
        $style = ($data['style'] ?? 'unordered') === 'ordered' ? 'ol' : 'ul';
        $items = $data['items'] ?? [];

        return $this->renderNestedItems($items, $style);
    }

    /**
     * @param  array<int, mixed>  $items
     */
    protected function renderNestedItems(array $items, string $style): string
    {
        $listClass = $style === 'ol' ? 'list-decimal' : 'list-disc';
        $html = "<{$style} class=\"{$listClass} pl-6 my-2\">\n";

        foreach ($items as $item) {
            $content = is_array($item) ? ($item['content'] ?? '') : $item;
            $html .= '  <li>'.$this->boostInlineStyles($content);

            if (is_array($item) && ! empty($item['items'])) {
                $html .= $this->renderNestedItems($item['items'], $style);
            }

            $html .= "</li>\n";
        }

        return $html."</{$style}>\n";
    }

    protected function renderSpace(array $data): string
    {
        $height = trim((string) ($data['height'] ?? '2rem'));
        // Allow only safe size values (digits + px/rem/em/%/vh/vw)
        if (! preg_match('/^[\d.]+(px|rem|em|%|vh|vw)?$/', $height)) {
            $height = '2rem';
        }

        return '<div aria-hidden="true" style="height:'.$height.'" class="ce-space"></div>'."\n";
    }

    protected function renderQuote(array $data): string
    {
        $text = $this->boostInlineStyles($data['text'] ?? '');
        $caption = $this->boostInlineStyles($data['caption'] ?? '');
        $align = $data['alignment'] ?? 'left';
        $captionHtml = $caption ? "<cite class=\"block mt-2 text-sm text-gray-500\">{$caption}</cite>" : '';

        return "<blockquote class=\"border-l-4 border-gray-300 pl-4 my-4 italic text-{$align}\">{$text}{$captionHtml}</blockquote>\n";
    }

    protected function renderCode(array $data): string
    {
        $code = htmlspecialchars($data['code'] ?? '');
        $lang = $data['language'] ?? '';
        $langClass = $lang ? " language-{$lang}" : '';

        return "<pre class=\"bg-gray-900 text-gray-100 rounded-lg p-4 my-4 overflow-x-auto\"><code class=\"{$langClass}\">{$code}</code></pre>\n";
    }

    protected function renderImage(array $data, array $tunes = []): string
    {
        $url = $data['file']['url'] ?? $data['url'] ?? '';
        if (! $url) {
            return '';
        }

        $caption = $data['caption'] ?? '';
        $withBorder = ($data['withBorder'] ?? false) ? 'border border-gray-200' : '';
        $withBackground = ($data['withBackground'] ?? false) ? 'bg-gray-100 p-4' : '';
        $stretched = ($data['stretched'] ?? false) ? 'w-full' : 'max-w-full';
        $classes = trim("my-4 rounded-lg {$withBorder} {$withBackground} {$stretched}");

        // Image resize tune (25/50/75/100% or custom value like '420px' / '60%')
        $sizeStyle = '';
        $sizeKey = $tunes['imageSize']['size'] ?? null;
        $custom = $tunes['imageSize']['custom'] ?? null;
        $sizeMap = ['25' => '25%', '50' => '50%', '75' => '75%', '100' => '100%'];
        $widthValue = null;
        if ($sizeKey === 'custom' && $custom) {
            $widthValue = trim((string) $custom);
        } elseif ($sizeKey && isset($sizeMap[$sizeKey])) {
            $widthValue = $sizeMap[$sizeKey];
        }
        if ($widthValue) {
            // Basic safety — only allow simple width syntax (digits + px/% + rem etc.)
            if (preg_match('/^[\d.]+(px|%|rem|em|vw)?$/', $widthValue)) {
                $sizeStyle = ' style="width:'.$widthValue.';max-width:'.$widthValue.';height:auto;"';
            }
        }

        $html = '<figure class="my-4">';
        $html .= "<img src=\"{$url}\" alt=\"".htmlspecialchars($caption)."\" class=\"{$classes}\"{$sizeStyle}>";
        if ($caption) {
            $html .= "<figcaption class=\"text-center text-sm text-gray-500 mt-2\">{$caption}</figcaption>";
        }

        return $html."</figure>\n";
    }

    protected function renderSimpleImage(array $data, array $tunes = []): string
    {
        return $this->renderImage($data, $tunes);
    }

    protected function renderEmbed(array $data): string
    {
        $service = $data['service'] ?? '';
        $embedUrl = $data['embed'] ?? '';
        $caption = $data['caption'] ?? '';
        $width = $data['width'] ?? 580;
        $height = $data['height'] ?? 320;

        if (! $embedUrl) {
            return '';
        }

        $captionHtml = $caption ? "<figcaption class=\"text-center text-sm text-gray-500 mt-2\">{$caption}</figcaption>" : '';

        return "<figure class=\"my-4\"><div class=\"relative\" style=\"padding-top:56.25%\"><iframe src=\"{$embedUrl}\" class=\"absolute inset-0 w-full h-full rounded-lg\" frameborder=\"0\" allowfullscreen></iframe></div>{$captionHtml}</figure>\n";
    }

    protected function renderTable(array $data): string
    {
        $content = $data['content'] ?? [];
        $withHeadings = $data['withHeadings'] ?? false;

        if (empty($content)) {
            return '';
        }

        $html = "<div class=\"overflow-x-auto my-4\"><table class=\"min-w-full border border-gray-200 rounded-lg\">\n";

        foreach ($content as $i => $row) {
            if ($i === 0 && $withHeadings) {
                $html .= '<thead class="bg-gray-50"><tr>';
                foreach ($row as $cell) {
                    $html .= '<th class="px-4 py-2 text-left text-sm font-semibold text-gray-700 border-b border-gray-200">'.($cell).'</th>';
                }
                $html .= "</tr></thead><tbody>\n";
            } else {
                $html .= '<tr class="border-b border-gray-100 hover:bg-gray-50">';
                foreach ($row as $cell) {
                    $html .= '<td class="px-4 py-2 text-sm text-gray-700">'.($cell).'</td>';
                }
                $html .= "</tr>\n";
            }
        }

        if ($withHeadings) {
            $html .= '</tbody>';
        }

        return $html."</table></div>\n";
    }

    protected function renderChecklist(array $data): string
    {
        $items = $data['items'] ?? [];
        $html = "<ul class=\"my-4 space-y-1\">\n";

        foreach ($items as $item) {
            $text = $item['text'] ?? '';
            $checked = ($item['checked'] ?? false) ? 'checked' : '';
            $lineThrough = $checked ? 'line-through text-gray-400' : '';
            $html .= "<li class=\"flex items-center gap-2\"><input type=\"checkbox\" {$checked} disabled class=\"rounded\"> <span class=\"{$lineThrough}\">{$text}</span></li>\n";
        }

        return $html."</ul>\n";
    }

    protected function renderWarning(array $data): string
    {
        $title = $data['title'] ?? 'Warning';
        $message = $data['message'] ?? '';

        return "<div class=\"bg-yellow-50 border-l-4 border-yellow-400 p-4 my-4 rounded-r-lg\">\n  <p class=\"font-semibold text-yellow-800\">{$title}</p>\n  <p class=\"text-yellow-700 mt-1\">{$message}</p>\n</div>\n";
    }

    protected function renderAttachment(array $data): string
    {
        $url = $data['file']['url'] ?? '';
        $name = $data['title'] ?? basename($url);
        $size = $data['file']['size'] ?? 0;
        $sizeText = $size ? ' ('.number_format($size / 1024, 1).' KB)' : '';

        if (! $url) {
            return '';
        }

        return "<div class=\"flex items-center gap-3 bg-gray-50 border border-gray-200 rounded-lg p-3 my-4\">\n  <svg class=\"w-6 h-6 text-gray-400\" fill=\"none\" stroke=\"currentColor\" viewBox=\"0 0 24 24\"><path stroke-linecap=\"round\" stroke-linejoin=\"round\" stroke-width=\"2\" d=\"M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z\"/></svg>\n  <a href=\"{$url}\" target=\"_blank\" class=\"text-blue-600 hover:underline flex-1\">{$name}{$sizeText}</a>\n</div>\n";
    }

    protected function renderLinkTool(array $data): string
    {
        $link = $data['link'] ?? '';
        $meta = $data['meta'] ?? [];
        $title = $meta['title'] ?? $link;
        $description = $meta['description'] ?? '';
        $image = $meta['image']['url'] ?? '';

        if (! $link) {
            return '';
        }

        $imageHtml = $image ? "<img src=\"{$image}\" class=\"w-20 h-20 object-cover rounded flex-shrink-0\" alt=\"\">" : '';

        return "<a href=\"{$link}\" target=\"_blank\" class=\"flex items-start gap-4 bg-gray-50 border border-gray-200 rounded-lg p-4 my-4 hover:bg-gray-100 transition no-underline\">{$imageHtml}<div><p class=\"font-semibold text-gray-900\">{$title}</p><p class=\"text-sm text-gray-500 mt-1\">{$description}</p><p class=\"text-xs text-blue-500 mt-1\">{$link}</p></div></a>\n";
    }

    /**
     * Render a SectionEmbed block — the generic "loop a template's entries
     * through a custom card design" tool.
     *
     * Expected $data shape (matches what the JS SectionEmbedTool saves):
     *   - source_template:    string slug          (which template's entries)
     *   - limit:              int                  (max rows; 0 = no limit)
     *   - order_by:           string column        (default created_at)
     *   - order_dir:          'asc' | 'desc'       (default desc)
     *   - columns:            int 1..6             (grid columns)
     *   - gap:                'tight'|'normal'|'large'
     *   - heading / subheading: optional title above the loop
     *   - card_template_slug: optional library reference  (CardTemplate.slug)
     *   - card_html:          inline card HTML if not using library
     *   - section_class:      Tailwind wrapper classes
     *
     * Falls back to an empty placeholder when the source template is missing
     * or the card has no HTML — never throws.
     */
    protected function renderSectionEmbed(array $data): string
    {
        $sourceSlug = trim((string) ($data['source_template'] ?? ''));
        if ($sourceSlug === '') {
            return '';
        }

        $cardHtml = $this->resolveCardHtml($data);
        if ($cardHtml === '') {
            return '';
        }

        $sourceTemplate = null;
        try {
            $sourceTemplate = \App\Models\Template::query()->where('slug', $sourceSlug)->first();
        } catch (\Throwable $e) {
            \Illuminate\Support\Facades\Log::warning('sectionEmbed: template lookup failed', [
                'slug' => $sourceSlug, 'error' => $e->getMessage(),
            ]);
        }
        if (! $sourceTemplate || ! $sourceTemplate->model_class) {
            return '';
        }

        $modelClass = str_contains($sourceTemplate->model_class, '\\')
            ? $sourceTemplate->model_class
            : 'App\\Models\\'.$sourceTemplate->model_class;
        if (! class_exists($modelClass)) {
            return '';
        }

        $limit = (int) ($data['limit'] ?? 12);
        $orderBy = (string) ($data['order_by'] ?? 'created_at');
        $orderDir = (($data['order_dir'] ?? 'desc') === 'asc') ? 'asc' : 'desc';
        $columns = (int) ($data['columns'] ?? 3);
        if (! in_array($columns, [1, 2, 3, 4, 5, 6], true)) {
            $columns = 3;
        }
        $gapKey = (string) ($data['gap'] ?? 'normal');
        $sectionClass = trim((string) ($data['section_class'] ?? ''));
        $heading = trim((string) ($data['heading'] ?? ''));
        $subheading = trim((string) ($data['subheading'] ?? ''));

        $gapClass = match ($gapKey) {
            'tight' => 'gap-4',
            'large' => 'gap-10',
            default => 'gap-6',
        };
        $gridClass = match ($columns) {
            1 => 'grid-cols-1',
            2 => 'grid-cols-1 sm:grid-cols-2',
            3 => 'grid-cols-1 sm:grid-cols-2 lg:grid-cols-3',
            4 => 'grid-cols-1 sm:grid-cols-2 lg:grid-cols-4',
            5 => 'grid-cols-1 sm:grid-cols-2 lg:grid-cols-5',
            6 => 'grid-cols-1 sm:grid-cols-2 lg:grid-cols-6',
        };

        try {
            $query = $modelClass::query();
            if (method_exists($modelClass, 'scopeActive')) {
                try {
                    $query->active();
                    // Some scopeActive implementations reference columns that
                    // don't exist on every consumer table (legacy schema drift).
                    // Probe the scoped query — if it explodes at SQL-time, fall
                    // back to an unscoped query so the renderer still shows
                    // entries rather than silently outputting nothing.
                    $query->toSql();
                } catch (\Throwable $e) {
                    \Illuminate\Support\Facades\Log::warning('sectionEmbed: scopeActive failed, dropping scope', [
                        'slug' => $sourceSlug, 'error' => $e->getMessage(),
                    ]);
                    $query = $modelClass::query();
                }
            }
            $table = (new $modelClass)->getTable();
            $isSortable = (bool) ($sourceTemplate->settings['sortable'] ?? false);
            if ($isSortable && \Illuminate\Support\Facades\Schema::hasColumn($table, 'sort_order')) {
                $query->orderBy('sort_order')->orderBy('id');
            } else {
                $orderColumn = \Illuminate\Support\Facades\Schema::hasColumn($table, $orderBy)
                    ? $orderBy : 'created_at';
                $query->orderBy($orderColumn, $orderDir);
            }
            if ($limit > 0) {
                $query->limit($limit);
            }
            $entries = $query->get();
        } catch (\Throwable $e) {
            \Illuminate\Support\Facades\Log::warning('sectionEmbed query failed', [
                'slug' => $sourceSlug, 'error' => $e->getMessage(),
            ]);

            return '';
        }

        if ($entries->isEmpty()) {
            return '';
        }

        $cards = '';
        $renderer = app(\App\Services\CardRenderer::class);
        foreach ($entries as $entry) {
            $cards .= $renderer->render($cardHtml, $entry, $sourceTemplate);
        }

        $titleBlock = '';
        if ($heading !== '' || $subheading !== '') {
            $titleBlock = '<div class="mb-8 text-center">';
            if ($subheading !== '') {
                $titleBlock .= '<p class="text-sm font-semibold uppercase tracking-wider opacity-70">'.e($subheading).'</p>';
            }
            if ($heading !== '') {
                $titleBlock .= '<h2 class="mt-2 text-3xl font-bold md:text-4xl">'.e($heading).'</h2>';
            }
            $titleBlock .= '</div>';
        }

        $wrapClass = $sectionClass !== '' ? $sectionClass : 'py-12';

        return '<section class="'.e($wrapClass).'">'
            .'<div class="mx-auto max-w-7xl px-4 sm:px-6 lg:px-8">'
            .$titleBlock
            .'<div class="grid '.$gridClass.' '.$gapClass.'">'
            .$cards
            .'</div></div></section>'."\n";
    }

    /**
     * Resolve the card HTML for a sectionEmbed block: prefer inline html,
     * fall back to the library reference, then to an empty string.
     */
    protected function resolveCardHtml(array $data): string
    {
        $inline = trim((string) ($data['card_html'] ?? ''));
        if ($inline !== '') {
            return $inline;
        }

        $slug = trim((string) ($data['card_template_slug'] ?? ''));
        if ($slug === '') {
            return '';
        }
        try {
            $card = \App\Models\CardTemplate::query()->where('slug', $slug)->first();

            return $card?->html ?? '';
        } catch (\Throwable $e) {
            return '';
        }
    }

    protected function renderPersonality(array $data): string
    {
        $name = $data['name'] ?? '';
        $description = $data['description'] ?? '';
        $photo = $data['photo']['url'] ?? '';
        $link = $data['link'] ?? '';
        $photoHtml = $photo ? "<img src=\"{$photo}\" class=\"w-16 h-16 rounded-full object-cover flex-shrink-0\" alt=\"{$name}\">" : '';
        $nameHtml = $link ? "<a href=\"{$link}\" target=\"_blank\" class=\"font-semibold text-blue-600 hover:underline\">{$name}</a>" : "<span class=\"font-semibold\">{$name}</span>";

        return "<div class=\"flex items-center gap-4 my-4\">{$photoHtml}<div>{$nameHtml}<p class=\"text-sm text-gray-500\">{$description}</p></div></div>\n";
    }
}
