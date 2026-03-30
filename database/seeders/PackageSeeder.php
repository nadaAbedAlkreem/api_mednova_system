<?php

namespace Database\Seeders;

use App\Models\Package;
use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;

class PackageSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        Package::create([
            'name_ar' => 'باقة وهمية',
            'name_en' => 'Dummy Package',
            'description_ar' => 'وصف باقة وهمية',
            'description_en' => 'Dummy package description',
            'type' => 'therapist', // نوع الاشتراك
            'price' => 100, // مثال على السعر
            'billing_cycle' => 'monthly',
            'is_active' => 1,
        ]);
    }
}
