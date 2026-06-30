<?php

namespace Weborange\VisualBuilder\Support;

use Weborange\VisualBuilder\Contracts\BuilderPersistence;

/**
 * Default no-op persistence so the package boots standalone. The pure visual
 * builder (tree/preview/inspector/palette) works; saving is disabled until the
 * host binds a real BuilderPersistence implementation.
 */
class NullPersistence implements BuilderPersistence
{
    public function targets(): array
    {
        return [];
    }

    public function sections(int|string $targetId): array
    {
        return [];
    }

    public function seedFor(int|string $targetId): ?string
    {
        return null;
    }

    public function styleTemplates(): array
    {
        return [];
    }

    public function save(array $payload): array
    {
        return [
            'success' => false,
            'message' => 'No persistence is configured for the visual builder. Bind '
                .BuilderPersistence::class.' in the host application.',
        ];
    }
}
