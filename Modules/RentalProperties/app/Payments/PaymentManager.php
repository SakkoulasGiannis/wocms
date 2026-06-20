<?php

namespace Modules\RentalProperties\Payments;

use App\Models\Setting;
use Modules\RentalProperties\Payments\Contracts\PaymentProvider;
use Modules\RentalProperties\Payments\Providers\ManualPaymentProvider;
use Modules\RentalProperties\Payments\Providers\PayPalProvider;
use Modules\RentalProperties\Payments\Providers\StripeProvider;
use Modules\RentalProperties\Payments\Providers\VivaProvider;

/**
 * Resolves the active payment provider and the charge policy (full vs deposit).
 * Both are configurable via config/payments.php with admin Setting overrides.
 */
class PaymentManager
{
    /** @var array<string, class-string<PaymentProvider>> */
    protected array $providers = [
        'manual' => ManualPaymentProvider::class,
        'viva' => VivaProvider::class,
        'paypal' => PayPalProvider::class,
        'stripe' => StripeProvider::class,
    ];

    public function provider(?string $key = null): PaymentProvider
    {
        $key = $key ?: $this->activeKey();
        $class = $this->providers[$key] ?? ManualPaymentProvider::class;

        return app($class);
    }

    public function activeKey(): string
    {
        return (string) (Setting::get('payment_provider', '') ?: config('payments.default', 'manual'));
    }

    public function chargeMode(): string
    {
        return (string) (Setting::get('payment_charge_mode', '') ?: config('payments.charge_mode', 'full'));
    }

    public function depositPercent(): float
    {
        $setting = Setting::get('payment_deposit_percent', null);
        $value = $setting !== null && $setting !== '' ? (float) $setting : (float) config('payments.deposit_percent', 30);

        return max(0, min(100, $value));
    }

    /**
     * Amount to charge at checkout for a given total, per the charge policy.
     */
    public function amountDue(float $total): float
    {
        if ($this->chargeMode() === 'deposit') {
            return round($total * $this->depositPercent() / 100, 2);
        }

        return round($total, 2);
    }
}
