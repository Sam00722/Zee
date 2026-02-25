<?php

namespace App\Http\Controllers;

use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;

class PayAgencyCallbackController extends Controller
{
    /**
     * Receive the user back from pay.agency's hosted payment page.
     *
     * The deposit status is updated separately via the webhook (PayAgencyWebhookController).
     * This route just returns the user to their deposits list.
     *
     * Configure this URL in your pay.agency payment template:
     *   https://yourdomain.com/pay-agency/callback
     */
    public function handle(Request $request): RedirectResponse
    {
        return redirect('/company/deposits');
    }
}
