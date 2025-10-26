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
                    'comment' => fake()->sentence(),
                ]);
            }
        }

        $this->command->info('✅ تم إنشاء تقييمات عشوائية لجميع أنواع المختصين بنجاح.');
    }
}
