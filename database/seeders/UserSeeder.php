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
            "first_name" => "Anthony",
            "last_name" => "DeVenuto",
            "role" => "admin",
            "email" => "anthonydevenuto@gmail.com",
            "password" => bcrypt('password123'),
        ]);

        DB::table('users')->insert([
            "first_name" => "John",
            "last_name" => "Milne",
            "role" => "admin",
            "email" => "jmilne@yahoo.com",
            "password" => bcrypt('moharder'),
        ]);
    }
}
