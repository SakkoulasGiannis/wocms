<?php

return [
    /*
    |--------------------------------------------------------------------------
    | Active payment provider
    |--------------------------------------------------------------------------
    | The provider key used to start checkouts. 'manual' takes no online
    | payment (booking stays pending until handled by staff). Real providers
    | (viva, paypal, stripe) are scaffolded but not yet implemented.
    | Can be overridden from the admin via Setting('payment_provider').
    */
    'default' => env('PAYMENTS_PROVIDER', 'manual'),

    /*
    |--------------------------------------------------------------------------
    | What to charge at checkout
    |--------------------------------------------------------------------------
    | 'full'    → charge the whole total.
    | 'deposit' → charge `deposit_percent` of the total now, rest later.
    | Overridable from the admin via Setting('payment_charge_mode') /
    | Setting('payment_deposit_percent').
    */
    'charge_mode' => env('PAYMENTS_CHARGE_MODE', 'full'),

    'deposit_percent' => (float) env('PAYMENTS_DEPOSIT_PERCENT', 30),

    /*
    |--------------------------------------------------------------------------
    | Provider credentials (placeholders — fill when a provider is implemented)
    |--------------------------------------------------------------------------
    */
    'providers' => [
        'manual' => [
            'label' => 'Manual / pay later',
        ],
        'viva' => [
            'label' => 'Viva.com',
            'merchant_id' => env('VIVA_MERCHANT_ID', ''),
            'api_key' => env('VIVA_API_KEY', ''),
            'client_id' => env('VIVA_CLIENT_ID', ''),
            'client_secret' => env('VIVA_CLIENT_SECRET', ''),
            'source_code' => env('VIVA_SOURCE_CODE', ''),
            'sandbox' => (bool) env('VIVA_SANDBOX', true),
        ],
        'paypal' => [
            'label' => 'PayPal',
            'client_id' => env('PAYPAL_CLIENT_ID', ''),
            'client_secret' => env('PAYPAL_CLIENT_SECRET', ''),
            'sandbox' => (bool) env('PAYPAL_SANDBOX', true),
        ],
        'stripe' => [
            'label' => 'Stripe',
            'publishable_key' => env('STRIPE_PUBLISHABLE_KEY', ''),
            'secret_key' => env('STRIPE_SECRET_KEY', ''),
            'webhook_secret' => env('STRIPE_WEBHOOK_SECRET', ''),
        ],
    ],
];
