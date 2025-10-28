<?php

namespace App\Services;

use App\Models\VariantGroup;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

class VariantGroupService
{
    /**
     * Get all variant groups with their variants count
     * 
     * @return \Illuminate\Database\Eloquent\Collection
     */
    public function getAllGroups()
    {
        try {
            // Fetch all groups with variant count using eager loading
            return VariantGroup::withCount('variants')
                ->orderBy('sort_order')
                ->orderBy('created_at', 'desc')
                ->get();
        } catch (\Exception $e) {
            Log::error('Error fetching variant groups: ' . $e->getMessage());
            throw $e;
        }
    }

    /**
     * Get a single variant group by ID with its variants
     * 
     * @param int $id
     * @return VariantGroup
     */
    public function getGroupById($id)
    {
        try {
            return VariantGroup::with('variants')->findOrFail($id);
        } catch (\Exception $e) {
            Log::error("Error fetching variant group ID {$id}: " . $e->getMessage());
            throw $e;
        }
    }

    /**
     * Create a new variant group
     * 
     * @param array $data
     * @return VariantGroup
     */
    public function createGroup(array $data)
    {
        try {
            DB::beginTransaction();

            // Get the next sort order
            $nextSortOrder = VariantGroup::max('sort_order') + 1;

            // Create new variant group
            $group = VariantGroup::create([
                'name' => $data['name'],
                'description' => $data['description'] ?? null,
                'status' => $data['status'] ?? 'active',
                'sort_order' => $data['sort_order'] ?? $nextSortOrder,
            ]);

            DB::commit();

            Log::info("Variant group created: {$group->name} (ID: {$group->id})");
            
            return $group;
        } catch (\Exception $e) {
            DB::rollBack();
            Log::error('Error creating variant group: ' . $e->getMessage());
            throw $e;
        }
    }

    /**
     * Update an existing variant group
     * 
     * @param int $id
     * @param array $data
     * @return VariantGroup
     */
    public function updateGroup($id, array $data)
    {
        try {
            DB::beginTransaction();

            $group = VariantGroup::findOrFail($id);

            // Update group details
            $group->update([
                'name' => $data['name'],
                'description' => $data['description'] ?? null,
                'status' => $data['status'] ?? 'active',
                'sort_order' => $data['sort_order'] ?? $group->sort_order,
            ]);

            DB::commit();

            Log::info("Variant group updated: {$group->name} (ID: {$group->id})");
            
            return $group->fresh();
        } catch (\Exception $e) {
            DB::rollBack();
            Log::error("Error updating variant group ID {$id}: " . $e->getMessage());
            throw $e;
        }
    }

    /**
     * Delete a variant group (soft delete)
     * 
     * @param int $id
     * @return bool
     */
    public function deleteGroup($id)
    {
        try {
            DB::beginTransaction();

            $group = VariantGroup::findOrFail($id);

            // Check if group has active variants
            $activeVariantsCount = $group->variants()->where('status', 'active')->count();
            
            if ($activeVariantsCount > 0) {
                throw new \Exception("Cannot delete group with {$activeVariantsCount} active variant(s). Please deactivate or remove them first.");
            }

            // Soft delete the group (will cascade to variants due to relationship)
            $group->delete();

            DB::commit();

            Log::info("Variant group deleted: {$group->name} (ID: {$group->id})");
            
            return true;
        } catch (\Exception $e) {
            DB::rollBack();
            Log::error("Error deleting variant group ID {$id}: " . $e->getMessage());
            throw $e;
        }
    }

    /**
     * Toggle variant group status (active/inactive)
     * 
     * @param int $id
     * @param string $status
     * @return VariantGroup
     */
    public function toggleStatus($id, $status)
    {
        try {
            DB::beginTransaction();

            $group = VariantGroup::findOrFail($id);
            
            // Validate status
            if (!in_array($status, ['active', 'inactive'])) {
                throw new \Exception('Invalid status. Must be "active" or "inactive".');
            }

            $group->update(['status' => $status]);

            DB::commit();

            Log::info("Variant group status changed: {$group->name} -> {$status}");
            
            return $group->fresh();
        } catch (\Exception $e) {
            DB::rollBack();
            Log::error("Error toggling variant group status ID {$id}: " . $e->getMessage());
            throw $e;
        }
    }

    /**
     * Get variant groups statistics
     * 
     * @return array
     */
    public function getStatistics()
    {
        try {
            return [
                'total_groups' => VariantGroup::count(),
                'active_groups' => VariantGroup::where('status', 'active')->count(),
                'inactive_groups' => VariantGroup::where('status', 'inactive')->count(),
                'total_variants' => \App\Models\Variant::count(),
            ];
        } catch (\Exception $e) {
            Log::error('Error fetching variant group statistics: ' . $e->getMessage());
            throw $e;
        }
    }

    /**
     * Get only active groups for dropdown/selection
     * 
     * @return \Illuminate\Database\Eloquent\Collection
     */
    public function getActiveGroups()
    {
        try {
            return VariantGroup::active()
                ->select('id', 'name', 'min_select', 'max_select')
                ->orderBy('sort_order')
                ->orderBy('name')
                ->get();
        } catch (\Exception $e) {
            Log::error('Error fetching active variant groups: ' . $e->getMessage());
            throw $e;
        }
    }

    /**
     * Bulk update sort order for variant groups
     * 
     * @param array $sortData [['id' => 1, 'sort_order' => 0], ...]
     * @return bool
     */
    public function updateSortOrder(array $sortData)
    {
        try {
            DB::beginTransaction();

            foreach ($sortData as $item) {
                VariantGroup::where('id', $item['id'])
                    ->update(['sort_order' => $item['sort_order']]);
            }

            DB::commit();

            Log::info('Variant group sort order updated');
            
            return true;
        } catch (\Exception $e) {
            DB::rollBack();
            Log::error('Error updating variant group sort order: ' . $e->getMessage());
            throw $e;
        }
    }
}