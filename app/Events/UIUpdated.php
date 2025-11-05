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

        Log::info('UIUpdated event created', [
            'terminal' => $terminalId,
            'channel' => 'pos-terminal.' . $terminalId,
            'keys' => array_keys($uiData ?? []),
        ]);
    }

    public function broadcastOn()
    {
        $channel = new Channel('pos-terminal.' . $this->terminalId);
        Log::info('Broadcasting UI update on channel', ['channel' => $channel->name]);
        return $channel;
    }

    public function broadcastAs()
    {
        return 'ui.updated';
    }

    public function broadcastWith()
    {
        return [
            'ui' => $this->uiData,
            'timestamp' => now()->toIso8601String(),
        ];
    }
}
