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
                'name_ar' => 'العلاج الطبيعي',
                'name_en' => 'Physiotherapy',
                'description' => 'تشخيص وعلاج المشاكل الحركية وتحسين الأداء البدني.',
            ],
            [
                'name_ar' => 'العلاج الوظيفي',
                'name_en' => 'Occupational Therapy',
                'description' => 'مساعدة المرضى على أداء الأنشطة اليومية وتحسين مهاراتهم الوظيفية.',
            ],
            [
                'name_ar' => 'العلاج العصبي',
                'name_en' => 'Neurorehabilitation',
                'description' => 'إعادة تأهيل المصابين بالاضطرابات العصبية وتحسين الوظائف العصبية.',
            ],
            [
                'name_ar' => 'علاج أمراض الشيخوخة وإعادة تأهيل كبار السن',
                'name_en' => 'Geriatric Rehabilitation',
                'description' => 'تحسين جودة الحياة للمسنين وإعادة تأهيلهم بعد الأمراض أو الإصابات.',
            ],
            [
                'name_ar' => 'تأهيل الأطفال',
                'name_en' => 'Pediatric Rehabilitation',
                'description' => 'تقديم الدعم العلاجي للأطفال لتحسين النمو والقدرات الحركية والعقلية.',
            ],
            [
                'name_ar' => 'العلاج بالذكاء الاصطناعي والتحليل الحركي',
                'name_en' => 'AI-Assisted Motion Analysis',
                'description' => 'استخدام التكنولوجيا الحديثة لتحليل الحركة وتقديم برامج علاجية دقيقة.',
            ],
            [
                'name_ar' => 'علاج النطق واللغة',
                'name_en' => 'Speech & Cognitive Rehabilitation',
                'description' => 'تحسين مهارات النطق واللغة والوظائف المعرفية للمرضى.',
            ],
            [
                'name_ar' => 'العلاج النفسي الداعم',
                'name_en' => 'Psychological Support Therapy',
                'description' => 'تقديم الدعم النفسي والعلاجي لتحسين الحالة النفسية للمريض.',
            ],
        ];

        foreach ($specialties as $specialty) {
            MedicalSpecialtie::create($specialty);
        }
    }
}
