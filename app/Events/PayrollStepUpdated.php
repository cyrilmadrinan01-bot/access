<?php

namespace App\Events;

use Illuminate\Broadcasting\Channel;
use Illuminate\Broadcasting\InteractsWithSockets;
use Illuminate\Broadcasting\PresenceChannel;
use Illuminate\Broadcasting\PrivateChannel;
use Illuminate\Contracts\Broadcasting\ShouldBroadcast;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

class PayrollStepUpdated implements ShouldBroadcast
{
    use Dispatchable, SerializesModels;

    public $cutoffId;
    public $step;
    public $status;

    public function __construct($cutoffId, $step, $status)
    {
        $this->cutoffId = $cutoffId;
        $this->step = $step;
        $this->status = $status;
    }

    public function broadcastOn()
    {
        return new Channel('payroll.' . $this->cutoffId);
    }
}
