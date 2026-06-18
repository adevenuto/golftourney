<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;

class ImportCourses extends Command
{
    protected $signature = 'courses:import {path? : Path to the CSV (defaults to storage/courses_rows.csv)} {--chunk=200}';

    protected $description = 'Import the course catalog from a CSV, streaming + upserting by external id.';

    public function handle(): int
    {
        $path = $this->argument('path') ?? storage_path('courses_rows.csv');

        if (! is_readable($path)) {
            $this->error("Cannot read CSV at {$path}");

            return self::FAILURE;
        }

        $handle = fopen($path, 'r');
        $header = fgetcsv($handle);

        if (! $header) {
            $this->error('CSV is empty.');
            fclose($handle);

            return self::FAILURE;
        }
        $header = array_map('trim', $header);

        $chunkSize = max(1, (int) $this->option('chunk'));
        $buffer = [];
        $imported = 0;
        $skipped = 0;
        $now = now();

        while (($row = fgetcsv($handle)) !== false) {
            if (count($row) !== count($header)) {
                $skipped++;

                continue;
            }

            $buffer[] = $this->mapRow(array_combine($header, $row), $now);

            if (count($buffer) >= $chunkSize) {
                $this->flush($buffer);
                $imported += count($buffer);
                $buffer = [];
                $this->output->write("\rImported {$imported} courses…");
            }
        }

        if ($buffer !== []) {
            $this->flush($buffer);
            $imported += count($buffer);
        }

        fclose($handle);

        $this->newLine();
        $this->info("Done. {$imported} courses imported".($skipped ? ", {$skipped} malformed rows skipped" : '').'.');

        return self::SUCCESS;
    }

    /**
     * @param  array<string, string>  $d
     * @return array<string, mixed>
     */
    private function mapRow(array $d, mixed $now): array
    {
        $intOrNull = fn (string $key) => isset($d[$key]) && $d[$key] !== '' ? (int) $d[$key] : null;
        $strOrNull = fn (string $key) => isset($d[$key]) && $d[$key] !== '' ? $d[$key] : null;
        $floatOrNull = fn (string $key) => isset($d[$key]) && $d[$key] !== '' ? (float) $d[$key] : null;

        return [
            'external_id' => $intOrNull('id'),
            'api_course_id' => $intOrNull('api_course_id'),
            'course_name' => $strOrNull('course_name') ?? 'Unknown course',
            'club_name' => $strOrNull('club_name'),
            'street' => $strOrNull('street'),
            'city_id' => $intOrNull('city_id'),
            'state_id' => $intOrNull('state_id'),
            'state' => $strOrNull('state'),
            'postal_code' => $strOrNull('postal_code'),
            'lat' => $floatOrNull('lat'),
            'lng' => $floatOrNull('lng'),
            'phone' => $strOrNull('phone'),
            'website' => $strOrNull('website'),
            'layout_data' => $strOrNull('layout_data'),
            'created_at' => $now,
            'updated_at' => $now,
        ];
    }

    /**
     * @param  array<int, array<string, mixed>>  $rows
     */
    private function flush(array $rows): void
    {
        DB::table('courses')->upsert(
            $rows,
            ['external_id'],
            ['course_name', 'club_name', 'street', 'city_id', 'state_id', 'state', 'postal_code', 'lat', 'lng', 'phone', 'website', 'layout_data', 'updated_at'],
        );
    }
}
