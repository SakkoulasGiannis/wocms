<?php

namespace Modules\RentalProperties\Models;

use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Booking extends Model
{
    use HasUuids;

    public const STATUS_PENDING = 'pending_payment';

    public const STATUS_PAID = 'paid';

    public const STATUS_CONFIRMED = 'confirmed';

    public const STATUS_FAILED = 'failed';

    public const STATUS_CANCELLED = 'cancelled';

    public const STATUS_EXPIRED = 'expired';

    protected $fillable = [
        'uuid', 'rental_property_id', 'hostaway_id',
        'checkin', 'checkout', 'nights', 'adults', 'children',
        'guest_name', 'guest_email', 'guest_phone', 'message',
        'currency', 'accommodation', 'cleaning_fee', 'extra_guest_fee', 'total', 'amount_due',
        'status', 'payment_provider', 'payment_ref', 'payment_status', 'paid_at',
        'hostaway_reservation_id', 'meta',
    ];

    /**
     * The column that should receive a generated UUID.
     *
     * @return array<int, string>
     */
    public function uniqueIds(): array
    {
        return ['uuid'];
    }

    /**
     * Route-model binding resolves bookings by their public uuid, not the id.
     */
    public function getRouteKeyName(): string
    {
        return 'uuid';
    }

    protected function casts(): array
    {
        return [
            'checkin' => 'date',
            'checkout' => 'date',
            'accommodation' => 'decimal:2',
            'cleaning_fee' => 'decimal:2',
            'extra_guest_fee' => 'decimal:2',
            'total' => 'decimal:2',
            'amount_due' => 'decimal:2',
            'paid_at' => 'datetime',
            'meta' => 'array',
        ];
    }

    public function rentalProperty(): BelongsTo
    {
        return $this->belongsTo(RentalProperty::class);
    }

    public function isPaid(): bool
    {
        return in_array($this->status, [self::STATUS_PAID, self::STATUS_CONFIRMED], true);
    }
}
