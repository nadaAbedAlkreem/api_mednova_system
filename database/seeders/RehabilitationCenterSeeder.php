<?php

namespace Database\Seeders;

use App\Models\Customer;
use App\Models\RehabilitationCenter;
use App\Models\Schedule;
use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;
use Illuminate\Support\Str;
use Maatwebsite\Excel\Facades\Excel;

class RehabilitationCenterSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        // ملف الاكسل
        $filePath = storage_path('app/rehab.xlsx');

        // قراءة البيانات
        $rows = Excel::toCollection((object)null, $filePath)[0]; // أول شيت

        foreach ($rows as $row) {
            // توليد بيانات وهمية للـ Customer
            $customer = Customer::create([
                'full_name' => $row['Provider Name'] ?? 'Center ' . Str::random(5),
                'email' => $row['Provider Email'] ?? 'center'.rand(1000,9999).'@example.com',
                'phone' => '9' . rand(10000000, 99999999),
                'gender' => 'Female',
                'password' => bcrypt('password123'),
                'birth_date' => now()->subYears(10),
                'image' => null,
                'provider' => null,
                'provider_id' => null,
                'fcm_token' => null,
                'is_online' => false,
                'last_active_at' => now(),
                'is_banned' => false,
                'type_account' => 'rehabilitation_center',
                'status' => 'active',
            ]);

            // إنشاء مركز إعادة التأهيل
            $center = RehabilitationCenter::create([
                'customer_id' => $customer->id,
                'year_establishment' => rand(2000, 2023),
                'license_number' => 'LIC-' . rand(1000, 9999),
                'license_authority' => 'MOH',
                'license_file' => null,
                'has_commercial_registration' => true,
                'commercial_registration_number' => 'CR-' . rand(1000, 9999),
                'commercial_registration_authority' => 'Ministry of Commerce',
                'commercial_registration_file' => null,
                'bio' => 'This is a dummy rehabilitation center imported from Excel.',
            ]);

            // توليد جدول الـ Schedule بشكل وهمي
            Schedule::create([
                'consultant_id' => $customer->id,
                'consultant_type' => 'rehabilitation_center',
                'day_of_week' => json_encode(['Saturday','Sunday','Monday','Tuesday','Wednesday','Thursday']),
                'start_time_morning' => '08:00',
                'end_time_morning' => '12:00',
                'is_have_evening_time' => true,
                'start_time_evening' => '16:00',
                'end_time_evening' => '20:00',
                'type' => 'offline',
                'is_active' => true,
            ]);
        }
    }
}
