<?php

namespace Modules\Properties\Livewire;

use Livewire\Component;
use Livewire\WithPagination;
use Modules\Properties\Models\Property;

class PropertyList extends Component
{
    use WithPagination;

    public string $search = '';

    public string $filterType = '';

    public string $filterStatus = '';

    public function updatedSearch(): void
    {
        $this->resetPage();
    }

    public function updatedFilterType(): void
    {
        $this->resetPage();
    }

    public function updatedFilterStatus(): void
    {
        $this->resetPage();
    }

    public function toggleActive(int $id): void
    {
        $property = Property::findOrFail($id);
        $property->update(['active' => ! $property->active]);
    }

    public function toggleFeatured(int $id): void
    {
        $property = Property::findOrFail($id);
        $property->update(['featured' => ! $property->featured]);
    }

    public function delete(int $id): void
    {
        $property = Property::findOrFail($id);
        $property->clearMediaCollection('featured_image');
        $property->clearMediaCollection('gallery');
        $property->delete();
        session()->flash('success', "Property '{$property->title}' deleted.");
    }

    public function render()
    {
        $properties = Property::query()
            ->when($this->search, fn ($q) => $q->where('title', 'like', "%{$this->search}%")->orWhere('city', 'like', "%{$this->search}%"))
            ->when($this->filterType, fn ($q) => $q->where('property_type', $this->filterType))
            ->when($this->filterStatus, fn ($q) => $q->where('status', $this->filterStatus))
            ->orderBy('order')->orderBy('created_at', 'desc')
            ->paginate(20);

        return view('properties::livewire.property-list', [
            'properties' => $properties,
            'propertyTypes' => Property::getPropertyTypes(),
            'statuses' => Property::getStatuses(),
        ])->layout('layouts.admin-clean')->title('Properties');
    }
}
