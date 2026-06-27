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
     * Embeddable sliders the builder can drop into a page (id + display name).
     * Return an empty array if the host has no slider feature.
     *
     * @return array<int, array{id:int|string, name:string}>
     */
    public function sliders(): array;

    /**
     * Render a single slider (by id) to its frontend HTML — used by the builder
     * live preview to show the real slider instead of a placeholder.
     */
    public function renderSlider(string $id): string;

    /**
     * Entries of a source (id + label) for the repeater's "pick specific items"
     * selector. Return an empty array if the source is unknown.
     *
     * @return array<int, array{id:int|string, name?:string, label?:string}>
     */
    public function entries(string $source): array;

    /**
     * All content-tree nodes (id + label) for the repeater's "children of …"
     * parent selector. Return an empty array if the host has no node tree.
     *
     * @return array<int, array{id:int|string, label:string}>
     */
    public function nodes(): array;

    /**
     * The host's site-wide custom CSS (applies to every front-end page). The
     * builder loads this into its "All pages" CSS editor. Return '' if the host
     * has no site-wide CSS store.
     */
    public function siteCss(): string;

    /**
     * Persist the host's site-wide custom CSS. Called from the builder's save
     * when the "All pages" CSS editor changed. No-op if the host has no store.
     */
    public function saveSiteCss(string $css): void;

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
