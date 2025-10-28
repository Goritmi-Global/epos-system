<?php

namespace App\Http\Controllers;

use App\Models\PromoScope;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use App\Http\Requests\StorePromoScopeRequest;
use App\Http\Requests\UpdatePromoScopeRequest;

class PromoScopeController extends Controller
{
    public function index()
    {
        $scopes = PromoScope::with(['promos', 'meals', 'menuItems'])->get();
        return response()->json(['data' => $scopes]);
    }

    public function store(StorePromoScopeRequest $request)
    {
        $validated = $request->validated();

        DB::beginTransaction();
        try {
            $scope = PromoScope::create();

            if (!empty($validated['promos'])) {
                $scope->promos()->attach($validated['promos']);
            }
            if (!empty($validated['meals'])) {
                $scope->meals()->attach($validated['meals']);
            }
            if (!empty($validated['menu_items'])) {
                $scope->menuItems()->attach($validated['menu_items']);
            }

            DB::commit();

            return response()->json([
                'message' => 'Promo scope created successfully',
                'data' => $scope->load(['promos', 'meals', 'menuItems'])
            ], 201);
        } catch (\Exception $e) {
            DB::rollBack();
            return response()->json([
                'message' => 'Failed to create promo scope',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    public function update(UpdatePromoScopeRequest $request, PromoScope $promoScope)
    {
        $validated = $request->validated();

        DB::beginTransaction();
        try {
            // Sync multiple promos
            $promoScope->promos()->sync($validated['promos'] ?? []);

            $promoScope->meals()->sync($validated['meals'] ?? []);

            $promoScope->menuItems()->sync($validated['menu_items'] ?? []);

            DB::commit();

            return response()->json([
                'message' => 'Promo scope updated successfully',
                'data' => $promoScope->load(['promos', 'meals', 'menuItems'])
            ]);
        } catch (\Exception $e) {
            DB::rollBack();
            return response()->json([
                'message' => 'Failed to update promo scope',
                'error' => $e->getMessage()
            ], 500);
        }
    }


    public function destroy(PromoScope $promoScope)
    {
        $promoScope->delete();
        return response()->json(['message' => 'Promo scope deleted successfully']);
    }
}