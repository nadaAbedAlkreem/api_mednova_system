<?php

namespace Database\Seeders;

use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;

class CustomerSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        DB::table('customers')->insert(
            [
                'full_name' => 'cut',
                'name' => 'cut',
                'email' => 'customer@gmail.com',
                'gender' => 'male',
                'phone' => '0598188846',
                'image' =>  'https://mednovacare.com/storage/therapist_profile_images/therapist_profile/aa69e132-5165-4d5d-a1f1-37b5091319b4.jpg',
                'password' => Hash::make('123456789'), // Ensure password is hashed
                'created_at' => now(),
                'updated_at' => now(),
            ]);
    }
}
