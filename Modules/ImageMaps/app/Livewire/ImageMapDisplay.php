<?php

namespace Modules\ImageMaps\Livewire;

use Livewire\Component;
use Modules\ImageMaps\Models\ImageMap;

class ImageMapDisplay extends Component
{
    public ?ImageMap $imageMap = null;

    public function mount(string $slug): void
    {
        $this->imageMap = ImageMap::where('slug', $slug)
            ->where('active', true)
            ->first();
    }

    public function render()
    {
        return view('imagemaps::frontend.image-map');
    }
}
