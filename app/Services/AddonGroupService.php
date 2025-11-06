<?php

namespace App\Services;

use App\Models\AddonGroup;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

class AddonGroupService
{
    /**
     * Get all addon groups with their addons count
     * 
     * @return \Illuminate\Database\Eloquent\Collection
     */
    public function getAllGroups()
    {
        try {
           
            return AddonGroup::withCount('addons')
            ->with('addons')
                ->orderBy('created_at', 'desc')
                ->get();
        } catch (\Exception $e) {
            Log::error('Error fetching addon groups: ' . $e->getMessage());
            throw $e;
        }
    }

    /**
     * Get a single addon group by ID with its addons
     * 
     * @param int $id
     * @return AddonGroup
     */
    public function getGroupById($id)
    {
        try {
            return AddonGroup::with('addons')->findOrFail($id);
        } catch (\Exception $e) {
            Log::error("Error fetching addon group ID {$id}: " . $e->getMessage());
            throw $e;
        }
    }

    /**
     * Create a new addon group
     * 
     * @param array $data
     * @return AddonGroup
     */
    public function createGroup(array $data)
    {
        try {
            DB::beginTransaction();

            // Create new addon group
            $group = AddonGroup::create([
                'name' => $data['name'],
                'min_select' => $data['min_select'],
                'max_select' => $data['max_select'],
                'description' => $data['description'] ?? null,
                'status' => $data['status'] ?? 'active',
            ]);

            DB::commit();

            Log::info("Addon group created: {$group->name} (ID: {$group->id})");
            
            return $group;
        } catch (\Exception $e) {
            DB::rollBack();
            Log::error('Error creating addon group: ' . $e->getMessage());
            throw $e;
        }
    }

    /**
     * Update an existing addon group
     * 
     * @param int $id
     * @param array $data
     * @return AddonGroup
     */
    public function updateGroup($id, array $data)
    {
        try {
            DB::beginTransaction();

            $group = AddonGroup::findOrFail($id);

            // Update group details
            $group->update([
                'name' => $data['name'],
                'min_select' => $data['min_select'],
                'max_select' => $data['max_select'],
                'description' => $data['description'] ?? null,
                'status' => $data['status'] ?? 'active',
            ]);

            DB::commit();

            Log::info("Addon group updated: {$group->name} (ID: {$group->id})");
            
            return $group->fresh();
        } catch (\Exception $e) {
            DB::rollBack();
            Log::error("Error updating addon group ID {$id}: " . $e->getMessage());
            throw $e;
        }
    }

    /**
     * Delete an addon group (soft delete)
     * 
     * @param int $id
     * @return bool
     */
    public function deleteGroup($id)
    {
        try {
            DB::beginTransaction();

            $group = AddonGroup::findOrFail($id);

            // Check if group has active addons
            $activeAddonsCount = $group->addons()->where('status', 'active')->count();
            
            if ($activeAddonsCount > 0) {
                throw new \Exception("Cannot delete group with {$activeAddonsCount} active addon(s). Please deactivate or remove them first.");
            }

            // Soft delete the group (will cascade to addons due to relationship)
            $group->delete();

            DB::commit();

            Log::info("Addon group deleted: {$group->name} (ID: {$group->id})");
            
            return true;
        } catch (\Exception $e) {
            DB::rollBack();
            Log::error("Error deleting addon group ID {$id}: " . $e->getMessage());
            throw $e;
        }
    }

    /**
     * Toggle addon group status (active/inactive)
     * 
     * @param int $id
     * @param string $status
     * @return AddonGroup
     */
    public function toggleStatus($id, $status)
    {
        try {
            DB::beginTransaction();

            $group = AddonGroup::findOrFail($id);
            
            // Validate status
            if (!in_array($status, ['active', 'inactive'])) {
                throw new \Exception('Invalid status. Must be "active" or "inactive".');
            }

            $group->update(['status' => $status]);

            DB::commit();

            Log::info("Addon group status changed: {$group->name} -> {$status}");
            
            return $group->fresh();
        } catch (\Exception $e) {
            DB::rollBack();
            Log::error("Error toggling addon group status ID {$id}: " . $e->getMessage());
            throw $e;
        }
    }

    /**
     * Get addon groups statistics
     * 
     * @return array
     */
    public function getStatistics()
    {
        try {
            return [
                'total_groups' => AddonGroup::count(),
                'active_groups' => AddonGroup::where('status', 'active')->count(),
                'inactive_groups' => AddonGroup::where('status', 'inactive')->count(),
                'total_addons' => \App\Models\Addon::count(),
            ];
        } catch (\Exception $e) {
            Log::error('Error fetching addon group statistics: ' . $e->getMessage());
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
            return AddonGroup::active()
                ->select('id', 'name', 'min_select', 'max_select')
                ->orderBy('name')
                ->get();
        } catch (\Exception $e) {
            Log::error('Error fetching active addon groups: ' . $e->getMessage());
            throw $e;
        }
    }
}