<?php

namespace App\Http\Controllers;

use App\Models\Course;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class CoursesController extends Controller
{
    public function __construct()
    {
        $this->middleware('auth');
    }

    /**
     * Autocomplete search over the course catalog (min 3 chars).
     */
    public function search(Request $request): JsonResponse
    {
        $term = trim((string) $request->query('q', ''));

        if (mb_strlen($term) < 3) {
            return response()->json(['courses' => []]);
        }

        $courses = Course::query()
            ->where('course_name', 'like', '%'.$term.'%')
            ->orderBy('course_name')
            ->limit(12)
            ->get(['id', 'course_name', 'club_name', 'state', 'postal_code', 'layout_data']);

        return response()->json([
            'courses' => $courses->map(fn (Course $course): array => [
                'id' => $course->id,
                'name' => $course->course_name,
                'club' => $course->club_name,
                'location' => trim(($course->state ?? '').' '.($course->postal_code ?? '')) ?: null,
                'holes' => is_array($course->layout_data) && isset($course->layout_data['hole_count'])
                    ? (int) $course->layout_data['hole_count']
                    : null,
                'teeboxes' => $this->teeboxSummary($course),
            ])->all(),
        ]);
    }

    /**
     * Slim per-teebox summary (name + rating/slope) for the autocomplete.
     *
     * @return array<int, array{name: string, slope: int|null, rating: float|null}>
     */
    private function teeboxSummary(Course $course): array
    {
        $summary = [];

        foreach ($course->teeboxes() as $tee) {
            $summary[] = [
                'name' => is_string($tee['name'] ?? null) ? $tee['name'] : 'Tees',
                'slope' => isset($tee['slope']) ? (int) $tee['slope'] : null,
                'rating' => isset($tee['courseRating']) ? (float) $tee['courseRating'] : null,
            ];
        }

        return $summary;
    }
}
