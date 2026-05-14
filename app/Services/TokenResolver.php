<?php

namespace App\Services;

use Spatie\MediaLibrary\HasMedia;

/**
 * Resolves {token} placeholders in section content/settings against an Eloquent entry.
 *
 * Supported syntax:
 *   {name}                 → $entry->name
 *   {location|—}           → $entry->location, falling back to '—' if blank
 *   {main_image:hero}      → $entry->getFirstMediaUrl('main_image', 'hero')   (Spatie media)
 *   {main_image:hero|/themes/.../fallback.jpg}   → media URL or static fallback
 *
 * The resolver is applied to BOTH string scalars and nested arrays/JSON so it
 * works transparently when section content is e.g. `{"hero":{"title":"Welcome to {name}"}}`.
 *
 * Strings that contain NO tokens pass through unchanged — calling resolve() with
 * a null entry is a no-op, which lets us call it unconditionally from the
 * render-section partial without breaking pages that have no $entry in context.
 */
class TokenResolver
{
    /**
     * Regex breakdown:
     *   \{                       literal {
     *   ([\w.-]+)                group 1: field name (alpha-num + . - _)
     *   (?::([\w-]+))?           group 2 (optional): :conversion (e.g. :hero, :thumb)
     *   (?:\|([^}]*))?           group 3 (optional): |fallback text up to closing }
     *   \}                       literal }
     */
    private const TOKEN_REGEX = '/\{([\w.\-]+)(?::([\w-]+))?(?:\|([^}]*))?\}/u';

    /**
     * Resolve tokens in $value against $entry. Handles strings, arrays (recursive),
     * and any other type passes through unchanged. Null entry → no-op.
     *
     * @param  mixed  $value
     * @param  object|null  $entry
     * @return mixed
     */
    public function resolve(mixed $value, ?object $entry): mixed
    {
        if ($entry === null) {
            return $value;
        }

        if (is_array($value)) {
            $out = [];
            foreach ($value as $k => $v) {
                $out[$k] = $this->resolve($v, $entry);
            }
            return $out;
        }

        if (! is_string($value) || $value === '' || ! str_contains($value, '{')) {
            return $value;
        }

        return preg_replace_callback(
            self::TOKEN_REGEX,
            fn (array $m) => $this->resolveOne(
                $entry,
                $m[1],
                $m[2] ?? null,
                $m[3] ?? null
            ),
            $value
        );
    }

    /**
     * Resolve a single {field} token. Tries Spatie media first when a conversion
     * is requested (or the field name looks image-like and the entry has media),
     * then falls through to direct property access.
     */
    protected function resolveOne(object $entry, string $field, ?string $conversion, ?string $fallback): string
    {
        // 1. Spatie media collection if entry implements HasMedia
        if ($entry instanceof HasMedia || method_exists($entry, 'getFirstMediaUrl')) {
            try {
                $url = $conversion
                    ? $entry->getFirstMediaUrl($field, $conversion)
                    : $entry->getFirstMediaUrl($field);
                if (! empty($url)) {
                    return $url;
                }
            } catch (\Throwable $e) {
                // collection may not be registered — fall through
            }
        }

        // 2. Direct property
        if (isset($entry->{$field}) && $entry->{$field} !== '' && $entry->{$field} !== null) {
            $raw = $entry->{$field};

            // Arrays / JSON fields — join with commas for inline placement
            if (is_array($raw)) {
                $raw = implode(', ', array_filter(array_map(fn ($v) => is_scalar($v) ? (string) $v : null, $raw)));
            }

            // Allow chained accessors like {agent.name} → $entry->agent->name
            return (string) $raw;
        }

        // 2b. Dot-notation traversal for related models / nested arrays
        if (str_contains($field, '.')) {
            $current = $entry;
            foreach (explode('.', $field) as $segment) {
                if (is_object($current) && isset($current->{$segment})) {
                    $current = $current->{$segment};
                } elseif (is_array($current) && isset($current[$segment])) {
                    $current = $current[$segment];
                } else {
                    $current = null;
                    break;
                }
            }
            if ($current !== null && $current !== '') {
                return is_scalar($current) ? (string) $current : '';
            }
        }

        // 3. Fallback or empty string
        return $fallback ?? '';
    }
}
