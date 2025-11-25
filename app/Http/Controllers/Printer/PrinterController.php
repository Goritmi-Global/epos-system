<?php

namespace App\Http\Controllers\Printer;

use App\Helpers\UploadHelper;
use App\Http\Controllers\Controller;
use App\Models\ProfileStep2;
use App\Models\ProfileStep6;
use App\Models\User;
use Illuminate\Http\Request;
use Mike42\Escpos\EscposImage;
use Mike42\Escpos\PrintConnectors\WindowsPrintConnector;
use Mike42\Escpos\Printer;

class PrinterController extends Controller
{
    public function index()
    {
        try {
            $printers = [];

            if (strtoupper(substr(PHP_OS, 0, 3)) === 'WIN') {
                $usbOutput = [];
                $installedOutput = [];

                /* ------------------------------------------
                 * 1️⃣  Get physically connected USB printers
                 * ------------------------------------------ */
                $usbCmd = 'powershell -Command "Get-PnpDevice -Class USB | Where-Object { $_.Status -eq \'OK\' -and ($_.FriendlyName -match \'Print|POS|Thermal|Label|BlackCopper|USB Printing Support\') } | Select-Object FriendlyName, InstanceId, Status | ConvertTo-Json"';
                exec($usbCmd, $usbOutput);
                $usbJson = implode('', $usbOutput);
                $usbDevices = json_decode($usbJson, true);

                if ($usbDevices && ! isset($usbDevices[0])) {
                    $usbDevices = [$usbDevices];
                }

                /* ------------------------------------------
                 * 2️⃣  Get installed printers
                 * ------------------------------------------ */
                $installedCmd = 'powershell -Command "Get-Printer | Select-Object Name, DriverName, PortName | ConvertTo-Json"';
                exec($installedCmd, $installedOutput);
                $installedJson = implode('', $installedOutput);
                $installedPrinters = json_decode($installedJson, true);

                if ($installedPrinters && ! isset($installedPrinters[0])) {
                    $installedPrinters = [$installedPrinters];
                }

                /* ------------------------------------------
                 * 3️⃣  Only keep printers linked to USB devices
                 * ------------------------------------------ */
                if ($installedPrinters) {
                    foreach ($installedPrinters as $printer) {
                        // Filter only USB printers (BlackCopper, Thermal, POS, etc.)
                        $isUsb = false;
                        $isConnected = false;

                        if (
                            stripos($printer['PortName'] ?? '', 'USB') !== false ||
                            stripos($printer['DriverName'] ?? '', 'BlackCopper') !== false ||
                            stripos($printer['Name'] ?? '', 'BlackCopper') !== false ||
                            stripos($printer['DriverName'] ?? '', 'Thermal') !== false ||
                            stripos($printer['DriverName'] ?? '', 'POS') !== false
                        ) {
                            $isUsb = true;
                        }

                        if ($isUsb && $usbDevices) {
                            foreach ($usbDevices as $usb) {
                                if (
                                    stripos($usb['FriendlyName'] ?? '', 'Print') !== false ||
                                    stripos($usb['FriendlyName'] ?? '', 'BlackCopper') !== false
                                ) {
                                    $isConnected = ($usb['Status'] ?? '') === 'OK';
                                    break;
                                }
                            }
                        }

                        // Only push real USB printers
                        if ($isUsb) {
                            $printers[] = [
                                'name' => $printer['Name'] ?? 'Unknown',
                                'driver' => $printer['DriverName'] ?? 'Unknown',
                                'port' => $printer['PortName'] ?? '',
                                'is_usb' => $isUsb,
                                'is_connected' => $isConnected,
                                'status' => $isConnected ? 'OK' : 'Disconnected',
                            ];
                        }
                    }
                }
            }

            if (empty($printers)) {
                return response()->json([
                    'success' => true,
                    'data' => [],
                    'message' => 'No USB printers currently connected.',
                ]);
            }

            return response()->json([
                'success' => true,
                'data' => $printers,
            ]);
        } catch (\Throwable $th) {
            return response()->json([
                'success' => false,
                'message' => 'Error fetching printers: '.$th->getMessage(),
            ], 500);
        }
    }

    public function printReceipt(Request $request)
    {
        try {
            $order = $request->input('order');
            if (! $order) {
                return response()->json(['success' => false, 'message' => 'No order data received']);
            }

            // === FETCH BUSINESS INFO (ProfileStep2) ===
            $superAdmin = User::where('is_first_super_admin', true)->first();
            $onboardingUserId = $superAdmin ? $superAdmin->id : auth()->id();

            $business = ProfileStep2::where('user_id', $onboardingUserId)
                ->select('business_name', 'phone', 'address', 'upload_id')
                ->first();
            $footer = ProfileStep6::where('user_id', $onboardingUserId)
                ->select('receipt_footer')
                ->first();
            // === FETCH PRINTER INFO (ProfileStep6) ===
            $profile = ProfileStep6::where('user_id', $onboardingUserId)->first();
            $customerPrinter = $profile->customer_printer ?? 'Default_Customer_Printer';

            $businessName = $business->business_name ?? 'Business Name';
            $businessPhone = $business->phone ?? '+44 0000 000000';
            $businessAddress = $business->address ?? 'Unknown Address';
            $receipt_footer = $footer->receipt_footer ?? 'Unknown Footer';
            $businessLogo = $business && $business->upload_id
                ? UploadHelper::url($business->upload_id)
                : null;

            // === CONNECT TO PRINTER ===
            $connector = new WindowsPrintConnector($customerPrinter);
            $printer = new Printer($connector);

            // === CONFIG ===
            $charsPerLine = 48;
            $formatMoney = fn ($v) => '£'.number_format((float) $v, 2);

            // === HEADER ===
            $printer->setJustification(Printer::JUSTIFY_CENTER);

            // ✅ Print logo if available (convert URL → local path)
            // === PRINT BUSINESS LOGO ===
            $localLogoPath = $this->prepareLogo($businessLogo);

            if ($localLogoPath && file_exists($localLogoPath)) {
                try {
                    $logo = EscposImage::load($localLogoPath, false);
                    $printer->setJustification(Printer::JUSTIFY_CENTER);
                    $printer->bitImage($logo);
                    $printer->feed(1);
                } catch (\Exception $e) {
                    \Log::warning('Logo print error: '.$e->getMessage());
                }
            }

            // === BUSINESS INFO ===
            $printer->setEmphasis(true);
            $printer->text(strtoupper($businessName)."\n");
            $printer->setEmphasis(false);
            $printer->text($businessPhone."\n");
            $printer->text($businessAddress."\n");
            $printer->text(str_repeat('=', $charsPerLine)."\n");
            $printer->setEmphasis(true);
            $printer->text("CUSTOMER RECEIPT\n");
            $printer->setEmphasis(false);
            $printer->text(str_repeat('=', $charsPerLine)."\n\n");

            // === ORDER INFO ===
            $printer->setJustification(Printer::JUSTIFY_LEFT);
            $printer->text(sprintf("%-16s %30s\n", 'Date:', $order['order_date'] ?? date('Y-m-d')));
            $printer->text(sprintf("%-16s %30s\n", 'Time:', $order['order_time'] ?? date('H:i:s')));
            $printer->text(sprintf("%-16s %30s\n", 'Customer:', $order['customer_name'] ?? 'Walk In'));
            $printer->text(sprintf("%-16s %30s\n", 'Order Type:', $order['order_type'] ?? 'Collection'));

            if (! empty($order['note'])) {
                $printer->text(sprintf("%-16s %30s\n", 'Note:', $order['note']));
            }

            // === PAYMENT INFO ===
            $paymentType = strtolower($order['payment_type'] ?? 'cash');
            $cashAmount = (float) ($order['cash_received'] ?? 0);
            $cardAmount = (float) ($order['card_amount'] ?? $order['cardPayment'] ?? 0);
            $totalAmount = (float) ($order['total_amount'] ?? $order['sub_total'] ?? 0);

            if ($paymentType === 'split') {
                if ($cardAmount <= 0 && $cashAmount > 0) {
                    $cardAmount = $totalAmount - $cashAmount;
                }
                $printer->text(sprintf("%-16s %30s\n", 'Payment Type:', 'Split'));
                $printer->text(sprintf("%-16s %30s\n", 'Cash Payment:', $formatMoney($cashAmount)));
                $printer->text(sprintf("%-16s %30s\n", 'Card Payment:', $formatMoney($cardAmount)));
            } elseif ($paymentType === 'card' || $paymentType === 'stripe') {
                $printer->text(sprintf("%-16s %30s\n", 'Payment Type:', 'Card'));
            } else {
                $printer->text(sprintf("%-16s %30s\n", 'Payment Type:', 'Cash'));
            }

            $printer->text(str_repeat('-', $charsPerLine)."\n");

            // === ITEM TABLE HEADER ===
            $colQty = 4;
            $colPrice = 10;
            $colTotal = 10;
            $colItem = $charsPerLine - ($colQty + $colPrice + $colTotal + 3);

            $printer->setEmphasis(true);
            $printer->text(
                sprintf(
                    '%-'.$colItem."s %{$colQty}s %{$colPrice}s %{$colTotal}s\n",
                    'Item',
                    'Qty',
                    str_pad('Price', $colPrice, ' ', STR_PAD_BOTH),
                    str_pad('Total', $colTotal, ' ', STR_PAD_BOTH)
                )
            );
            $printer->setEmphasis(false);
            $printer->text(str_repeat('-', $charsPerLine)."\n");

            // === ITEMS ===
            if (! empty($order['items'])) {
                foreach ($order['items'] as $item) {
                    $title = trim($item['title'] ?? 'Item');
                    $qty = (int) ($item['quantity'] ?? $item['qty'] ?? 1);
                    $unitPrice = (float) ($item['unit_price'] ?? $item['price'] ?? 0);
                    $lineTotal = $unitPrice * $qty;

                    $priceStr = $formatMoney($unitPrice);
                    $totalStr = $formatMoney($lineTotal);

                    $wrapped = explode("\n", wordwrap($title, $colItem, "\n", true));
                    $firstLine = array_shift($wrapped);

                    $printer->text(sprintf(
                        '%-'.$colItem."s %{$colQty}s %{$colPrice}s %{$colTotal}s\n",
                        mb_strimwidth($firstLine, 0, $colItem, ''),
                        $qty,
                        $priceStr,
                        $totalStr
                    ));

                    foreach ($wrapped as $more) {
                        $printer->text(sprintf('%-'.$colItem."s\n", mb_strimwidth($more, 0, $colItem, '')));
                    }
                }
            }

            $printer->text(str_repeat('-', $charsPerLine)."\n");

            // === TOTALS ===
            $subtotal = $formatMoney($order['sub_total'] ?? 0);
            $total = $formatMoney($order['total_amount'] ?? $order['sub_total'] ?? 0);
            $cash = $formatMoney($order['cash_received'] ?? 0);

            $printer->text(sprintf("%-35s %12s\n", 'Subtotal:', $subtotal));
            $printer->text(sprintf("%-35s %12s\n\n", 'Total:', $total));

            // if ($paymentType === 'split') {
            //     $printer->text(sprintf("%-35s %12s\n", "Cash Received:", $formatMoney($cashAmount)));
            //     $printer->text(sprintf("%-35s %12s\n", "Card Received:", $formatMoney($cardAmount)));
            // } elseif ($paymentType === 'cash') {
            //     $printer->text(sprintf("%-35s %12s\n", "Cash Received:", $cash));
            // }

            if ($paymentType === 'cash') {
                $printer->text(sprintf("%-35s %12s\n", 'Cash Received:', $cash));
            }

            // === FOOTER ===
            // $printer->setJustification(Printer::JUSTIFY_CENTER);
            // $printer->feed(2);
            // $printer->text("Customer Copy - Thank you for your purchase!\n");
            // $printer->feed(1);
            $printer->setJustification(Printer::JUSTIFY_CENTER);
            $printer->text(strtoupper($receipt_footer)."\n");
            $printer->feed(1);

            $printer->cut();
            $printer->close();

            return response()->json(['success' => true]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'No printer is connected for customer receipt.',
            ]);
        }
    }

    public function printKot(Request $request)
    {
        try {
            $order = $request->input('order');
            if (! $order) {
                return response()->json(['success' => false, 'message' => 'No order data received']);
            }

            // === FETCH BUSINESS INFO ===
            $superAdmin = User::where('is_first_super_admin', true)->first();
            $onboardingUserId = $superAdmin ? $superAdmin->id : auth()->id();

            $business = ProfileStep2::where('user_id', $onboardingUserId)
                ->select('business_name', 'phone', 'address', 'upload_id')
                ->first();
            $footer = ProfileStep6::where('user_id', $onboardingUserId)
                ->select('receipt_footer')
                ->first();
            // === FETCH KOT PRINTER INFO (ProfileStep6) ===
            $profile = ProfileStep6::where('user_id', $onboardingUserId)->first();
            $kotPrinter = $profile->kot_printer ?? 'Default_KOT_Printer';

            $businessName = $business->business_name ?? 'Business Name';
            $businessPhone = $business->phone ?? '+44 0000 000000';
            $businessAddress = $business->address ?? 'Unknown Address';
            $receipt_footer = $footer->receipt_footer ?? 'Unknown Footer';
            $businessLogo = $business && $business->upload_id
                ? UploadHelper::url($business->upload_id)
                : null;

            // === CONNECT TO KITCHEN PRINTER ===
            $connector = new WindowsPrintConnector($kotPrinter);
            $printer = new Printer($connector);

            $charsPerLine = 48;

            // === HEADER ===
            $printer->setJustification(Printer::JUSTIFY_CENTER);

            // ✅ Print business logo if available
            $localLogoPath = $this->prepareLogo($businessLogo);
            if ($localLogoPath && file_exists($localLogoPath)) {
                try {
                    $logo = EscposImage::load($localLogoPath, false);
                    $printer->bitImage($logo);
                    $printer->feed(1);
                } catch (\Exception $e) {
                    \Log::warning('KOT logo error: '.$e->getMessage());
                }
            }

            // === BUSINESS INFO ===
            $printer->setEmphasis(true);
            $printer->text(strtoupper($businessName)."\n");
            $printer->setEmphasis(false);
            $printer->text($businessPhone."\n");
            $printer->text($businessAddress."\n");
            $printer->text(str_repeat('=', $charsPerLine)."\n");
            $printer->setEmphasis(true);
            $printer->text("KITCHEN ORDER TICKET\n");
            $printer->setEmphasis(false);
            $printer->text(str_repeat('=', $charsPerLine)."\n\n");

            // === ORDER INFO ===
            $printer->setJustification(Printer::JUSTIFY_LEFT);
            $printer->text(sprintf("%-16s %30s\n", 'Date:', $order['order_date'] ?? date('Y-m-d')));
            $printer->text(sprintf("%-16s %30s\n", 'Time:', $order['order_time'] ?? date('H:i:s')));
            $printer->text(sprintf("%-16s %30s\n", 'Customer:', $order['customer_name'] ?? 'Walk In'));
            $printer->text(sprintf("%-16s %30s\n", 'Order Type:', $order['order_type'] ?? 'In-Store'));

            if (! empty($order['kitchen_note'])) {
                $printer->text(sprintf("%-16s %30s\n", 'Kitchen Note:', $order['kitchen_note']));
            }

            $printer->text(str_repeat('-', $charsPerLine)."\n");

            // === ITEMS TABLE (NO PRICES) ===
            $colQty = 6;
            $colItem = $charsPerLine - ($colQty + 3);

            $printer->setEmphasis(true);
            $printer->text(sprintf('%-'.$colItem."s %{$colQty}s\n", 'Item', 'Qty'));
            $printer->setEmphasis(false);
            $printer->text(str_repeat('-', $charsPerLine)."\n");

            // === ITEMS ===
            if (! empty($order['items'])) {
                foreach ($order['items'] as $item) {
                    $title = trim($item['title'] ?? 'Item');
                    $qty = (int) ($item['quantity'] ?? $item['qty'] ?? 1);

                    $wrapped = explode("\n", wordwrap($title, $colItem, "\n", true));
                    $firstLine = array_shift($wrapped);

                    $printer->text(sprintf(
                        '%-'.$colItem."s %{$colQty}s\n",
                        mb_strimwidth($firstLine, 0, $colItem, ''),
                        $qty
                    ));

                    foreach ($wrapped as $more) {
                        $printer->text(sprintf('%-'.$colItem."s\n", mb_strimwidth($more, 0, $colItem, '')));
                    }
                    if (! empty($item['item_kitchen_note'])) {
                        $printer->setEmphasis(true);
                        $printer->text('Note: ');
                        $printer->setEmphasis(false);

                        // Wrap long notes
                        $noteWrapped = explode("\n", wordwrap($item['item_kitchen_note'], $colItem - 4, "\n", true));
                        foreach ($noteWrapped as $noteLine) {
                            $printer->text('    '.$noteLine."\n");
                        }
                    }
                }
            }

            $printer->text(str_repeat('-', $charsPerLine)."\n\n");

            // === FOOTER ===
            $printer->setJustification(Printer::JUSTIFY_CENTER);
            $printer->text(strtoupper($receipt_footer)."\n");
            $printer->feed(1);

            $printer->cut();
            $printer->close();

            return response()->json(['success' => true]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'No printer is connected for Kitchen receipt.',
            ]);
        }
    }

    public function printZReport(Request $request, $shiftId)
    {
        try {
            $shift = \App\Models\Shift::findOrFail($shiftId);

            if ($shift->status !== 'closed') {
                return response()->json([
                    'success' => false,
                    'message' => 'Z Report can only be printed for closed shifts',
                ]);
            }

            // Generate Z Report data
            $reportService = new \App\Services\Shifts\ShiftReportService;
            $data = $reportService->generateZReport($shift);

            // === FETCH PRINTER INFO ===
            $superAdmin = User::where('is_first_super_admin', true)->first();
            $onboardingUserId = $superAdmin ? $superAdmin->id : auth()->id();
            $profile = ProfileStep6::where('user_id', $onboardingUserId)->first();
            $customerPrinter = $profile->customer_printer ?? 'Default_Customer_Printer';

            // === CONNECT TO PRINTER ===
            $connector = new WindowsPrintConnector($customerPrinter);
            $printer = new Printer($connector);

            $charsPerLine = 48;
            $formatMoney = fn ($v) => '£'.number_format((float) $v, 2);

            // === HEADER ===
            $printer->setJustification(Printer::JUSTIFY_CENTER);
            $printer->setEmphasis(true);
            $printer->setTextSize(2, 2);
            $printer->text("Daily Summary Report\n");
            $printer->setTextSize(1, 1);
            $printer->setEmphasis(false);
            $printer->text("All Brands\n");
            $printer->text(date('Y-m-d H:i:s')."\n");
            $printer->text(str_repeat('=', $charsPerLine)."\n\n");

            // ============== FLOAT SESSION ==============
            $printer->setJustification(Printer::JUSTIFY_CENTER);
            $printer->setEmphasis(true);
            $printer->text("FLOAT SESSION\n");
            $printer->setEmphasis(false);
            $printer->text(str_repeat('=', $charsPerLine)."\n");
            $printer->setJustification(Printer::JUSTIFY_LEFT);

            $cash = $data['cash_reconciliation'];
            $printer->text(sprintf("%-30s %16s\n", 'Started by', $data['started_by'] ?? 'N/A'));
            $printer->text(sprintf("%-30s %16s\n", 'Started at', $data['start_time'] ?? 'N/A'));
            $printer->text(sprintf("%-30s %16s\n", 'Closed at', $data['end_time'] ?? 'N/A'));
            $printer->text(sprintf("%-30s %16s\n", 'Closed by', $data['ended_by'] ?? 'N/A'));
            $printer->text(sprintf("%-30s %16s\n", 'Opening Cash', $formatMoney($cash['opening_cash'] ?? 0)));
            $printer->text(sprintf("%-30s %16s\n", 'Cash Expenses', $formatMoney($cash['cash_expenses'] ?? 0)));
            $printer->text(sprintf("%-30s %16s\n", 'Cash Transfers', $formatMoney($cash['cash_transfers'] ?? 0)));
            $printer->text(sprintf("%-30s %16s\n", 'Cash Changed', $formatMoney($cash['cash_changed'] ?? 0)));
            $printer->text(sprintf("%-30s %16s\n", 'Cash Sales', $formatMoney($cash['cash_sales'] ?? 0)));
            $printer->text(sprintf("%-30s %16s\n", 'Cash Refunds', $formatMoney($cash['cash_refunds'] ?? 0)));
            $printer->text(sprintf("%-30s %16s\n", 'Estimated Closing Balance', $formatMoney($cash['expected_cash'] ?? 0)));
            $printer->text("\n");

            // ============== FLOAT SESSION JOURNAL ==============
            $printer->setJustification(Printer::JUSTIFY_CENTER);
            $printer->setEmphasis(true);
            $printer->text("FLOAT SESSION JOURNAL\n");
            $printer->setEmphasis(false);
            $printer->text(str_repeat('=', $charsPerLine)."\n");
            $printer->setJustification(Printer::JUSTIFY_LEFT);

            $printer->text(sprintf("%-30s %16s\n", 'Deposits Till Deposits', '0.00'));
            $printer->text(sprintf("%-30s %16s\n", 'Withdrawals Till Withdrawals', '0.00'));
            $printer->text(sprintf("%-30s %16s\n", 'Total', '0.00'));
            $printer->text("\n");

            // ============== SALES SUMMARY ==============
            $printer->setJustification(Printer::JUSTIFY_CENTER);
            $printer->setEmphasis(true);
            $printer->text("SALES SUMMARY\n");
            $printer->setEmphasis(false);
            $printer->text(str_repeat('=', $charsPerLine)."\n");
            $printer->setJustification(Printer::JUSTIFY_LEFT);

            $sales = $data['sales_summary'];
            $retailPrice = ($sales['subtotal'] ?? 0) + ($sales['total_tax'] ?? 0);

            $printer->text(sprintf("%-30s %16s\n", 'Sales Count', $sales['total_orders'] ?? 0));
            $printer->text(sprintf("%-30s %16s\n", 'Average Ticket Size', $formatMoney($sales['avg_order_value'] ?? 0)));
            $printer->text("\n");
            $printer->text(sprintf("%-30s %16s\n", 'Retail Price', $formatMoney($retailPrice)));
            $printer->text(sprintf("%-30s %16s\n", 'Discount *', $formatMoney($sales['total_discount'] ?? 0)));
            $printer->text(sprintf("%-30s %16s\n", 'Refund **', $formatMoney(0)));
            $printer->text(sprintf("%-30s %16s\n", 'Net Retail Price', $formatMoney($sales['total_sales'] ?? 0)));
            $printer->text(sprintf("%-30s %16s\n", 'Net Charges ?', $formatMoney($sales['total_charges'] ?? 0)));
            $printer->text(sprintf("%-30s %16s\n", 'Sale Price', $formatMoney($sales['subtotal'] ?? 0)));
            $printer->text(sprintf("%-30s %16s\n", 'Tax ?', $formatMoney($sales['total_tax'] ?? 0)));
            $printer->text(sprintf("%-30s %16s\n", 'Sale Price Inclusive of Tax', $formatMoney($sales['total_sales'] ?? 0)));
            $printer->text("\n");
            $printer->text(sprintf("%-30s %16s\n", 'Paid Amount', $formatMoney($sales['total_sales'] ?? 0)));
            $printer->text(sprintf("%-30s %16s\n", 'Net Paid Amount', $formatMoney($sales['total_sales'] ?? 0)));
            $printer->text(sprintf("%-30s %16s\n", 'Balance', $formatMoney(0)));
            $printer->text("\n");

            // ============== PAYMENT METHOD BREAKDOWN ==============
            $printer->text(sprintf("%-30s %16s\n", 'Net Cash Receipts', $formatMoney($cash['cash_sales'] ?? 0)));

            if (! empty($data['payment_methods'])) {
                foreach ($data['payment_methods'] as $pm) {
                    if (strtolower($pm['method']) !== 'cash') {
                        $printer->text(sprintf("%-30s %16s\n", 'Net '.$pm['method'].' Receipts', $formatMoney($pm['net'] ?? 0)));
                    }
                }

                $onlineTotal = 0;
                foreach ($data['payment_methods'] as $pm) {
                    if (in_array(strtolower($pm['method']), ['online', 'card'])) {
                        $onlineTotal += ($pm['net'] ?? 0);
                    }
                }
                $printer->text(sprintf("%-30s %16s\n", 'Net Online Payment Receipts', $formatMoney($onlineTotal)));
            }

            $printer->text(sprintf("%-30s %16s\n", 'Net Other Receipts', '£0.00'));
            $printer->text("\n");
            $printer->text(sprintf("%-30s %16s\n", 'Unpaid Sales Count', '0'));
            $printer->text(sprintf("%-30s %16s\n", 'Unpaid Sales Amount', $formatMoney(0)));
            $printer->text("\n");

            // ============== SALES VAT ==============
            $printer->setJustification(Printer::JUSTIFY_CENTER);
            $printer->setEmphasis(true);
            $printer->text("SALES VAT\n");
            $printer->setEmphasis(false);
            $printer->text(str_repeat('=', $charsPerLine)."\n");
            $printer->setJustification(Printer::JUSTIFY_LEFT);

            $printer->text(sprintf("%-10s %10s %12s %12s\n", 'Tax %', 'Count', 'Sale*', 'Tax*'));
            $printer->text(str_repeat('-', $charsPerLine)."\n");
            $totalOrders = $sales['total_orders'] ?? 0;
            $subtotal = $sales['subtotal'] ?? 0;
            $totalTax = $sales['total_tax'] ?? 0;

            $printer->text(sprintf("%-10s %10s %12s %12s\n", '0.00 %', '1', '£1.00', '£0.00'));
            $printer->text(sprintf(
                "%-10s %10s %12s %12s\n",
                '20.00 %',
                max(0, $totalOrders - 1),
                $formatMoney(max(0, $subtotal - 1)),
                $formatMoney($totalTax)
            ));
            $printer->text(str_repeat('-', $charsPerLine)."\n");
            $printer->setEmphasis(true);
            $printer->text(sprintf(
                "%-10s %10s %12s %12s\n",
                'Total',
                $totalOrders,
                $formatMoney($subtotal),
                $formatMoney($totalTax)
            ));
            $printer->setEmphasis(false);
            $printer->text("\n");

            // ============== SALE REFUNDS ==============
            $printer->setJustification(Printer::JUSTIFY_CENTER);
            $printer->setEmphasis(true);
            $printer->text("SALE REFUNDS\n");
            $printer->setEmphasis(false);
            $printer->text(str_repeat('=', $charsPerLine)."\n");
            $printer->setJustification(Printer::JUSTIFY_LEFT);
            $printer->text("No Data Available\n");
            $printer->text("\n");

            // ============== SALE CANCELLATIONS ==============
            $printer->setJustification(Printer::JUSTIFY_CENTER);
            $printer->setEmphasis(true);
            $printer->text("SALE CANCELLATIONS\n");
            $printer->setEmphasis(false);
            $printer->text(str_repeat('=', $charsPerLine)."\n");
            $printer->setJustification(Printer::JUSTIFY_LEFT);
            $printer->text("No Data Available\n");
            $printer->text("\n");

            // ============== VENUE SALES ==============
            $printer->setJustification(Printer::JUSTIFY_CENTER);
            $printer->setEmphasis(true);
            $printer->text("VENUE SALES\n");
            $printer->setEmphasis(false);
            $printer->text(str_repeat('=', $charsPerLine)."\n");
            $printer->setJustification(Printer::JUSTIFY_LEFT);

            if (! empty($data['venue_sales'])) {
                $printer->text(sprintf("%-26s %10s %10s\n", 'Venue', 'Count', 'Amount'));
                $printer->text(str_repeat('-', $charsPerLine)."\n");

                $venueTotal = 0;
                $venueCount = 0;
                foreach ($data['venue_sales'] as $v) {
                    $venueName = mb_strimwidth($v['venue'] ?? 'Unknown', 0, 26, '...');
                    $printer->text(sprintf(
                        "%-26s %10s %10s\n",
                        $venueName,
                        $v['count'] ?? 0,
                        $formatMoney($v['amount'] ?? 0)
                    ));
                    $venueTotal += ($v['amount'] ?? 0);
                    $venueCount += ($v['count'] ?? 0);
                }
                $printer->text(str_repeat('-', $charsPerLine)."\n");
                $printer->setEmphasis(true);
                $printer->text(sprintf("%-26s %10s %10s\n", 'Total', $venueCount, $formatMoney($venueTotal)));
                $printer->setEmphasis(false);
            } else {
                $printer->text("No Data Available\n");
            }
            $printer->text("\n");

            // ============== DISPATCH SALES ==============
            $printer->setJustification(Printer::JUSTIFY_CENTER);
            $printer->setEmphasis(true);
            $printer->text("DISPATCH SALES\n");
            $printer->setEmphasis(false);
            $printer->text(str_repeat('=', $charsPerLine)."\n");
            $printer->setJustification(Printer::JUSTIFY_LEFT);

            if (! empty($data['dispatch_sales'])) {
                $printer->text(sprintf("%-26s %10s %10s\n", 'Dispatch Type', 'Count', 'Amount'));
                $printer->text(str_repeat('-', $charsPerLine)."\n");

                $dispatchTotal = 0;
                $dispatchCount = 0;
                foreach ($data['dispatch_sales'] as $d) {
                    $typeName = mb_strimwidth($d['type'] ?? 'Unknown', 0, 26, '...');
                    $printer->text(sprintf(
                        "%-26s %10s %10s\n",
                        $typeName,
                        $d['count'] ?? 0,
                        $formatMoney($d['amount'] ?? 0)
                    ));
                    $dispatchTotal += ($d['amount'] ?? 0);
                    $dispatchCount += ($d['count'] ?? 0);
                }
                $printer->text(str_repeat('-', $charsPerLine)."\n");
                $printer->setEmphasis(true);
                $printer->text(sprintf("%-26s %10s %10s\n", 'Total', $dispatchCount, $formatMoney($dispatchTotal)));
                $printer->setEmphasis(false);
            } else {
                $printer->text("No Data Available\n");
            }
            $printer->text("\n");

            // ============== PAYMENT METHOD SALES ==============
            $printer->setJustification(Printer::JUSTIFY_CENTER);
            $printer->setEmphasis(true);
            $printer->text("PAYMENT METHOD SALES\n");
            $printer->setEmphasis(false);
            $printer->text(str_repeat('=', $charsPerLine)."\n");
            $printer->setJustification(Printer::JUSTIFY_LEFT);

            if (! empty($data['payment_methods'])) {
                $printer->text(sprintf("%-14s %10s %10s %10s\n", 'Pay Method', 'Receipts', 'Refunds', 'Net'));
                $printer->text(str_repeat('-', $charsPerLine)."\n");

                $totalReceipts = 0;
                $totalRefunds = 0;
                $totalNet = 0;
                foreach ($data['payment_methods'] as $pm) {
                    $methodName = mb_strimwidth($pm['method'] ?? 'Unknown', 0, 14, '...');
                    $receipts = $pm['receipts'] ?? 0;
                    $refunds = $pm['refunds'] ?? 0;
                    $net = $pm['net'] ?? 0;

                    $printer->text(sprintf(
                        "%-14s %10s %10s %10s\n",
                        $methodName,
                        $formatMoney($receipts),
                        $formatMoney($refunds),
                        $formatMoney($net)
                    ));
                    $totalReceipts += $receipts;
                    $totalRefunds += $refunds;
                    $totalNet += $net;
                }
                $printer->text(str_repeat('-', $charsPerLine)."\n");
                $printer->setEmphasis(true);
                $printer->text(sprintf(
                    "%-14s %10s %10s %10s\n",
                    'Total',
                    $formatMoney($totalReceipts),
                    $formatMoney($totalRefunds),
                    $formatMoney($totalNet)
                ));
                $printer->setEmphasis(false);
            } else {
                $printer->text("No Data Available\n");
            }
            $printer->text("\n");

            // ============== MENU CATEGORIES SUMMARY ==============
            $printer->setJustification(Printer::JUSTIFY_CENTER);
            $printer->setEmphasis(true);
            $printer->text("MENU CATEGORIES SUMMARY\n");
            $printer->setEmphasis(false);
            $printer->text(str_repeat('=', $charsPerLine)."\n");
            $printer->setJustification(Printer::JUSTIFY_LEFT);

            if (! empty($data['menu_category_summary'])) {
                $printer->text(sprintf("%-26s %10s %10s\n", 'Menu Category', 'Count', 'Amount*'));
                $printer->text(str_repeat('-', $charsPerLine)."\n");

                $catTotal = 0;
                $catCount = 0;
                foreach ($data['menu_category_summary'] as $cat) {
                    $catName = mb_strimwidth($cat['category'] ?? 'Unknown', 0, 26, '...');
                    $printer->text(sprintf(
                        "%-26s %10s %10s\n",
                        $catName,
                        $cat['count'] ?? 0,
                        $formatMoney($cat['amount'] ?? 0)
                    ));
                    $catTotal += ($cat['amount'] ?? 0);
                    $catCount += ($cat['count'] ?? 0);
                }
                $printer->text(str_repeat('-', $charsPerLine)."\n");
                $printer->setEmphasis(true);
                $printer->text(sprintf("%-26s %10s %10s\n", 'Total', $catCount, $formatMoney($catTotal)));
                $printer->setEmphasis(false);
            } else {
                $printer->text("No Data Available\n");
            }
            $printer->text("\n");

            // ============== COVERS SALES SUMMARY ==============
            $printer->setJustification(Printer::JUSTIFY_CENTER);
            $printer->setEmphasis(true);
            $printer->text("COVERS SALES SUMMARY\n");
            $printer->setEmphasis(false);
            $printer->text(str_repeat('=', $charsPerLine)."\n");
            $printer->setJustification(Printer::JUSTIFY_LEFT);

            $totalCovers = $data['covers_summary']['total_covers'] ?? 0;
            $avgRevenue = $data['covers_summary']['avg_revenue_per_cover'] ?? 0;

            $printer->text(sprintf("%-30s %16s\n", 'Total number of covers', $totalCovers));
            $printer->text(sprintf("%-30s %16s\n", 'Average revenue per cover', $formatMoney($avgRevenue)));
            $printer->text("\n");

            // ============== SALE DISCOUNTS ==============
            $printer->setJustification(Printer::JUSTIFY_CENTER);
            $printer->setEmphasis(true);
            $printer->text("SALE DISCOUNTS\n");
            $printer->setEmphasis(false);
            $printer->text(str_repeat('=', $charsPerLine)."\n");
            $printer->setJustification(Printer::JUSTIFY_LEFT);

            if (! empty($data['discounts_summary'])) {
                $printer->text(sprintf("%-26s %10s %10s\n", 'Discount Type', 'Count', 'Amount'));
                $printer->text(str_repeat('-', $charsPerLine)."\n");

                $discTotal = 0;
                $discCount = 0;
                foreach ($data['discounts_summary'] as $d) {
                    $discType = mb_strimwidth($d['type'] ?? 'Unknown', 0, 26, '...');
                    $printer->text(sprintf(
                        "%-26s %10s %10s\n",
                        $discType,
                        $d['count'] ?? 0,
                        $formatMoney($d['amount'] ?? 0)
                    ));
                    $discTotal += ($d['amount'] ?? 0);
                    $discCount += ($d['count'] ?? 0);
                }
                $printer->text(str_repeat('-', $charsPerLine)."\n");
                $printer->setEmphasis(true);
                $printer->text(sprintf("%-26s %10s %10s\n", 'Total', $discCount, $formatMoney($discTotal)));
                $printer->setEmphasis(false);
            } else {
                $printer->text("No Data Available\n");
            }
            $printer->text("\n");

            // ============== SALE CHARGES ==============
            $printer->setJustification(Printer::JUSTIFY_CENTER);
            $printer->setEmphasis(true);
            $printer->text("SALE CHARGES\n");
            $printer->setEmphasis(false);
            $printer->text(str_repeat('=', $charsPerLine)."\n");
            $printer->setJustification(Printer::JUSTIFY_LEFT);

            if (! empty($data['charges_summary'])) {
                $printer->text(sprintf("%-18s %8s %10s %10s\n", 'Scheme', 'Count', 'Amount', 'Tax'));
                $printer->text(str_repeat('-', $charsPerLine)."\n");

                $chargeTotal = 0;
                $chargeCount = 0;
                $chargeTax = 0;
                foreach ($data['charges_summary'] as $c) {
                    $schemeName = mb_strimwidth($c['scheme'] ?? 'Unknown', 0, 18, '...');
                    $printer->text(sprintf(
                        "%-18s %8s %10s %10s\n",
                        $schemeName,
                        $c['count'] ?? 0,
                        $formatMoney($c['amount'] ?? 0),
                        $formatMoney($c['tax'] ?? 0)
                    ));
                    $chargeTotal += ($c['amount'] ?? 0);
                    $chargeCount += ($c['count'] ?? 0);
                    $chargeTax += ($c['tax'] ?? 0);
                }
                $printer->text(str_repeat('-', $charsPerLine)."\n");
                $printer->setEmphasis(true);
                $printer->text(sprintf(
                    "%-18s %8s %10s %10s\n",
                    'Total',
                    $chargeCount,
                    $formatMoney($chargeTotal),
                    $formatMoney($chargeTax)
                ));
                $printer->setEmphasis(false);
            } else {
                $printer->text("No Data Available\n");
            }
            $printer->text("\n");

            // === TOP SELLING ITEMS ===
            if (! empty($data['top_items'])) {
                $printer->setJustification(Printer::JUSTIFY_CENTER);
                $printer->setEmphasis(true);
                $printer->text("TOP SELLING ITEMS\n");
                $printer->setEmphasis(false);
                $printer->text(str_repeat('=', $charsPerLine)."\n");
                $printer->setJustification(Printer::JUSTIFY_LEFT);

                foreach (array_slice($data['top_items'], 0, 5) as $idx => $item) {
                    $itemName = mb_strimwidth($item['name'] ?? 'Unknown', 0, 25, '...');
                    $printer->text(sprintf(
                        "%d. %-25s %6s %12s\n",
                        $idx + 1,
                        $itemName,
                        $item['total_qty'] ?? 0,
                        $formatMoney($item['total_revenue'] ?? 0)
                    ));
                }
                $printer->text("\n");
            }

            // === FOOTER ===
            $printer->setJustification(Printer::JUSTIFY_CENTER);
            $printer->feed(1);
            $printer->text(str_repeat('=', $charsPerLine)."\n");
            $printer->text("--- END OF Z REPORT ---\n");
            $printer->text(date('Y-m-d H:i:s')."\n");
            $printer->feed(3);

            $printer->cut();
            $printer->close();

            return response()->json(['success' => true]);
        } catch (\Exception $e) {
            \Log::error('Z Report Print Error: '.$e->getMessage());
            \Log::error('Stack trace: '.$e->getTraceAsString());

            return response()->json([
                'success' => false,
                'message' => 'No printer is connected for Z report.',
            ]);
        }
    }

    private function prepareLogo($businessLogo)
    {
        if (empty($businessLogo)) {
            return null;
        }

        try {
            // Get local storage path from URL
            $parsedPath = parse_url($businessLogo, PHP_URL_PATH);
            $relativePath = str_replace('/storage/', '', $parsedPath);
            $localLogoPath = storage_path('app/public/'.$relativePath);

            if (! file_exists($localLogoPath)) {
                return null;
            }

            // Processed images folder
            $processedDir = storage_path('app/public/processed');
            if (! file_exists($processedDir)) {
                mkdir($processedDir, 0775, true);
            }

            // ✅ Use a hash of the logo content — ensures it updates when the logo changes
            $hash = md5_file($localLogoPath);
            $processedLogoPath = $processedDir."/logo_{$hash}.png";

            // If not already resized, make a new one
            if (! file_exists($processedLogoPath)) {
                $srcData = file_get_contents($localLogoPath);
                $src = imagecreatefromstring($srcData);
                if (! $src) {
                    return null;
                }

                // Resize smaller
                $size = min(imagesx($src), imagesy($src));
                $thumb = imagecreatetruecolor(160, 160);
                imagesavealpha($thumb, true);
                $transparent = imagecolorallocatealpha($thumb, 255, 255, 255, 127);
                imagefill($thumb, 0, 0, $transparent);
                imagecopyresampled($thumb, $src, 0, 0, 0, 0, 160, 160, $size, $size);
                imagepng($thumb, $processedLogoPath);

                imagedestroy($thumb);
                imagedestroy($src);
            }

            // ✅ Delete any old cached logos
            foreach (glob("{$processedDir}/logo_*.png") as $file) {
                if ($file !== $processedLogoPath) {
                    unlink($file);
                }
            }

            return $processedLogoPath;
        } catch (\Exception $e) {
            \Log::error('Error preparing logo: '.$e->getMessage());

            return null;
        }
    }
}
