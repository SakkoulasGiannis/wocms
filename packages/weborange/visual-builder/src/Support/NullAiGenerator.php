<?php

namespace Weborange\VisualBuilder\Support;

use Weborange\VisualBuilder\Contracts\AiGenerator;

/**
 * Default no-op AI generator — the host binds a real one to enable the AI button.
 */
class NullAiGenerator implements AiGenerator
{
    public function generate(string $prompt, ?string $currentHtml = null, ?string $styleReference = null): array
    {
        return ['ok' => false, 'error' => 'AI generation is not configured for this app.'];
    }

    public function fixStructure(string $html): array
    {
        return ['ok' => false, 'error' => 'AI generation is not configured for this app.'];
    }
}
