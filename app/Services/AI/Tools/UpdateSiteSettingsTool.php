<?php

namespace App\Services\AI\Tools;

use App\Models\Setting;
use App\Services\ThemeManager;

class UpdateSiteSettingsTool extends BaseTool
{
    /**
     * Whitelist of settings keys that this tool is allowed to update.
     *
     * @var list<string>
     */
    protected const ALLOWED_KEYS = [
        'site_name',
        'site_email',
        'site_phone',
        'site_address',
        'site_opentime',
        'site_latitude',
        'site_longitude',
        'site_description',
        'social_facebook',
        'social_instagram',
        'social_twitter',
        'social_linkedin',
        'social_youtube',
        'active_theme',
    ];

    public function name(): string
    {
        return 'update_site_settings';
    }

    public function label(): string
    {
        return 'Update Site Settings';
    }

    public function description(): string
    {
        return 'Batch-update site-wide settings (site name, contact info, social links, active theme). Only a fixed whitelist of keys is accepted. Use this when the user wants to change global site settings.';
    }

    public function schema(): array
    {
        return [
            '$schema' => 'https://json-schema.org/draft/2020-12/schema',
            'type' => 'object',
            'properties' => [
                'settings' => [
                    'type' => 'object',
                    'description' => 'Map of setting key → new value. Allowed keys: '.implode(', ', self::ALLOWED_KEYS).'.',
                    'properties' => $this->buildSettingsProperties(),
                    'additionalProperties' => false,
                    'minProperties' => 1,
                ],
            ],
            'required' => ['settings'],
            'additionalProperties' => false,
        ];
    }

    protected function validationRules(): array
    {
        return [
            'settings' => 'required|array|min:1',
        ];
    }

    public function previewMessage(array $args): string
    {
        $settings = $args['settings'] ?? [];
        if (! is_array($settings) || empty($settings)) {
            return 'Θα ενημερώσω site settings (δεν δόθηκαν τιμές)';
        }

        $keys = array_keys($settings);
        $keysList = implode(', ', $keys);

        return 'Θα ενημερώσω τα settings: '.$keysList;
    }

    public function execute(array $args): array
    {
        $errors = $this->validate($args);
        if (! empty($errors)) {
            return $this->error('Validation failed: '.implode(', ', $errors));
        }

        $settings = $args['settings'];
        if (! is_array($settings) || empty($settings)) {
            return $this->error('Δεν δόθηκαν settings προς ενημέρωση.');
        }

        // Validate every key is in the whitelist
        $unknownKeys = array_diff(array_keys($settings), self::ALLOWED_KEYS);
        if (! empty($unknownKeys)) {
            return $this->error('Μη επιτρεπτά settings keys: '.implode(', ', $unknownKeys));
        }

        // Capture BEFORE values
        $previous = [];
        foreach ($settings as $key => $value) {
            $previous[$key] = Setting::get($key);
        }

        $updated = [];
        try {
            foreach ($settings as $key => $value) {
                if ($key === 'active_theme') {
                    /** @var ThemeManager $themeManager */
                    $themeManager = app(ThemeManager::class);
                    $themeManager->setActiveTheme((string) $value);
                } else {
                    Setting::set($key, $value);
                }

                $updated[$key] = $value;
            }
        } catch (\Throwable $e) {
            return $this->error('❌ Σφάλμα κατά την αποθήκευση: '.$e->getMessage());
        }

        return $this->success(
            '✅ Ενημέρωσα '.count($updated).' setting(s)',
            [
                'updated' => $updated,
            ],
            [
                'previous' => $previous,
            ]
        );
    }

    /**
     * Build the per-key JSON schema properties for the nested settings object.
     *
     * @return array<string, array{type: string, description?: string}>
     */
    protected function buildSettingsProperties(): array
    {
        $props = [];
        foreach (self::ALLOWED_KEYS as $key) {
            $props[$key] = [
                'type' => 'string',
                'description' => $this->describeKey($key),
            ];
        }

        return $props;
    }

    /**
     * Human-readable description of each allowed setting key (for the LLM).
     */
    protected function describeKey(string $key): string
    {
        return match ($key) {
            'site_name' => 'Public site name.',
            'site_email' => 'Public contact email.',
            'site_phone' => 'Public phone number.',
            'site_address' => 'Physical address.',
            'site_opentime' => 'Opening hours text.',
            'site_latitude' => 'Latitude for map display.',
            'site_longitude' => 'Longitude for map display.',
            'site_description' => 'Short site description / tagline.',
            'social_facebook' => 'Facebook URL.',
            'social_instagram' => 'Instagram URL.',
            'social_twitter' => 'Twitter/X URL.',
            'social_linkedin' => 'LinkedIn URL.',
            'social_youtube' => 'YouTube URL.',
            'active_theme' => 'Active theme slug (validated via ThemeManager).',
            default => '',
        };
    }
}
