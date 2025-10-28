<?php

namespace App\Services;

use App\Models\Variant;
use App\Models\VariantGroup;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

class VariantService
{
    /**
     * Get all variants with their group information
     * 
     * @return \Illuminate\Database\Eloquent\Collection
     */
    public function getAllVariants()
    {
        try {
            // Fetch all variants with their parent group
            return Variant::with('variantGroup')
                ->orderBy('sort_order')
                ->orderBy('created_at', 'desc')
                ->get();
        } catch (\Exception $e) {
            Log::error('Error fetching variants: ' . $e->getMessage());
            throw $e;
        }
    }

    /**
     * Get variants by group ID
     * 
     * @param int $groupId
     * @return \Illuminate\Database\Eloquent\Collection
     */
    public function getVariantsByGroup($groupId)
    {
        try {
            return Variant::where('variant_group_id', $groupId)
                ->orderBy('sort_order')
                ->orderBy('name')
                ->get();
        } catch (\Exception $e) {
            Log::error("Error fetching variants for group ID {$groupId}: " . $e->getMessage());
            throw $e;
        }
    }

    /**
     * Get a single variant by ID
     * 
     * @param int $id
     * @return Variant
     */
    public function getVariantById($id)
    {
        try {
            return Variant::with('variantGroup')->findOrFail($id);
        } catch (\Exception $e) {
            Log::error("Error fetching variant ID {$id}: " . $e->getMessage());
            throw $e;
        }
    }

    /**
     * Create a new variant
     * 
     * @param array $data
     * @return Variant
     */
    public function createVariant(array $data)
    {
        try {
            DB::beginTransaction();
            $group = VariantGroup::findOrFail($data['variant_group_id']);
            
            if ($group->status === 'inactive') {
                throw new \Exception('Cannot add variant to an inactive group.');
            }

            $nextSortOrder = Variant::where('variant_group_id', $data['variant_group_id'])
                ->max('sort_order') + 1;

            // Create new variant
            $variant = Variant::create([
                'name' => $data['name'],
                'variant_group_id' => $data['variant_group_id'],
                'description' => $data['description'] ?? null,
                'status' => $data['status'] ?? 'active',
                'sort_order' => $data['sort_order'] ?? $nextSortOrder,
            ]);

            DB::commit();

            Log::info("Variant created: {$variant->name} in group {$group->name} (ID: {$variant->id})");
            
            return $variant->load('variantGroup');
        } catch (\Exception $e) {
            DB::rollBack();
            Log::error('Error creating variant: ' . $e->getMessage());
            throw $e;
        }
    }

    /**
     * Update an existing variant
     * 
     * @param int $id
     * @param array $data
     * @return Variant
     */
    public function updateVariant($id, array $data)
    {
        try {
            DB::beginTransaction();

            $variant = Variant::findOrFail($id);

            // If changing group, verify new group exists and is active
            if (isset($data['variant_group_id']) && $data['variant_group_id'] != $variant->variant_group_id) {
                $newGroup = VariantGroup::findOrFail($data['variant_group_id']);
                
                if ($newGroup->status === 'inactive') {
                    throw new \Exception('Cannot move variant to an inactive group.');
                }
            }

            // Update variant details
            $variant->update([
                'name' => $data['name'],
                'variant_group_id' => $data['variant_group_id'],
                'description' => $data['description'] ?? null,
                'status' => $data['status'] ?? 'active',
                'sort_order' => $data['sort_order'] ?? $variant->sort_order,
            ]);

            DB::commit();
            
            return $variant->fresh()->load('variantGroup');
        } catch (\Exception $e) {
            DB::rollBack();
            Log::error("Error updating variant ID {$id}: " . $e->getMessage());
            throw $e;
        }
    }

    /**
     * Delete a variant (soft delete)
     * 
     * @param int $id
     * @return bool
     */
    public function deleteVariant($id)
    {
        try {
            DB::beginTransaction();

            $variant = Variant::findOrFail($id);
            $variant->delete();

            DB::commit();
            
            return true;
        } catch (\Exception $e) {
            DB::rollBack();
            Log::error("Error deleting variant ID {$id}: " . $e->getMessage());
            throw $e;
        }
    }

    /**
     * Toggle variant status (active/inactive)
     * 
     * @param int $id
     * @param string $status
     * @return Variant
     */
    public function toggleStatus($id, $status)
    {
        try {
            DB::beginTransaction();

            $variant = Variant::findOrFail($id);
            
            // Validate status
            if (!in_array($status, ['active', 'inactive'])) {
                throw new \Exception('Invalid status. Must be "active" or "inactive".');
            }

            $variant->update(['status' => $status]);

            DB::commit();

            Log::info("Variant status changed: {$variant->name} -> {$status}");
            
            return $variant->fresh();
        } catch (\Exception $e) {
            DB::rollBack();
            Log::error("Error toggling variant status ID {$id}: " . $e->getMessage());
            throw $e;
        }
    }

    /**
     * Bulk update sort order for variants
     * 
     * @param array $sortData [['id' => 1, 'sort_order' => 0], ...]
     * @return bool
     */
    public function updateSortOrder(array $sortData)
    {
        try {
            DB::beginTransaction();

            foreach ($sortData as $item) {
                Variant::where('id', $item['id'])
                    ->update(['sort_order' => $item['sort_order']]);
            }

            DB::commit();

            Log::info('Variant sort order updated');
            
            return true;
        } catch (\Exception $e) {
            DB::rollBack();
            Log::error('Error updating variant sort order: ' . $e->getMessage());
            throw $e;
        }
    }

    /**
     * Get variants statistics
     * 
     * @return array
     */
    public function getStatistics()
    {
        try {
            return [
                'total_variants' => Variant::count(),
                'active_variants' => Variant::where('status', 'active')->count(),
                'inactive_variants' => Variant::where('status', 'inactive')->count(),
                'average_price_modifier' => Variant::avg('price_modifier'),
            ];
        } catch (\Exception $e) {
            Log::error('Error fetching variant statistics: ' . $e->getMessage());
            throw $e;
        }
    }

    /**
     * Validate variant selection against group rules
     * Used during order creation to ensure min/max rules are followed
     * 
     * @param int $groupId
     * @param array $selectedVariantIds
     * @return array ['valid' => bool, 'message' => string]
     */
    public function validateSelection($groupId, array $selectedVariantIds)
    {
        try {
            $group = VariantGroup::findOrFail($groupId);
            $selectedCount = count($selectedVariantIds);

            // Check minimum requirement
            if ($selectedCount < $group->min_select) {
                return [
                    'valid' => false,
                    'message' => "Please select at least {$group->min_select} option(s) for {$group->name}."
                ];
            }

            // Check maximum limit
            if ($selectedCount > $group->max_select) {
                return [
                    'valid' => false,
                    'message' => "You can select maximum {$group->max_select} option(s) for {$group->name}."
                ];
            }

            // Verify all selected variants belong to this group and are active
            $validVariants = Variant::where('variant_group_id', $groupId)
                ->where('status', 'active')
                ->whereIn('id', $selectedVariantIds)
                ->count();

            if ($validVariants !== $selectedCount) {
                return [
                    'valid' => false,
                    'message' => 'Some selected variants are invalid or inactive.'
                ];
            }

            return [
                'valid' => true,
                'message' => 'Selection is valid.'
            ];
        } catch (\Exception $e) {
            Log::error("Error validating variant selection: " . $e->getMessage());
            return [
                'valid' => false,
                'message' => 'Error validating selection.'
            ];
        }
    }

    /**
     * Calculate total price modifier for selected variants
     * 
     * @param array $variantIds
     * @return float
     */
    public function calculateTotalPriceModifier(array $variantIds)
    {
        try {
            return Variant::whereIn('id', $variantIds)
                ->where('status', 'active')
                ->sum('price_modifier');
        } catch (\Exception $e) {
            Log::error('Error calculating price modifier: ' . $e->getMessage());
            return 0.00;
        }
    }
}