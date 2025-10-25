<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use App\Models\Shift;
use App\Models\PosOrder;
use App\Models\ShiftDetail;
use App\Models\ShiftInventorySnapshot;
use App\Models\ShiftInventorySnapshotDetail;
use App\Models\InventoryItem;
use Illuminate\Support\Facades\DB;

class AutoCloseShift extends Command
{
    protected $signature = 'shift:auto-close';
    protected $description = 'Automatically close all open shifts at the end of the day';

    public function handle()
    {
        $openShifts = Shift::where('status', 'open')->get();

        if ($openShifts->isEmpty()) {
            $this->info('No open shifts to close.');
            return;
        }

        foreach ($openShifts as $shift) {
            DB::transaction(function () use ($shift) {
                $totalSales = PosOrder::where('shift_id', $shift->id)
                    ->where('status', 'paid')
                    ->sum('total_amount');

                $closingCash = $shift->opening_cash + $totalSales;

                $shift->update([
                    'status'       => 'closed',
                    'end_time'     => now(),
                    'ended_by'     => 1, 
                    'closing_cash' => $closingCash,
                    'sales_total'  => $totalSales,
                ]);

                // Update shift details
                $shiftDetails = ShiftDetail::where('shift_id', $shift->id)->get();
                foreach ($shiftDetails as $detail) {
                    $userSales = PosOrder::where('shift_id', $shift->id)
                        ->where('status', 'paid')
                        ->where('user_id', $detail->user_id)
                        ->sum('total_amount');

                    $detail->update([
                        'sales_amount' => $userSales,
                        'left_at' => now(),
                    ]);
                }

                // Create end inventory snapshot
                $snapshot = ShiftInventorySnapshot::create([
                    'shift_id' => $shift->id,
                    'type'     => 'ended',
                    'user_id'  => 1,
                    'created_at' => now(),
                ]);

                $inventoryItems = InventoryItem::all();
                foreach ($inventoryItems as $item) {
                    ShiftInventorySnapshotDetail::create([
                        'snap_id' => $snapshot->id,
                        'item_id' => $item->id,
                        'stock_quantity' => $item->stock,
                    ]);
                }

                // 🔥 Logout all non-Super Admin users
                $usersToLogout = \App\Models\User::whereDoesntHave('roles', function ($q) {
                    $q->where('name', 'Super Admin');
                })->pluck('id');

                DB::table('sessions')->whereIn('user_id', $usersToLogout)->delete();

                $this->info("Shift #{$shift->id} closed automatically.");
            });
        }

        $this->info('✅ All open shifts have been automatically closed.');
    }
}