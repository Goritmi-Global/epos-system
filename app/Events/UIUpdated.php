<?php

namespace App\Events;

use Illuminate\Broadcasting\Channel;
use Illuminate\Broadcasting\InteractsWithSockets;
use Illuminate\Contracts\Broadcasting\ShouldBroadcast;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Log;

class UIUpdated implements ShouldBroadcast
{
    use Dispatchable, InteractsWithSockets, SerializesModels;

    public $terminalId;
    public $uiData;

    public function __construct($terminalId, $uiData)
    {
        $this->terminalId = $terminalId;
        $this->uiData = $uiData;

        Log::info('🔔 UIUpdated Event Created', [
            'terminal' => $terminalId,
            'channel' => 'pos-terminal.' . $terminalId,
            'keys' => array_keys($uiData ?? []),
        ]);
    }

    public function broadcastOn()
    {
        $channelName = 'pos-terminal.' . $this->terminalId;
        Log::info('📡 Broadcasting UIUpdated on channel: ' . $channelName);
        return new Channel($channelName);
    }

    public function broadcastAs()
    {
        return 'ui.updated';
    }

    public function broadcastWith()
    {
        $data = [
            'ui' => $this->uiData,
            'timestamp' => now()->toIso8601String(),
        ];
        
        Log::info('📤 Broadcasting data:', [
            'event' => 'ui.updated',
            'channel' => 'pos-terminal.' . $this->terminalId,
            'data_keys' => array_keys($data)
        ]);
        
        return $data;
    }
}