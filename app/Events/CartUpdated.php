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
        
        Log::info('🔔 CartUpdated Event Created', [
            'terminal' => $terminalId,
            'channel' => 'pos-terminal.' . $terminalId,
            'items_count' => count($cartData['items'] ?? []),
            'total' => $cartData['total'] ?? 0
        ]);
    }

    public function broadcastOn()
    {
        $channelName = 'pos-terminal.' . $this->terminalId;
        Log::info('📡 Broadcasting CartUpdated on channel: ' . $channelName);
        return new Channel($channelName);
    }

    public function broadcastAs()
    {
        return 'cart.updated';
    }

    public function broadcastWith()
    {
        $data = [
            'cart' => $this->cartData,
            'timestamp' => now()->toIso8601String(),
        ];
        
        Log::info('📤 Broadcasting data:', [
            'event' => 'cart.updated',
            'channel' => 'pos-terminal.' . $this->terminalId,
            'data_keys' => array_keys($data)
        ]);
        
        return $data;
    }
}