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

    /**
     * The course's hole count (9 or 18) from layout_data, or null if unknown.
     */
    public function holeCount(): ?int
    {
        $count = is_array($this->layout_data) ? ($this->layout_data['hole_count'] ?? null) : null;

        return in_array($count, [9, 18], true) ? (int) $count : null;
    }

    /**
     * Total par for a teebox — matched by name, else by slope, else the first.
     * Sums the per-hole pars (stored as strings). Null if unavailable.
     */
    public function parForTeebox(?string $name, ?int $slope = null): ?int
    {
        $teeboxes = $this->teeboxes();
        $match = null;

        foreach ($teeboxes as $tee) {
            if ($name && strcasecmp((string) ($tee['name'] ?? ''), $name) === 0) {
                $match = $tee;
                break;
            }
        }

        if (! $match && $slope) {
            foreach ($teeboxes as $tee) {
                if (isset($tee['slope']) && (int) $tee['slope'] === $slope) {
                    $match = $tee;
                    break;
                }
            }
        }

        $match ??= $teeboxes[0] ?? null;

        if (! $match || ! is_array($match['holes'] ?? null)) {
            return null;
        }

        $par = 0;
        foreach ($match['holes'] as $hole) {
            $par += (int) ($hole['par'] ?? 0);
        }

        return $par ?: null;
    }

    /**
     * Per-hole par for a teebox, keyed by hole number (1-based): {1:4, 2:5, …}.
     * Used to snapshot a game's hole pars (PAR row + score colouring).
     *
     * @return array<int, int>
     */
    public function holePars(?string $teebox): array
    {
        return $this->holeField($teebox, 'par');
    }

    /**
     * Per-hole length (yardage) for a teebox, keyed by hole number: {1:507, …}.
     * Used to snapshot a game's hole yardages for the scorecard's Yards row.
     *
     * @return array<int, int>
     */
    public function holeLengths(?string $teebox): array
    {
        return $this->holeField($teebox, 'length');
    }

    /**
     * Per-hole integer values for a teebox field ('par' | 'length'), keyed by
     * hole number (1-based). Empty if the tee or its hole data is missing.
     *
     * @return array<int, int>
     */
    private function holeField(?string $teebox, string $field): array
    {
        $match = null;
        foreach ($this->teeboxes() as $tee) {
            if ($teebox && strcasecmp((string) ($tee['name'] ?? ''), $teebox) === 0) {
                $match = $tee;
                break;
            }
        }
        $match ??= $this->teeboxes()[0] ?? null;

        if (! $match || ! is_array($match['holes'] ?? null)) {
            return [];
        }

        $values = [];
        $i = 0;
        foreach ($match['holes'] as $key => $hole) {
            $i++;
            $n = preg_match('/(\d+)/', (string) $key, $m) ? (int) $m[1] : $i;
            $value = (int) ($hole[$field] ?? 0);
            if ($n >= 1 && $value > 0) {
                $values[$n] = $value;
            }
        }
        ksort($values);

        return $values;
    }

    /**
     * The handicap-scoring context for a teebox: normalized course rating,
     * slope, par, and hole count. Null if the teebox or its data is missing.
     * Used to snapshot a casual round's course onto the round.
     *
     * @return array{course_rating: float, slope_rating: int, par: int, holes: int}|null
     */
    public function teeboxContext(?string $teebox): ?array
    {
        $match = null;
        foreach ($this->teeboxes() as $tee) {
            if ($teebox && strcasecmp((string) ($tee['name'] ?? ''), $teebox) === 0) {
                $match = $tee;
                break;
            }
        }
        $match ??= $this->teeboxes()[0] ?? null;

        $par = $this->parForTeebox($match['name'] ?? $teebox);
        $holes = $this->holeCount();
        $rawRating = isset($match['courseRating']) ? (float) $match['courseRating'] : null;
        $slope = isset($match['slope']) ? (int) $match['slope'] : null;

        if (! $match || $rawRating === null || ! $slope || ! $par || ! $holes) {
            return null;
        }

        return [
            'course_rating' => self::normalizeRating($rawRating, $par),
            'slope_rating' => $slope,
            'par' => $par,
            'holes' => $holes,
        ];
    }

    /**
     * Normalize a stored course rating to the scale matching its par.
     * The source API doubled 9-hole ratings onto the 18-hole scale; par is the
     * anchor — whichever of {par, 2*par} the rating is closer to reveals its
     * scale. Safe to call on already-correct ratings (no-op) and when par is
     * unknown.
     */
    public static function normalizeRating(float $rating, int $par): float
    {
        if ($par <= 0) {
            return $rating;
        }

        return abs($rating - 2 * $par) < abs($rating - $par)
            ? $rating / 2
            : $rating;
    }
}
