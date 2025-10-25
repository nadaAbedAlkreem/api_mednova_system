<?php

namespace Database\Seeders;

use App\Models\Customer;
use App\Models\Rating;
use App\Models\RehabilitationCenter;
use App\Models\Therapist;
use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

class RatingSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        DB::statement('SET FOREIGN_KEY_CHECKS=0;');
        Rating::truncate();
        DB::statement('SET FOREIGN_KEY_CHECKS=1;');

        // 🧍‍♂️ المرضى كمراجعين
        $patients = Customer::where('type_account', 'patient')->get();

        // 👨‍⚕️ الأخصائيين والمراكز كمقيّمين عليهم
        $therapists = Therapist::with('customer')->get();
        $centers = RehabilitationCenter::with('customer')->get();

        if ($patients->isEmpty() || ($therapists->isEmpty() && $centers->isEmpty())) {
            $this->command->warn("⚠️ لا يوجد بيانات كافية لإنشاء التقييمات، تأكد من تشغيل DatabaseSeeder أولاً.");
            return;
        }

        $allReviewees = collect();

        // دمج العملاء من المعالجين والمراكز مع نوع الحساب
        foreach ($therapists as $t) {
            $allReviewees->push([
                'id' => $t->customer->id,
                'type' => 'App\Models\Program'
            ]);
        }

        foreach ($centers as $c) {
            $allReviewees->push([
                'id' => $c->customer->id,
                'type' => 'App\Models\Customer'
            ]);
        }

        // 🔹 إنشاء التقييمات
        foreach ($patients as $patient) {
            // عدد التقييمات لكل مريض (من 2 إلى 5)
            $count = rand(2, 5);

            for ($i = 0; $i < $count; $i++) {
                $reviewee = $allReviewees->random();

                Rating::create([
                    'reviewer_id' => $patient->id,
                    'reviewee_id' => $reviewee['id'],
                    'reviewee_type' => $reviewee['type'],
                    'rating' => rand(30, 50) / 10, // يولّد أرقام بين 3.0 و5.0
                    'comment' => fake()->sentence(),
                ]);
            }
        }

        $this->command->info('✅ تم إنشاء تقييمات عشوائية للمعالجين والمراكز بنجاح.');

    }
}
