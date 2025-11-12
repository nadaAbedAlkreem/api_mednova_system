<?php

namespace Database\Seeders;

 use Illuminate\Database\Seeder;
 use Illuminate\Support\Carbon;
 use Illuminate\Support\Facades\DB;
 use Illuminate\Support\Str;

 class DeviceSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        DB::table('devices')->insert([
            'name_ar' => 'قفاز ذكي',
            'name_en' => 'Smart Glove',
            'description_ar' => 'قفاز متطور يساعد المرضى على تحسين الحركة والتوازن، مزود بحساسات ذكية لقياس القوة والدقة.',
            'description_en' => 'An advanced glove that helps patients improve mobility and balance, equipped with smart sensors to measure strength and precision.',
            'token' => Str::random(40),
            'stock' => 10,
            'created_at' => Carbon::now(),
            'updated_at' => Carbon::now(),
        ]);
    }
}
