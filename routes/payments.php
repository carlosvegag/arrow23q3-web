<?php

//PayPal
Route::get('/paypal/suscripcion', 'Payments\PaypalController@callback')->name('paypal.redirect');
Route::get('/paypal/return', 'Payments\PaypalController@paypalReturn')->name('paypal.return');


