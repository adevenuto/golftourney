<?php

use App\Models\Course;
use App\Models\League;
use Illuminate\Database\Migrations\Migration;

return new class extends Migration
{
    /**
     * Backfill each league's hole count and par from its linked catalog course,
     * so Course Handicaps can be derived. Holes come from layout_data.hole_count;
     * par is the sum of the relevant teebox's hole pars (matched by the league's
     * chosen teebox, else by slope, else the first teebox). Leagues with no
     * linked course keep the default 18 holes and a null par (their rounds are
     * then excluded from the index until par is set).
     */
    public function up(): void
    {
        foreach (League::all() as $league) {
            if (! $league->course_id) {
                continue;
            }

            $course = Course::find($league->course_id);

            if (! $course) {
                continue;
            }

            $league->update([
                'holes' => $course->holeCount() ?? $league->holes,
                'par' => $course->parForTeebox($league->teebox, (int) $league->slope_rating),
            ]);
        }
    }

    public function down(): void
    {
        // Forward-only: leave the derived holes/par in place.
    }
};
