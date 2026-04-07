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

        return match ($type) {
            'paragraph' => $this->renderParagraph($data, $alignStyle),
            'header' => $this->renderHeader($data, $alignStyle),
            'list' => $this->renderList($data),
            'nestedList' => $this->renderNestedList($data),
            'quote' => $this->renderQuote($data),
            'code' => $this->renderCode($data),
            'delimiter' => '<hr class="editorjs-delimiter my-8"/>',
            'image' => $this->renderImage($data),
            'simpleImage' => $this->renderSimpleImage($data),
            'embed' => $this->renderEmbed($data),
            'table' => $this->renderTable($data),
            'raw' => $data['html'] ?? '',
            'checklist' => $this->renderChecklist($data),
            'warning' => $this->renderWarning($data),
            'attaches' => $this->renderAttachment($data),
            'linkTool' => $this->renderLinkTool($data),
            'personality' => $this->renderPersonality($data),
            default => '',
        };
    }

    protected function renderParagraph(array $data, string $alignStyle): string
    {
        $text = $data['text'] ?? '';

        return "<p{$alignStyle}>{$text}</p>\n";
    }

    protected function renderHeader(array $data, string $alignStyle): string
    {
        $level = min(max((int) ($data['level'] ?? 2), 1), 6);
        $text = $data['text'] ?? '';

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
            $html .= "  <li>{$text}</li>\n";
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
            $html .= "  <li>{$content}";

            if (is_array($item) && ! empty($item['items'])) {
                $html .= $this->renderNestedItems($item['items'], $style);
            }

            $html .= "</li>\n";
        }

        return $html."</{$style}>\n";
    }

    protected function renderQuote(array $data): string
    {
        $text = $data['text'] ?? '';
        $caption = $data['caption'] ?? '';
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

    protected function renderImage(array $data): string
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

        $html = '<figure class="my-4">';
        $html .= "<img src=\"{$url}\" alt=\"".htmlspecialchars($caption)."\" class=\"{$classes}\">";
        if ($caption) {
            $html .= "<figcaption class=\"text-center text-sm text-gray-500 mt-2\">{$caption}</figcaption>";
        }

        return $html."</figure>\n";
    }

    protected function renderSimpleImage(array $data): string
    {
        return $this->renderImage($data);
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
