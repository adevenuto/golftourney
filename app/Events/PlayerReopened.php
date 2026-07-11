<?php

namespace App\Events;

use Illuminate\Broadcasting\InteractsWithSockets;
use Illuminate\Broadcasting\PresenceChannel;
use Illuminate\Contracts\Broadcasting\ShouldBroadcast;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

/**
 * A player reopened their card after finishing (to fix/complete a hole) while
 * the game is still going. Peers flip them back to "still playing" on the live
 * results list.
 */
class PlayerReopened implements ShouldBroadcast
{
    use Dispatchable, InteractsWithSockets, SerializesModels;

    public function __construct(public int $gameId, public int $userId) {}

    /**
     * @return array<int, PresenceChannel>
     */
    public function broadcastOn(): array
    {
        return [new PresenceChannel('game.'.$this->gameId)];
    }

    public function broadcastAs(): string
    {
        return 'player.reopened';
    }
}
