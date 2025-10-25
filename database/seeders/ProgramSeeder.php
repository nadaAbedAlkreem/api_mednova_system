<?php

namespace Database\Seeders;

use App\Models\Customer;
use App\Models\CustomerCourseProgress;
use App\Models\Program;
use App\Models\ProgramEnrollment;
use App\Models\ProgramVideos;
use App\Models\RehabilitationCenter;
use App\Models\Therapist;
use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

class ProgramSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {

        DB::statement('SET FOREIGN_KEY_CHECKS=0;');
        Program::truncate();
        ProgramVideos::truncate();
        ProgramEnrollment::truncate();
        CustomerCourseProgress::truncate();
        DB::statement('SET FOREIGN_KEY_CHECKS=1;');

        // 🩺 جلب الأخصائيين والمراكز والمرضى
        $therapists = Therapist::with('customer')->get();
        $centers = RehabilitationCenter::with('customer')->get();
        $patients = Customer::where('type_account', 'patient')->get();

        if ($therapists->isEmpty() || $patients->isEmpty()) {
            $this->command->warn("⚠️ لا يوجد معالجين أو مرضى، تأكد من تشغيل DatabaseSeeder أولاً.");
            return;
        }

        // 🧠 إنشاء برامج لكل معالج ومركز
        $creators = collect([$therapists, $centers])->flatten();

        foreach ($creators as $creator) {
            $creatorType = $creator instanceof Therapist
                ? Therapist::class
                : RehabilitationCenter::class;

            // 🔹 إنشاء 2 إلى 4 برامج لكل معالج أو مركز
            $programCount = rand(2, 4);
            for ($i = 1; $i <= $programCount; $i++) {
                $program = Program::create([
                    'creator_id' => $creator->id,
                    'creator_type' => $creatorType,
                    'title_ar' => "البرنامج التدريبي رقم $i لـ " . ($creatorType === Therapist::class ? 'المعالج' : 'المركز'),
                    'title_en' => "Training Program #$i",
                    'description_ar' => "هذا وصف تجريبي للبرنامج رقم $i.",
                    'description_en' => "This is a sample description for program #$i.",
                    'cover_image' => 'https://via.placeholder.com/600x400.png?text=Program+' . $i,
                    'price' => rand(100, 500),
                    'status' => 'published',
                    'is_approved' => true,
                ]);

                // 🔹 إنشاء فيديوهات للبرنامج
                $videoCount = rand(3, 6);
                for ($v = 1; $v <= $videoCount; $v++) {
                    ProgramVideos::create([
                        'program_id' => $program->id,
                        'title_ar' => "الفيديو رقم $v",
                        'title_en' => "Video #$v",
                        'description_ar' => "شرح تفصيلي للفيديو رقم $v.",
                        'description_en' => "Detailed description for video #$v.",
                        'video_path' => "https://example.com/videos/program_{$program->id}_video_{$v}.mp4",
                        'duration_minute' => rand(3, 10),
                        'order' => $v,
                        'is_preview' => $v === 1,
                        'is_free' => $v === 1,
                    ]);
                }

                // 🔹 تسجيل بعض المرضى في البرنامج
                $enrolledPatients = $patients->random(rand(2, 5));
                foreach ($enrolledPatients as $patient) {
                    ProgramEnrollment::create([
                        'customer_id' => $patient->id,
                        'program_id' => $program->id,
                        'enrolled_at' => now(),
                        'is_completed' => false,
                    ]);

                    // 🔹 إضافة بيانات التقدم لكل مريض
                    CustomerCourseProgress::create([
                        'customer_id' => $patient->id,
                        'program_id' => $program->id,
                        'videos_completed' => rand(0, $videoCount - 1),
                        'current_video' => rand(1, $videoCount),
                        'current_time' => rand(10, 300),
                    ]);
                }
            }
        }


    }
}
