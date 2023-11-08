<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

class RoundSeeder extends Seeder
{
    public function run(): void
    {
        $path = storage_path()."/round_102723.csv";
        if (($handle = fopen($path, 'r')) !== false) {
            fgetcsv($handle);
            while (($row = fgetcsv($handle, 1000, ',')) !== false) {
                $data = [];
                foreach ($row as $column) {
                    $data[] = $column;
                }

                // if($data[1]==297) \Log::info($data);

                if($data[13]==='Robert A. Black') {
                    DB::table('rounds')->insert([
                        'golfer_id' => $data[1],
                        'score' => intval($data[18])/2,
                        'course_name' => $data[13],
                        'date_of_round' => \Carbon\Carbon::parse($data[5])->format('Y/m/d H:i:s')
                    ]);
                }
                
            }
            fclose($handle);
        }
    }
}