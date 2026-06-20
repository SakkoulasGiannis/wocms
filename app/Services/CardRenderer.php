<?php

namespace App\Services;

use App\Models\Template;
use Illuminate\Support\Facades\Log;

/**
 * Renders a card HTML template by substituting {token} placeholders against
 * a single Eloquent entry.
 *
 * Layered on top of {@see TokenResolver} for field/media resolution and
 * adds three things on top:
 *
 *   1. The virtual {entry_url} token, which resolves to /{template_slug}/{slug}
 *      using the supplied source template.
 *   2. Modifier-driven escaping: {title} is HTML-escaped, {description:raw}
 *      and {description:html} pass through verbatim. Image conversions like
 *      {main_image:preview} return URLs and are escaped too — safe to drop
 *      into either src="…" or href="…".
 *   3. Helpful introspection (availableTokens) for the configurator sidebar.
 *
 * Used by both the in-editor preview and the server-side EditorJS renderer
 * for the SectionEmbed block.
 */
class CardRenderer
{
    /**
     * Modifier names that should pass values through without HTML-escaping.
     * Everything else (including null/missing modifier) gets `e()`.
     */
    private const RAW_MODIFIERS = ['raw', 'html'];

    /** Same regex as TokenResolver — kept in sync intentionally. */
    private const TOKEN_REGEX = '/\{([\w.\-]+)(?::([\w-]+))?(?:\|([^}]*))?\}/u';

    public function __construct(private readonly TokenResolver $tokens) {}

    /**
     * Substitute {tokens} in $cardHtml against a single $entry. Returns the
     * rendered HTML string. Tokens that don't resolve become empty strings
     * (or their |fallback when supplied).
     */
    public function render(string $cardHtml, object $entry, ?Template $sourceTemplate = null): string
    {
        if ($cardHtml === '') {
            return '';
        }

        // 1. Virtual {entry_url} — replaced before regex pass so it benefits
        //    from one fewer subpattern match per card.
        if ($sourceTemplate !== null) {
            $cardHtml = str_replace(
                '{entry_url}',
                e($this->entryUrl($entry, $sourceTemplate)),
                $cardHtml
            );
        }

        if (! str_contains($cardHtml, '{')) {
            return $cardHtml;
        }

        return preg_replace_callback(
            self::TOKEN_REGEX,
            function (array $m) use ($entry) {
                $field = $m[1];
                $modifier = $m[2] ?? null;
                $fallback = $m[3] ?? null;

                // Reconstruct the inner token so TokenResolver can do its
                // field/media/dot-notation lookup — single source of truth.
                $inner = '{'.$field
                    .($modifier !== null ? ':'.$modifier : '')
                    .($fallback !== null ? '|'.$fallback : '')
                    .'}';

                try {
                    $value = $this->tokens->resolve($inner, $entry);
                } catch (\Throwable $e) {
                    Log::warning('CardRenderer token failed', [
                        'field' => $field,
                        'error' => $e->getMessage(),
                    ]);

                    return $fallback ?? '';
                }

                if (! is_string($value)) {
                    $value = (string) ($value ?? '');
                }

                return in_array($modifier, self::RAW_MODIFIERS, true)
                    ? $value
                    : e($value);
            },
            $cardHtml
        );
    }

    /**
     * Build a per-entry URL using the source template's slug. Falls back to
     * the entry id when no `slug` column exists.
     */
    public function entryUrl(object $entry, Template $sourceTemplate): string
    {
        $segment = $entry->slug ?? $entry->id ?? '';

        return '/'.trim($sourceTemplate->slug, '/').'/'.$segment;
    }

    /**
     * Introspect a source template and return the tokens that will resolve
     * against its entries. Used by the configurator sidebar so users see
     * a clickable list of available placeholders instead of having to
     * remember field names.
     *
     * @return array<int, array{token: string, label: string, group: string}>
     */
    public function availableTokens(?Template $sourceTemplate): array
    {
        $tokens = [
            ['token' => '{entry_url}', 'label' => 'Entry URL', 'group' => 'Special'],
        ];

        if (! $sourceTemplate) {
            return $tokens;
        }

        // Common columns most entry models expose
        $tokens[] = ['token' => '{id}', 'label' => 'ID', 'group' => 'Common'];
        $tokens[] = ['token' => '{slug}', 'label' => 'Slug', 'group' => 'Common'];

        // Template-defined fields
        try {
            foreach ($sourceTemplate->fields()->orderBy('order')->get() as $field) {
                $type = $field->type ?? 'text';
                $isImageish = in_array($type, ['image', 'gallery', 'file'], true);
                $token = $isImageish
                    ? '{'.$field->name.':preview}'
                    : '{'.$field->name.'}';

                $tokens[] = [
                    'token' => $token,
                    'label' => $field->label ?? $field->name,
                    'group' => $isImageish ? 'Media' : 'Fields',
                ];

                // Offer a :raw variant for wysiwyg/markdown fields so users
                // can drop full HTML output into their card.
                if (in_array($type, ['wysiwyg', 'markdown', 'grapejs'], true)) {
                    $tokens[] = [
                        'token' => '{'.$field->name.':raw}',
                        'label' => ($field->label ?? $field->name).' (HTML)',
                        'group' => 'Fields',
                    ];
                }
            }
        } catch (\Throwable $e) {
            Log::warning('CardRenderer availableTokens failed', [
                'template' => $sourceTemplate->slug,
                'error' => $e->getMessage(),
            ]);
        }

        return $tokens;
    }
}
