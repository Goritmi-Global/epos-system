<?php

namespace App\Http\Controllers\Printer;

use Illuminate\Http\Request;
use App\Http\Controllers\Controller;

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

                        if (stripos($printer['PortName'] ?? '', 'USB') !== false ||
                            stripos($printer['DriverName'] ?? '', 'BlackCopper') !== false ||
                            stripos($printer['Name'] ?? '', 'BlackCopper') !== false ||
                            stripos($printer['DriverName'] ?? '', 'Thermal') !== false ||
                            stripos($printer['DriverName'] ?? '', 'POS') !== false) {
                            $isUsb = true;
                        }

                        if ($isUsb && $usbDevices) {
                            foreach ($usbDevices as $usb) {
                                if (stripos($usb['FriendlyName'] ?? '', 'Print') !== false ||
                                    stripos($usb['FriendlyName'] ?? '', 'BlackCopper') !== false) {
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
}
