<?php

namespace App\Http\Controllers\Shifts;

use Illuminate\Http\Request;
use App\Http\Controllers\Controller;
use App\Models\InventoryItem;
use Illuminate\Support\Facades\Auth;
use App\Models\Shift;
use App\Models\ShiftDetail;
use App\Models\ShiftInventorySnapshot;
use App\Models\ShiftInventorySnapshotDetail;
use App\Services\Shifts\ShiftManagementService;
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
        // Step 1: Update shift fields
        $shift->update([
            'status'       => 'closed',
            'end_time'     => now(),
            'ended_by'     => Auth::id(),
            'closing_cash' => 0,
            'sales_total'  => 0,
        ]);

        // Step 2: Create end inventory snapshot
        $snapshot = ShiftInventorySnapshot::create([
            'shift_id'   => $shift->id,
            'type'       => 'ended',
            'user_id'    => Auth::id(),
            'created_at' => now(),
        ]);

        // Step 3: Take snapshot of all current inventory items
        $inventoryItems = InventoryItem::all();

        foreach ($inventoryItems as $item) {
            ShiftInventorySnapshotDetail::create([
                'snap_id'        => $snapshot->id,
                'item_id'        => $item->id,
                'stock_quantity' => $item->stock,
            ]);
        }
        // 🟢 Step 4: Set session flag to show "Start Shift" modal next time
        session()->forget('current_shift_id');
        session()->flash('show_shift_modal', true);


        // Step 4: Return response (or redirect)
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
