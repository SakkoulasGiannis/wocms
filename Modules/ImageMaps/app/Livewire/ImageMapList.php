<?php

namespace Modules\ImageMaps\Livewire;

use Livewire\Component;
use Modules\ImageMaps\Models\ImageMap;

class ImageMapList extends Component
{
    public $imageMaps;

    public function mount(): void
    {
        $this->loadMaps();
    }

    public function loadMaps(): void
    {
        $this->imageMaps = ImageMap::orderBy('created_at', 'desc')->get();
    }

    public function toggleActive(int $id): void
    {
        $map = ImageMap::findOrFail($id);
        $map->update(['active' => ! $map->active]);
        $this->loadMaps();
    }

    public function delete(int $id): void
    {
        $map = ImageMap::findOrFail($id);
        $map->delete();
        $this->loadMaps();
        session()->flash('success', "Image Map '{$map->title}' deleted.");
    }

    public function render()
    {
        return view('imagemaps::livewire.image-map-list')
            ->layout('layouts.admin-clean')->title('Image Maps');
    }
}
