<?php

namespace App\Services\AI;

use App\Services\AI\Tools\CreateContentEntryTool;
use App\Services\AI\Tools\CreatePageSectionTool;
use App\Services\AI\Tools\CreateSliderWithSlidesTool;
use App\Services\AI\Tools\ToolInterface;
use App\Services\AI\Tools\UpdatePageSectionTool;
use App\Services\AI\Tools\UpdateSiteSettingsTool;

class ToolRegistry
{
    /**
     * @var ToolInterface[]
     */
    protected array $tools = [];

    public function __construct()
    {
        $this->bootDefaults();
    }

    protected function bootDefaults(): void
    {
        $this->register(new CreateContentEntryTool);
        $this->register(new CreatePageSectionTool);
        $this->register(new UpdatePageSectionTool);
        $this->register(new CreateSliderWithSlidesTool);
        $this->register(new UpdateSiteSettingsTool);
    }

    public function register(ToolInterface $tool): void
    {
        $this->tools[$tool->name()] = $tool;
    }

    /**
     * @return ToolInterface[]
     */
    public function all(): array
    {
        return array_values($this->tools);
    }

    public function find(string $name): ?ToolInterface
    {
        return $this->tools[$name] ?? null;
    }

    public function has(string $name): bool
    {
        return isset($this->tools[$name]);
    }

    /**
     * Export tools in Claude's native tool_use format.
     * See: https://docs.claude.com/en/docs/build-with-claude/tool-use
     */
    public function toClaudeSchema(): array
    {
        return array_map(fn (ToolInterface $tool) => [
            'name' => $tool->name(),
            'description' => $tool->description(),
            'input_schema' => $tool->schema(),
        ], $this->all());
    }

    /**
     * Export tools in OpenAI's native function calling format.
     * See: https://platform.openai.com/docs/guides/function-calling
     */
    public function toOpenAISchema(): array
    {
        return array_map(fn (ToolInterface $tool) => [
            'type' => 'function',
            'function' => [
                'name' => $tool->name(),
                'description' => $tool->description(),
                'parameters' => $tool->schema(),
            ],
        ], $this->all());
    }
}
