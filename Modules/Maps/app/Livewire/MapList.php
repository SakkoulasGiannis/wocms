<?php

namespace Modules\Maps\Livewire;

use Livewire\Component;
use Modules\Maps\Models\Map;

class MapList extends Component
{
    public $maps;

    public function mount(): void
    {
        $this->loadMaps();
    }

    public function loadMaps(): void
    {
        $this->maps = Map::orderBy('created_at', 'desc')->get();
    }

    public function toggleActive(int $id): void
    {
        $map = Map::findOrFail($id);
        $map->update(['active' => ! $map->active]);
        $this->loadMaps();
    }

    public function delete(int $id): void
    {
        $map = Map::findOrFail($id);
        $map->delete();
        $this->loadMaps();
        session()->flash('success', "Map '{$map->title}' deleted.");
    }

    public function render()
    {
        return view('maps::livewire.map-list')
            ->layout('layouts.admin-clean')->title('Maps');
    }
}
