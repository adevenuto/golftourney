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

    /**
     * A display label for the game's course (club name, then course name).
     */
    public function courseLabel(): string
    {
        $course = $this->course;

        if (! $course) {
            return 'Casual game';
        }

        return $course->club_name ?? $course->course_name ?? 'Casual game';
    }

    /**
     * A compact list of the games a user is in, most-relevant first (active,
     * then waiting, then finished) — for the games hub and my-handicap.
     *
     * @return list<array<string, mixed>>
     */
    public static function listForUser(User $user): array
    {
        return static::query()
            ->whereHas('players', fn ($q) => $q->where('user_id', $user->id))
            ->with('course:id,club_name,course_name')
            ->withCount('players')
            ->orderByRaw("case status when 'active' then 0 when 'lobby' then 1 else 2 end")
            ->orderByDesc('created_at')
            ->limit(12)
            ->get()
            ->map(fn (Game $g): array => [
                'id' => $g->id,
                'status' => $g->status,
                'course_name' => $g->courseLabel(),
                'holes' => $g->holes,
                'players_count' => $g->players_count,
                'is_owner' => $g->owner_id === $user->id,
            ])
            ->all();
    }
}
