<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Round extends Model
{
    use HasFactory;

    /**
     * The attributes that are mass assignable.
     *
     * @var list<string>
     */
    protected $fillable = [
        'golfer_id',
        'league_id',
        'score',
        'course_name',
        'created_at', // admins may backdate a round
    ];

    /**
     * The attributes that should be cast.
     *
     * @var array<string, string>
     */
    protected $casts = [
        'score' => 'integer',
        'created_at' => 'datetime',
    ];

    /**
     * The golfer who posted this round.
     *
     * @return BelongsTo<Golfer, $this>
     */
    public function golfer(): BelongsTo
    {
        return $this->belongsTo(Golfer::class);
    }

    /**
     * The league (and thus course) this round was played in.
     *
     * @return BelongsTo<League, $this>
     */
    public function league(): BelongsTo
    {
        return $this->belongsTo(League::class);
    }
}
