<?php

namespace Modules\RentalProperties\Livewire;

use Livewire\Component;
use Livewire\WithPagination;
use Modules\RentalProperties\Models\Booking;

class BookingList extends Component
{
    use WithPagination;

    public string $search = '';

    public string $filterStatus = '';

    public function updatedSearch(): void
    {
        $this->resetPage();
    }

    public function updatedFilterStatus(): void
    {
        $this->resetPage();
    }

    public function delete(int $id): void
    {
        $booking = Booking::findOrFail($id);
        $ref = $booking->uuid;
        $booking->delete();
        session()->flash('success', "Booking {$ref} deleted.");
    }

    /**
     * @return array<string, string>
     */
    public function statuses(): array
    {
        return [
            Booking::STATUS_PENDING => 'Pending payment',
            Booking::STATUS_PAID => 'Paid',
            Booking::STATUS_CONFIRMED => 'Confirmed',
            Booking::STATUS_FAILED => 'Failed',
            Booking::STATUS_CANCELLED => 'Cancelled',
            Booking::STATUS_EXPIRED => 'Expired',
        ];
    }

    public function render()
    {
        $bookings = Booking::query()
            ->with('rentalProperty')
            ->when($this->search, fn ($q) => $q->where(fn ($w) => $w
                ->where('guest_name', 'like', "%{$this->search}%")
                ->orWhere('guest_email', 'like', "%{$this->search}%")
                ->orWhere('uuid', 'like', "%{$this->search}%")))
            ->when($this->filterStatus, fn ($q) => $q->where('status', $this->filterStatus))
            ->latest()
            ->paginate(20);

        return view('rentalproperties::livewire.booking-list', [
            'bookings' => $bookings,
            'statuses' => $this->statuses(),
            'counts' => Booking::query()
                ->selectRaw('status, count(*) as c')
                ->groupBy('status')
                ->pluck('c', 'status'),
        ])->layout('layouts.admin-clean')->title('Bookings');
    }
}
