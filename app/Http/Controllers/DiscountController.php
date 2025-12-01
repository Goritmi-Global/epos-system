<?php

namespace App\Http\Controllers;

use App\Http\Requests\StoreDiscountRequest;
use App\Http\Requests\UpdateDiscountRequest;
use App\Services\DiscountService;
use Carbon\Carbon;
use Exception;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
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
                'message' => 'Failed to fetch discounts: '.$e->getMessage(),
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
                'message' => 'Failed to fetch discounts: '.$e->getMessage(),
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
                'message' => 'Discount created successfully',
            ], 201);
        } catch (Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to create discount: '.$e->getMessage(),
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
                'message' => 'Failed to update discount: '.$e->getMessage(),
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
                'message' => 'Failed to delete discount: '.$e->getMessage(),
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
                'message' => 'Failed to update discount status: '.$e->getMessage(),
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
                'message' => 'Failed to fetch active discounts: '.$e->getMessage(),
            ], 500);
        }
    }

    public function import(Request $request): JsonResponse
    {
        try {
            $discounts = $request->input('discounts', []);

            if (empty($discounts)) {
                return response()->json([
                    'success' => false,
                    'message' => 'No discounts data provided',
                ], 422);
            }

            $importedCount = 0;
            $errors = [];

            foreach ($discounts as $index => $row) {
                $name = $row['name'] ?? null;
                $type = strtolower($row['type'] ?? 'percent');
                $discountAmount = $row['discount_amount'] ?? null;

                // Normalize dates
                $startDate = $this->normalizeExcelDate($row['start_date'] ?? null);
                $endDate = $this->normalizeExcelDate($row['end_date'] ?? null);

                $minPurchase = $row['min_purchase'] ?? 0;
                $maxDiscount = $row['max_discount'] ?? null;
                $status = strtolower($row['status'] ?? 'active');
                $description = $row['description'] ?? null;

                // Required fields
                if (! $name || ! $discountAmount || ! $startDate || ! $endDate) {
                    $errors[] = 'Row '.($index + 1).': Missing required fields';

                    continue;
                }

                // Type validation
                if (! in_array($type, ['flat', 'percent'])) {
                    $errors[] = 'Row '.($index + 1).': Invalid type';

                    continue;
                }

                // Status validation
                if (! in_array($status, ['active', 'inactive'])) {
                    $errors[] = 'Row '.($index + 1).': Invalid status';

                    continue;
                }

                // Date validation
                try {
                    $startDateObj = Carbon::parse($startDate)->startOfDay();
                    $endDateObj = Carbon::parse($endDate)->startOfDay();

                    if ($endDateObj->lt($startDateObj)) {
                        $errors[] = 'Row '.($index + 1).': End date cannot be before start date';

                        continue;
                    }
                } catch (\Exception $e) {
                    $errors[] = 'Row '.($index + 1).': Invalid date format';

                    continue;
                }

                // Discount validation
                if (! is_numeric($discountAmount) || $discountAmount <= 0) {
                    $errors[] = 'Row '.($index + 1).': Invalid discount amount';

                    continue;
                }

                if ($type === 'percent' && $discountAmount > 100) {
                    $errors[] = 'Row '.($index + 1).': Percentage cannot exceed 100%';

                    continue;
                }

                try {
                    $this->discountService->createDiscount([
                        'name' => trim($name),
                        'type' => $type,
                        'discount_amount' => $discountAmount,
                        'start_date' => $startDateObj->format('Y-m-d'),
                        'end_date' => $endDateObj->format('Y-m-d'),
                        'min_purchase' => $minPurchase,
                        'max_discount' => $maxDiscount ?: null,
                        'status' => $status,
                        'description' => $description,
                    ]);

                    $importedCount++;

                } catch (\Exception $e) {
                    $errors[] = 'Row '.($index + 1).': '.$e->getMessage();
                }
            }

            if ($importedCount === 0) {
                return response()->json([
                    'success' => false,
                    'message' => 'No valid discounts were imported',
                    'errors' => $errors,
                ], 422);
            }

            return response()->json([
                'success' => true,
                'message' => "Imported {$importedCount} discount(s) with ".count($errors).' warning(s)',
                'imported_count' => $importedCount,
                'errors' => $errors,
            ]);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to import discounts: '.$e->getMessage(),
            ], 500);
        }
    }

    private function normalizeExcelDate($value)
    {
        if (! $value) {
            return null;
        }

        // Excel serial number (e.g., 45678)
        if (is_numeric($value) && $value > 30000) {
            $unixDate = ($value - 25569) * 86400;

            return Carbon::createFromTimestamp($unixDate)->format('Y-m-d');
        }

        // Already in correct format
        if (preg_match('/^\d{4}-\d{2}-\d{2}$/', $value)) {
            return $value;
        }

        // Try normal parsing
        try {
            return Carbon::parse($value)->format('Y-m-d');
        } catch (\Exception $e) {
            return null;
        }
    }
}
