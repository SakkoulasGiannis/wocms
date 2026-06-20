<?php

return [
    /*
    |--------------------------------------------------------------------------
    | Hostaway API credentials
    |--------------------------------------------------------------------------
    |
    | Used by Modules\RentalProperties\Services\HostawayClient to fetch the
    | availability calendar (and, later, reservations). These may also be set
    | from the admin at /admin/crm-sync — the admin Settings value, when
    | present, takes precedence over these env defaults.
    |
    */

    'account_id' => env('HOSTAWAY_ACCOUNT_ID', ''),

    'api_key' => env('HOSTAWAY_API_KEY', ''),

    /*
    | Shared secret used to verify incoming Hostaway webhooks (calendar /
    | reservation change notifications) before acting on them.
    */
    'webhook_token' => env('HOSTAWAY_WEBHOOK_TOKEN', ''),
];
