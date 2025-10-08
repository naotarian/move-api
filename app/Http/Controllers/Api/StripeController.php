<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Stripe\Customer;
use Stripe\SetupIntent;
use Illuminate\Support\Facades\Auth;
use Stripe\Stripe;

class StripeController extends Controller
{
    public function setupIntent(Request $request)
    {
        $organization = Auth::guard('organization')->user();
        if (!$organization) {
            return response()->json([
                'success' => false,
                'error' => '認証されていません'
            ], 401);
        }
        // Stripe API Keyを設定

        Stripe::setApiKey(config('services.stripe.secret'));

        if (!$organization->stripe_customer_id) {
            $customer = Customer::create([
                'email' => $organization->email,
                'name' => $organization->name,
                'phone' => $organization->phone,
                'metadata' => [
                    'status'   => 'pending',        // ← 作成直後は pending
                    'organization_id' => (string)$organization->id,
                ],
            ]);
            $organization->stripe_customer_id = $customer->id;
            $organization->save();
        }

        // 将来のオフセッション用の SetupIntent
        $si = SetupIntent::create([
            'customer' => $organization->stripe_customer_id,
            'usage'    => 'off_session',
            'payment_method_types' => ['card'],
        ]);

        $stripe_pk = config('services.stripe.publishable');

        return response()->json([
            'clientSecret'   => $si->client_secret,
            'publishableKey' => $stripe_pk,
        ]);
    }
}
