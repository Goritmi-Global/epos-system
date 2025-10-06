<?php

namespace App\Http\Controllers;

use Inertia\Inertia;
use Illuminate\Http\Request;
use App\Models\ProfileStep1;
use App\Models\ProfileStep2;
use App\Models\ProfileStep3;
use App\Models\ProfileStep4;
use App\Models\ProfileStep5;
use App\Models\ProfileStep6;
use App\Models\ProfileStep7;
use App\Models\ProfileStep8;
use App\Models\ProfileStep9;
use App\Models\InventoryItem;
use App\Models\StockEntry;
use Carbon\Carbon;

class DashboardController extends Controller
{
    public function index(Request $request)
    {
        $user = auth()->user();

        // ✅ Step check for onboarding
        $stepsCompleted = ProfileStep1::where('user_id', $user->id)->exists()
            && ProfileStep2::where('user_id', $user->id)->exists()
            && ProfileStep3::where('user_id', $user->id)->exists()
            && ProfileStep4::where('user_id', $user->id)->exists()
            && ProfileStep5::where('user_id', $user->id)->exists()
            && ProfileStep6::where('user_id', $user->id)->exists()
            && ProfileStep7::where('user_id', $user->id)->exists()
            && ProfileStep8::where('user_id', $user->id)->exists()
            && ProfileStep9::where('user_id', $user->id)->exists();

        if (! $stepsCompleted && $request->routeIs('dashboard')) {
            session()->forget('url.intended');
            return redirect()->route('onboarding.index');
        }

        // ✅ Inventory alert calculations with item details
        $inventories = InventoryItem::all();
        $stockEntries = StockEntry::all();

        $outOfStockItems = [];
        $lowStockItems = [];
        $expiredItems = [];
        $nearExpiryItems = [];

        foreach ($inventories as $item) {
            $stock = $item->stock ?? 0;
            $minAlert = $item->minAlert ?? 5;

            // Format: "Product Name (Current Stock: X)"
            if ($stock <= 0) {
                $outOfStockItems[] = $item->name . ' (Stock: 0)';
            } elseif ($stock < $minAlert) {
                $lowStockItems[] = $item->name . ' (Stock: ' . $stock . ', Min: ' . $minAlert . ')';
            }
        }

        foreach ($stockEntries as $entry) {
            if (!$entry->expiry_date) continue;

            $expiry = Carbon::parse($entry->expiry_date);
            $today = Carbon::today();

            // Get item name - adjust based on your actual relationship
            $itemName = $entry->product->name ?? $entry->item_name ?? 'Unknown Item';
            $batchNumber = $entry->batch_number ?? '';

            if ($expiry->lt($today)) {
                $expiredItems[] = $itemName . ' (Expired: ' . $expiry->format('M d, Y') . ')' . ($batchNumber ? ' - Batch: ' . $batchNumber : '');
            } elseif ($expiry->diffInDays($today) <= 15 && $expiry->gt($today)) {
                $daysLeft = $expiry->diffInDays($today);
                $nearExpiryItems[] = $itemName . ' (Expires in ' . $daysLeft . ' day' . ($daysLeft > 1 ? 's' : '') . ')' . ($batchNumber ? ' - Batch: ' . $batchNumber : '');
            }
        }

        $inventoryAlerts = [
            'outOfStock' => count($outOfStockItems),
            'lowStock' => count($lowStockItems),
            'expired' => count($expiredItems),
            'nearExpiry' => count($nearExpiryItems),
            'outOfStockItems' => $outOfStockItems,
            'lowStockItems' => $lowStockItems,
            'expiredItems' => $expiredItems,
            'nearExpiryItems' => $nearExpiryItems,
        ];

        return Inertia::render('Backend/Dashboard/Index', [
            'inventoryAlerts' => $inventoryAlerts,
            'showPopup' => session('show_inventory_popup', false), 
        ]);
    }
}