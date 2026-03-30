<?php

namespace Modules\ImageMaps\Livewire;

use Illuminate\Support\Str;
use Livewire\Component;
use Livewire\WithFileUploads;
use Modules\ImageMaps\Models\ImageMap;

class ImageMapForm extends Component
{
    use WithFileUploads;

    public ?int $imageMapId = null;

    public string $title = '';

    public string $slug = '';

    public bool $active = true;

    public $imageUpload = null;

    public string $currentImageUrl = '';

    public string $shapesJson = '{"shapes":[],"settings":{"defaultColor":"#1563df","defaultOpacity":0.3,"showTooltips":true}}';

    public function mount(?int $imageMapId = null): void
    {
        $this->imageMapId = $imageMapId;

        if ($this->imageMapId) {
            $map = ImageMap::findOrFail($this->imageMapId);
            $this->title = $map->title;
            $this->slug = $map->slug;
            $this->active = $map->active;
            $this->currentImageUrl = $map->getFirstMediaUrl('image');

            if ($map->items && ! empty($map->items)) {
                $this->shapesJson = json_encode($map->items);
            }
        }
    }

    public function updatedTitle(): void
    {
        if (! $this->imageMapId) {
            $this->slug = Str::slug($this->title);
        }
    }

    public function updatedImageUpload(): void
    {
        $this->validate(['imageUpload' => 'image|max:10240']);

        // Create record if needed
        if (! $this->imageMapId) {
            $map = ImageMap::create([
                'title' => $this->title ?: 'Untitled',
                'slug' => $this->slug ?: Str::slug($this->title ?: 'untitled-'.time()),
                'items' => [],
                'active' => $this->active,
            ]);
            $this->imageMapId = $map->id;
        } else {
            $map = ImageMap::findOrFail($this->imageMapId);
        }

        // Save image
        $map->clearMediaCollection('image');
        $map->addMedia($this->imageUpload->getRealPath())
            ->usingFileName($this->imageUpload->getClientOriginalName())
            ->toMediaCollection('image');

        $this->currentImageUrl = $map->fresh()->getFirstMediaUrl('image');
        $this->imageUpload = null;
    }

    public function save(): void
    {
        $this->validate([
            'title' => 'required|string|max:255',
            'slug' => 'required|string|max:255|unique:image_maps,slug,'.$this->imageMapId,
        ]);

        $items = json_decode($this->shapesJson, true) ?? [];

        $imageMap = ImageMap::updateOrCreate(['id' => $this->imageMapId], [
            'title' => $this->title,
            'slug' => $this->slug,
            'items' => $items,
            'active' => $this->active,
        ]);

        $this->imageMapId = $imageMap->id;

        session()->flash('success', 'Image Map saved successfully.');
    }

    public function render()
    {
        return view('imagemaps::livewire.image-map-form')
            ->layout('layouts.admin-clean')
            ->title($this->imageMapId ? 'Edit Image Map' : 'Create Image Map');
    }
}
