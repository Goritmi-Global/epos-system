<?php

namespace App\Http\Controllers\Printer;

use App\Helpers\UploadHelper;
use App\Http\Controllers\Controller;
use App\Models\ProfileStep2;
use App\Models\ProfileStep6;
use App\Models\User;
use Illuminate\Http\Request;

/**
 * PrinterController for Web-Based POS
 * 
 * This controller generates print-ready data that gets sent to the client.
 * Actual printing happens on the client side via JavaScript.
 */
class PrinterController extends Controller
{
    /**
     * Get available printers info
     * Since we're on shared hosting, we return instructions for client-side setup
     */
    public function index()
    {
        return response()->json([
            'success' => true,
            'message' => 'Printer detection happens on client side',
            'instructions' => [
                'Use the thermal-print.js library on your frontend',
                'Printers are detected via WebUSB or browser print dialog',
                'Ensure Chrome/Edge browser for best WebUSB support'
            ],
            'supported_methods' => [
                'webusb' => 'Direct USB printing via WebUSB API (Chrome/Edge)',
                'browser' => 'Browser print dialog (all browsers)',
                'electron' => 'Electron app with node-escpos'
            ]
        ]);
    }

    /**
     * Generate receipt data for client-side printing
     */
    public function getReceiptData(Request $request)
    {
        try {
            $order = $request->input('order');
            if (!$order) {
                return response()->json(['success' => false, 'message' => 'No order data received']);
            }

            // Fetch business info
            $businessData = $this->getBusinessData();
            
            // Build receipt data structure
            $receiptData = [
                'type' => 'customer_receipt',
                'business' => $businessData,
                'order' => $this->formatOrderData($order),
                'items' => $this->formatItems($order['items'] ?? []),
                'totals' => $this->calculateTotals($order),
                'payment' => $this->formatPayment($order),
                'generated_at' => now()->toIso8601String(),
            ];

            return response()->json([
                'success' => true,
                'data' => $receiptData,
                'print_commands' => $this->generateEscPosCommands($receiptData, 'receipt')
            ]);

        } catch (\Throwable $th) {
            return response()->json([
                'success' => false,
                'message' => 'Error generating receipt: ' . $th->getMessage(),
            ], 500);
        }
    }

    /**
     * Generate KOT data for client-side printing
     */
    public function getKotData(Request $request)
    {
        try {
            $order = $request->input('order');
            if (!$order) {
                return response()->json(['success' => false, 'message' => 'No order data received']);
            }

            $businessData = $this->getBusinessData();

            $kotData = [
                'type' => 'kitchen_order_ticket',
                'business' => $businessData,
                'order' => [
                    'date' => $order['order_date'] ?? date('Y-m-d'),
                    'time' => $order['order_time'] ?? date('H:i:s'),
                    'customer' => $order['customer_name'] ?? 'Walk In',
                    'order_type' => $order['order_type'] ?? 'In-Store',
                    'kitchen_note' => $order['kitchen_note'] ?? null,
                ],
                'items' => $this->formatKotItems($order['items'] ?? []),
                'generated_at' => now()->toIso8601String(),
            ];

            return response()->json([
                'success' => true,
                'data' => $kotData,
                'print_commands' => $this->generateEscPosCommands($kotData, 'kot')
            ]);

        } catch (\Throwable $th) {
            return response()->json([
                'success' => false,
                'message' => 'Error generating KOT: ' . $th->getMessage(),
            ], 500);
        }
    }

    /**
     * Generate Z Report data for client-side printing
     */
    public function getZReportData(Request $request, $shiftId)
    {
        try {
            $shift = \App\Models\Shift::findOrFail($shiftId);

            if ($shift->status !== 'closed') {
                return response()->json([
                    'success' => false,
                    'message' => 'Z Report can only be printed for closed shifts',
                ]);
            }

            $reportService = new \App\Services\Shifts\ShiftReportService();
            $data = $reportService->generateZReport($shift);
            $businessData = $this->getBusinessData();

            $zReportData = [
                'type' => 'z_report',
                'business' => $businessData,
                'report' => $data,
                'generated_at' => now()->toIso8601String(),
            ];

            return response()->json([
                'success' => true,
                'data' => $zReportData,
                'print_commands' => $this->generateEscPosCommands($zReportData, 'zreport')
            ]);

        } catch (\Throwable $th) {
            return response()->json([
                'success' => false,
                'message' => 'Error generating Z Report: ' . $th->getMessage(),
            ], 500);
        }
    }

    /**
     * Legacy endpoints - redirect to new data endpoints
     */
    public function printReceipt(Request $request)
    {
        return $this->getReceiptData($request);
    }

    public function printKot(Request $request)
    {
        return $this->getKotData($request);
    }

    public function printZReport(Request $request, $shiftId)
    {
        return $this->getZReportData($request, $shiftId);
    }

    // ==================== HELPER METHODS ====================

    private function getBusinessData(): array
    {
        $superAdmin = User::where('is_first_super_admin', true)->first();
        $onboardingUserId = $superAdmin ? $superAdmin->id : auth()->id();

        $business = ProfileStep2::where('user_id', $onboardingUserId)
            ->select('business_name', 'phone', 'address', 'upload_id')
            ->first();

        $footer = ProfileStep6::where('user_id', $onboardingUserId)
            ->select('receipt_footer')
            ->first();

        $logoUrl = null;
        if ($business && $business->upload_id) {
            $logoUrl = UploadHelper::url($business->upload_id);
        }

        return [
            'name' => $business->business_name ?? 'Business Name',
            'phone' => $business->phone ?? '+44 0000 000000',
            'address' => $business->address ?? 'Unknown Address',
            'footer' => $footer->receipt_footer ?? 'Thank you for your purchase!',
            'logo_url' => $logoUrl,
        ];
    }

    private function formatOrderData(array $order): array
    {
        return [
            'date' => $order['order_date'] ?? date('Y-m-d'),
            'time' => $order['order_time'] ?? date('H:i:s'),
            'customer' => $order['customer_name'] ?? 'Walk In',
            'order_type' => $order['order_type'] ?? 'Collection',
            'note' => $order['note'] ?? null,
        ];
    }

    private function formatItems(array $items): array
    {
        return array_map(function ($item) {
            $qty = (int)($item['quantity'] ?? $item['qty'] ?? 1);
            $unitPrice = (float)($item['unit_price'] ?? $item['price'] ?? 0);
            
            return [
                'title' => trim($item['title'] ?? 'Item'),
                'quantity' => $qty,
                'unit_price' => $unitPrice,
                'total' => $unitPrice * $qty,
            ];
        }, $items);
    }

    private function formatKotItems(array $items): array
    {
        return array_map(function ($item) {
            return [
                'title' => trim($item['title'] ?? 'Item'),
                'quantity' => (int)($item['quantity'] ?? $item['qty'] ?? 1),
                'kitchen_note' => $item['item_kitchen_note'] ?? null,
            ];
        }, $items);
    }

    private function calculateTotals(array $order): array
    {
        $subtotal = (float)($order['sub_total'] ?? 0);
        $total = (float)($order['total_amount'] ?? $subtotal);
        
        return [
            'subtotal' => $subtotal,
            'total' => $total,
            'tax' => $total - $subtotal,
        ];
    }

    private function formatPayment(array $order): array
    {
        $paymentType = strtolower($order['payment_type'] ?? 'cash');
        $cashAmount = (float)($order['cash_received'] ?? 0);
        $cardAmount = (float)($order['card_amount'] ?? $order['cardPayment'] ?? 0);
        $totalAmount = (float)($order['total_amount'] ?? $order['sub_total'] ?? 0);

        if ($paymentType === 'split' && $cardAmount <= 0 && $cashAmount > 0) {
            $cardAmount = $totalAmount - $cashAmount;
        }

        return [
            'type' => $paymentType,
            'cash_received' => $cashAmount,
            'card_amount' => $cardAmount,
            'change' => max(0, $cashAmount - $totalAmount),
        ];
    }

    /**
     * Generate ESC/POS command bytes for thermal printers
     * These commands can be sent directly to the printer via WebUSB
     */
    private function generateEscPosCommands(array $data, string $type): array
    {
        $commands = [];
        
        // ESC/POS command constants
        $ESC = "\x1B";
        $GS = "\x1D";
        
        // Initialize printer
        $commands[] = ['type' => 'raw', 'data' => $ESC . '@']; // Initialize
        
        // Center alignment
        $commands[] = ['type' => 'raw', 'data' => $ESC . 'a' . "\x01"]; // Center
        
        // Business name (emphasized, double size)
        $commands[] = ['type' => 'raw', 'data' => $ESC . 'E' . "\x01"]; // Bold on
        $commands[] = ['type' => 'raw', 'data' => $GS . '!' . "\x11"]; // Double width/height
        $commands[] = ['type' => 'text', 'data' => strtoupper($data['business']['name'] ?? 'Business')];
        $commands[] = ['type' => 'raw', 'data' => $GS . '!' . "\x00"]; // Normal size
        $commands[] = ['type' => 'raw', 'data' => $ESC . 'E' . "\x00"]; // Bold off
        
        $commands[] = ['type' => 'text', 'data' => $data['business']['phone'] ?? ''];
        $commands[] = ['type' => 'text', 'data' => $data['business']['address'] ?? ''];
        $commands[] = ['type' => 'text', 'data' => str_repeat('=', 48)];
        
        // Receipt type header
        $receiptType = match($type) {
            'receipt' => 'CUSTOMER RECEIPT',
            'kot' => 'KITCHEN ORDER TICKET',
            'zreport' => 'DAILY SUMMARY REPORT',
            default => 'RECEIPT'
        };
        
        $commands[] = ['type' => 'raw', 'data' => $ESC . 'E' . "\x01"]; // Bold on
        $commands[] = ['type' => 'text', 'data' => $receiptType];
        $commands[] = ['type' => 'raw', 'data' => $ESC . 'E' . "\x00"]; // Bold off
        $commands[] = ['type' => 'text', 'data' => str_repeat('=', 48)];
        
        // Left alignment for details
        $commands[] = ['type' => 'raw', 'data' => $ESC . 'a' . "\x00"]; // Left align
        
        // Add type-specific content
        if ($type === 'receipt' || $type === 'kot') {
            $orderData = $data['order'] ?? [];
            $commands[] = ['type' => 'text', 'data' => sprintf("%-16s %30s", 'Date:', $orderData['date'] ?? '')];
            $commands[] = ['type' => 'text', 'data' => sprintf("%-16s %30s", 'Time:', $orderData['time'] ?? '')];
            $commands[] = ['type' => 'text', 'data' => sprintf("%-16s %30s", 'Customer:', $orderData['customer'] ?? '')];
            $commands[] = ['type' => 'text', 'data' => sprintf("%-16s %30s", 'Order Type:', $orderData['order_type'] ?? '')];
        }
        
        $commands[] = ['type' => 'text', 'data' => str_repeat('-', 48)];
        
        // Items
        if (isset($data['items'])) {
            foreach ($data['items'] as $item) {
                if ($type === 'kot') {
                    $commands[] = ['type' => 'text', 'data' => sprintf("%-40s %6s", $item['title'], $item['quantity'])];
                    if (!empty($item['kitchen_note'])) {
                        $commands[] = ['type' => 'text', 'data' => '  Note: ' . $item['kitchen_note']];
                    }
                } else {
                    $commands[] = ['type' => 'text', 'data' => sprintf(
                        "%-24s %4s %8s %8s",
                        substr($item['title'], 0, 24),
                        $item['quantity'],
                        '£' . number_format($item['unit_price'], 2),
                        '£' . number_format($item['total'], 2)
                    )];
                }
            }
        }
        
        $commands[] = ['type' => 'text', 'data' => str_repeat('-', 48)];
        
        // Totals (for receipts)
        if ($type === 'receipt' && isset($data['totals'])) {
            $commands[] = ['type' => 'text', 'data' => sprintf("%-35s %12s", 'Subtotal:', '£' . number_format($data['totals']['subtotal'], 2))];
            $commands[] = ['type' => 'raw', 'data' => $ESC . 'E' . "\x01"]; // Bold
            $commands[] = ['type' => 'text', 'data' => sprintf("%-35s %12s", 'Total:', '£' . number_format($data['totals']['total'], 2))];
            $commands[] = ['type' => 'raw', 'data' => $ESC . 'E' . "\x00"]; // Bold off
        }
        
        // Footer
        $commands[] = ['type' => 'feed', 'lines' => 2];
        $commands[] = ['type' => 'raw', 'data' => $ESC . 'a' . "\x01"]; // Center
        $commands[] = ['type' => 'text', 'data' => strtoupper($data['business']['footer'] ?? '')];
        $commands[] = ['type' => 'feed', 'lines' => 3];
        
        // Cut paper
        $commands[] = ['type' => 'raw', 'data' => $GS . 'V' . "\x00"]; // Full cut
        
        return $commands;
    }
}