<?php

namespace App\Services\Shifts;

use App\Models\Shift;

class ShiftManagementService
{
    /**
     * Fetch all shifts with their related user and computed values.
     */
    public function getAllShifts()
    {
        return \App\Models\Shift::with(['starter', 'ender', 'details'])
            ->orderBy('created_at', 'desc')
            ->get()
            ->map(function ($shift) {
                return [
                    'id' => $shift->id,
                    'started_by' => $shift->starter?->name ?? 'N/A',
                    'ended_by' => $shift->ender?->name ?? 'N/A',
                    'start_time' => $shift->start_time,
                    'opening_cash' => $shift->opening_cash,
                    'closing_cash' => $shift->closing_cash,
                    'sales_total' => $shift->sales_total ?? 0,
                    'status' => $shift->status,
                    'roles' => $shift->details->pluck('role')->unique()->values(),
                ];
            });
    }
}
