<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

class GolferSeeder extends Seeder
{   
    public function run(): void
    {
        $path = storage_path()."/golfers_copy.csv";
        if (($handle = fopen($path, 'r')) !== false) {
            fgetcsv($handle);
            while (($row = fgetcsv($handle, 1000, ',')) !== false) {
                $data = [];
                foreach ($row as $column) {
                    $data[] = $column;
                    
                }
                
                DB::table('golfers')->insert([
                    'first_name' => $data[1],
                    'last_name' => $data[2],
                    'email' => strtolower(substr($data[1], 0, 1).$data[2].'@noreply.com'),
                    'created_at' => $data[6],
                ]);
            }
            fclose($handle);
        }
    }
}