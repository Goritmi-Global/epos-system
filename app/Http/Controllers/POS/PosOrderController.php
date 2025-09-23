<?php

namespace App\Http\Controllers\POS;

use App\Http\Controllers\Controller;
use App\Http\Requests\PosOrders\StorePosOrderRequest;
use App\Services\POS\PosOrderService;
use Inertia\Inertia;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class PosOrderController extends Controller
{
    public function __construct(private PosOrderService $service) {}

    public function index()
    {   
        return Inertia::render('Backend/POS/Index');
    }


    public function store(StorePosOrderRequest $request)
    {
        $order = $this->service->create($request->validated());

        return response()->json([
            'message' => 'Order created successfully',
            'order' => $order,
        ]);
    }
    public function fetchMenuCategories()
    {  
        $menuCategories = $this->service->getMenuCategories();
        return $menuCategories; 
    }
    public function fetchMenuItems()
    {  
        $menuItems = $this->service->getAllMenus();
        return $menuItems; 
    }

   public function fetchProfileTables()
    {  
        $profileTables = $this->service->getProfileTable();
        return $profileTables; 
    }



// Paymet Using Stript
public function createIntent(Request $request)
    { 
        $amount     = (float) $request->input('amount', 0);
        $currency   = strtolower($request->input('currency', 'usd'));
        $orderCode  = $request->input('order_code') ?: (now()->format('Ymd-His') . rand(10, 99));

        //  Convert to cents (integers)
        $amountInCents = (int) round($amount * 100);
        if ($amountInCents < 50) {
            return response()->json(['error' => 'Amount must be at least 0.50'], 422);
        }
  
       $stripe = new \Stripe\StripeClient(config('app.stripe_secret_key'));

        //  Create PI with the real amount
        $pi = $stripe->paymentIntents->create([
            'amount'   => $amountInCents,
            'currency' => $currency,
            'automatic_payment_methods' => ['enabled' => true],
            'metadata' => [
                'order_code' => $orderCode,
                'source'     => 'pos-web',
            ],
            'description' => "POS Order {$orderCode} ({$currency} {$amount})",
        ]);

        return response()->json([
            'payment_intent' => $pi->id,
            'client_secret'  => $pi->client_secret,
            'order_code'     => $orderCode,
            'amount'         => $amount,
            'currency'       => $currency,
        ]);
    }
    


public function placeStripeOrder(Request $request)
{
    $paymentIntentId = $request->query('payment_intent');
    $redirectStatus  = $request->query('redirect_status'); // succeeded | failed | requires_action

    if ($redirectStatus !== 'succeeded' || empty($paymentIntentId)) {
        return redirect()->route('pos.order')->with('error', 'Stripe payment not successful.');
    }

    try {
        $stripe = new \Stripe\StripeClient(config('app.stripe_secret_key'));
        $pi = $stripe->paymentIntents->retrieve($paymentIntentId, [
            'expand' => ['payment_method', 'latest_charge.payment_method_details']
        ]);
    } catch (\Throwable $e) {
        return redirect()->route('pos.order')->with('error', 'Unable to verify payment with Stripe.');
    }

    if (($pi->status ?? null) !== 'succeeded') {
        return redirect()->route('pos.order')->with('error', 'Payment not captured yet.');
    }

    // Items JSON from query
    $items = [];
    if ($request->filled('items')) {
        try { $items = json_decode($request->query('items'), true) ?? []; } catch (\Throwable $e) {}
    }

    $currency = strtoupper($pi->currency ?? $request->query('currency_code', 'USD'));

    // Card details (prefer latest_charge)
    $pm        = $pi->payment_method;
    $chargePmd = $pi->latest_charge->payment_method_details->card ?? null;

    $brand     = $chargePmd->brand   ?? ($pm->card->brand   ?? null);
    $last4     = $chargePmd->last4   ?? ($pm->card->last4   ?? null);
    $expMonth  = $pm->card->exp_month ?? null;
    $expYear   = $pm->card->exp_year  ?? null;

    $code = $request->query('order_code') ?: (date('Ymd-His') . rand(10, 99));

    $data = [
        'customer_name'    => $request->query('customer_name'),
        'sub_total'        => (float) $request->query('sub_total', 0),
        'total_amount'     => (float) $request->query('total_amount', 0),
        'tax'              => (float) $request->query('tax', 0),
        'service_charges'  => (float) $request->query('service_charges', 0),
        'delivery_charges' => (float) $request->query('delivery_charges', 0),
        'status'           => 'paid',
        'note'             => $request->query('note'),
        'order_date'       => $request->query('order_date', now()->toDateString()),
        'order_time'       => $request->query('order_time', now()->toTimeString()),
        'order_type'       => $request->query('order_type'),
        'table_number'     => $request->query('table_number'),
        'items'            => $items,

        // Payment block
        'payment_method'   => 'Stripe',
        'payment_status'   => $pi->status ?? 'succeeded',
        'cash_received'    => (float) $request->query('cash_received', $request->query('total_amount', 0)),

        // Tracking
        'order_code'               => $code,
        'stripe_payment_intent_id' => $paymentIntentId,

        // Card details
        'last_digits'    => $last4,
        'brand'          => $brand,
        'currency_code'  => $currency,
        'exp_month'      => $expMonth,
        'exp_year'       => $expYear,
    ];

    
    $order = $this->service->create($data);

    // for printing receipt
    $printPayload = [
        'id'             => $order->id,
        'customer_name'  => $data['customer_name'] ?? null,
        'order_type'     => $data['order_type'] ?? null,
        'payment_method' => 'Card', // display text for receipt
        'card_brand'     => $data['brand'] ?? null,
        'last4'          => $data['last_digits'] ?? null,
        'sub_total'      => $data['sub_total'] ?? 0,
        'total_amount'   => $data['total_amount'] ?? 0,
        'items'          => $data['items'] ?? [], // from your query params
    ];

    // 🔔 Flash for the frontend toast
    $msg = "Payment successful! Order #{$order->id} placed. Card {$brand} •••• {$last4}.";
    return redirect()
    ->route('pos.order')
    ->with([
        'success'        => $msg,
        'print_payload'  => $printPayload, // 👈 add this
    ]);
}
}
