<?php

declare(strict_types=1);

return [
    /*
     * Seconds the public page payloads stay cached. Content edits in the
     * admin panel flush the cache immediately, so this can be long.
     */
    'cache_ttl' => (int) env('PORTFOLIO_CACHE_TTL', 86400),

    /*
     * The account created by the AdminUserSeeder. Only users flagged
     * is_admin can open the /admin panel.
     */
    'admin' => [
        'name' => env('PORTFOLIO_ADMIN_NAME', 'Erkihun Bewket'),
        'email' => env('PORTFOLIO_ADMIN_EMAIL'),
        'password' => env('PORTFOLIO_ADMIN_PASSWORD'),
    ],

    /*
     * Where contact-form notifications are delivered. Falls back to the
     * public email on the profile when not set.
     */
    'notify_email' => env('PORTFOLIO_NOTIFY_EMAIL'),

    'contact' => [
        'per_minute' => (int) env('PORTFOLIO_CONTACT_PER_MINUTE', 3),
        'per_day' => (int) env('PORTFOLIO_CONTACT_PER_DAY', 10),
    ],
];
