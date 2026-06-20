<?php

namespace Modules\RentalProperties\Payments;

/**
 * Outcome of a payment-provider operation (start checkout / handle callback).
 */
class PaymentResult
{
    /**
     * @param  array<string, mixed>  $raw
     */
    public function __construct(
        public bool $ok,
        public string $status,            // redirect | pending | paid | failed
        public ?string $redirectUrl = null,
        public ?string $reference = null,
        public ?string $message = null,
        public array $raw = [],
    ) {}

    public static function redirect(string $url, ?string $reference = null): self
    {
        return new self(true, 'redirect', $url, $reference);
    }

    public static function pending(?string $message = null, ?string $reference = null): self
    {
        return new self(true, 'pending', null, $reference, $message);
    }

    /**
     * @param  array<string, mixed>  $raw
     */
    public static function paid(?string $reference = null, array $raw = []): self
    {
        return new self(true, 'paid', null, $reference, null, $raw);
    }

    public static function failed(string $message): self
    {
        return new self(false, 'failed', null, null, $message);
    }
}
