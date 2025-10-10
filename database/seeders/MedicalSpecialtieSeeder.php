<?php

namespace Database\Seeders;

use App\Models\MedicalSpecialtie;
use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;

class MedicalSpecialtieSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {

        $specialties = [
            [
                'name_ar' => 'طب الأسنان',
                'name_en' => 'Dentistry',
                'description' => 'تشخيص وعلاج أمراض الفم والأسنان واللثة.',
            ],
            [
                'name_ar' => 'طب العيون',
                'name_en' => 'Ophthalmology',
                'description' => 'تشخيص وعلاج أمراض العيون والإبصار.',
            ],
            [
                'name_ar' => 'طب الأطفال',
                'name_en' => 'Pediatrics',
                'description' => 'رعاية الأطفال ونموهم وتشخيص أمراضهم.',
            ],
            [
                'name_ar' => 'طب النساء والتوليد',
                'name_en' => 'Obstetrics and Gynecology',
                'description' => 'متابعة الحمل والولادة وعلاج أمراض النساء.',
            ],
            [
                'name_ar' => 'طب الباطنية',
                'name_en' => 'Internal Medicine',
                'description' => 'تشخيص وعلاج الأمراض الباطنية للكبار.',
            ],
            [
                'name_ar' => 'طب الجلدية',
                'name_en' => 'Dermatology',
                'description' => 'تشخيص وعلاج أمراض الجلد والشعر والأظافر.',
            ],
            [
                'name_ar' => 'طب الأنف والأذن والحنجرة',
                'name_en' => 'Otolaryngology (ENT)',
                'description' => 'تشخيص وعلاج أمراض الأنف والأذن والحنجرة.',
            ],
            [
                'name_ar' => 'جراحة عامة',
                'name_en' => 'General Surgery',
                'description' => 'تشخيص وعلاج الحالات التي تتطلب تدخلًا جراحيًا.',
            ],
        ];

        foreach ($specialties as $specialty) {
            MedicalSpecialtie::create($specialty);
        }
    }
}
