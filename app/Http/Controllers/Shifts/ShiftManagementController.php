<?php

namespace App\Http\Controllers\Shifts;

use App\Http\Controllers\Controller;
use App\Models\InventoryItem;
use App\Models\PosOrder;
use App\Models\Shift;
use App\Models\ShiftChecklist;
use App\Models\ShiftChecklistItem;
use App\Models\ShiftDetail;
use App\Models\ShiftInventorySnapshot;
use App\Models\ShiftInventorySnapshotDetail;
use App\Services\Shifts\ShiftManagementService;
use App\Services\Shifts\ShiftReportService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Inertia\Inertia;

class ShiftManagementController extends Controller
{
    protected $shiftService;

    protected $reportService;

    public function __construct(ShiftManagementService $shiftService, ShiftReportService $reportService)
    {
        $this->shiftService = $shiftService;
        $this->reportService = $reportService;
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

    public function getChecklistItems(Request $request)
    {
       
        $query = ShiftChecklistItem::where('is_default', true)->orWhere('is_default', false);

        if ($request->has('type')) {
            $query->where('type', $request->type);
        }

        $items = $query->orderBy('created_at', 'asc')->get();

        return response()->json([
            'success' => true,
            'data' => $items,
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
            'checklists' => 'required|array|min:1',
            'checklists.*' => 'required|string',
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

        ShiftChecklist::create([
            'shift_id' => $shift->id,
            'type' => 'started', // ✅ Added
            'checklist_item_ids' => $request->checklists ?? [],
        ]);

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

        if (! $activeShift) {
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

 
public function storeCustomChecklistItem(Request $request)
{
 
    $request->validate([
        'name' => 'required|string|max:255',
        'type' => 'required|in:start,end',
    ]);

    $item = ShiftChecklistItem::create([
        'name' => $request->name,
        'description' => null,
        'is_default' => false, // Custom items are not default
        'type' => $request->type,
    ]);

    return response()->json([
        'success' => true,
        'data' => $item,
    ]);
}

    public function closeShift(Request $request, Shift $shift)
    {
        // Validate closing checklist
        $request->validate([
            'checklists' => 'required|array|min:1',
            'checklists.*' => 'required|string',
        ]);

        $isSuperAdmin = Auth::user()->hasRole('Super Admin');

        // 1️⃣ Calculate total sales for this shift
        $totalSales = PosOrder::where('shift_id', $shift->id)
            ->where('status', 'paid')
            ->sum('total_amount');

        $closingCash = $shift->opening_cash + $totalSales;

        // 2️⃣ Update shift summary
        $shift->update([
            'status' => 'closed',
            'end_time' => now(),
            'ended_by' => Auth::id(),
            'closing_cash' => $closingCash,
            'sales_total' => $totalSales,
        ]);

        // 3️⃣ ✅ CREATE NEW closing checklist record (don't update the existing one)
        // This will create a SEPARATE record with type 'ended'
        ShiftChecklist::create([
            'shift_id' => $shift->id,
            'type' => 'ended', // ✅ This is the closing checklist
            'checklist_item_ids' => $request->checklists ?? [],
        ]);

        // 4️⃣ Update ShiftDetail records
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

        // 5️⃣ Create end inventory snapshot
        $snapshot = ShiftInventorySnapshot::create([
            'shift_id' => $shift->id,
            'type' => 'ended',
            'user_id' => Auth::id(),
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

        // 6️⃣ Logout all non-Super Admin users
        $usersToLogout = \App\Models\User::whereDoesntHave('roles', function ($q) {
            $q->where('name', 'Super Admin');
        })->pluck('id');

        DB::table('sessions')->whereIn('user_id', $usersToLogout)->delete();

        // 7️⃣ Clear session shift data
        session()->forget('current_shift_id');

        // 8️⃣ Handle based on user role
        if (! $isSuperAdmin) {
            // Logout current user
            Auth::guard('web')->logout();
            $request->session()->invalidate();
            $request->session()->regenerateToken();

            return response()->json([
                'success' => true,
                'redirect' => url('/login'),
                'message' => 'Shift closed — please log in again.',
            ]);
        }

        // 9️⃣ For Super Admin
        session()->flash('show_shift_modal', true);

        return response()->json([
            'success' => true,
            'redirect' => route('shift.manage'),
            'message' => 'Shift closed successfully.',
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

    public function details($id)
    {
        $shift = \App\Models\Shift::with('details', 'checklists.checklistItem')->findOrFail($id);

        return response()->json([
            'success' => true,
            'data' => $shift->details->map(function ($detail) {
                return [
                    'role' => $detail->role,
                    'joined_at' => $detail->joined_at,
                    'sales_amount' => $detail->sales_amount,
                ];
            }),
            'checklists' => $shift->checklists->map(function ($checklist) {
                return [
                    'name' => $checklist->checklistItem->name,
                    'is_completed' => $checklist->is_completed,
                ];
            }),
        ]);
    }

    public function generateXReport(Shift $shift)
    {
        try {
            $reportData = $this->reportService->generateXReport($shift);

            return response()->json([
                'success' => true,
                'data' => $reportData,
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => $e->getMessage(),
            ], 400);
        }
    }

    // /**
    //  * Download X Report as PDF
    //  *
    //  * @return StreamedResponse
    //  */
    public function downloadXReportPdf(Shift $shift)
    {
        try {
            $reportData = $this->reportService->generateXReport($shift);

            // Format data for PDF
            $pdfData = [
                'title' => 'X Report (Mid-Shift)',
                'generatedDate' => now()->format('d/m/Y H:i:s'),
                'shiftId' => $reportData['shift_id'],
                'startedBy' => $reportData['started_by'],
                'startTime' => \Carbon\Carbon::parse($reportData['start_time'])->format('d/m/Y H:i:s'),
                'openingCash' => number_format($reportData['opening_cash'], 2),
                'status' => $reportData['status'],
                'salesSummary' => $reportData['sales_summary'],
                'cashSummary' => $reportData['cash_summary'],
                'paymentMethods' => $reportData['payment_methods'],
                'salesByUser' => $reportData['sales_by_user'],
                'topItems' => $reportData['top_items'],
            ];

            return response()->json([
                'success' => true,
                'data' => $pdfData,
                'fileName' => 'X_Report_Shift_'.$shift->id.'_'.now()->format('Y-m-d_H-i-s').'.pdf',
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to prepare X Report PDF: '.$e->getMessage(),
            ], 400);
        }
    }

    /**
     * Generate Z Report (End-of-Shift Report)
     *
     * @return \Illuminate\Http\JsonResponse
     */
    public function generateZReport(Shift $shift)
    {
        try {
            $reportData = $this->reportService->generateZReport($shift);

            return response()->json([
                'success' => true,
                'data' => $reportData,
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => $e->getMessage(),
            ], 400);
        }
    }

  
  public function downloadZReportPdf(Shift $shift)
{
    try {
        $reportData = $this->reportService->generateZReport($shift);

        // Format data for PDF with all sections matching client template
        $pdfData = [
            'title' => 'Daily Summary Report - All Brands',
            'generatedDate' => now()->format('l, M d, Y'),
            'shiftId' => $reportData['shift_id'],
            'startedBy' => $reportData['started_by'],
            'startTime' => \Carbon\Carbon::parse($reportData['start_time'])->format('l, h:i a'),
            'endedBy' => $reportData['ended_by'],
            'endTime' => \Carbon\Carbon::parse($reportData['end_time'])->format('l, h:i a'),
            'duration' => $reportData['duration'],
            'status' => $reportData['status'],
            
            // Sales Summary
            'salesSummary' => [
                'total_orders' => $reportData['sales_summary']['total_orders'],
                'subtotal' => $reportData['sales_summary']['subtotal'],
                'total_tax' => $reportData['sales_summary']['total_tax'],
                'total_discount' => $reportData['sales_summary']['total_discount'],
                'total_charges' => $reportData['sales_summary']['total_charges'] ?? 0,
                'total_sales' => $reportData['sales_summary']['total_sales'],
                'avg_order_value' => $reportData['sales_summary']['avg_order_value'],
                'total_refunds' => 0, // Add if you track refunds
            ],
            
            // Cash Reconciliation
            'cashReconciliation' => [
                'opening_cash' => $reportData['cash_reconciliation']['opening_cash'],
                'cash_expenses' => $reportData['cash_reconciliation']['cash_expenses'] ?? 0,
                'cash_transfers' => $reportData['cash_reconciliation']['cash_transfers'] ?? 0,
                'cash_changed' => $reportData['cash_reconciliation']['cash_changed'] ?? 0,
                'cash_sales' => $reportData['cash_reconciliation']['cash_sales'],
                'cash_refunds' => $reportData['cash_reconciliation']['cash_refunds'] ?? 0,
                'expected_cash' => $reportData['cash_reconciliation']['expected_cash'],
                'actual_cash' => $reportData['cash_reconciliation']['actual_cash'],
                'variance' => $reportData['cash_reconciliation']['variance'],
                'variance_percentage' => $reportData['cash_reconciliation']['variance_percentage'],
            ],
            
            // Payment Methods
            'paymentMethods' => $reportData['payment_methods'],
            
            // New sections matching template
            'venue_sales' => $reportData['venue_sales'] ?? [],
            'dispatch_sales' => $reportData['dispatch_sales'] ?? [],
            'menu_category_summary' => $reportData['menu_category_summary'] ?? [],
            'covers_summary' => $reportData['covers_summary'] ?? [
                'total_covers' => 0,
                'avg_revenue_per_cover' => 0,
            ],
            'discounts_summary' => $reportData['discounts_summary'] ?? [],
            'charges_summary' => $reportData['charges_summary'] ?? [],
            'cancelled_items' => $reportData['cancelled_items'] ?? [],
            
            // Additional sections
            'salesByUser' => $reportData['sales_by_user'],
            'topItems' => $reportData['top_items'],
            'stockMovement' => $reportData['stock_movement'],
        ];

        return response()->json([
            'success' => true,
            'data' => $pdfData,
            'fileName' => 'Z_Report_Shift_'.$shift->id.'_'.now()->format('Y-m-d_H-i-s').'.pdf',
        ]);
    } catch (\Exception $e) {
        return response()->json([
            'success' => false,
            'message' => 'Failed to prepare Z Report PDF: '.$e->getMessage(),
        ], 400);
    }
}
}
