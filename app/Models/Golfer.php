<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Golfer extends Model
{
    use HasFactory;

    /**
     * The attributes that are mass assignable.
     *
     * @var array<int, string>
     */
    protected $fillable = [
        'first_name',
        'last_name',
        'handicap',
        'email',
        'phone',
    ];

    /**
     * The attributes that should be cast.
     *
     * @var array<string, string>
     */
    protected $casts = [
        'handicap' => 'decimal:2',
    ];

    /**
     * Cascade-delete a golfer's rounds when the golfer is removed.
     */
    protected static function booted(): void
    {
        static::deleting(function (Golfer $golfer) {
            $golfer->rounds()->delete();
        });
    }

    /**
     * The rounds posted by this golfer.
     */
    public function rounds(): HasMany
    {
        return $this->hasMany(Round::class);
    }
}
