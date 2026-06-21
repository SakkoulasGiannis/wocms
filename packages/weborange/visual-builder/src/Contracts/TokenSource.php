<?php

namespace Weborange\VisualBuilder\Contracts;

/**
 * The host app implements this to expose dynamic-value sources (collections /
 * entity types) and the {tokens} available for each — used by the builder's
 * token picker and dynamic-loop feature.
 */
interface TokenSource
{
    /**
     * Available collection/entity sources for loops and tokens.
     *
     * @return array<int, array{slug:string, name:string}>
     */
    public function sources(): array;

    /**
     * The {tokens} (fields) available for a given source.
     *
     * @return array<int, array{token:string, label:string}>
     */
    public function tokens(string $source): array;

    /**
     * Embeddable forms the builder can drop into a page (slug + display name).
     * Return an empty array if the host has no forms feature.
     *
     * @return array<int, array{slug:string, name:string}>
     */
    public function forms(): array;

    /**
     * Render a repeater's item template once per entity of the query, with
     * {tokens} resolved against each entity. Powers the live preview and the
     * frontend expansion of data-vb-loop regions.
     *
     * @param  array{limit?:int, order_by?:string, order_dir?:string, offset?:int, filter_field?:string, filter_value?:string}  $query
     * @return array<int, string> resolved HTML per entity
     */
    public function renderLoop(string $source, array $query, string $itemHtml): array;
}
