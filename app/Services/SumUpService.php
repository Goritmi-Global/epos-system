<?php
// app/Services/SumUpService.php

namespace App\Services;

use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

class SumUpService
{
    private $apiKey;
    private $merchantCode;
    private $affiliateKey;
    private $readerId;
    private $baseUrl = 'https://api.sumup.com';

    public function __construct()
    {
        $this->apiKey = config('services.sumup.api_key');
        $this->merchantCode = config('services.sumup.merchant_code');
        $this->affiliateKey = config('services.sumup.affiliate_key');
        $this->readerId = config('services.sumup.reader_id');
    }

    /**
     * List all paired readers
     */
    public function getReaders()
    {
        try {
            $response = Http::withHeaders([
                'Authorization' => 'Bearer ' . $this->apiKey,
            ])->get("{$this->baseUrl}/v0.1/merchants/{$this->merchantCode}/readers");

            return $response->json();
        } catch (\Exception $e) {
            Log::error('SumUp get readers error: ' . $e->getMessage());
            throw $e;
        }
    }

    /**
     * Pair a new Solo reader
     */
    public function pairReader($pairingCode)
    {
        try {
            $response = Http::withHeaders([
                'Authorization' => 'Bearer ' . $this->apiKey,
                'Content-Type' => 'application/json',
            ])->post("{$this->baseUrl}/v0.1/merchants/{$this->merchantCode}/readers", [
                'pairing_code' => $pairingCode
            ]);

            return $response->json();
        } catch (\Exception $e) {
            Log::error('SumUp pair reader error: ' . $e->getMessage());
            throw $e;
        }
    }

    /**
     * Create checkout and send to Solo reader
     */
    public function createCheckout($amount, $currency = 'GBP', $description = 'POS Payment', $referenceId = null)
    {
        try {
            $checkoutId = $referenceId ?? 'POS_' . time() . '_' . uniqid();

            // Create checkout
            $response = Http::withHeaders([
                'Authorization' => 'Bearer ' . $this->apiKey,
                'Content-Type' => 'application/json',
            ])->post("{$this->baseUrl}/v0.1/checkouts", [
                'checkout_reference' => $checkoutId,
                'amount' => (float) $amount,
                'currency' => $currency,
                'merchant_code' => $this->merchantCode,
                'description' => $description,
                'redirect_url' => config('app.url') . '/sumup/callback',
            ]);

            $checkout = $response->json();

            if (!isset($checkout['id'])) {
                throw new \Exception('Failed to create checkout');
            }

            Log::info('SumUp checkout created: ' . $checkout['id']);

            return $checkout;
        } catch (\Exception $e) {
            Log::error('SumUp create checkout error: ' . $e->getMessage());
            throw $e;
        }
    }

    /**
     * Process checkout on Solo reader
     */
    public function processOnReader($checkoutId, $readerId = null)
    {
        try {
            $readerId = $readerId ?? $this->readerId;

            $response = Http::withHeaders([
                'Authorization' => 'Bearer ' . $this->apiKey,
                'Content-Type' => 'application/json',
            ])->post("{$this->baseUrl}/v0.1/checkouts/{$checkoutId}/reader", [
                'reader_id' => $readerId
            ]);

            $result = $response->json();

            Log::info('SumUp payment sent to reader: ' . json_encode($result));

            return $result;
        } catch (\Exception $e) {
            Log::error('SumUp process on reader error: ' . $e->getMessage());
            throw $e;
        }
    }

    /**
     * Get checkout status
     */
    public function getCheckoutStatus($checkoutId)
    {
        try {
            $response = Http::withHeaders([
                'Authorization' => 'Bearer ' . $this->apiKey,
            ])->get("{$this->baseUrl}/v0.1/checkouts/{$checkoutId}");

            return $response->json();
        } catch (\Exception $e) {
            Log::error('SumUp get status error: ' . $e->getMessage());
            throw $e;
        }
    }

    /**
     * Cancel/Complete checkout
     */
    public function completeCheckout($checkoutId)
    {
        try {
            $response = Http::withHeaders([
                'Authorization' => 'Bearer ' . $this->apiKey,
                'Content-Type' => 'application/json',
            ])->put("{$this->baseUrl}/v0.1/checkouts/{$checkoutId}", [
                'status' => 'PAID'
            ]);

            return $response->json();
        } catch (\Exception $e) {
            Log::error('SumUp complete checkout error: ' . $e->getMessage());
            throw $e;
        }
    }

    /**
     * Poll checkout status until completed
     */
    public function pollCheckoutStatus($checkoutId, $maxAttempts = 60, $delaySeconds = 2)
    {
        $attempts = 0;

        while ($attempts < $maxAttempts) {
            $status = $this->getCheckoutStatus($checkoutId);

            Log::info("Polling attempt {$attempts}: " . ($status['status'] ?? 'unknown'));

            if (isset($status['status']) && in_array($status['status'], ['PAID', 'FAILED', 'CANCELLED'])) {
                return $status;
            }

            sleep($delaySeconds);
            $attempts++;
        }

        throw new \Exception('Payment timeout - no response from reader');
    }
}