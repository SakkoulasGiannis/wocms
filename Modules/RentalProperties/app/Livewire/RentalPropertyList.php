<?php

namespace Modules\RentalProperties\Livewire;

use Livewire\Component;
use Livewire\WithPagination;
use Modules\RentalProperties\Models\RentalProperty;

class RentalPropertyList extends Component
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
        $p = RentalProperty::findOrFail($id);
        $p->update(['active' => ! $p->active]);
    }

    public function delete(int $id): void
    {
        $p = RentalProperty::findOrFail($id);
        $p->clearMediaCollection('featured_image');
        $p->clearMediaCollection('gallery');
        $p->delete();
        session()->flash('success', "Rental property '{$p->title}' deleted.");
    }

    public function render()
    {
        $properties = RentalProperty::query()
            ->when($this->search, fn ($q) => $q->where('title', 'like', "%{$this->search}%")->orWhere('city', 'like', "%{$this->search}%"))
            ->when($this->filterType, fn ($q) => $q->where('property_type', $this->filterType))
            ->when($this->filterStatus, fn ($q) => $q->where('status', $this->filterStatus))
            ->orderBy('created_at', 'desc')
            ->paginate(20);

        return view('rentalproperties::livewire.rental-property-list', [
            'properties' => $properties,
            'propertyTypes' => RentalProperty::getPropertyTypes(),
            'statuses' => RentalProperty::getStatuses(),
        ])->layout('layouts.admin-clean')->title('Rental Properties');
    }
}
