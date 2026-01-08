<?php

namespace Database\Seeders;

use App\Models\Customer;
use App\Models\Program;
use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

class ProgramEnrollmentSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        DB::statement('SET FOREIGN_KEY_CHECKS=0;');
        DB::table('program_enrollments')->truncate();
        DB::statement('SET FOREIGN_KEY_CHECKS=1;');

        // المرضى فقط
        $patients = Customer::where('type_account', 'patient')->get();

        // البرامج المطلوبة
        $programs = Program::whereBetween('id', [227, 229])->get();

        if ($patients->isEmpty() || $programs->isEmpty()) {
            $this->command->warn('⚠️ لا يوجد مرضى أو برامج لإنشاء التسجيلات.');
            return;
        }

        foreach ($patients as $patient) {
            // تسجيل المريض في عدد عشوائي من البرامج
            $enrolledPrograms = $programs->random(rand(1, $programs->count()));

            foreach ($enrolledPrograms as $program) {
                DB::table('program_enrollments')->insert([
                    'customer_id'  => $patient->id,
                    'program_id'   => $program->id,
                    'enrolled_at'  => now()->subDays(rand(1, 30)),
                    'is_completed' => rand(0, 1),
                    'created_at'   => now(),
                    'updated_at'   => now(),
                ]);
            }
        }

        $this->command->info('✅ تم تسجيل المرضى في البرامج من 227 إلى 229 بنجاح.');
    }
}
