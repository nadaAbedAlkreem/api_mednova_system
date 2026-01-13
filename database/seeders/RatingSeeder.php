<?php

namespace Database\Seeders;

use App\Models\Customer;
use App\Models\Rating;
use App\Models\RehabilitationCenter;
use App\Models\Therapist;
use App\Models\Program;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

class RatingSeeder extends Seeder
{
    public function run(): void
    {
        DB::statement('SET FOREIGN_KEY_CHECKS=0;');
        Rating::truncate();
        DB::statement('SET FOREIGN_KEY_CHECKS=1;');

        $patients = Customer::where('type_account', 'patient')->get();

        // جميع أنواع المختصين
        $therapists = Therapist::with('customer')->get();
        $centers = RehabilitationCenter::with('customer')->get();
        $programs = Program::all();

        if ($patients->isEmpty() || ($therapists->isEmpty() && $centers->isEmpty() && $programs->isEmpty())) {
            $this->command->warn("⚠️ لا يوجد بيانات كافية لإنشاء التقييمات.");
            return;
        }

        // دمج كل الـ reviewees مع نوعهم الصحيح
        $allReviewees = collect();

        foreach ($therapists as $t) {
            $allReviewees->push([
                'id' => $t->customer->id,
                'type' => Customer::class,
            ]);
        }

        foreach ($centers as $c) {
            $allReviewees->push([
                'id' => $c->customer->id,
                'type' => Customer::class,
            ]);
        }

        foreach ($programs as $p) {
            $allReviewees->push([
                'id' => $p->id,
                'type' => Program::class,
            ]);
        }

        // إنشاء التقييمات لكل مريض
        foreach ($patients as $patient) {
            $count = rand(2, 5); // عدد التقييمات لكل مريض

            for ($i = 0; $i < $count; $i++) {
                $reviewee = $allReviewees->random();

                Rating::create([
                    'reviewer_id' => $patient->id,
                    'reviewee_id' => $reviewee['id'],
                    'reviewee_type' => $reviewee['type'],
                    'rating' => rand(30, 50) / 10,
                    'comment' => '',
                ]);
            }
        }

        $this->command->info('✅ تم إنشاء تقييمات عشوائية لجميع أنواع المختصين بنجاح.');
//        DB::statement('SET FOREIGN_KEY_CHECKS=0;');
//        Rating::truncate();
//        DB::statement('SET FOREIGN_KEY_CHECKS=1;');
//
//        // المرضى فقط
//        $patients = Customer::where('type_account', 'patient')->get();
//
//        // البرامج من 227 إلى 229
//        $programs = Program::whereBetween('id', [227, 229])->get();
//
//        if ($patients->isEmpty() || $programs->isEmpty()) {
//            $this->command->warn('⚠️ لا يوجد مرضى أو برامج لإنشاء التقييمات.');
//            return;
//        }
//
//        $comments = [
//            'برنامج ممتاز وساعدني كثيرًا',
//            'تجربة رائعة وأنصح به',
//            'البرنامج منظم وفعال',
//            'نتائج واضحة بعد فترة قصيرة',
//            'محتوى احترافي ومفيد',
//        ];
//
//        foreach ($patients as $patient) {
//            foreach ($programs as $program) {
//                Rating::create([
//                    'reviewer_id'   => $patient->id,
//                    'reviewee_id'   => $program->id,
//                    'reviewee_type' => Program::class,
//                    'rating'        => rand(35, 50) / 10, // من 3.5 إلى 5.0
//                    'comment'       => $comments[array_rand($comments)],
//                ]);
//            }
//        }

        $this->command->info('✅ تم إنشاء تقييمات مع تعليقات للبرامج من 227 إلى 229 بنجاح.');
    }
}
