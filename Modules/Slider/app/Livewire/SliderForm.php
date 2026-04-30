<?php

namespace Modules\Slider\Livewire;

use Illuminate\Support\Str;
use Livewire\Component;
use Livewire\WithFileUploads;
use Modules\Slider\Models\Slide;
use Modules\Slider\Models\Slider;

class SliderForm extends Component
{
    use WithFileUploads;

    public ?int $sliderId = null;

    public string $name = '';

    public string $slug = '';

    public string $description = '';

    public bool $isActive = true;

    // Display Settings
    public string $height = 'h-screen';

    public string $imageFit = 'cover';

    public string $overlayOpacity = '0.5';

    public string $overlayColor = 'black';

    public bool $autoplay = true;

    public int $autoplayInterval = 5000;

    public bool $showArrows = true;

    public bool $showDots = true;

    public string $transitionEffect = 'fade';

    public string $textPosition = 'center';

    public string $contentMaxWidth = 'max-w-4xl';

    // Slides
    public array $slides = [];

    public array $newImages = [];

    public array $newVideos = [];

    protected function rules(): array
    {
        return [
            'name' => 'required|string|max:255',
            'slug' => 'required|string|max:255|unique:sliders,slug,'.$this->sliderId,
            'description' => 'nullable|string',
            'isActive' => 'boolean',
            'height' => 'required|string',
            'imageFit' => 'required|in:cover,contain,fill',
            'overlayOpacity' => 'required|numeric|between:0,1',
            'overlayColor' => 'required|in:black,brand,white',
            'autoplay' => 'boolean',
            'autoplayInterval' => 'required|integer|min:1000|max:30000',
            'showArrows' => 'boolean',
            'showDots' => 'boolean',
            'transitionEffect' => 'required|in:fade,slide',
            'textPosition' => 'required|in:center,left,right',
            'contentMaxWidth' => 'required|string',
            'slides.*.title' => 'nullable|string|max:255',
            'slides.*.description' => 'nullable|string',
            'slides.*.link' => 'nullable|string|max:255',
            'slides.*.button_text' => 'nullable|string|max:255',
            'slides.*.media_type' => 'nullable|in:image,video,youtube',
            'slides.*.video_url' => 'nullable|string|max:500',
            'slides.*.is_active' => 'boolean',
            'newImages.*' => 'nullable|image|max:10240',
            'newVideos.*' => 'nullable|file|mimes:mp4,webm,ogg|max:102400',
        ];
    }

    public function mount(?int $sliderId = null): void
    {
        $this->sliderId = $sliderId;

        if ($this->sliderId) {
            $this->loadSlider();
        }
    }

    public function loadSlider(): void
    {
        $slider = Slider::with(['slides' => function ($query) {
            $query->orderBy('order');
        }])->findOrFail($this->sliderId);

        $this->name = $slider->name;
        $this->slug = $slider->slug;
        $this->description = $slider->description ?? '';
        $this->isActive = $slider->is_active;

        $s = $slider->settings ?? [];
        $this->height = $s['height'] ?? 'h-screen';
        $this->imageFit = $s['image_fit'] ?? 'cover';
        $this->overlayOpacity = $s['overlay_opacity'] ?? '0.5';
        $this->overlayColor = $s['overlay_color'] ?? 'black';
        $this->autoplay = (bool) ($s['autoplay'] ?? true);
        $this->autoplayInterval = (int) ($s['autoplay_interval'] ?? 5000);
        $this->showArrows = (bool) ($s['show_arrows'] ?? true);
        $this->showDots = (bool) ($s['show_dots'] ?? true);
        $this->transitionEffect = $s['transition_effect'] ?? 'fade';
        $this->textPosition = $s['text_position'] ?? 'center';
        $this->contentMaxWidth = $s['content_max_width'] ?? 'max-w-4xl';

        $this->slides = $slider->slides->map(function ($slide) {
            return [
                'id' => $slide->id,
                'title' => $slide->title ?? '',
                'description' => $slide->description ?? '',
                'link' => $slide->link ?? '',
                'button_text' => $slide->button_text ?? '',
                'media_type' => $slide->media_type ?? 'image',
                'video_url' => $slide->video_url ?? '',
                'is_active' => $slide->is_active,
                'order' => $slide->order,
                'image_url' => $slide->getFirstMediaUrl('image', 'thumb') ?: $slide->getFirstMediaUrl('image'),
                'video_file_url' => $slide->getFirstMediaUrl('video'),
            ];
        })->toArray();
    }

    public function updatedName(): void
    {
        if (! $this->sliderId) {
            $this->slug = Str::slug($this->name);
        }
    }

    public function addSlide(): void
    {
        $this->slides[] = [
            'id' => null,
            'title' => '',
            'description' => '',
            'link' => '',
            'button_text' => '',
            'media_type' => 'image',
            'video_url' => '',
            'is_active' => true,
            'order' => count($this->slides),
            'image_url' => '',
            'video_file_url' => '',
        ];
    }

    public function removeSlide(int $index): void
    {
        $slide = $this->slides[$index] ?? null;

        if ($slide && isset($slide['id']) && $slide['id']) {
            $slideModel = Slide::find($slide['id']);
            if ($slideModel) {
                $slideModel->clearMediaCollection('image');
                $slideModel->delete();
            }
        }

        unset($this->slides[$index]);
        $this->slides = array_values($this->slides);

        if (isset($this->newImages[$index])) {
            unset($this->newImages[$index]);
            $this->newImages = array_values($this->newImages);
        }
    }

    /**
     * Auto-save the image immediately when uploaded to a slide.
     */
    public function updatedNewImages($value, $key): void
    {
        // $key is the index like "0" or "1" etc.
        $index = (int) $key;
        $file = $this->newImages[$index] ?? null;

        if (! $file || ! isset($this->slides[$index])) {
            return;
        }

        // Validate single file
        $this->validate([
            "newImages.{$index}" => 'image|max:10240',
        ]);

        $slideData = $this->slides[$index];

        // If slide has no ID yet, we need slider to exist first — skip auto-save
        if (empty($slideData['id']) || empty($this->sliderId)) {
            return;
        }

        $slide = Slide::find($slideData['id']);
        if (! $slide) {
            return;
        }

        // Replace image
        $slide->clearMediaCollection('image');
        $slide->addMedia($file->getRealPath())
            ->usingFileName($file->getClientOriginalName())
            ->toMediaCollection('image');

        // Update in-memory URL for preview
        $slide->refresh();
        $this->slides[$index]['image_url'] = $slide->getFirstMediaUrl('image', 'thumb') ?: $slide->getFirstMediaUrl('image');

        // Clear the newImages entry since it's saved
        unset($this->newImages[$index]);
        $this->newImages = array_values($this->newImages);

        session()->flash('success', 'Image uploaded for Slide #'.($index + 1));
        $this->dispatch('notify', message: 'Image saved!', type: 'success');
    }

    public function updateSlideOrder(array $orderedIds): void
    {
        $newOrder = [];
        foreach ($orderedIds as $position => $index) {
            if (isset($this->slides[$index])) {
                $this->slides[$index]['order'] = $position;
                $newOrder[] = $this->slides[$index];
            }
        }
        $this->slides = $newOrder;
    }

    public function save(): void
    {
        $this->validate();

        $slider = Slider::updateOrCreate(
            ['id' => $this->sliderId],
            [
                'name' => $this->name,
                'slug' => $this->slug,
                'description' => $this->description ?: null,
                'is_active' => $this->isActive,
                'settings' => [
                    'height' => $this->height,
                    'image_fit' => $this->imageFit,
                    'overlay_opacity' => $this->overlayOpacity,
                    'overlay_color' => $this->overlayColor,
                    'autoplay' => $this->autoplay,
                    'autoplay_interval' => $this->autoplayInterval,
                    'show_arrows' => $this->showArrows,
                    'show_dots' => $this->showDots,
                    'transition_effect' => $this->transitionEffect,
                    'text_position' => $this->textPosition,
                    'content_max_width' => $this->contentMaxWidth,
                ],
            ]
        );

        $this->sliderId = $slider->id;

        // Save slides
        $existingSlideIds = [];
        foreach ($this->slides as $index => $slideData) {
            $slide = Slide::updateOrCreate(
                ['id' => $slideData['id'] ?? null],
                [
                    'slider_id' => $slider->id,
                    'title' => $slideData['title'] ?: null,
                    'description' => $slideData['description'] ?: null,
                    'link' => $slideData['link'] ?: null,
                    'button_text' => $slideData['button_text'] ?: null,
                    'media_type' => $slideData['media_type'] ?? 'image',
                    'video_url' => $slideData['video_url'] ?: null,
                    'is_active' => $slideData['is_active'] ?? true,
                    'order' => $index,
                ]
            );

            // Handle image upload (for image type or as video poster/thumbnail)
            if (isset($this->newImages[$index]) && $this->newImages[$index]) {
                $slide->clearMediaCollection('image');
                $slide->addMedia($this->newImages[$index]->getRealPath())
                    ->usingFileName($this->newImages[$index]->getClientOriginalName())
                    ->toMediaCollection('image');
            }

            // Handle video file upload
            if (isset($this->newVideos[$index]) && $this->newVideos[$index]) {
                $slide->clearMediaCollection('video');
                $slide->addMedia($this->newVideos[$index]->getRealPath())
                    ->usingFileName($this->newVideos[$index]->getClientOriginalName())
                    ->toMediaCollection('video');
            }

            $existingSlideIds[] = $slide->id;
        }

        // Remove deleted slides
        $slider->slides()->whereNotIn('id', $existingSlideIds)->each(function ($slide) {
            $slide->clearMediaCollection('image');
            $slide->delete();
        });

        $this->newImages = [];
        $this->newVideos = [];
        $this->loadSlider();

        session()->flash('success', 'Slider saved successfully.');
    }

    public function render()
    {
        return view('slider::livewire.slider-form')
            ->layout('layouts.admin-clean')
            ->title($this->sliderId ? 'Edit Slider' : 'Create Slider');
    }
}
