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
}
