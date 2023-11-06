<?php

use PayPal\Rest\ApiContext;
use PayPal\Auth\OAuthTokenCredential;

$apiContext = new ApiContext(
    new OAuthTokenCredential(config('services.paypal.client_id'), config('services.paypal.secret'))
);
