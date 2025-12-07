<?php
// app/Http/Controllers/SumUpController.php

namespace App\Http\Controllers;

use App\Services\SumUpService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;

class SumUpController extends Controller
{
    private $sumup;

    public function __construct(SumUpService $sumup)
    {
        $this->sumup = $sumup;
    }

    /**
     * Get paired readers
     */
    public function getReaders()
    {
        try {
            $readers = $this->sumup->getReaders();
            return response()->json([
                'success' => true,
                'data' => $readers
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * Pair new reader
     */
    public function pairReader(Request $request)
    {
        $request->validate([
            'pairing_code' => 'required|string|size:8'
        ]);

        try {
            $reader = $this->sumup->pairReader($request->pairing_code);
            return response()->json([
                'success' => true,
                'data' => $reader,
                'message' => 'Reader paired successfully'
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * Process card payment on Solo reader
     */
    public function processPayment(Request $request)
    {
        $request->validate([
            'amount' => 'required|numeric|min:0.01',
            'currency' => 'nullable|string|size:3',
            'description' => 'nullable|string',
            'order_id' => 'nullable|string',
            'reader_id' => 'nullable|string',
        ]);

        try {
            // 1. Create checkout
            $checkout = $this->sumup->createCheckout(
                $request->amount,
                $request->currency ?? 'GBP',
                $request->description ?? 'POS Payment',
                $request->order_id
            );

            // 2. Send to Solo reader
            $readerResponse = $this->sumup->processOnReader(
                $checkout['id'],
                $request->reader_id
            );

            // 3. Return checkout info for status polling
            return response()->json([
                'success' => true,
                'checkout_id' => $checkout['id'],
                'status' => 'PENDING',
                'message' => 'Payment sent to card reader'
            ]);

        } catch (\Exception $e) {
            Log::error('SumUp payment error: ' . $e->getMessage());
            return response()->json([
                'success' => false,
                'message' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * Check payment status
     */
    public function checkStatus($checkoutId)
    {
        try {
            $status = $this->sumup->getCheckoutStatus($checkoutId);
            
            return response()->json([
                'success' => true,
                'data' => $status
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * Wait for payment completion (long polling)
     */
    public function waitForPayment($checkoutId)
    {
        try {
            $result = $this->sumup->pollCheckoutStatus($checkoutId);
            
            $success = $result['status'] === 'PAID';

            return response()->json([
                'success' => $success,
                'status' => $result['status'],
                'data' => $result,
                'message' => $success ? 'Payment successful' : 'Payment failed'
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => $e->getMessage()
            ], 500);
        }
    }
}