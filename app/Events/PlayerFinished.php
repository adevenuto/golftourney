<?php

namespace App\Events;

use Illuminate\Broadcasting\InteractsWithSockets;
use Illuminate\Broadcasting\PresenceChannel;
use Illuminate\Contracts\Broadcasting\ShouldBroadcast;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

/**
 * One player finished their own round (posted it to their handicap). Peers use
 * this to mark them done on the live results list. The game itself completes
 * only once every player has finished (a separate GameCompleted event).
 */
class PlayerFinished implements ShouldBroadcast
{
    use Dispatchable, InteractsWithSockets, SerializesModels;

    public function __construct(public int $gameId, public int $userId, public int $gross) {}

    /**
     * @return array<int, PresenceChannel>
     */
    public function broadcastOn(): array
    {
        return [new PresenceChannel('game.'.$this->gameId)];
    }

    public function broadcastAs(): string
    {
        return 'player.finished';
    }
}
