<?php

namespace App\Http\Controllers\Shifts;

use Illuminate\Http\Request;
use App\Http\Controllers\Controller;
use App\Models\InventoryItem;
use App\Models\PosOrder;
use Illuminate\Support\Facades\Auth;
use App\Models\Shift;
use App\Models\ShiftDetail;
use App\Models\ShiftInventorySnapshot;
use App\Models\ShiftInventorySnapshotDetail;
use App\Services\Shifts\ShiftManagementService;
use Illuminate\Support\Facades\DB;
use Inertia\Inertia;

class ShiftManagementController extends Controller
{
    protected $shiftService;

    public function __construct(ShiftManagementService $shiftService)
    {
        $this->shiftService = $shiftService;
    }
    public function index()
    {
        return Inertia::render('Backend/Shifts/Index');
    }
    public function showShiftModal()
    {
        return Inertia::render('Backend/Shifts/ShowShifts', [
            'showShiftModal' => session('show_shift_modal', false),
            'showNoShiftModal' => session('show_no_shift_modal', false),
        ]);
    }

    public function startShift(Request $request)
    {
        $activeShift = Shift::where('status', 'open')->first();
        if ($activeShift) {
            return redirect()->route('dashboard')
                ->with('info', 'A shift is already active.');
        }
        $request->validate([
            'opening_cash' => 'required|numeric',
            'notes' => 'nullable|string',
        ]);

        $shift = Shift::create([
            'started_by' => Auth::id(),
            'start_time' => now(),
            'opening_cash' => $request->opening_cash,
            'cash_notes' => $request->notes,
            'status' => 'open',
        ]);

        // Store shift in session for other users
        session(['current_shift_id' => $shift->id]);

        // Take inventory snapshot
        $snapshot = ShiftInventorySnapshot::create([
            'shift_id' => $shift->id,
            'type' => 'started',
            'user_id' => Auth::id(),
            'created_at' => now(),
        ]);

        // 2. Get all current inventory items
        $inventoryItems = \App\Models\InventoryItem::all();

        // 3. Store snapshot details for each item
        foreach ($inventoryItems as $item) {
            ShiftInventorySnapshotDetail::create([
                'snap_id' => $snapshot->id,
                'item_id' => $item->id,
                'stock_quantity' => $item->stock,
            ]);
        }

        return redirect()->route('dashboard')->with('success', 'Shift started successfully!');
    }

    public function checkActiveShift(Request $request)
    {
        $user = auth()->user();
        $role = $user->getRoleNames()->first();

        $activeShift = Shift::where('status', 'open')->first();

        if (!$activeShift) {
            return response()->json(['active' => false]);
        }

        // Create ShiftDetail for this user if not already exists
        ShiftDetail::firstOrCreate(
            [
                'shift_id' => $activeShift->id,
                'user_id' => $user->id,
            ],
            [
                'role' => $role,
                'joined_at' => now(),
            ]
        );

        // Store shift in session
        session(['current_shift_id' => $activeShift->id]);

        // Determine redirect URL based on role permissions
        if ($role === 'Cashier') {
            $redirectUrl = route('pos.order');
        } else {
            $redirectUrl = route('dashboard');
        }

        return response()->json([
            'active' => true,
            'shift_id' => $activeShift->id,
            'redirect_url' => $redirectUrl,
        ]);
    }


    

public function closeShift(Request $request, Shift $shift)
{
    DB::transaction(function () use ($shift) {
        $totalSales = PosOrder::where('shift_id', $shift->id)
            ->where('status', 'paid')
            ->sum('total_amount');

        $closingCash = $shift->opening_cash + $totalSales;

        $shift->update([
            'status'       => 'closed',
            'end_time'     => now(),
            'ended_by'     => Auth::id(),
            'closing_cash' => $closingCash,
            'sales_total'  => $totalSales,
        ]);

        // Update all ShiftDetails
        $shiftDetails = \App\Models\ShiftDetail::where('shift_id', $shift->id)
            ->whereNull('left_at')
            ->get();

        foreach ($shiftDetails as $detail) {
            $userSales = \App\Models\PosOrder::where('shift_id', $shift->id)
                ->where('user_id', $detail->user_id)
                ->where('status', 'paid')
                ->sum('total_amount');

            $detail->update([
                'left_at' => now(),
                'sales_amount' => $userSales,
            ]);
        }

        // Create snapshot
        $snapshot = ShiftInventorySnapshot::create([
            'shift_id'   => $shift->id,
            'type'       => 'ended',
            'user_id'    => Auth::id(),
            'created_at' => now(),
        ]);

        foreach (InventoryItem::all() as $item) {
            ShiftInventorySnapshotDetail::create([
                'snap_id'        => $snapshot->id,
                'item_id'        => $item->id,
                'stock_quantity' => $item->stock,
            ]);
        }
    });

    session()->forget('current_shift_id');
    session()->flash('show_shift_modal', true);

    return response()->json([
        'success' => true,
        'message' => 'Shift closed successfully!',
        'redirect' => route('shift.manage'),
    ]);
}


    // Fetch All shifts
    public function getAllShifts()
    {
        $shifts = $this->shiftService->getAllShifts();

        return response()->json([
            'success' => true,
            'data' => $shifts,
        ]);
    }
}
