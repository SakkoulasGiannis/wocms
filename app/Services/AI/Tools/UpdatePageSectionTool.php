<?php

namespace App\Services\AI\Tools;

use App\Models\PageSection;

class UpdatePageSectionTool extends BaseTool
{
    public function name(): string
    {
        return 'update_page_section';
    }

    public function label(): string
    {
        return 'Update Page Section';
    }

    public function description(): string
    {
        return 'Update an existing PageSection. Supports partial merges of content and settings, renaming, toggling visibility, and reordering. Use this when the user asks to change text, colors, toggle display, or reorder an existing section.';
    }

    public function schema(): array
    {
        return [
            '$schema' => 'https://json-schema.org/draft/2020-12/schema',
            'type' => 'object',
            'properties' => [
                'section_id' => [
                    'type' => 'integer',
                    'minimum' => 1,
                    'description' => 'ID of the PageSection to update.',
                ],
                'content' => [
                    'type' => 'object',
                    'description' => 'Partial content update. Merged with existing content (new values override).',
                    'additionalProperties' => true,
                ],
                'settings' => [
                    'type' => 'object',
                    'description' => 'Partial settings update. Merged with existing settings (new values override).',
                    'additionalProperties' => true,
                ],
                'name' => [
                    'type' => 'string',
                    'description' => 'Rename the section.',
                ],
                'is_visible' => [
                    'type' => 'boolean',
                    'description' => 'Show (true) or hide (false) the section.',
                ],
                'order' => [
                    'type' => 'integer',
                    'minimum' => 0,
                    'description' => 'New display order.',
                ],
            ],
            'required' => ['section_id'],
            'additionalProperties' => false,
        ];
    }

    protected function validationRules(): array
    {
        return [
            'section_id' => 'required|integer|min:1',
            'content' => 'sometimes|array',
            'settings' => 'sometimes|array',
            'name' => 'sometimes|string',
            'is_visible' => 'sometimes|boolean',
            'order' => 'sometimes|integer|min:0',
        ];
    }

    public function previewMessage(array $args): string
    {
        $id = $args['section_id'] ?? '?';
        $updates = [];

        if (isset($args['content'])) {
            $updates[] = 'content';
        }
        if (isset($args['settings'])) {
            $updates[] = 'settings';
        }
        if (isset($args['name'])) {
            $updates[] = "όνομα → '{$args['name']}'";
        }
        if (isset($args['is_visible'])) {
            $updates[] = $args['is_visible'] ? 'εμφάνιση' : 'απόκρυψη';
        }
        if (isset($args['order'])) {
            $updates[] = "order → {$args['order']}";
        }

        $summary = empty($updates) ? 'χωρίς αλλαγές' : implode(', ', $updates);

        return "Θα ενημερώσω το section #{$id} ({$summary})";
    }

    public function execute(array $args): array
    {
        $errors = $this->validate($args);
        if (! empty($errors)) {
            return $this->error('Validation failed: '.implode(', ', $errors));
        }

        $section = PageSection::find($args['section_id']);
        if (! $section) {
            return $this->error("Δεν βρέθηκε PageSection #{$args['section_id']}.");
        }

        // Capture BEFORE state for undo
        $previous = [
            'content' => $section->content,
            'settings' => $section->settings,
            'name' => $section->name,
            'is_visible' => $section->is_visible,
            'order' => $section->order,
        ];

        $updatedFields = [];

        if (array_key_exists('content', $args) && is_array($args['content'])) {
            $existing = is_array($section->content) ? $section->content : [];
            $section->content = array_merge($existing, $args['content']);
            $updatedFields[] = 'content';
        }

        if (array_key_exists('settings', $args) && is_array($args['settings'])) {
            $existing = is_array($section->settings) ? $section->settings : [];
            $section->settings = array_merge($existing, $args['settings']);
            $updatedFields[] = 'settings';
        }

        if (array_key_exists('name', $args)) {
            $section->name = $args['name'];
            $updatedFields[] = 'name';
        }

        if (array_key_exists('is_visible', $args)) {
            $section->is_visible = (bool) $args['is_visible'];
            $updatedFields[] = 'is_visible';
        }

        if (array_key_exists('order', $args)) {
            $section->order = (int) $args['order'];
            $updatedFields[] = 'order';
        }

        if (empty($updatedFields)) {
            return $this->error('Δεν δόθηκε κανένα πεδίο προς ενημέρωση.');
        }

        try {
            $section->save();
        } catch (\Throwable $e) {
            return $this->error('❌ Σφάλμα κατά την αποθήκευση: '.$e->getMessage());
        }

        return $this->success(
            "✅ Ενημέρωσα το section #{$section->id}",
            [
                'id' => $section->id,
                'updated_fields' => $updatedFields,
            ],
            [
                'section_id' => $section->id,
                'previous' => $previous,
            ]
        );
    }
}
