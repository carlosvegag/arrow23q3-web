<?php
return array (
    'client_id' => env('PAYPAL_CLIENT_ID', ''),
    'secret' => env('PAYPAL_SECRET', ''),
    'settings' => [
        'mode' => env('PAYPAL_MODE', 'SANDBOX')
    ]
);
?>