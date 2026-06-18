<?php

namespace Tests\Feature;

use App\Models\Course;
use App\Models\League;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class CourseCatalogTest extends TestCase
{
    use RefreshDatabase;

    public function test_it_imports_and_upserts_courses_from_a_csv(): void
    {
        $path = tempnam(sys_get_temp_dir(), 'courses').'.csv';
        $fh = fopen($path, 'w');
        fputcsv($fh, ['id', 'city_id', 'state_id', 'course_name', 'street', 'state', 'postal_code', 'layout_data', 'created_at', 'updated_at', 'lat', 'lng', 'phone', 'website', 'enriched_at', 'api_course_id', 'club_name']);
        fputcsv($fh, [100, 1, 1, 'Test Course', '123 St', 'IL', '60000', '{"teeboxes":[{"name":"White","slope":110,"courseRating":68.5}]}', '2026-01-01', '2026-01-01', 42.1, -87.8, '', '', '', 500, 'Test Club']);
        fputcsv($fh, [101, '', '', 'Empty Course', '', 'IL', '', '', '2026-01-01', '2026-01-01', '', '', '', '', '', '', '']);
        fclose($fh);

        $this->artisan('courses:import', ['path' => $path])->assertSuccessful();

        $this->assertDatabaseCount('courses', 2);
        $this->assertDatabaseHas('courses', [
            'external_id' => 100,
            'course_name' => 'Test Course',
            'state' => 'IL',
            'api_course_id' => 500,
        ]);

        $course = Course::where('external_id', 100)->first();
        $this->assertSame('White', $course->teeboxes()[0]['name']);

        // Re-running upserts by external_id (no duplicates).
        $this->artisan('courses:import', ['path' => $path])->assertSuccessful();
        $this->assertDatabaseCount('courses', 2);

        @unlink($path);
    }

    public function test_a_league_can_reference_a_catalog_course(): void
    {
        $course = Course::factory()->create();
        $league = League::factory()->create(['course_id' => $course->id, 'teebox' => 'White']);

        $this->assertTrue($league->course->is($course));
        $this->assertTrue($course->leagues->contains($league));
    }
}
