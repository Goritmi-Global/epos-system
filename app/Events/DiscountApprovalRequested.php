<?php

namespace App\Events;

use Illuminate\Broadcasting\Channel;
use Illuminate\Broadcasting\InteractsWithSockets;
use Illuminate\Contracts\Broadcasting\ShouldBroadcast;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

class DiscountApprovalRequested implements ShouldBroadcast
{
    use Dispatchable, InteractsWithSockets, SerializesModels;

    public $approvals;

    public function __construct($approvals)
    {
        $this->approvals = $approvals;
    }

    public function broadcastOn()
    {
        return new Channel('discount-approvals');
    }

    public function broadcastAs()
    {
        return 'approval.requested';
    }
}