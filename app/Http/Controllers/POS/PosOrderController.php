<?php

namespace App\Http\Controllers\POS;

use App\Events\CartUpdated;
use App\Http\Controllers\Controller;
use App\Http\Requests\PosOrders\StorePosOrderRequest;
use App\Models\KitchenOrderItem;
use App\Services\POS\PosOrderService;
use Illuminate\Http\Request;
use Inertia\Inertia;

class PosOrderController extends Controller
{
    public function __construct(private PosOrderService $service) {}

    public function index()
    {
        return Inertia::render('Backend/POS/Index');
    }

    // public function store(StorePosOrderRequest $request)
    // {
    //     $result = $this->service->create($request->validated());

    //     return response()->json([
    //         'message' => 'Order created successfully',
    //         'order' => $result['order'],
    //         'kot' => $result['kot'] ? $result['kot']->load('items') : null,
    //     ]);
    // }
    public function store(StorePosOrderRequest $request)
    {
        $result = $this->service->create($request->validated());

        // ✅ Check if this is an array with logout flag (cashier auto-logout)
        if (is_array($result) && isset($result['logout']) && $result['logout'] === true) {
            $order = $result['order'];

            return response()->json([
                'message' => 'Order created successfully. You have been logged out.',
                'order' => $order,
                'kot' => $order->kot ? $order->kot->load('items') : null,
                'redirect' => route('login'),
                'logout' => true,
            ]);
        }

        // ✅ Normal response (non-cashier or auto-logout disabled)
        return response()->json([
            'message' => 'Order created successfully',
            'order' => $result,
            'kot' => $result->kot ? $result->kot->load('items') : null,
        ]);
    }

    public function fetchMenuCategories()
    {
        $menuCategories = $this->service->getMenuCategories();

        return $menuCategories;
    }

    public function fetchMenuItems()
    {
        $menus = $this->service->getAllMenus();

        return response()->json([
            'success' => true,
            'data' => $menus,
        ]);
    }

    public function fetchProfileTables()
    {
        $profileTables = $this->service->getProfileTable();

        return $profileTables;
    }

    public function updateKotItemStatus(Request $request, KitchenOrderItem $item)
    {
        $request->validate([
            'status' => 'required|in:Waiting,Done,Cancelled',
        ]);

        $item->update(['status' => $request->status]);

        return response()->json([
            'status' => $item->status,
            'message' => 'Item status updated successfully.',
        ]);
    }

    // Fetch Today KOT order
    public function getTodaysOrders()
    {
        $todayKotOrders = $this->service->getTodaysOrders();

        return response()->json(['orders' => $todayKotOrders]);
    }

    // Paymet Using Stript
    public function createIntent(Request $request)
    {
        $amount = (float) $request->input('amount', 0);
        $currency = strtolower($request->input('currency', 'usd'));
        $orderCode = $request->input('order_code') ?: (now()->format('Ymd-His') . rand(10, 99));

        //  Convert to cents (integers)
        $amountInCents = (int) round($amount * 100);
        if ($amountInCents < 50) {
            return response()->json(['error' => 'Amount must be at least 0.50'], 422);
        }

        $stripe = new \Stripe\StripeClient(config('app.stripe_secret_key'));

        //  Create PI with the real amount
        $pi = $stripe->paymentIntents->create([
            'amount' => $amountInCents,
            'currency' => $currency,
            'automatic_payment_methods' => ['enabled' => true],
            'metadata' => [
                'order_code' => $orderCode,
                'source' => 'pos-web',
            ],
            'description' => "POS Order {$orderCode} ({$currency} {$amount})",
        ]);

        return response()->json([
            'payment_intent' => $pi->id,
            'client_secret' => $pi->client_secret,
            'order_code' => $orderCode,
            'amount' => $amount,
            'currency' => $currency,
        ]);
    }

    public function placeStripeOrder(Request $request)
    {
        $paymentIntentId = $request->query('payment_intent');
        $redirectStatus = $request->query('redirect_status'); // succeeded | failed | requires_action

        if ($redirectStatus !== 'succeeded' || empty($paymentIntentId)) {
            return redirect()->route('pos.order')->with('error', 'Stripe payment not successful.');
        }

        try {
            $stripe = new \Stripe\StripeClient(config('app.stripe_secret_key'));
            $pi = $stripe->paymentIntents->retrieve($paymentIntentId, [
                'expand' => ['payment_method', 'latest_charge.payment_method_details'],
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
            try {
                $items = json_decode($request->query('items'), true) ?? [];
                \Log::info('Parsed items:', $items);
            } catch (\Throwable $e) {
                \Log::error('Failed to parse items', ['error' => $e->getMessage()]);
            }
        }

        // ✅ Parse applied_promos JSON
        $appliedPromos = [];
        if ($request->filled('applied_promos')) {
            try {
                $appliedPromos = json_decode($request->query('applied_promos'), true) ?? [];
            } catch (\Throwable $e) {
                \Log::error('Failed to parse applied_promos', ['error' => $e->getMessage()]);
            }
        }

        // ✅ Calculate total promo discount from array
        $totalPromoDiscount = 0;
        if (!empty($appliedPromos)) {
            foreach ($appliedPromos as $promo) {
                $totalPromoDiscount += (float) ($promo['discount_amount'] ?? 0);
            }
        }

        $currency = strtoupper($pi->currency ?? $request->query('currency_code', 'GBP'));

        // Card details (prefer latest_charge)
        $pm = $pi->payment_method;
        $chargePmd = $pi->latest_charge->payment_method_details->card ?? null;

        $brand = $chargePmd->brand ?? ($pm->card->brand ?? null);
        $last4 = $chargePmd->last4 ?? ($pm->card->last4 ?? null);
        $expMonth = $pm->card->exp_month ?? null;
        $expYear = $pm->card->exp_year ?? null;

        $code = $request->query('order_code') ?: (date('Ymd-His') . rand(10, 99));

        $data = [
            'customer_name' => $request->query('customer_name'),
            'sub_total' => (float) $request->query('sub_total', 0),
            'total_amount' => (float) $request->query('total_amount', 0),
            'tax' => (float) $request->query('tax', 0),
            'service_charges' => (float) $request->query('service_charges', 0),
            'delivery_charges' => (float) $request->query('delivery_charges', 0),
            'status' => 'paid',
            'note' => $request->query('note'),
            'kitchen_note' => $request->query('kitchen_note'),
            'order_date' => $request->query('order_date', now()->toDateString()),
            'order_time' => $request->query('order_time', now()->toTimeString()),
            'order_type' => $request->query('order_type'),
            'table_number' => $request->query('table_number'),
            'payment_type' => $request->query('payment_type'),
            'items' => $items,

            // Payment block
            'payment_method' => 'Stripe',
            'payment_status' => $pi->status ?? 'succeeded',
            'cash_received' => (float) $request->query('cash_received', $request->query('total_amount', 0)),
            'card_payment' => (float) $request->query('card_payment', 0),

            // Tracking
            'order_code' => $code,
            'stripe_payment_intent_id' => $paymentIntentId,

            // Card details
            'last_digits' => $last4,
            'brand' => $brand,
            'currency_code' => $currency,
            'exp_month' => $expMonth,
            'exp_year' => $expYear,

            // ✅ NEW: Pass the full applied_promos array
            'applied_promos' => $appliedPromos,

            // ✅ Keep these for backward compatibility (optional)
            'promo_discount' => $totalPromoDiscount,
        ];

        $order = $this->service->create($data);

        // ✅ Build promo names string for receipt
        $promoNames = !empty($appliedPromos)
            ? implode(', ', array_column($appliedPromos, 'promo_name'))
            : null;

        // for printing receipt
        $printPayload = [
            'id' => $order->id,
            'customer_name' => $data['customer_name'] ?? null,
            'order_type' => $data['order_type'] ?? null,
            'payment_method' => 'Card', // display text for receipt
            'card_brand' => $data['brand'] ?? null,
            'last4' => $data['last_digits'] ?? null,
            'sub_total' => $data['sub_total'] ?? 0,
            'total_amount' => $data['total_amount'] ?? 0,
            'items' => $data['items'] ?? [],
            'payment_type' => $data['payment_type'] ?? 'Card',

            // ✅ Updated promo fields for receipt
            'promo_discount' => $totalPromoDiscount,
            'promo_names' => $promoNames,
            'applied_promos' => $appliedPromos,

            // ✅ Split payment amounts for receipt
            'cash_amount' => $data['cash_amount'] ?? null,
            'card_amount' => $data['card_amount'] ?? null,
            'cash_received' => $data['cash_received'] ?? 0,

            'note' => $data['note'] ?? null,
            'kitchen_note' => $data['kitchen_note'] ?? null,
            'item_kitchen_note' => $data['item_kitchen_note'] ?? null,
        ];

        // 🔔 Flash for the frontend toast
        $promoMsg = $totalPromoDiscount > 0 ? " with £{$totalPromoDiscount} promo discount" : "";
        $msg = "Payment successful! Order #{$order->id} placed{$promoMsg}. Card {$brand} •••• {$last4}.";

        return redirect()
            ->route('pos.order')
            ->with([
                'success' => $msg,
                'print_payload' => $printPayload,
            ]);
    }



    public function broadcastCart(Request $request)
    {
        $validated = $request->validate([
            'terminal_id' => 'required|string',
            'cart' => 'required|array',
        ]);

        event(new CartUpdated(
            $validated['terminal_id'],
            $validated['cart']
        ));

        return response()->json(['success' => true]);
    }

    public function broadcastUI(Request $request)
    {
        $validated = $request->validate([
            'terminal_id' => 'required|string',
            'ui' => 'required|array',
        ]);

        event(new \App\Events\UIUpdated(
            $validated['terminal_id'],
            $validated['ui']
        ));

        return response()->json(['success' => true]);
    }
}
