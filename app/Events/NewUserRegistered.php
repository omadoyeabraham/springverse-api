<?php

namespace App\Events;

use App\User;
use Illuminate\Broadcasting\Channel;
use Illuminate\Broadcasting\InteractsWithSockets;
use Illuminate\Broadcasting\PresenceChannel;
use Illuminate\Broadcasting\PrivateChannel;
use Illuminate\Contracts\Broadcasting\ShouldBroadcast;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

class NewUserRegistered
{
    use Dispatchable, InteractsWithSockets, SerializesModels;

    public $user;

    public $defaultPassword;
    /**
     * @var String
     */
    public $source;

    /**
     * Create a new event instance.
     *
     * @param User $user
     * @param String $defaultPassword
     * @param String $source
     */
    public function __construct(User $user, String $defaultPassword, $source)
    {
        $this->user = $user;
        $this->defaultPassword = $defaultPassword;
        $this->source = $source;
    }

    /**
     * Get the channels the event should broadcast on.
     *
     * @return \Illuminate\Broadcasting\Channel|array
     */
    public function broadcastOn()
    {
        return new PrivateChannel('channel-name');
    }
}
