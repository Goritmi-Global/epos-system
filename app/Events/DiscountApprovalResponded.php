<?php

namespace App\Events;

use Illuminate\Broadcasting\Channel;
use Illuminate\Broadcasting\InteractsWithSockets;
use Illuminate\Contracts\Broadcasting\ShouldBroadcast;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

class DiscountApprovalResponded implements ShouldBroadcast
{
    use Dispatchable, InteractsWithSockets, SerializesModels;

    public $approval;

    public function __construct($approval)
    {
        $this->approval = $approval;
    }

    public function broadcastOn()
    {
        return new Channel('discount-approvals');
    }

    public function broadcastAs()
    {
        return 'approval.responded';
    }
}