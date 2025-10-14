<?php

namespace App\Http\Controllers;

use App\Http\Requests\StorePromoRequest;
use App\Http\Requests\UpdatePromoRequests;
use App\Services\PromoService;
use Exception;
use Inertia\Inertia;

class PromoController extends Controller
{
    protected PromoService $promoService;

    public function __construct(PromoService $promoService)
    {
        $this->promoService = $promoService;
    }

    /**
     * Display a listing of promos
     */
    public function index()
    {

        return Inertia::render('Backend/Promo/Index');
    }

    public function fetchAllPromos()
    {
        try {
            $promos = $this->promoService->getAllPromos();

            return response()->json([
                'success' => true,
                'data' => $promos,
            ], 200);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to fetch promos: ' . $e->getMessage(),
            ], 500);
        }
    }

    /**
     * Get today's active promos
     */
    public function getTodayPromos()
    {
        try {
            $promos = $this->promoService->getTodayPromos();

            return response()->json([
                'success' => true,
                'data' => $promos,
            ], 200);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to fetch promos: ' . $e->getMessage(),
            ], 500);
        }
    }

    /**
     * Store a newly created promo
     */
    public function store(StorePromoRequest $request)
    {

        try {
            $this->promoService->createPromo($request->validated());

            return redirect()->back()->with('success', 'Promo created successfully');
        } catch (Exception $e) {
            return redirect()->back()
                ->withErrors(['error' => 'Failed to create promo: ' . $e->getMessage()])
                ->withInput();
        }
    }

    /**
     * Display the specified promo
     */
    public function show(int $id)
    {
        try {
            $promo = $this->promoService->getPromoById($id);

            return inertia('Backend/Promo/Index', [
                'promo' => $promo,
            ]);
        } catch (Exception $e) {
            return redirect()->route('promos.index')
                ->with('error', 'Promo not found');
        }
    }

    /**
     * Update the specified promo
     */
    public function update(UpdatePromoRequests $request, int $id)
    {
        try {
            $this->promoService->updatePromo($id, $request->validated());

            return redirect()->back()->with('success', 'Promo updated successfully');
        } catch (Exception $e) {
            return redirect()->back()
                ->withErrors(['error' => 'Failed to update promo: ' . $e->getMessage()])
                ->withInput();
        }
    }

    /**
     * Remove the specified promo
     */
    public function destroy(int $id)
    {
        try {
            $this->promoService->deletePromo($id);

            return redirect()->back()->with('success', 'Promo deleted successfully');
        } catch (Exception $e) {
            return redirect()->back()
                ->with('error', 'Failed to delete promo: ' . $e->getMessage());
        }
    }

    /**
     * Toggle promo status
     */
    public function toggleStatus(int $id)
    {
        try {
            $promo = $this->promoService->toggleStatus($id);

            return response()->json([
                'success' => true,
                'message' => 'Promo status updated successfully',
                'data' => $promo
            ]);
        } catch (Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to update promo status: ' . $e->getMessage()
            ], 500);
        }
    }
}
