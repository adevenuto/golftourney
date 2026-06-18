<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Course extends Model
{
    use HasFactory;

    /**
     * @var list<string>
     */
    protected $fillable = [
        'external_id',
        'api_course_id',
        'course_name',
        'club_name',
        'street',
        'city_id',
        'state_id',
        'state',
        'postal_code',
        'lat',
        'lng',
        'phone',
        'website',
        'layout_data',
    ];

    /**
     * @var array<string, string>
     */
    protected $casts = [
        'layout_data' => 'array',
        'lat' => 'decimal:7',
        'lng' => 'decimal:7',
    ];

    /**
     * Leagues that play this course.
     *
     * @return HasMany<League, $this>
     */
    public function leagues(): HasMany
    {
        return $this->hasMany(League::class);
    }

    /**
     * The teeboxes (each with rating/slope/yardage) parsed from layout_data.
     *
     * @return array<int, array<string, mixed>>
     */
    public function teeboxes(): array
    {
        return $this->layout_data['teeboxes'] ?? [];
    }
}
