<?php

namespace Modules\Slider\Livewire;

use Livewire\Component;
use Modules\Slider\Models\Slider;

class SliderList extends Component
{
    public $sliders;

    public function mount(): void
    {
        $this->loadSliders();
    }

    public function loadSliders(): void
    {
        $this->sliders = Slider::withCount('slides')
            ->orderBy('created_at', 'desc')
            ->get();
    }

    public function toggleActive(int $id): void
    {
        $slider = Slider::findOrFail($id);
        $slider->update(['is_active' => ! $slider->is_active]);
        $this->loadSliders();
    }

    public function delete(int $id): void
    {
        $slider = Slider::findOrFail($id);
        $slider->slides()->each(function ($slide) {
            $slide->clearMediaCollection('image');
            $slide->delete();
        });
        $slider->delete();
        $this->loadSliders();
        session()->flash('success', "Slider '{$slider->name}' deleted successfully.");
    }

    public function render()
    {
        return view('slider::livewire.slider-list')
            ->layout('layouts.admin-clean')
            ->title('Sliders');
    }
}
