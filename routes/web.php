<?php

use App\Http\Controllers\PayAgencyCallbackController;
use App\Http\Controllers\PayAgencyWebhookController;
use Illuminate\Support\Facades\Route;

Route::get('/', function () {
    return view('welcome');
});

// pay.agency — user redirect-back after completing hosted payment page.
// Set this URL in your pay.agency payment template: https://yourdomain.com/pay-agency/callback
Route::get('/pay-agency/callback', [PayAgencyCallbackController::class, 'handle'])
    ->middleware('auth')
    ->name('pay-agency.callback');

// pay.agency — server-to-server webhook (SUCCESS / FAILED / BLOCKED).
// Set this URL in your pay.agency payment template: https://yourdomain.com/pay-agency/webhook
// CSRF is excluded in bootstrap/app.php for this route.
Route::post('/pay-agency/webhook', [PayAgencyWebhookController::class, 'handle'])
    ->name('pay-agency.webhook');
