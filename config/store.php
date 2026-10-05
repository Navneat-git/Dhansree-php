<?php

declare(strict_types=1);

return [

    /*
    |---------------------------------------------------------------------------
    | Display timezone
    |---------------------------------------------------------------------------
    |
    | The timezone the shop is operated in, used for FORMATTING only - order timestamps shown to
    | staff, "today" in a date filter, and the dates on an invoice.
    |
    | Timestamps are STORED in UTC (config/app.php), because the Node application stores them that
    | way and may read the same database. Never conflate the two.
    |
    */

    'timezone' => env('STORE_TIMEZONE', 'Asia/Kolkata'),

    /*
    |---------------------------------------------------------------------------
    | Money
    |---------------------------------------------------------------------------
    |
    | Every monetary amount in this application is an INTEGER NUMBER OF PAISE, matching the Node
    | schema (`*_paise` columns). Never introduce a float or a decimal cast for money.
    |
    */

    'currency' => 'INR',

    /*
    |---------------------------------------------------------------------------
    | Media
    |---------------------------------------------------------------------------
    |
    | Product media is served from an external asset host rather than this application's directory,
    | so it survives deploys and can move to S3 by changing this one value.
    |
    */

    'media_base_url' => env('MEDIA_PUBLIC_BASE_URL'),

    /*
    |---------------------------------------------------------------------------
    | Provider switches
    |---------------------------------------------------------------------------
    |
    | Both off means cash on delivery only, with online payment and courier booking hidden.
    |
    */

    'razorpay_enabled' => env('RAZORPAY_ENABLED', false),
    'shiprocket_enabled' => env('SHIPROCKET_ENABLED', false),

];
