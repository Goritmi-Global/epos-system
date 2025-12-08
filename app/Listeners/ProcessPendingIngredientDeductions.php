<?php

namespace App\Listeners;

use App\Events\StockEntryCreated;
use App\Models\PendingIngredientDeduction;
use App\Models\StockEntry;
use App\Models\StockOutAllocation;
use App\Models\InventoryItem;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

class ProcessPendingIngredientDeductions
{
    public function __construct()
    {
        Log::info('🏗️ Listener Loaded: ProcessPendingIngredientDeductions');
    }

    /**
     * Handle the event.
     */
    public function handle(StockEntryCreated $event)
    {
        Log::info('🎯 Listener Triggered: StockEntryCreated');

        $stockEntry = $event->stockEntry;

        if (!$stockEntry) {
            Log::warning('⚠️ No stock entry received in event — exiting');
            return;
        }

        Log::info('📦 Stock Entry Details', [
            'id' => $stockEntry->id,
            'product_id' => $stockEntry->product_id,
            'stock_type' => $stockEntry->stock_type,
            'quantity' => $stockEntry->quantity,
        ]);

        // Only process when stock is added
        if (strtolower($stockEntry->stock_type) !== 'stockin') {
            Log::info('⏭️ Skipping — not a stockin entry');
            return;
        }

        $inventoryItemId = $stockEntry->product_id;

        Log::info('🔎 Finding pending deductions…', [
            'inventory_item_id' => $inventoryItemId
        ]);

        $pendingDeductions = PendingIngredientDeduction::where('status', 'pending')
            ->where('inventory_item_id', $inventoryItemId)
            ->orderBy('created_at', 'asc')
            ->get();

        if ($pendingDeductions->isEmpty()) {
            Log::info('👍 No pending deductions found — exiting');
            return;
        }

        Log::info('📊 Pending deductions found', [
            'count' => $pendingDeductions->count(),
            'ids' => $pendingDeductions->pluck('id')->toArray(),
        ]);

        DB::beginTransaction();

        try {
            $inventoryItem = InventoryItem::find($inventoryItemId);

            if (!$inventoryItem) {
                Log::error('❌ Inventory item not found — aborting');
                DB::rollBack();
                return;
            }

            // Get available stock-in batches using FIFO (earliest expiry first)
            $batches = StockEntry::query()
                ->where('product_id', $inventoryItemId)
                ->where('stock_type', 'stockin')
                ->select(['id', 'quantity', 'price', 'expiry_date', 'created_at'])
                ->orderByRaw('expiry_date IS NULL, expiry_date ASC, created_at ASC, id ASC')
                ->lockForUpdate()
                ->get();

            Log::info('📦 Stock-in batches found', [
                'count' => $batches->count(),
                'batch_ids' => $batches->pluck('id')->toArray(),
            ]);

            $processedCount = 0;
            $fulfilledCount = 0;
            $partialCount = 0;

            foreach ($pendingDeductions as $index => $deduction) {
                $pendingQty = (float)$deduction->pending_quantity;
                $remainingToFulfill = $pendingQty;

                Log::info("🔄 Processing deduction #{$index}", [
                    'deduction_id' => $deduction->id,
                    'pending_qty' => $pendingQty,
                    'order_id' => $deduction->order_id,
                ]);

                // Create the stockout entry
                $stockOut = StockEntry::create([
                    'product_id'     => $inventoryItem->id,
                    'name'           => $deduction->inventory_item_name,
                    'category_id'    => $inventoryItem->category_id,
                    'supplier_id'    => $inventoryItem->supplier_id,
                    'quantity'       => 0, // Will be updated after allocations
                    'value'          => 0,
                    'operation_type' => 'pos_pending_fulfill',
                    'stock_type'     => 'stockout',
                    'description'    => "Auto fulfill pending deduction for Order #{$deduction->order_id}",
                    'user_id'        => auth()->id() ?? 1,
                ]);

                Log::info('📦 Stockout entry created', [
                    'stockout_id' => $stockOut->id,
                ]);

                $totalDeducted = 0;
                $totalCost = 0;

                // Allocate from batches using FIFO
                foreach ($batches as $batch) {
                    if ($remainingToFulfill <= 0) {
                        break;
                    }

                    // Calculate remaining stock in this batch
                    $allocated = (float) StockOutAllocation::where('stock_in_entry_id', $batch->id)
                        ->sum('quantity');
                    $available = max(0, (float)$batch->quantity - $allocated);

                    if ($available <= 0) {
                        continue;
                    }

                    $deductQty = min($available, $remainingToFulfill);

                    Log::info("  📥 Allocating from batch", [
                        'batch_id' => $batch->id,
                        'available' => $available,
                        'allocating' => $deductQty,
                        'expiry_date' => $batch->expiry_date,
                    ]);

                    // Create allocation record (FIFO tracking)
                    StockOutAllocation::create([
                        'stock_out_entry_id' => $stockOut->id,
                        'stock_in_entry_id'  => $batch->id,
                        'product_id'         => $inventoryItemId,
                        'quantity'           => $deductQty,
                        'unit_price'         => $batch->price ?? 0,
                        'expiry_date'        => $batch->expiry_date,
                    ]);

                    $totalDeducted += $deductQty;
                    $totalCost += ((float)$batch->price ?? 0) * $deductQty;
                    $remainingToFulfill -= $deductQty;

                    Log::info("  ✅ Allocation created", [
                        'deducted' => $deductQty,
                        'remaining_to_fulfill' => $remainingToFulfill,
                    ]);
                }

                // Update stockout entry with totals
                $stockOut->update([
                    'quantity' => $totalDeducted,
                    'value' => $totalCost,
                ]);

                Log::info('📦 Stockout finalized', [
                    'total_quantity' => $totalDeducted,
                    'total_value' => $totalCost,
                ]);

                // Update deduction status
                if ($totalDeducted >= $pendingQty) {
                    // Fully fulfilled
                    $deduction->update([
                        'status'       => 'fulfilled',
                        'fulfilled_at' => now(),
                        'fulfilled_by' => auth()->id() ?? 1,
                    ]);

                    $fulfilledCount++;

                    Log::info('✅ Deduction fully fulfilled', [
                        'deduction_id' => $deduction->id,
                    ]);

                } elseif ($totalDeducted > 0) {
                    // Partially fulfilled
                    $deduction->update([
                        'pending_quantity' => $pendingQty - $totalDeducted,
                        'notes' => ($deduction->notes ?? '') 
                            . " | Partial fulfillment: {$totalDeducted} on " . now(),
                    ]);

                    $partialCount++;

                    Log::info('⚠️ Deduction partially fulfilled', [
                        'deduction_id' => $deduction->id,
                        'fulfilled' => $totalDeducted,
                        'remaining' => $pendingQty - $totalDeducted,
                    ]);
                } else {
                    Log::warning('⚠️ No stock available for deduction', [
                        'deduction_id' => $deduction->id,
                    ]);
                }

                if ($totalDeducted > 0) {
                    $processedCount++;
                }

                // Break if no more stock available in any batch
                $totalAvailable = 0;
                foreach ($batches as $batch) {
                    $allocated = (float) StockOutAllocation::where('stock_in_entry_id', $batch->id)
                        ->sum('quantity');
                    $totalAvailable += max(0, (float)$batch->quantity - $allocated);
                }

                if ($totalAvailable <= 0) {
                    Log::warning('⚠️ All stock depleted — stopping', [
                        'remaining_deductions' => $pendingDeductions->count() - $index - 1
                    ]);
                    break;
                }
            }

            DB::commit();

            // Refresh inventory to get updated stock
            $inventoryItem->refresh();

            Log::info('🎉 Pending deductions processing completed', [
                'total'           => $pendingDeductions->count(),
                'processed'       => $processedCount,
                'fully_fulfilled' => $fulfilledCount,
                'partial'         => $partialCount,
                'current_stock'   => $inventoryItem->stock,
            ]);

        } catch (\Exception $e) {

            DB::rollBack();

            Log::error('❌ Error while processing deductions', [
                'message' => $e->getMessage(),
                'file'    => $e->getFile(),
                'line'    => $e->getLine(),
                'trace'   => $e->getTraceAsString(),
            ]);
        }

        Log::info('🏁 Listener completed.');
    }
}