<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

class RoundSeeder extends Seeder
{
    public function run(): void
    {
        $path = storage_path()."/rounds_copy.csv";
        if (($handle = fopen($path, 'r')) !== false) {
            fgetcsv($handle);
            while (($row = fgetcsv($handle, 1000, ',')) !== false) {
                $data = [];
                foreach ($row as $column) {
                    $data[] = $column;
                }
                
                DB::table('rounds')->insert([
                    'golfer_id' => $data[1],
                    'score' => intval($data[2]),
                    'course_name' => $data[3],
                    'created_at' => $data[4]
                ]);
                
            }
            fclose($handle);
        }
    }
}