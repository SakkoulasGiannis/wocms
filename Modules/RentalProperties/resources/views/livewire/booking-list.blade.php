<div class="px-4 sm:px-0">
    <div class="mb-6 flex items-center justify-between">
        <div>
            <h1 class="text-2xl font-bold text-gray-900">Bookings</h1>
            <p class="mt-1 text-sm text-gray-600">Booking requests &amp; reservations from the website</p>
        </div>
        <a href="{{ route('admin.rentals.index') }}" class="px-4 py-2 bg-gray-100 hover:bg-gray-200 text-gray-700 font-medium rounded-lg transition"><i class="fa fa-arrow-left mr-2"></i>Rental Properties</a>
    </div>

    @if(session('success'))
    <div class="mb-6 bg-green-50 border border-green-200 text-green-800 px-4 py-3 rounded-lg">{{ session('success') }}</div>
    @endif

    <div class="bg-white rounded-lg shadow mb-6 p-4">
        <div class="grid grid-cols-1 md:grid-cols-3 gap-4">
            <input type="text" wire:model.live.debounce.300ms="search" placeholder="Search guest / email / reference..." class="rounded-lg border-gray-300 shadow-sm focus:border-blue-500 focus:ring-blue-500 md:col-span-2">
            <select wire:model.live="filterStatus" class="rounded-lg border-gray-300 shadow-sm focus:border-blue-500 focus:ring-blue-500">
                <option value="">All statuses</option>
                @foreach($statuses as $k => $v)<option value="{{ $k }}">{{ $v }} ({{ $counts[$k] ?? 0 }})</option>@endforeach
            </select>
        </div>
    </div>

    @php
        $badge = [
            'pending_payment' => 'bg-amber-100 text-amber-800',
            'paid' => 'bg-blue-100 text-blue-800',
            'confirmed' => 'bg-green-100 text-green-800',
            'failed' => 'bg-red-100 text-red-800',
            'cancelled' => 'bg-gray-100 text-gray-700',
            'expired' => 'bg-gray-100 text-gray-500',
        ];
    @endphp

    @if($bookings->count())
    <div class="bg-white rounded-lg shadow overflow-hidden">
        <table class="min-w-full divide-y divide-gray-200">
            <thead class="bg-gray-50"><tr>
                <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Property / Guest</th>
                <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Dates</th>
                <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Total</th>
                <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Status</th>
                <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Hostaway</th>
                <th class="px-6 py-3 text-right text-xs font-medium text-gray-500 uppercase w-28">Actions</th>
            </tr></thead>
            <tbody class="divide-y divide-gray-200">
                @foreach($bookings as $b)
                @php $sym = ['EUR' => '€', 'USD' => '$', 'GBP' => '£'][$b->currency] ?? ($b->currency . ' '); @endphp
                <tr wire:key="bk-{{ $b->id }}" class="hover:bg-gray-50 align-top">
                    <td class="px-6 py-4">
                        <div class="font-medium text-gray-900">{{ $b->rentalProperty?->title ?? ('Listing ' . $b->hostaway_id) }}</div>
                        <div class="text-sm text-gray-700 mt-1">{{ $b->guest_name }}</div>
                        <div class="text-xs text-gray-500">{{ $b->guest_email }}@if($b->guest_phone) · {{ $b->guest_phone }}@endif</div>
                        <div class="text-[11px] text-gray-400 mt-1">{{ $b->created_at?->format('d M Y H:i') }} · {{ $b->uuid }}</div>
                    </td>
                    <td class="px-6 py-4 text-sm text-gray-700">
                        {{ $b->checkin->format('d M Y') }} → {{ $b->checkout->format('d M Y') }}
                        <div class="text-xs text-gray-500">{{ $b->nights }} nights · {{ $b->adults }}A{{ $b->children ? ' ' . $b->children . 'C' : '' }}</div>
                    </td>
                    <td class="px-6 py-4">
                        <div class="font-medium text-gray-900">{{ $sym }}{{ number_format($b->total, 2) }}</div>
                        @if((float) $b->amount_due > 0 && (float) $b->amount_due < (float) $b->total)
                            <div class="text-xs text-gray-500">Due: {{ $sym }}{{ number_format($b->amount_due, 2) }}</div>
                        @endif
                        <div class="text-[11px] text-gray-400">{{ $b->payment_provider }}</div>
                    </td>
                    <td class="px-6 py-4"><span class="inline-flex px-2 py-0.5 text-xs font-medium rounded-full {{ $badge[$b->status] ?? 'bg-gray-100 text-gray-700' }}">{{ $statuses[$b->status] ?? $b->status }}</span></td>
                    <td class="px-6 py-4 text-sm text-gray-500">
                        @if($b->hostaway_reservation_id)
                            <span class="inline-flex items-center text-green-700"><i class="fa fa-check-circle mr-1"></i>#{{ $b->hostaway_reservation_id }}</span>
                        @else
                            <span class="text-gray-400">—</span>
                        @endif
                    </td>
                    <td class="px-6 py-4 text-right text-sm whitespace-nowrap">
                        <a href="{{ route('booking.show', $b->uuid) }}" target="_blank" title="Open guest page" class="text-blue-600 hover:text-blue-900 mr-3"><i class="fa fa-external-link-alt"></i></a>
                        <button wire:click="delete({{ $b->id }})" wire:confirm="Delete this booking record?" class="text-red-600 hover:text-red-900"><i class="fa fa-trash"></i></button>
                    </td>
                </tr>
                @endforeach
            </tbody>
        </table>
    </div>
    <div class="mt-4">{{ $bookings->links() }}</div>
    @else
    <div class="text-center py-12"><i class="fa fa-calendar-check text-gray-300 text-6xl mb-4"></i><p class="text-gray-500 text-lg">No bookings yet</p></div>
    @endif
</div>
