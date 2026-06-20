<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Support\Facades\Cache;

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
        'course_rating',
        'slope_rating',
        'recent_rounds',
        'counting_rounds',
    ];

    /**
     * @var array<string, string>
     */
    protected $casts = [
        'course_rating' => 'decimal:2',
        'slope_rating' => 'integer',
        'recent_rounds' => 'integer',
        'counting_rounds' => 'integer',
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
     * Golfers on this league's roster, with their per-league handicap.
     *
     * @return BelongsToMany<Golfer, $this>
     */
    public function golfers(): BelongsToMany
    {
        return $this->belongsToMany(Golfer::class)
            ->withPivot('handicap')
            ->withTimestamps();
    }

    /**
     * Users who belong to this league, with their per-league role.
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
     * Cache key for this league's golfer roster (the Golfers/Index payload).
     */
    public function rosterCacheKey(): string
    {
        return "league.{$this->id}.roster";
    }

    /**
     * Invalidate the cached roster — call whenever a golfer, round, or handicap
     * in this league changes.
     */
    public function forgetRosterCache(): void
    {
        Cache::forget($this->rosterCacheKey());
    }
}
