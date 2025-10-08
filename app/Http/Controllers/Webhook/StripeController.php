<?php

namespace App\Http\Controllers\Webhook;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use Stripe\Webhook;
use Stripe\PaymentMethod;
use Stripe\Customer;
use App\Models\Organization;
use App\Models\OrganizationPaymentMethod;
use App\Http\Controllers\Controller;
use Stripe\Stripe;

class StripeController extends Controller
{
    public function handle(Request $request)
    {
        $payload = $request->getContent();
        $sigHeader = $request->header('Stripe-Signature');
        try {
            Stripe::setApiKey(config('services.stripe.secret'));
            $event = Webhook::constructEvent(
                $payload,
                $sigHeader,
                config('services.stripe.webhook_secret')
            );
        } catch (\Throwable $e) {
            Log::warning('Stripe webhook invalid: ' . $e->getMessage());
            return response('invalid', 400);
        }

        switch ($event->type) {
            case 'setup_intent.succeeded':
                $si = $event->data->object;            // \Stripe\SetupIntent
                $customerId = $si->customer;
                $pmId       = $si->payment_method;

                // 念のため attach（既にattachされているケースもある）
                try {
                    PaymentMethod::retrieve($pmId)->attach(['customer' => $customerId]);
                } catch (\Throwable $e) {
                }

                // 自社DBへ反映
                $organization = Organization::where('stripe_customer_id', $customerId)->first();
                if ($organization) {
                    $pm = PaymentMethod::retrieve($pmId);
                    $opm = OrganizationPaymentMethod::firstOrCreate(
                        ['stripe_payment_method_id' => $pmId],
                        [
                            'organization_id' => $organization->id,
                            'type'  => $pm->type, // 'card'
                            'brand' => data_get($pm, 'card.brand'),
                            'last4' => data_get($pm, 'card.last4'),
                            'exp_month' => data_get($pm, 'card.exp_month'),
                            'exp_year'  => data_get($pm, 'card.exp_year'),
                            'fingerprint' => data_get($pm, 'card.fingerprint'),
                            'status' => 'active',
                            'billing_name'  => data_get($pm, 'billing_details.name'),
                            'billing_email' => data_get($pm, 'billing_details.email'),
                            'label' => data_get($pm, 'metadata.label'),
                        ]
                    );

                    // 初回カードなら org 既定にセット
                    if (!$organization->org_default_payment_method_id) {
                        Customer::update($customerId, [
                            'invoice_settings' => ['default_payment_method' => $pmId],
                            'metadata' => ['status' => 'active'],
                        ]);
                        $organization->org_default_payment_method_id = $opm->id;
                        $organization->save();
                    }
                }
                break;
        }

        return response('ok', 200);
    }
}
