<?php

namespace App\Services\AI\Tools;

use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use Modules\Slider\Models\Slide;
use Modules\Slider\Models\Slider;

class CreateSliderWithSlidesTool extends BaseTool
{
    public function name(): string
    {
        return 'create_slider_with_slides';
    }

    public function label(): string
    {
        return 'Create Slider';
    }

    public function description(): string
    {
        return 'Create a new Slider together with one or more Slides in a single transaction. Image URLs (if provided) are downloaded and attached via Spatie MediaLibrary. Use this when the user wants to build a slider/carousel with multiple slides at once.';
    }

    public function schema(): array
    {
        return [
            '$schema' => 'https://json-schema.org/draft/2020-12/schema',
            'type' => 'object',
            'properties' => [
                'name' => [
                    'type' => 'string',
                    'minLength' => 1,
                    'description' => 'Slider name.',
                ],
                'description' => [
                    'type' => 'string',
                    'description' => 'Optional slider description.',
                ],
                'slides' => [
                    'type' => 'array',
                    'minItems' => 1,
                    'description' => 'Array of slides to create.',
                    'items' => [
                        'type' => 'object',
                        'properties' => [
                            'title' => ['type' => 'string'],
                            'subtitle' => ['type' => 'string'],
                            'description' => ['type' => 'string'],
                            'button_text' => ['type' => 'string'],
                            'link' => ['type' => 'string'],
                            'image_url' => [
                                'type' => 'string',
                                'description' => 'A reachable HTTP(S) image URL (not a file upload).',
                            ],
                        ],
                        'additionalProperties' => false,
                    ],
                ],
                'settings' => [
                    'type' => 'object',
                    'description' => 'Optional slider display settings (e.g. autoplay, interval, dots, arrows).',
                    'additionalProperties' => true,
                ],
            ],
            'required' => ['name', 'slides'],
            'additionalProperties' => false,
        ];
    }

    protected function validationRules(): array
    {
        return [
            'name' => 'required|string|min:1',
            'description' => 'sometimes|string',
            'slides' => 'required|array|min:1',
            'slides.*.title' => 'sometimes|string',
            'slides.*.subtitle' => 'sometimes|string',
            'slides.*.description' => 'sometimes|string',
            'slides.*.button_text' => 'sometimes|string',
            'slides.*.link' => 'sometimes|string',
            'slides.*.image_url' => 'sometimes|string',
            'settings' => 'sometimes|array',
        ];
    }

    public function previewMessage(array $args): string
    {
        $name = $args['name'] ?? '(χωρίς όνομα)';
        $count = isset($args['slides']) && is_array($args['slides']) ? count($args['slides']) : 0;

        return "Θα δημιουργήσω slider '{$name}' με {$count} slide(s)";
    }

    public function execute(array $args): array
    {
        $errors = $this->validate($args);
        if (! empty($errors)) {
            return $this->error('Validation failed: '.implode(', ', $errors));
        }

        $name = $args['name'];
        $description = $args['description'] ?? null;
        $slides = $args['slides'];
        $settings = $args['settings'] ?? [];

        $slug = $this->generateUniqueSlug(Str::slug($name) ?: 'slider');

        try {
            $result = DB::transaction(function () use ($name, $slug, $description, $settings, $slides) {
                $slider = Slider::create([
                    'name' => $name,
                    'slug' => $slug,
                    'description' => $description,
                    'is_active' => true,
                    'settings' => $settings,
                ]);

                $created = [];
                foreach ($slides as $index => $slideData) {
                    $slide = Slide::create([
                        'slider_id' => $slider->id,
                        'title' => $slideData['title'] ?? null,
                        'description' => $slideData['description'] ?? null,
                        'link' => $slideData['link'] ?? null,
                        'button_text' => $slideData['button_text'] ?? null,
                        'order' => $index,
                        'is_active' => true,
                    ]);

                    // Attach image from URL if provided (non-fatal on failure)
                    if (! empty($slideData['image_url'])) {
                        try {
                            $slide->addMediaFromUrl($slideData['image_url'])
                                ->toMediaCollection('image');
                        } catch (\Throwable $imageError) {
                            \Log::warning('Slide image download failed', [
                                'slide_id' => $slide->id,
                                'image_url' => $slideData['image_url'],
                                'error' => $imageError->getMessage(),
                            ]);
                        }
                    }

                    $created[] = $slide;
                }

                return ['slider' => $slider, 'slides' => $created];
            });
        } catch (\Throwable $e) {
            return $this->error('❌ Σφάλμα κατά τη δημιουργία slider: '.$e->getMessage());
        }

        /** @var Slider $slider */
        $slider = $result['slider'];
        $slidesCount = count($result['slides']);

        $editUrl = $this->buildEditUrl($slider->id);

        return $this->success(
            "✅ Δημιούργησα slider '{$slider->name}' με {$slidesCount} slide(s)",
            [
                'id' => $slider->id,
                'name' => $slider->name,
                'slides_count' => $slidesCount,
                'edit_url' => $editUrl,
            ],
            [
                'slider_id' => $slider->id,
            ]
        );
    }

    /**
     * Ensure slider slug is unique.
     */
    protected function generateUniqueSlug(string $baseSlug): string
    {
        $slug = $baseSlug;
        $counter = 2;

        while (Slider::where('slug', $slug)->exists()) {
            $slug = $baseSlug.'-'.$counter;
            $counter++;
        }

        return $slug;
    }

    /**
     * Build the admin slider edit URL.
     */
    protected function buildEditUrl(int $sliderId): string
    {
        try {
            return route('admin.slider.edit', ['sliderId' => $sliderId]);
        } catch (\Throwable $e) {
            return url("/admin/sliders/{$sliderId}/edit");
        }
    }
}
