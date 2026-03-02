<?php

use App\Http\Controllers\PayAgencyWebhookController;
use Illuminate\Support\Facades\Route;

Route::get('/', function () {
    return view('welcome');
});

// pay.agency — server-to-server webhook (SUCCESS / FAILED / BLOCKED).
// Handles async confirmations (e.g. 3DS). Configure in your pay.agency account:
//   https://yourdomain.com/pay-agency/webhook
// CSRF is excluded in bootstrap/app.php for this route.
Route::post('/pay-agency/webhook', [PayAgencyWebhookController::class, 'handle'])
    ->name('pay-agency.webhook');
