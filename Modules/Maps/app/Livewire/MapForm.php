<?php

namespace Modules\Maps\Livewire;

use Illuminate\Support\Str;
use Livewire\Component;
use Modules\Maps\Models\Map;

class MapForm extends Component
{
    public ?int $mapId = null;

    public string $title = '';

    public string $slug = '';

    public string $description = '';

    public $default_lat = 37.9838;

    public $default_lng = 23.7275;

    public int $default_zoom = 13;

    public int $min_zoom = 3;

    public int $max_zoom = 18;

    public array $markers = [];

    public array $areas = [];

    public bool $show_controls = true;

    public bool $show_search = true;

    public bool $show_legend = false;

    public string $legend_html = '';

    public string $meta_title = '';

    public string $meta_description = '';

    public bool $active = true;

    public function mount(?int $mapId = null): void
    {
        $this->mapId = $mapId;
        if ($this->mapId) {
            $map = Map::findOrFail($this->mapId);
            $this->title = $map->title;
            $this->slug = $map->slug;
            $this->description = $map->description ?? '';
            $this->default_lat = $map->default_lat;
            $this->default_lng = $map->default_lng;
            $this->default_zoom = $map->default_zoom;
            $this->min_zoom = $map->min_zoom;
            $this->max_zoom = $map->max_zoom;
            $this->markers = $map->markers ?? [];
            $this->areas = $map->areas ?? [];
            $this->show_controls = $map->show_controls;
            $this->show_search = $map->show_search;
            $this->show_legend = $map->show_legend;
            $this->legend_html = $map->legend_html ?? '';
            $this->meta_title = $map->meta_title ?? '';
            $this->meta_description = $map->meta_description ?? '';
            $this->active = $map->active;
        }
    }

    public function updatedTitle(): void
    {
        if (! $this->mapId) {
            $this->slug = Str::slug($this->title);
        }
    }

    public function addMarker(): void
    {
        $this->markers[] = [
            'lat' => $this->default_lat,
            'lng' => $this->default_lng,
            'title' => '',
            'description' => '',
            'icon' => 'default',
            'color' => '#3B82F6',
        ];
    }

    public function removeMarker(int $index): void
    {
        unset($this->markers[$index]);
        $this->markers = array_values($this->markers);
    }

    public function save(): void
    {
        $this->validate([
            'title' => 'required|string|max:255',
            'slug' => 'required|string|max:255|unique:maps,slug,'.$this->mapId,
        ]);

        Map::updateOrCreate(['id' => $this->mapId], [
            'title' => $this->title,
            'slug' => $this->slug,
            'description' => $this->description ?: null,
            'default_lat' => $this->default_lat,
            'default_lng' => $this->default_lng,
            'default_zoom' => $this->default_zoom,
            'min_zoom' => $this->min_zoom,
            'max_zoom' => $this->max_zoom,
            'markers' => $this->markers,
            'areas' => $this->areas,
            'show_controls' => $this->show_controls,
            'show_search' => $this->show_search,
            'show_legend' => $this->show_legend,
            'legend_html' => $this->legend_html ?: null,
            'meta_title' => $this->meta_title ?: null,
            'meta_description' => $this->meta_description ?: null,
            'active' => $this->active,
        ]);

        session()->flash('success', 'Map saved successfully.');
    }

    public function render()
    {
        return view('maps::livewire.map-form')
            ->layout('layouts.admin-clean')->title($this->mapId ? 'Edit Map' : 'Create Map');
    }
}
