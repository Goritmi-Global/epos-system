<?php

namespace App\Services;

use App\Models\Addon;
use App\Models\AddonGroup;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

class AddonService
{
    /**
     * Get all addons with their group information
     * 
     * @return \Illuminate\Database\Eloquent\Collection
     */
    public function getAllAddons()
    {
        try {
            // Fetch all addons with their parent group
            return Addon::with('addonGroup')
                ->orderBy('sort_order')
                ->orderBy('created_at', 'desc')
                ->get();
        } catch (\Exception $e) {
            Log::error('Error fetching addons: ' . $e->getMessage());
            throw $e;
        }
    }

    /**
     * Get addons by group ID
     * 
     * @param int $groupId
     * @return \Illuminate\Database\Eloquent\Collection
     */
    public function getAddonsByGroup($groupId)
    {
        try {
            return Addon::where('addon_group_id', $groupId)
                ->orderBy('sort_order')
                ->orderBy('name')
                ->get();
        } catch (\Exception $e) {
            Log::error("Error fetching addons for group ID {$groupId}: " . $e->getMessage());
            throw $e;
        }
    }

    /**
     * Get a single addon by ID
     * 
     * @param int $id
     * @return Addon
     */
    public function getAddonById($id)
    {
        try {
            return Addon::with('addonGroup')->findOrFail($id);
        } catch (\Exception $e) {
            Log::error("Error fetching addon ID {$id}: " . $e->getMessage());
            throw $e;
        }
    }

    /**
     * Create a new addon
     * 
     * @param array $data
     * @return Addon
     */
    public function createAddon(array $data)
    {
        try {
            DB::beginTransaction();

            // Verify the addon group exists and is active
            $group = AddonGroup::findOrFail($data['addon_group_id']);
            
            if ($group->status === 'inactive') {
                throw new \Exception('Cannot add addon to an inactive group.');
            }

            // Get the next sort order for this group
            $nextSortOrder = Addon::where('addon_group_id', $data['addon_group_id'])
                ->max('sort_order') + 1;

            // Create new addon
            $addon = Addon::create([
                'name' => $data['name'],
                'addon_group_id' => $data['addon_group_id'],
                'price' => $data['price'],
                'description' => $data['description'] ?? null,
                'status' => $data['status'] ?? 'active',
                'sort_order' => $data['sort_order'] ?? $nextSortOrder,
            ]);

            DB::commit();

            Log::info("Addon created: {$addon->name} in group {$group->name} (ID: {$addon->id})");
            
            return $addon->load('addonGroup');
        } catch (\Exception $e) {
            DB::rollBack();
            Log::error('Error creating addon: ' . $e->getMessage());
            throw $e;
        }
    }

    /**
     * Update an existing addon
     * 
     * @param int $id
     * @param array $data
     * @return Addon
     */
    public function updateAddon($id, array $data)
    {
        try {
            DB::beginTransaction();

            $addon = Addon::findOrFail($id);

            // If changing group, verify new group exists and is active
            if (isset($data['addon_group_id']) && $data['addon_group_id'] != $addon->addon_group_id) {
                $newGroup = AddonGroup::findOrFail($data['addon_group_id']);
                
                if ($newGroup->status === 'inactive') {
                    throw new \Exception('Cannot move addon to an inactive group.');
                }
            }

            // Update addon details
            $addon->update([
                'name' => $data['name'],
                'addon_group_id' => $data['addon_group_id'],
                'price' => $data['price'],
                'description' => $data['description'] ?? null,
                'status' => $data['status'] ?? 'active',
                'sort_order' => $data['sort_order'] ?? $addon->sort_order,
            ]);

            DB::commit();

            Log::info("Addon updated: {$addon->name} (ID: {$addon->id})");
            
            return $addon->fresh()->load('addonGroup');
        } catch (\Exception $e) {
            DB::rollBack();
            Log::error("Error updating addon ID {$id}: " . $e->getMessage());
            throw $e;
        }
    }

    /**
     * Delete an addon (soft delete)
     * 
     * @param int $id
     * @return bool
     */
    public function deleteAddon($id)
    {
        try {
            DB::beginTransaction();

            $addon = Addon::findOrFail($id);

            // TODO: Add check if addon is being used in any orders
            // Example: if ($addon->orderItems()->exists()) { throw exception }

            // Soft delete the addon
            $addon->delete();

            DB::commit();

            Log::info("Addon deleted: {$addon->name} (ID: {$addon->id})");
            
            return true;
        } catch (\Exception $e) {
            DB::rollBack();
            Log::error("Error deleting addon ID {$id}: " . $e->getMessage());
            throw $e;
        }
    }

    /**
     * Toggle addon status (active/inactive)
     * 
     * @param int $id
     * @param string $status
     * @return Addon
     */
    public function toggleStatus($id, $status)
    {
        try {
            DB::beginTransaction();

            $addon = Addon::findOrFail($id);
            
            // Validate status
            if (!in_array($status, ['active', 'inactive'])) {
                throw new \Exception('Invalid status. Must be "active" or "inactive".');
            }

            $addon->update(['status' => $status]);

            DB::commit();

            Log::info("Addon status changed: {$addon->name} -> {$status}");
            
            return $addon->fresh();
        } catch (\Exception $e) {
            DB::rollBack();
            Log::error("Error toggling addon status ID {$id}: " . $e->getMessage());
            throw $e;
        }
    }

    /**
     * Bulk update sort order for addons
     * 
     * @param array $sortData [['id' => 1, 'sort_order' => 0], ...]
     * @return bool
     */
    public function updateSortOrder(array $sortData)
    {
        try {
            DB::beginTransaction();

            foreach ($sortData as $item) {
                Addon::where('id', $item['id'])
                    ->update(['sort_order' => $item['sort_order']]);
            }

            DB::commit();

            Log::info('Addon sort order updated');
            
            return true;
        } catch (\Exception $e) {
            DB::rollBack();
            Log::error('Error updating addon sort order: ' . $e->getMessage());
            throw $e;
        }
    }

    /**
     * Get addons statistics
     * 
     * @return array
     */
    public function getStatistics()
    {
        try {
            return [
                'total_addons' => Addon::count(),
                'active_addons' => Addon::where('status', 'active')->count(),
                'inactive_addons' => Addon::where('status', 'inactive')->count(),
                'average_price' => Addon::avg('price'),
            ];
        } catch (\Exception $e) {
            Log::error('Error fetching addon statistics: ' . $e->getMessage());
            throw $e;
        }
    }

    /**
     * Validate addon selection against group rules
     * Used during order creation to ensure min/max rules are followed
     * 
     * @param int $groupId
     * @param array $selectedAddonIds
     * @return array ['valid' => bool, 'message' => string]
     */
    public function validateSelection($groupId, array $selectedAddonIds)
    {
        try {
            $group = AddonGroup::findOrFail($groupId);
            $selectedCount = count($selectedAddonIds);

            // Check minimum requirement
            if ($selectedCount < $group->min_select) {
                return [
                    'valid' => false,
                    'message' => "Please select at least {$group->min_select} item(s) from {$group->name}."
                ];
            }

            // Check maximum limit
            if ($selectedCount > $group->max_select) {
                return [
                    'valid' => false,
                    'message' => "You can select maximum {$group->max_select} item(s) from {$group->name}."
                ];
            }

            // Verify all selected addons belong to this group and are active
            $validAddons = Addon::where('addon_group_id', $groupId)
                ->where('status', 'active')
                ->whereIn('id', $selectedAddonIds)
                ->count();

            if ($validAddons !== $selectedCount) {
                return [
                    'valid' => false,
                    'message' => 'Some selected addons are invalid or inactive.'
                ];
            }

            return [
                'valid' => true,
                'message' => 'Selection is valid.'
            ];
        } catch (\Exception $e) {
            Log::error("Error validating addon selection: " . $e->getMessage());
            return [
                'valid' => false,
                'message' => 'Error validating selection.'
            ];
        }
    }
}