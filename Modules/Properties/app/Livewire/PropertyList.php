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
        // The `order` column is listed in the Property fillable but never
        // existed as a DB column on production — guard the orderBy with a
        // Schema check so this works whether the column is added later or
        // not. Falls back to most-recent-first.
        $hasOrderCol = \Schema::hasColumn('properties', 'order');

        $properties = Property::query()
            ->when($this->search, fn ($q) => $q->where('title', 'like', "%{$this->search}%")->orWhere('city', 'like', "%{$this->search}%"))
            ->when($this->filterType, fn ($q) => $q->where('property_type', $this->filterType))
            ->when($this->filterStatus, fn ($q) => $q->where('status', $this->filterStatus))
            ->when($hasOrderCol, fn ($q) => $q->orderBy('order'))
            ->orderBy('created_at', 'desc')
            ->paginate(20);

        return view('properties::livewire.property-list', [
            'properties' => $properties,
            'propertyTypes' => Property::getPropertyTypes(),
            'statuses' => Property::getStatuses(),
        ])->layout('layouts.admin-clean')->title('Properties');
    }
}
