<?php

namespace Database\Seeders;

use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;

class AdminSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        DB::table('admins')->insert(
            [
                'full_name' => 'super_admin',
                'email' => 'super_admin@gmail.com',
                'password' => Hash::make('123456789'), // Ensure password is hashed
                'phone' =>  '123456789', // Ensure password is hashed
                'created_at' => now(),
                'updated_at' => now(),
            ]);
    }
}
