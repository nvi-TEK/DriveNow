<?php

namespace App\Events;

use Pubnub\Pubnub;
use Illuminate\Broadcasting\Channel;
use Illuminate\Queue\SerializesModels;
use Illuminate\Broadcasting\PrivateChannel;
use Illuminate\Broadcasting\PresenceChannel;
use Illuminate\Broadcasting\InteractsWithSockets;
use Illuminate\Contracts\Broadcasting\ShouldBroadcast;

class tracking
{
    use InteractsWithSockets, SerializesModels;

    protected $pubnub;
    /**
     * Create a new event instance.
     *
     * @return void
     */
    public function __construct()
    {
        $this->pubnub = $pubnub;
    }

    /**
     * Get the channels the event should broadcast on.
     *
     * @return Channel|array
     */
    public function broadcast(array $channels, $event, array $payload = [])
    {
        foreach ($channels as $channel) {
            $this->pubnub->publish($channel, [
                'event' => $event,
                'data' => $payload
            ]);
        }
    }
}
