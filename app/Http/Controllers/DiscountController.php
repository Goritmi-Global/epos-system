<?php

namespace App\Http\Controllers;

use App\Http\Requests\StoreDiscountRequest;
use App\Http\Requests\UpdateDiscountRequest;
use App\Services\DiscountService;
use Exception;
use Inertia\Inertia;

class DiscountController extends Controller
{
    // Inject the DiscountService via constructor dependency injection
    protected DiscountService $discountService;

    public function __construct(DiscountService $discountService)
    {
        $this->discountService = $discountService;
    }

    /**
     * Display a listing of discounts
     * This renders the index page with all discounts
     */
    public function index()
    {
        // Render the Discount index page using Inertia
        return Inertia::render('Backend/Discount/Index');
    }

    /**
     * Fetch all discounts via API
     * This is called by the Vue component to populate the table
     */
    public function fetchAllDiscounts()
    {
        try {
            // Get all discounts from the service
            $discounts = $this->discountService->getAllDiscounts();

            return response()->json([
                'success' => true,
                'data' => $discounts,
            ], 200);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to fetch discounts: ' . $e->getMessage(),
            ], 500);
        }
    }

    /**
     * Get today's active discounts
     */
    public function getTodayDiscounts()
    {
        try {
            // Get discounts that are active today
            $discounts = $this->discountService->getTodayDiscounts();

            return response()->json([
                'success' => true,
                'data' => $discounts,
            ], 200);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to fetch discounts: ' . $e->getMessage(),
            ], 500);
        }
    }

    /**
     * Store a newly created discount
     * Validates input and creates a new discount record
     */
    public function store(StoreDiscountRequest $request)
    {
        try {
            // Use the service to create the discount
            $this->discountService->createDiscount($request->validated());

            return response()->json([
                'success' => true,
                'message' => 'Discount created successfully'
            ], 201);
        } catch (Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to create discount: ' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * Display the specified discount
     */
    public function show(int $id)
    {
        try {
            // Get a single discount by ID
            $discount = $this->discountService->getDiscountById($id);

            return response()->json([
                'success' => true,
                'data' => $discount,
            ], 200);
        } catch (Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Discount not found',
            ], 404);
        }
    }

    /**
     * Update the specified discount
     * Validates input and updates the discount record
     */
    public function update(UpdateDiscountRequest $request, int $id)
    {
        try {
           
            $discount = $this->discountService->updateDiscount($id, $request->validated());

            return response()->json([
                'success' => true,
                'message' => 'Discount updated successfully',
                'data' => $discount,
            ], 200);
        } catch (Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to update discount: ' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * Remove the specified discount
     */
    public function destroy(int $id)
    {
        try {
            // Delete the discount
            $this->discountService->deleteDiscount($id);

            return response()->json([
                'success' => true,
                'message' => 'Discount deleted successfully',
            ], 200);
        } catch (Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to delete discount: ' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * Toggle discount status between active and inactive
     * Called when user clicks the status toggle
     */
    public function toggleStatus(int $id)
    {
        try {
            // Toggle the status via the service
            $discount = $this->discountService->toggleStatus($id);

            return response()->json([
                'success' => true,
                'message' => 'Discount status updated successfully',
                'data' => $discount,
            ], 200);
        } catch (Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to update discount status: ' . $e->getMessage(),
            ], 500);
        }
    }

    /**
     * Get all active discounts
     * Useful for displaying available discounts to customers
     */
    public function getActiveDiscounts()
    {
        try {
            $discounts = $this->discountService->getActiveDiscounts();

            return response()->json([
                'success' => true,
                'data' => $discounts,
            ], 200);
        } catch (Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to fetch active discounts: ' . $e->getMessage(),
            ], 500);
        }
    }
}