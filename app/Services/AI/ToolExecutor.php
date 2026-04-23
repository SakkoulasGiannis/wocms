<?php

namespace App\Services\AI;

use App\Models\AIToolAudit;
use App\Services\AI\Tools\ToolInterface;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

class ToolExecutor
{
    public function __construct(
        protected ToolRegistry $registry
    ) {}

    /**
     * Execute a tool call with validation, transaction, and audit logging.
     *
     * @param  array  $options  ['provider' => 'claude|openai', 'chat_message_id' => int, 'confirmed' => bool, 'dry_run' => bool]
     * @return array ['success' => bool, 'message' => string, 'data' => array, 'undo_payload' => array, 'audit_id' => int]
     */
    public function execute(string $toolName, array $args, array $options = []): array
    {
        $tool = $this->registry->find($toolName);

        if (! $tool) {
            return $this->failedResult("Tool not found: {$toolName}", null);
        }

        // Pre-validate
        $errors = $tool->validate($args);
        if (! empty($errors)) {
            $audit = $this->createAudit($tool, $args, $options, false, false, false, 'Validation failed: '.implode('; ', $errors));

            return $this->failedResult('Validation failed: '.implode('; ', $errors), $audit);
        }

        // Confirmation gate
        $requiresConfirmation = $tool->requiresConfirmation();
        $confirmed = (bool) ($options['confirmed'] ?? false);

        if ($requiresConfirmation && ! $confirmed) {
            // User must confirm first — return preview
            $audit = $this->createAudit($tool, $args, $options, false, false, false, null);

            return [
                'success' => false,
                'requires_confirmation' => true,
                'preview' => $tool->previewMessage($args),
                'tool_name' => $tool->name(),
                'tool_label' => $tool->label(),
                'args' => $args,
                'audit_id' => $audit->id,
                'message' => 'Awaiting user confirmation',
            ];
        }

        // Dry-run mode
        if ($options['dry_run'] ?? false) {
            return [
                'success' => true,
                'dry_run' => true,
                'message' => '[DRY RUN] '.$tool->previewMessage($args),
                'data' => [],
                'undo_payload' => [],
            ];
        }

        // Execute inside DB transaction
        try {
            $result = DB::transaction(function () use ($tool, $args) {
                return $tool->execute($args);
            });
        } catch (\Throwable $e) {
            Log::error('Tool execution failed', [
                'tool' => $toolName,
                'args' => $args,
                'exception' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
            ]);
            $audit = $this->createAudit($tool, $args, $options, true, true, false, $e->getMessage());

            return $this->failedResult("Execution failed: {$e->getMessage()}", $audit);
        }

        $audit = $this->createAudit(
            $tool,
            $args,
            $options,
            confirmed: true,
            executed: true,
            success: (bool) ($result['success'] ?? false),
            error: $result['success'] ? null : ($result['message'] ?? 'Unknown error'),
            result: $result,
            undoPayload: $result['undo_payload'] ?? []
        );

        return [
            'success' => (bool) ($result['success'] ?? false),
            'message' => $result['message'] ?? '',
            'data' => $result['data'] ?? [],
            'undo_payload' => $result['undo_payload'] ?? [],
            'audit_id' => $audit->id,
        ];
    }

    protected function createAudit(
        ToolInterface $tool,
        array $args,
        array $options,
        bool $confirmed,
        bool $executed,
        bool $success,
        ?string $error,
        array $result = [],
        array $undoPayload = []
    ): AIToolAudit {
        return AIToolAudit::create([
            'user_id' => Auth::id(),
            'chat_message_id' => $options['chat_message_id'] ?? null,
            'tool_name' => $tool->name(),
            'provider' => $options['provider'] ?? null,
            'args' => $args,
            'result' => $result ?: null,
            'undo_payload' => $undoPayload ?: null,
            'confirmed' => $confirmed,
            'executed' => $executed,
            'success' => $success,
            'error' => $error,
        ]);
    }

    protected function failedResult(string $message, ?AIToolAudit $audit): array
    {
        return [
            'success' => false,
            'message' => $message,
            'data' => [],
            'undo_payload' => [],
            'audit_id' => $audit?->id,
        ];
    }
}
