<?php

namespace App\Events;

use Illuminate\Broadcasting\Channel;
use Illuminate\Broadcasting\InteractsWithSockets;
use Illuminate\Contracts\Broadcasting\ShouldBroadcast;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Log;

class CartUpdated implements ShouldBroadcast
{
    use Dispatchable, InteractsWithSockets, SerializesModels;

    public $terminalId;
    public $cartData;

    public function __construct($terminalId, $cartData)
    {
        $this->terminalId = $terminalId;
        $this->cartData = $cartData;
        
        Log::info('CartUpdated event created', [
            'terminal' => $terminalId,
            'channel' => 'pos-terminal.' . $terminalId,
            'items_count' => count($cartData['items'] ?? [])
        ]);
    }

    public function broadcastOn()
    {
        $channel = new Channel('pos-terminal.' . $this->terminalId);
        Log::info('Broadcasting on channel', ['channel' => $channel->name]);
        return $channel;
    }

    public function broadcastAs()
    {
        return 'cart.updated';
    }

    public function broadcastWith()
    {
        Log::info('Broadcast data prepared', [
            'items_count' => count($this->cartData['items'] ?? []),
            'total' => $this->cartData['total'] ?? 0
        ]);
        
        return [
            'cart' => $this->cartData,
            'timestamp' => now()->toIso8601String(),
        ];
    }
}