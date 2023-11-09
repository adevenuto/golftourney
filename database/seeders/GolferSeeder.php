<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

class GolferSeeder extends Seeder
{   
    public function run(): void
    {
        $path = storage_path()."/golfer_102723.csv";
        if (($handle = fopen($path, 'r')) !== false) {
            fgetcsv($handle);
            while (($row = fgetcsv($handle, 1000, ',')) !== false) {
                $data = [];
                foreach ($row as $column) {
                    $data[] = $column;
                    
                }
                DB::table('golfers')->insert([
                    'golfer_id' => $data[0],
                    'first_name' => $data[2],
                    'last_name' => $data[3],
                    'email' => strtolower($data[2].$data[3].'@noreply.com'),
                    'created_at' => \Carbon\Carbon::parse($data[5])->format('Y/m/d H:i:s'),
                ]);
            }
            fclose($handle);
        }
    }
}