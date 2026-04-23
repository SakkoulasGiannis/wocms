<?php

namespace App\Services\AI\Tools;

use Illuminate\Support\Facades\Validator;

abstract class BaseTool implements ToolInterface
{
    /**
     * Default: destructive tools require confirmation, read-only don't.
     */
    public function requiresConfirmation(): bool
    {
        return $this->isDestructive();
    }

    /**
     * Default: all tools are destructive (safer default). Override to return false for read-only.
     */
    public function isDestructive(): bool
    {
        return true;
    }

    /**
     * Convert the JSON Schema into Laravel validation rules.
     * Subclasses can override for custom rules.
     */
    protected function validationRules(): array
    {
        return [];
    }

    public function validate(array $args): array
    {
        $rules = $this->validationRules();
        if (empty($rules)) {
            return [];
        }

        $validator = Validator::make($args, $rules);

        if ($validator->fails()) {
            return $validator->errors()->all();
        }

        return [];
    }

    /**
     * Helper to build a consistent success response.
     */
    protected function success(string $message, array $data = [], array $undoPayload = []): array
    {
        return [
            'success' => true,
            'message' => $message,
            'data' => $data,
            'undo_payload' => $undoPayload,
        ];
    }

    /**
     * Helper to build a consistent error response.
     */
    protected function error(string $message, array $data = []): array
    {
        return [
            'success' => false,
            'message' => $message,
            'data' => $data,
            'undo_payload' => [],
        ];
    }

    /**
     * Default preview — subclasses should override for better UX.
     */
    public function previewMessage(array $args): string
    {
        return $this->label().': '.json_encode($args, JSON_UNESCAPED_UNICODE);
    }
}
