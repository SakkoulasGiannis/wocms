<?php

namespace App\Services\AI;

class AIResponse
{
    public function __construct(
        public string $content,
        public array $data = [],
        public string $intent = 'chat',
        public bool $success = true,
        public ?string $error = null
    ) {}

    public static function success(string $content, array $data = [], string $intent = 'chat'): self
    {
        return new self($content, $data, $intent, true, null);
    }

    public static function error(string $error): self
    {
        return new self('', [], 'error', false, $error);
    }
}
