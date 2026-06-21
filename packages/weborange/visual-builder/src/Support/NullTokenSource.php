<?php

namespace Weborange\VisualBuilder\Support;

use Weborange\VisualBuilder\Contracts\TokenSource;

/**
 * Default empty token source — no dynamic sources/tokens until the host binds a
 * real TokenSource implementation.
 */
class NullTokenSource implements TokenSource
{
    public function sources(): array
    {
        return [];
    }

    public function tokens(string $source): array
    {
        return [];
    }

    public function forms(): array
    {
        return [];
    }

    public function renderLoop(string $source, array $query, string $itemHtml): array
    {
        return [];
    }
}
