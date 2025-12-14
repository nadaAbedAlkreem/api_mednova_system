<?php

namespace Database\Seeders;

use App\Models\Customer;
use App\Models\Location;
use App\Models\RehabilitationCenter;
use App\Models\Schedule;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use Maatwebsite\Excel\Facades\Excel;

class RehabilitationCenterSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $filePath = storage_path('app/public/rehab.xlsx');
        $rows = Excel::toArray([], $filePath)[0];
        $headers = array_map('trim', $rows[0]);

        foreach (array_slice($rows, 1) as $row) {
            $data = array_combine($headers, $row);

            $centerName = $data['Provider Name Arabic'] ?? null;
            if (!$centerName) {
                continue;
            }
            $centerImagePath = $this->getCenterImage($centerName);
            if (!$centerImagePath) {
                Log::warning("Center skipped (no image): {$centerName}");
                continue;
            }

            $centerImage = asset($centerImagePath);

            $email   = $data['Provider Email'] ?? 'center' . rand(1000,9999) . '@example.com';
            $address = $data['Provider Willayat'] ?? 'Unknown Address';
            $phone   = $data['Phone Number'] ?? '9' . rand(10000000, 99999999);
            // 1) إنشاء المستخدم Customer
            Log::info($data);
            $customer = Customer::create([
                'full_name' => $centerName,
                'email' => $email,
                'phone' => $phone,
                'gender' => 'Female',
                'password' => bcrypt('password123'),
                'birth_date' => now()->subYears(10),
                'type_account' => 'rehabilitation_center',
                'image' => $centerImage,
//                'image' => 'https://demoapplication.jawebhom.com/storage/patient_profile_images/5d8f71ad-0d97-4e0e-95c3-07f1a16ecb32.jpg',
                'status' => 'active',
                'is_online' => false,
                'last_active_at' => now(),
            ]);

            // 2) إنشاء مركز التأهيل RehabilitationCenter
            $center = RehabilitationCenter::create([
                'customer_id' => $customer->id,
                'year_establishment' => rand(2005, 2023),
                'license_number' => 'LIC-' . rand(1000, 9999),
                'license_authority' => 'MOH',
                'has_commercial_registration' => true,
                'commercial_registration_number' => 'CR-' . rand(1000, 9999),
                'commercial_registration_authority' => 'Ministry of Commerce',
                'bio' => 'Imported rehabilitation center from Excel file.',
            ]);

            // 3) إنشاء جدول الموقع Location
            Location::create([
                'customer_id' => $customer->id,
                'latitude' => 23.5 + (rand(1, 100) / 1000),   // بيانات افتراضية
                'longitude' => 58.4 + (rand(1, 100) / 1000),  // بيانات افتراضية
                'formatted_address' => $address,
                'country' => 'Oman',
                'region' => $data['Provider Governorate'] ?? 'Unknown',
                'city' => $data['Provider Willayat'] ?? 'Unknown',
                'district' => null,
                'postal_code' => rand(100, 999),
                'location_type' => 'rehabilitation_center'
            ]);

            // 4) إنشاء جدول المواعيد Schedule
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
    private function getCenterImage(string $centerName): ?string
    {
        $imagesPath = storage_path('app/public/rehab_images');

        foreach (File::files($imagesPath) as $file) {

            // اسم الصورة بدون الامتداد
            $imageName = pathinfo($file->getFilename(), PATHINFO_FILENAME);

            // إذا اسم الصورة = اسم المركز
            if ($imageName === trim($centerName)) {

                $newName = Str::uuid() . '.' . $file->getExtension();
                $path = 'rehabilitation_centers/logos/' . $newName;

                Storage::disk('public')->put(
                    $path,
                    File::get($file->getPathname())
                );

                return Storage::url($path);
            }
        }

        return null;
    }

}
