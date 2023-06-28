<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

class UserSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        DB::table('users')->insert([
            "name" => "Admin",
            "handicap" => 5.50,
            "role" => "admin",
            "email" => "anthonydevenuto@gmail.com",
            "password" => bcrypt('PT6t0f9wnpc4X85fzCRv'),
        ]);


        $path = storage_path()."/data.csv";
        if (($handle = fopen($path, 'r')) !== false) {
            fgetcsv($handle);
            while (($row = fgetcsv($handle, 1000, ',')) !== false) {
                $data = [];
                foreach ($row as $column) {
                    $data[] = $column;
                }
                DB::table('users')->insert([
                    'name' => $data[0],
                    'handicap' => intval($data[1]),
                    'role' => 'player',
                    'email' => str_replace(' ', '', strtolower($data[0])).'@noreply.com' ,
                    'password' => bcrypt('password')
                ]);
            }
            fclose($handle);
        }

        
    }
}
