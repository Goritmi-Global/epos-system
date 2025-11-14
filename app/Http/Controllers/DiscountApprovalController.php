<?php

namespace App\Http\Controllers;

use App\Http\Controllers\Controller;
use App\Models\DiscountApproval;
use App\Models\Discount;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use App\Events\DiscountApprovalRequested;
use App\Events\DiscountApprovalResponded;

class DiscountApprovalController extends Controller
{
    /**
     * Request discount approval - PERCENTAGE ONLY
     */
    public function requestApproval(Request $request)
    {
        $request->validate([
            'discounts' => 'required|array',
            'discounts.*.discount_id' => 'required|exists:discounts,id',
            'discounts.*.discount_percentage' => 'required|numeric|min:0|max:100', // ✅ Changed to percentage
            'discounts.*.discount_name' => 'required|string', // ✅ Added name
            'order_items' => 'required|array',
            'order_subtotal' => 'required|numeric|min:0',
            'request_note' => 'nullable|string|max:500',
        ]);

        $approvalRequests = [];

        foreach ($request->discounts as $discountData) {
            $discount = Discount::findOrFail($discountData['discount_id']);

            $approval = DiscountApproval::create([
                'discount_id' => $discount->id,
                'requested_by' => Auth::id(),
                'status' => 'pending',
                'discount_percentage' => $discountData['discount_percentage'], // ✅ Store percentage
                'discount_name' => $discountData['discount_name'], // ✅ Store name
                'order_items' => $request->order_items,
                'order_subtotal' => $request->order_subtotal,
                'request_note' => $request->request_note,
                'requested_at' => now(),
            ]);

            $approvalRequests[] = $approval->load(['discount', 'requestedBy']);
        }

        // Broadcast to Super Admin
        broadcast(new DiscountApprovalRequested($approvalRequests))->toOthers();

        return response()->json([
            'success' => true,
            'message' => 'Discount approval request sent to Super Admin',
            'data' => $approvalRequests,
        ]);
    }

    /**
     * Get pending approval requests (for Super Admin)
     */
    public function getPendingRequests()
    {
        $requests = DiscountApproval::with(['discount', 'requestedBy'])
            ->where('status', 'pending')
            ->orderBy('requested_at', 'desc')
            ->get()
            ->map(function ($approval) {
                // ✅ Calculate current discount amount based on percentage
                $discountAmount = ($approval->order_subtotal * $approval->discount_percentage) / 100;
                
                return [
                    'id' => $approval->id,
                    'discount_id' => $approval->discount_id,
                    'discount_name' => $approval->discount_name,
                    'discount_percentage' => $approval->discount_percentage,
                    'discount_amount' => round($discountAmount, 2), // ✅ Calculated amount for display
                    'order_subtotal' => $approval->order_subtotal,
                    'order_items' => $approval->order_items,
                    'request_note' => $approval->request_note,
                    'requested_at' => $approval->requested_at,
                    'requested_by' => $approval->requestedBy,
                    'discount' => $approval->discount,
                    'status' => $approval->status,
                ];
            });

        return response()->json([
            'success' => true,
            'data' => $requests,
        ]);
    }

    /**
     * Check approval status (for POS to poll)
     */
    public function checkApprovalStatus(Request $request)
    {
        $request->validate([
            'approval_ids' => 'required|array',
            'approval_ids.*' => 'required|integer|exists:discount_approvals,id',
        ]);

        $approvals = DiscountApproval::with(['discount', 'approvedBy'])
            ->whereIn('id', $request->approval_ids)
            ->get()
            ->map(function ($approval) {
                return [
                    'id' => $approval->id,
                    'discount_id' => $approval->discount_id,
                    'discount_name' => $approval->discount_name,
                    'discount_percentage' => $approval->discount_percentage, // ✅ Return percentage
                    'status' => $approval->status,
                    'approval_note' => $approval->approval_note,
                    'responded_at' => $approval->responded_at,
                    'discount' => $approval->discount,
                    'approved_by' => $approval->approvedBy,
                ];
            });

        return response()->json([
            'success' => true,
            'data' => $approvals,
        ]);
    }

    /**
     * Approve or reject discount request (Super Admin only)
     */
    public function respondToRequest(Request $request, $id)
    {
        // Check if user is Super Admin
        if (!Auth::user()->hasRole('Super Admin')) {
            return response()->json([
                'success' => false,
                'message' => 'Unauthorized. Only Super Admin can approve discounts.',
            ], 403);
        }

        $request->validate([
            'status' => 'required|in:approved,rejected',
            'approval_note' => 'nullable|string|max:500',
        ]);

        $approval = DiscountApproval::findOrFail($id);

        if ($approval->status !== 'pending') {
            return response()->json([
                'success' => false,
                'message' => 'This request has already been processed.',
            ], 400);
        }

        $approval->update([
            'status' => $request->status,
            'approved_by' => Auth::id(),
            'approval_note' => $request->approval_note,
            'responded_at' => now(),
        ]);

        $approval->load(['discount', 'approvedBy', 'requestedBy']);

        // ✅ Format response with percentage
        $responseData = [
            'id' => $approval->id,
            'discount_id' => $approval->discount_id,
            'discount_name' => $approval->discount_name,
            'discount_percentage' => $approval->discount_percentage,
            'status' => $approval->status,
            'approval_note' => $approval->approval_note,
            'responded_at' => $approval->responded_at,
            'discount' => $approval->discount,
            'approved_by' => $approval->approvedBy,
        ];

        // Broadcast to the requesting cashier
        broadcast(new DiscountApprovalResponded($responseData))->toOthers();

        return response()->json([
            'success' => true,
            'message' => ucfirst($request->status) . ' successfully',
            'data' => $responseData,
        ]);
    }

    /**
     * Get approval history
     */
    public function getApprovalHistory(Request $request)
    {
        $query = DiscountApproval::with(['discount', 'requestedBy', 'approvedBy'])
            ->orderBy('requested_at', 'desc');

        // Filter by status if provided
        if ($request->has('status')) {
            $query->where('status', $request->status);
        }

        // Filter by date range if provided
        if ($request->has('start_date') && $request->has('end_date')) {
            $query->whereBetween('requested_at', [$request->start_date, $request->end_date]);
        }

        $approvals = $query->paginate(20)->through(function ($approval) {
            // ✅ Calculate discount amount for display
            $discountAmount = ($approval->order_subtotal * $approval->discount_percentage) / 100;
            
            return [
                'id' => $approval->id,
                'discount_id' => $approval->discount_id,
                'discount_name' => $approval->discount_name,
                'discount_percentage' => $approval->discount_percentage,
                'discount_amount' => round($discountAmount, 2), 
                'order_subtotal' => $approval->order_subtotal,
                'status' => $approval->status,
                'request_note' => $approval->request_note,
                'approval_note' => $approval->approval_note,
                'requested_at' => $approval->requested_at,
                'responded_at' => $approval->responded_at,
                'requested_by' => $approval->requestedBy,
                'approved_by' => $approval->approvedBy,
                'discount' => $approval->discount,
            ];
        });

        return response()->json([
            'success' => true,
            'data' => $approvals,
        ]);
    }
}