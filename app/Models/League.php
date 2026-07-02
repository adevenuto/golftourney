<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;

class League extends Model
{
    use HasFactory;

    /**
     * @var list<string>
     */
    protected $fillable = [
        'name',
        'owner_id',
        'course_id',
        'teebox',
        'holes',
        'par',
        'course_rating',
        'slope_rating',
        'recent_rounds',
        'counting_rounds',
        'league_only',
        'display_nine_hole_index',
    ];

    /**
     * @var array<string, string>
     */
    protected $casts = [
        'course_rating' => 'decimal:2',
        'slope_rating' => 'integer',
        'holes' => 'integer',
        'par' => 'integer',
        'recent_rounds' => 'integer',
        'counting_rounds' => 'integer',
        'league_only' => 'boolean',
        'display_nine_hole_index' => 'boolean',
    ];

    /**
     * The user who created/owns this league.
     *
     * @return BelongsTo<User, $this>
     */
    public function owner(): BelongsTo
    {
        return $this->belongsTo(User::class, 'owner_id');
    }

    /**
     * The catalog course this league plays (for provenance/display).
     *
     * @return BelongsTo<Course, $this>
     */
    public function course(): BelongsTo
    {
        return $this->belongsTo(Course::class);
    }

    /**
     * Users on this league's roster, with their per-league role.
     * (Every member is a golfer; the 4 admins included.)
     *
     * @return BelongsToMany<User, $this>
     */
    public function members(): BelongsToMany
    {
        return $this->belongsToMany(User::class, 'league_user')
            ->withPivot('role')
            ->withTimestamps();
    }

    /**
     * @return HasMany<Round, $this>
     */
    public function rounds(): HasMany
    {
        return $this->hasMany(Round::class);
    }

    /**
     * Bump whenever the cached roster payload's SHAPE changes (a new/renamed
     * field), so old forever-cached payloads are abandoned on deploy instead of
     * serving a stale shape.
     */
    public const ROSTER_CACHE_VERSION = 3;

    /**
     * Cache key for this league's golfer roster (the Golfers/Index payload).
     */
    public function rosterCacheKey(): string
    {
        return "league.{$this->id}.roster.v".self::ROSTER_CACHE_VERSION;
    }

    /**
     * Invalidate the cached roster — call whenever a golfer, round, or handicap
     * in this league changes.
     */
    public function forgetRosterCache(): void
    {
        Cache::forget($this->rosterCacheKey());
    }

    /**
     * Tear this league down: keep everyone's history by detaching this league's
     * rounds into standalone casual rounds, remove every membership, move anyone
     * parked here onto their next available league (or none), then delete the
     * league. No users are deleted — that would cascade their rounds.
     */
    public function dissolve(): void
    {
        DB::transaction(function () {
            // Keep the history: league rounds live on as casual rounds.
            $this->rounds()->update(['league_id' => null]);

            // Everyone currently viewing this league; move them off it once gone.
            $strandedIds = User::where('current_league_id', $this->id)->pluck('id');

            $this->members()->detach();

            foreach ($strandedIds as $userId) {
                // Their next league, ordered like the nav switcher (by name).
                $next = DB::table('league_user as lu')
                    ->join('leagues as l', 'l.id', '=', 'lu.league_id')
                    ->where('lu.user_id', $userId)
                    ->orderBy('l.name')
                    ->value('lu.league_id');

                User::whereKey($userId)->update(['current_league_id' => $next]);
            }

            $this->delete();
        });

        $this->forgetRosterCache();
    }
}
