<?php

namespace App\Events;

use App\Models\StockEntry;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Log;
use Illuminate\Contracts\Events\ShouldDispatchAfterCommit;

class StockEntryCreated implements ShouldDispatchAfterCommit
{
    use Dispatchable, SerializesModels;

    public $stockEntry;

    public function __construct(StockEntry $stockEntry)
    {
        Log::info('🎪 EVENT CONSTRUCTED: StockEntryCreated', [
            'stock_entry_id' => $stockEntry->id,
            'product_id' => $stockEntry->product_id,
            'stock_type' => $stockEntry->stock_type,
        ]);
        
        $this->stockEntry = $stockEntry;
    }
}