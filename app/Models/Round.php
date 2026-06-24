<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * A league and course are both optional: a casual round has no league, and its
 * league relation resolves to null.
 *
 * @property-read League|null $league
 * @property-read Course|null $course
 */
class Round extends Model
{
    use HasFactory;

    /**
     * The attributes that are mass assignable.
     *
     * @var list<string>
     */
    protected $fillable = [
        'user_id',
        'league_id',
        'course_id',
        'teebox',
        'course_rating',
        'slope_rating',
        'par',
        'holes',
        'score',
        'created_at', // admins may backdate a round
    ];

    /**
     * The attributes that should be cast.
     *
     * @var array<string, string>
     */
    protected $casts = [
        'score' => 'integer',
        'course_rating' => 'decimal:2',
        'slope_rating' => 'integer',
        'par' => 'integer',
        'holes' => 'integer',
        'created_at' => 'datetime',
    ];

    /**
     * The user who posted this round.
     *
     * @return BelongsTo<User, $this>
     */
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    /**
     * The league this round was played in (null for casual rounds).
     *
     * @return BelongsTo<League, $this>
     */
    public function league(): BelongsTo
    {
        return $this->belongsTo(League::class);
    }

    /**
     * The catalog course this round was played at (set for casual rounds).
     *
     * @return BelongsTo<Course, $this>
     */
    public function course(): BelongsTo
    {
        return $this->belongsTo(Course::class);
    }
}
