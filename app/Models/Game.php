<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Support\Str;

/**
 * A live game: a shared hole-by-hole scorecard for casual (non-league) play.
 * Course context is snapshotted at creation so the game/rounds are self-contained.
 */
class Game extends Model
{
    use HasFactory;

    public const STATUS_LOBBY = 'lobby';

    public const STATUS_ACTIVE = 'active';

    public const STATUS_COMPLETED = 'completed';

    public const STATUS_ABANDONED = 'abandoned';

    /** Max players in a game. */
    public const MAX_PLAYERS = 4;

    /** Minimum players required to start. */
    public const MIN_PLAYERS = 2;

    /**
     * @var list<string>
     */
    protected $fillable = [
        'owner_id',
        'course_id',
        'teebox',
        'holes',
        'par',
        'course_rating',
        'slope_rating',
        'status',
        'join_code',
        'started_at',
        'completed_at',
    ];

    /**
     * @var array<string, string>
     */
    protected $casts = [
        'course_rating' => 'decimal:2',
        'slope_rating' => 'integer',
        'holes' => 'integer',
        'par' => 'integer',
        'started_at' => 'datetime',
        'completed_at' => 'datetime',
    ];

    protected static function booted(): void
    {
        // Assign a unique short join code on create if one wasn't provided.
        static::creating(function (Game $game): void {
            if (empty($game->join_code)) {
                do {
                    $code = Str::upper(Str::random(6));
                } while (static::where('join_code', $code)->exists());

                $game->join_code = $code;
            }
        });
    }

    /**
     * @return BelongsTo<User, $this>
     */
    public function owner(): BelongsTo
    {
        return $this->belongsTo(User::class, 'owner_id');
    }

    /**
     * @return BelongsTo<Course, $this>
     */
    public function course(): BelongsTo
    {
        return $this->belongsTo(Course::class);
    }

    /**
     * @return HasMany<GamePlayer, $this>
     */
    public function players(): HasMany
    {
        return $this->hasMany(GamePlayer::class);
    }

    /**
     * @return BelongsToMany<User, $this>
     */
    public function users(): BelongsToMany
    {
        return $this->belongsToMany(User::class, 'game_players')
            ->withPivot(['confirmed_at', 'round_id'])
            ->withTimestamps();
    }

    /**
     * @return HasMany<GameScore, $this>
     */
    public function scores(): HasMany
    {
        return $this->hasMany(GameScore::class);
    }

    /**
     * Hole numbers 1..holes.
     *
     * @return list<int>
     */
    public function holeNumbers(): array
    {
        return range(1, $this->holes);
    }

    /**
     * A player's gross total (sum of entered strokes across holes).
     */
    public function grossFor(User $user): int
    {
        return (int) $this->scores()
            ->where('user_id', $user->id)
            ->whereNotNull('strokes')
            ->sum('strokes');
    }

    public function isLobby(): bool
    {
        return $this->status === self::STATUS_LOBBY;
    }

    public function isActive(): bool
    {
        return $this->status === self::STATUS_ACTIVE;
    }

    public function isCompleted(): bool
    {
        return $this->status === self::STATUS_COMPLETED;
    }

    public function isFull(): bool
    {
        return $this->players()->count() >= self::MAX_PLAYERS;
    }
}
