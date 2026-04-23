<?php

namespace App\Services\AI;

/**
 * Unified response from any provider's chatWithTools call.
 *
 * Represents ONE step of the agentic loop. Either:
 *   - the LLM returned text (finished, no tool call) — toolCalls = []
 *   - the LLM wants to call tool(s) — text may be empty, toolCalls populated
 */
class ToolCallResponse
{
    /**
     * @param  string  $text  Assistant's natural-language reply (may be empty when calling tools)
     * @param  array  $toolCalls  Each: ['id' => 'call_xxx', 'name' => 'tool_name', 'arguments' => [...]]
     * @param  string|null  $stopReason  Provider-specific stop reason (e.g. 'tool_use', 'end_turn', 'stop')
     * @param  array  $raw  Raw provider response for debugging
     */
    public function __construct(
        public string $text = '',
        public array $toolCalls = [],
        public ?string $stopReason = null,
        public array $raw = []
    ) {}

    public function hasToolCalls(): bool
    {
        return ! empty($this->toolCalls);
    }

    public function toArray(): array
    {
        return [
            'text' => $this->text,
            'tool_calls' => $this->toolCalls,
            'stop_reason' => $this->stopReason,
        ];
    }
}
