<?php

namespace Database\Seeders;

use App\Models\Customer;
use App\Models\Location;
use App\Models\MedicalSpecialtie;
use App\Models\Patient;
use App\Models\Rating;
use App\Models\RehabilitationCenter;
use App\Models\Therapist;
use Database\Factories\CustomerFactory;
use Database\Factories\RatingFactory;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

class DatabaseSeeder extends Seeder
{
    /**
     * Seed the application's database.
     */
    public function run(): void
    {
       $this->call([
//                AdminSeeder::class,
           MedicalSpecialtieSeeder::class,
//           DeviceSeeder::class,
//         PackageSeeder::class,

       ]);
        // جلب التخصصات التي تم إنشاؤها
//        $specialties = MedicalSpecialtie::all();
//
//        // 🔹 أنشئ عملاء من نوع (Therapist)
//        Customer::factory(10)->create([
//            'type_account' => 'therapist',
//        ])->each(function ($customer) use ($specialties) {
//            Therapist::factory()->create([
//                'customer_id' => $customer->id,
//                'medical_specialties_id' => $specialties->random()->id,
//            ]);
//
//            Location::factory()->create(['customer_id' => $customer->id]);
//        });

//         🔹 أنشئ عملاء من نوع (Rehabilitation Center)
//        Customer::factory(5)->create([
//            'type_account' => 'rehabilitation_center',
//        ])->each(function ($customer) use ($specialties) {
//            RehabilitationCenter::factory()->create([
//                'customer_id' => $customer->id,
//            ]);
//
//            // اربط المركز بتخصصات عشوائية
//            $centerSpecialties = $specialties->random(rand(1, 3))->pluck('id')->toArray();
//            foreach ($centerSpecialties as $specialtyId) {
//                DB::table('rehabilitation_specialist_specialty')->insert([
//                    'customer_id' => $customer->id,
//                    'specialty_id' => $specialtyId,
//                    'created_at' => now(),
//                    'updated_at' => now(),
//                ]);
//            }
//
//            Location::factory()->create(['customer_id' => $customer->id]);
//        });
//////
////        // 🔹 أنشئ عملاء من نوع (Patient)
//        Customer::factory(10)->create([
//            'type_account' => 'patient',
//        ])->each(function ($customer) {
//            Patient::factory()->create([
//                'customer_id' => $customer->id,
//            ]);
//
//            Location::factory()->create(['customer_id' => $customer->id]);
//        });

//        // 🔹 تقييمات تجريبية
//        $this->call([RehabilitationCenterSeeder::class]);
//        $this->call([ProgramEnrollmentSeeder::class]);
//        $this->call([RatingSeeder::class]);

    }
}
