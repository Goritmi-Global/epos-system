<?php

namespace App\Http\Controllers\Printer;

use Illuminate\Http\Request;
use App\Http\Controllers\Controller;
use Mike42\Escpos\Printer;
use Mike42\Escpos\PrintConnectors\WindowsPrintConnector;
use Exception;

use App\Models\ProfileStep2;
use App\Models\User;
use App\Helpers\UploadHelper;
use App\Models\ProfileStep6;
use Mike42\Escpos\EscposImage;

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

                if ($usbDevices && !isset($usbDevices[0])) {
                    $usbDevices = [$usbDevices];
                }

                /* ------------------------------------------
                 * 2️⃣  Get installed printers
                 * ------------------------------------------ */
                $installedCmd = 'powershell -Command "Get-Printer | Select-Object Name, DriverName, PortName | ConvertTo-Json"';
                exec($installedCmd, $installedOutput);
                $installedJson = implode('', $installedOutput);
                $installedPrinters = json_decode($installedJson, true);

                if ($installedPrinters && !isset($installedPrinters[0])) {
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
                                'status' => $isConnected ? 'OK' : 'Disconnected'
                            ];
                        }
                    }
                }
            }

            if (empty($printers)) {
                return response()->json([
                    'success' => true,
                    'data' => [],
                    'message' => 'No USB printers currently connected.'
                ]);
            }

            return response()->json([
                'success' => true,
                'data' => $printers
            ]);
        } catch (\Throwable $th) {
            return response()->json([
                'success' => false,
                'message' => 'Error fetching printers: ' . $th->getMessage()
            ], 500);
        }
    }

    public function printReceipt(Request $request)
    {
        try {
            $order = $request->input('order');
            if (!$order) {
                return response()->json(['success' => false, 'message' => 'No order data received']);
            }

            // === FETCH BUSINESS INFO (ProfileStep2) ===
            $superAdmin = User::where('is_first_super_admin', true)->first();
            $onboardingUserId = $superAdmin ? $superAdmin->id : auth()->id();

            $business = ProfileStep2::where('user_id', $onboardingUserId)
                ->select('business_name', 'phone', 'address', 'upload_id')
                ->first();
            // === FETCH PRINTER INFO (ProfileStep6) ===
            $profile = ProfileStep6::where('user_id', $onboardingUserId)->first();
            $customerPrinter = $profile->customer_printer ?? 'Default_Customer_Printer';

            $businessName = $business->business_name ?? 'Business Name';
            $businessPhone = $business->phone ?? '+44 0000 000000';
            $businessAddress = $business->address ?? 'Unknown Address';
            $businessLogo = $business && $business->upload_id
                ? UploadHelper::url($business->upload_id)
                : null;


            // === CONNECT TO PRINTER ===
            $connector = new WindowsPrintConnector($customerPrinter);
            $printer = new Printer($connector);

            // === CONFIG ===
            $charsPerLine = 48;
            $formatMoney = fn($v) => "£" . number_format((float)$v, 2);

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
                    \Log::warning('Logo print error: ' . $e->getMessage());
                }
            }

            // === BUSINESS INFO ===
            $printer->setEmphasis(true);
            $printer->text(strtoupper($businessName) . "\n");
            $printer->setEmphasis(false);
            $printer->text($businessPhone . "\n");
            $printer->text($businessAddress . "\n");
            $printer->text(str_repeat("=", $charsPerLine) . "\n");
            $printer->setEmphasis(true);
            $printer->text("CUSTOMER RECEIPT\n");
            $printer->setEmphasis(false);
            $printer->text(str_repeat("=", $charsPerLine) . "\n\n");

            // === ORDER INFO ===
            $printer->setJustification(Printer::JUSTIFY_LEFT);
            $printer->text(sprintf("%-16s %30s\n", "Date:", $order['order_date'] ?? date('Y-m-d')));
            $printer->text(sprintf("%-16s %30s\n", "Time:", $order['order_time'] ?? date('H:i:s')));
            $printer->text(sprintf("%-16s %30s\n", "Customer:", $order['customer_name'] ?? 'Walk In'));
            $printer->text(sprintf("%-16s %30s\n", "Order Type:", $order['order_type'] ?? 'Collection'));

            if (!empty($order['note'])) {
                $printer->text(sprintf("%-16s %30s\n", "Note:", $order['note']));
            }

            // === PAYMENT INFO ===
            $paymentType = strtolower($order['payment_type'] ?? 'cash');
            $cashAmount = (float)($order['cash_received'] ?? 0);
            $cardAmount = (float)($order['card_amount'] ?? $order['cardPayment'] ?? 0);
            $totalAmount = (float)($order['total_amount'] ?? $order['sub_total'] ?? 0);

            if ($paymentType === 'split') {
                if ($cardAmount <= 0 && $cashAmount > 0) {
                    $cardAmount = $totalAmount - $cashAmount;
                }
                $printer->text(sprintf("%-16s %30s\n", "Payment Type:", "Split"));
                $printer->text(sprintf("%-16s %30s\n", "Cash Payment:", $formatMoney($cashAmount)));
                $printer->text(sprintf("%-16s %30s\n", "Card Payment:", $formatMoney($cardAmount)));
            } elseif ($paymentType === 'card' || $paymentType === 'stripe') {
                $printer->text(sprintf("%-16s %30s\n", "Payment Type:", "Card"));
            } else {
                $printer->text(sprintf("%-16s %30s\n", "Payment Type:", "Cash"));
            }

            $printer->text(str_repeat("-", $charsPerLine) . "\n");

            // === ITEM TABLE HEADER ===
            $colQty = 4;
            $colPrice = 10;
            $colTotal = 10;
            $colItem = $charsPerLine - ($colQty + $colPrice + $colTotal + 3);

            $printer->setEmphasis(true);
            $printer->text(
                sprintf(
                    "%-" . $colItem . "s %{$colQty}s %{$colPrice}s %{$colTotal}s\n",
                    "Item",
                    "Qty",
                    str_pad("Price", $colPrice, " ", STR_PAD_BOTH),
                    str_pad("Total", $colTotal, " ", STR_PAD_BOTH)
                )
            );
            $printer->setEmphasis(false);
            $printer->text(str_repeat("-", $charsPerLine) . "\n");

            // === ITEMS ===
            if (!empty($order['items'])) {
                foreach ($order['items'] as $item) {
                    $title = trim($item['title'] ?? 'Item');
                    $qty = (int)($item['quantity'] ?? $item['qty'] ?? 1);
                    $unitPrice = (float)($item['unit_price'] ?? $item['price'] ?? 0);
                    $lineTotal = $unitPrice * $qty;

                    $priceStr = $formatMoney($unitPrice);
                    $totalStr = $formatMoney($lineTotal);

                    $wrapped = explode("\n", wordwrap($title, $colItem, "\n", true));
                    $firstLine = array_shift($wrapped);

                    $printer->text(sprintf(
                        "%-" . $colItem . "s %{$colQty}s %{$colPrice}s %{$colTotal}s\n",
                        mb_strimwidth($firstLine, 0, $colItem, ""),
                        $qty,
                        $priceStr,
                        $totalStr
                    ));

                    foreach ($wrapped as $more) {
                        $printer->text(sprintf("%-" . $colItem . "s\n", mb_strimwidth($more, 0, $colItem, "")));
                    }
                }
            }

            $printer->text(str_repeat("-", $charsPerLine) . "\n");

            // === TOTALS ===
            $subtotal = $formatMoney($order['sub_total'] ?? 0);
            $total = $formatMoney($order['total_amount'] ?? $order['sub_total'] ?? 0);
            $cash = $formatMoney($order['cash_received'] ?? 0);

            $printer->text(sprintf("%-35s %12s\n", "Subtotal:", $subtotal));
            $printer->text(sprintf("%-35s %12s\n\n", "Total:", $total));

            // if ($paymentType === 'split') {
            //     $printer->text(sprintf("%-35s %12s\n", "Cash Received:", $formatMoney($cashAmount)));
            //     $printer->text(sprintf("%-35s %12s\n", "Card Received:", $formatMoney($cardAmount)));
            // } elseif ($paymentType === 'cash') {
            //     $printer->text(sprintf("%-35s %12s\n", "Cash Received:", $cash));
            // }

            if ($paymentType === 'cash') {
                $printer->text(sprintf("%-35s %12s\n", "Cash Received:", $cash));
            }

            // === FOOTER ===
            // $printer->setJustification(Printer::JUSTIFY_CENTER);
            // $printer->feed(2);
            // $printer->text("Customer Copy - Thank you for your purchase!\n");
            // $printer->feed(1);

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
            if (!$order) {
                return response()->json(['success' => false, 'message' => 'No order data received']);
            }

            // === FETCH BUSINESS INFO ===
            $superAdmin = User::where('is_first_super_admin', true)->first();
            $onboardingUserId = $superAdmin ? $superAdmin->id : auth()->id();

            $business = ProfileStep2::where('user_id', $onboardingUserId)
                ->select('business_name', 'phone', 'address', 'upload_id')
                ->first();
            // === FETCH KOT PRINTER INFO (ProfileStep6) ===
            $profile = ProfileStep6::where('user_id', $onboardingUserId)->first();
            $kotPrinter = $profile->kot_printer ?? 'Default_KOT_Printer';

            $businessName = $business->business_name ?? 'Business Name';
            $businessPhone = $business->phone ?? '+44 0000 000000';
            $businessAddress = $business->address ?? 'Unknown Address';
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
                    \Log::warning('KOT logo error: ' . $e->getMessage());
                }
            }

            // === BUSINESS INFO ===
            $printer->setEmphasis(true);
            $printer->text(strtoupper($businessName) . "\n");
            $printer->setEmphasis(false);
            $printer->text($businessPhone . "\n");
            $printer->text($businessAddress . "\n");
            $printer->text(str_repeat("=", $charsPerLine) . "\n");
            $printer->setEmphasis(true);
            $printer->text("KITCHEN ORDER TICKET\n");
            $printer->setEmphasis(false);
            $printer->text(str_repeat("=", $charsPerLine) . "\n\n");

            // === ORDER INFO ===
            $printer->setJustification(Printer::JUSTIFY_LEFT);
            $printer->text(sprintf("%-16s %30s\n", "Date:", $order['order_date'] ?? date('Y-m-d')));
            $printer->text(sprintf("%-16s %30s\n", "Time:", $order['order_time'] ?? date('H:i:s')));
            $printer->text(sprintf("%-16s %30s\n", "Customer:", $order['customer_name'] ?? 'Walk In'));
            $printer->text(sprintf("%-16s %30s\n", "Order Type:", $order['order_type'] ?? 'In-Store'));

            if (!empty($order['kitchen_note'])) {
                $printer->text(sprintf("%-16s %30s\n", "Kitchen Note:", $order['kitchen_note']));
            }

            $printer->text(str_repeat("-", $charsPerLine) . "\n");

            // === ITEMS TABLE (NO PRICES) ===
            $colQty = 6;
            $colItem = $charsPerLine - ($colQty + 3);

            $printer->setEmphasis(true);
            $printer->text(sprintf("%-" . $colItem . "s %{$colQty}s\n", "Item", "Qty"));
            $printer->setEmphasis(false);
            $printer->text(str_repeat("-", $charsPerLine) . "\n");

            // === ITEMS ===
            if (!empty($order['items'])) {
                foreach ($order['items'] as $item) {
                    $title = trim($item['title'] ?? 'Item');
                    $qty = (int)($item['quantity'] ?? $item['qty'] ?? 1);

                    $wrapped = explode("\n", wordwrap($title, $colItem, "\n", true));
                    $firstLine = array_shift($wrapped);

                    $printer->text(sprintf(
                        "%-" . $colItem . "s %{$colQty}s\n",
                        mb_strimwidth($firstLine, 0, $colItem, ""),
                        $qty
                    ));

                    foreach ($wrapped as $more) {
                        $printer->text(sprintf("%-" . $colItem . "s\n", mb_strimwidth($more, 0, $colItem, "")));
                    }
                }
            }

            $printer->text(str_repeat("-", $charsPerLine) . "\n\n");

            // === FOOTER ===
            $printer->setJustification(Printer::JUSTIFY_CENTER);
            $printer->text("Kitchen Copy - For Staff Use Only\n");
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

    private function prepareLogo($businessLogo)
    {
        if (empty($businessLogo)) return null;

        try {
            // Get local storage path from URL
            $parsedPath = parse_url($businessLogo, PHP_URL_PATH);
            $relativePath = str_replace('/storage/', '', $parsedPath);
            $localLogoPath = storage_path('app/public/' . $relativePath);

            if (!file_exists($localLogoPath)) return null;

            // Processed images folder
            $processedDir = storage_path('app/public/processed');
            if (!file_exists($processedDir)) {
                mkdir($processedDir, 0775, true);
            }

            // ✅ Use a hash of the logo content — ensures it updates when the logo changes
            $hash = md5_file($localLogoPath);
            $processedLogoPath = $processedDir . "/logo_{$hash}.png";

            // If not already resized, make a new one
            if (!file_exists($processedLogoPath)) {
                $srcData = file_get_contents($localLogoPath);
                $src = imagecreatefromstring($srcData);
                if (!$src) return null;

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
            \Log::error("Error preparing logo: " . $e->getMessage());
            return null;
        }
    }
}
