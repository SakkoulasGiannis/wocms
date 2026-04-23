<?php

namespace App\Services\AI\Tools;

interface ToolInterface
{
    /**
     * Unique tool identifier (snake_case). Used in LLM tool calls.
     */
    public function name(): string;

    /**
     * Short human-readable label (shown in confirmation UI).
     */
    public function label(): string;

    /**
     * Natural-language description shown to the LLM. Should explain WHEN to use this tool.
     */
    public function description(): string;

    /**
     * JSON Schema for the tool's inputs. Must include type, properties, required.
     */
    public function schema(): array;

    /**
     * Whether the tool should pause and ask user confirmation before executing.
     */
    public function requiresConfirmation(): bool;

    /**
     * Whether the tool modifies data (destructive / write) vs read-only.
     */
    public function isDestructive(): bool;

    /**
     * Short preview shown to user before execution (e.g. in a confirmation modal).
     * Should summarize the intended action based on the arguments.
     */
    public function previewMessage(array $args): string;

    /**
     * Validate the given arguments against the schema. Returns array of errors (empty = valid).
     */
    public function validate(array $args): array;

    /**
     * Execute the tool. Returns structured result:
     *   [
     *     'success' => bool,
     *     'message' => string,        // user-facing success/error message
     *     'data' => array|null,       // tool-specific output
     *     'undo_payload' => array|null, // data needed to undo this action
     *   ]
     */
    public function execute(array $args): array;
}
