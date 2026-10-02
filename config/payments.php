<?php

return [

    /*
    |--------------------------------------------------------------------------
    | Payments Environment
    |--------------------------------------------------------------------------
    |
    | Explicit sandbox/live switch for gateway endpoints. Never derive this
    | from APP_DEBUG — a production app can legitimately run with APP_DEBUG
    | true while still needing live gateway endpoints disabled, and vice
    | versa during local testing against a real sandbox.
    */

    'env' => env('PAYMENTS_ENV', 'sandbox'),

    'currency' => 'NPR',

    /*
    |--------------------------------------------------------------------------
    | Attempt lifetime
    |--------------------------------------------------------------------------
    */

    'attempt_ttl_minutes' => env('PAYMENT_ATTEMPT_TTL_MINUTES', 15),

    'esewa' => [
        'merchant_code' => env('ESEWA_MERCHANT_CODE'),
        'secret_key' => env('ESEWA_SECRET_KEY'),
        'form_url' => env('PAYMENTS_ENV', 'sandbox') === 'live'
            ? 'https://epay.esewa.com.np/api/epay/main/v2/form'
            : 'https://rc-epay.esewa.com.np/api/epay/main/v2/form',
        'status_url' => env('PAYMENTS_ENV', 'sandbox') === 'live'
            ? 'https://epay.esewa.com.np/api/epay/transaction/status/'
            : 'https://rc.esewa.com.np/api/epay/transaction/status/',
    ],

    'khalti' => [
        'secret_key' => env('KHALTI_SECRET_KEY'),
        'base_url' => env('PAYMENTS_ENV', 'sandbox') === 'live'
            ? 'https://khalti.com/api/v2'
            : 'https://dev.khalti.com/api/v2',
    ],

    'fonepay' => [
        'merchant_code' => env('FONEPAY_MERCHANT_CODE'),
        'username' => env('FONEPAY_USERNAME'),
        'password' => env('FONEPAY_PASSWORD'),
        'base_url' => env('PAYMENTS_ENV', 'sandbox') === 'live'
            ? 'https://merchantapi.fonepay.com/api'
            : 'https://uat-new-merchant-api.fonepay.com/api',
    ],

];
