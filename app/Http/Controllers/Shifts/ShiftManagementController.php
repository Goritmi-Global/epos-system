<?php

namespace App\Http\Controllers\Shifts;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use App\Http\Controllers\Controller;
use App\Models\InventoryItem;
use App\Models\PosOrder;
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
        $isSuperAdmin = Auth::user()->hasRole('Super Admin');

        // 1️⃣ Calculate total sales for this shift
        $totalSales = PosOrder::where('shift_id', $shift->id)
            ->where('status', 'paid')
            ->sum('total_amount');

        $closingCash = $shift->opening_cash + $totalSales;

        // 2️⃣ Update shift summary
        $shift->update([
            'status'       => 'closed',
            'end_time'     => now(),
            'ended_by'     => Auth::id(),
            'closing_cash' => $closingCash,
            'sales_total'  => $totalSales,
        ]);

        // 3️⃣ Update ShiftDetail records
        $shiftDetails = ShiftDetail::where('shift_id', $shift->id)->get();

        foreach ($shiftDetails as $detail) {
            $userSales = PosOrder::where('shift_id', $shift->id)
                ->where('status', 'paid')
                ->where('user_id', $detail->user_id)
                ->sum('total_amount');

            $detail->update([
                'sales_amount' => $userSales,
                'left_at'      => now(),
            ]);
        }

        // 4️⃣ Create end inventory snapshot
        $snapshot = ShiftInventorySnapshot::create([
            'shift_id'   => $shift->id,
            'type'       => 'ended',
            'user_id'    => Auth::id(),
            'created_at' => now(),
        ]);

        $inventoryItems = InventoryItem::all();

        foreach ($inventoryItems as $item) {
            ShiftInventorySnapshotDetail::create([
                'snap_id'        => $snapshot->id,
                'item_id'        => $item->id,
                'stock_quantity' => $item->stock,
            ]);
        }

        // 5️⃣ Logout all non-Super Admin users
        $usersToLogout = \App\Models\User::whereDoesntHave('roles', function ($q) {
            $q->where('name', 'Super Admin');
        })->pluck('id');

        DB::table('sessions')->whereIn('user_id', $usersToLogout)->delete();

        // 6️⃣ Clear session shift data
        session()->forget('current_shift_id');

        // 7️⃣ Handle based on user role
        if (!$isSuperAdmin) {
            // Logout current user
            Auth::guard('web')->logout();
            $request->session()->invalidate();
            $request->session()->regenerateToken();

            // Return JSON response for AJAX or redirect
            if ($request->expectsJson()) {
                return response()->json([
                    'success' => true,
                    'redirect' => url('/login'),
                    'message' => 'Shift closed — please log in again.'
                ]);
            }

            return redirect()->away(url('/login'))->with('info', 'Shift closed — please log in again.');
        }

        // 8️⃣ For Super Admin
        session()->flash('show_shift_modal', true);

        // Return JSON response for AJAX or redirect
        if ($request->expectsJson()) {
            return response()->json([
                'success' => true,
                'redirect' => route('shift.manage'),
                'message' => 'Shift closed successfully.'
            ]);
        }

        return redirect()->route('shift.manage')->with('success', 'Shift closed successfully.');
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

    public function details($id)
    {
        $shift = \App\Models\Shift::with('details')->findOrFail($id);

        return response()->json([
            'success' => true,
            'data' => $shift->details->map(function ($detail) {
                return [
                    'role' => $detail->role,
                    'joined_at' => $detail->joined_at,
                    'sales_amount' => $detail->sales_amount,
                ];
            }),
        ]);
    }
}
