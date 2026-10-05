<?php

namespace Database\Seeders;

use App\Models\Clinic;
use App\Models\Diagnosis;
use App\Models\DoctorSchedule;
use App\Models\MedicalProcedure;
use App\Models\MedicalStaff;
use App\Models\Medicine;
use App\Models\MedicineCategory;
use App\Models\PaymentMethod;
use App\Models\Polyclinic;
use App\Models\ServiceTariff;
use App\Models\Supplier;
use App\Models\Unit;
use App\Models\User;
use Illuminate\Database\Seeder;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\Models\Role;
use Spatie\Permission\PermissionRegistrar;

class CoreSeeder extends Seeder
{
    public function run(): void
    {
        $password = config('clinic.demo_password');
        if (! $password && ! app()->environment('testing')) {
            throw new \RuntimeException('Set DEMO_PASSWORD (12+ characters) in local .env before demo seeding.');
        }$password = $password ?: 'TestingOnly!2026';
        app(PermissionRegistrar::class)->forgetCachedPermissions();
        $clinic = Clinic::firstOrCreate(['name' => 'Klinik Sehat Sentosa'], ['address' => 'Jl. Melati No. 24, Jakarta Selatan', 'phone' => '021-555-0124', 'active' => true]);
        $accounts = ['Super Admin' => ['superadmin', 'Administrator'], 'Admin Klinik' => ['admin', 'Angga Pratama'], 'Petugas Pendaftaran' => ['pendaftaran', 'Dina Putri'], 'Perawat' => ['perawat', 'Siti Rahma'], 'Dokter' => ['dokter', 'dr. Aditya Putra'], 'Apoteker' => ['apoteker', 'Rani Wulandari'], 'Kasir' => ['kasir', 'Budi Santoso'], 'Kepala Klinik' => ['kepala', 'dr. Maya Lestari']];
        foreach (config('clinic.roles') as $name => $permissions) {
            $role = Role::firstOrCreate(['name' => $name, 'guard_name' => 'web']);
            foreach ($permissions as $permission) {
                Permission::firstOrCreate(['name' => $permission, 'guard_name' => 'web']);
            }$role->syncPermissions($permissions);
            [$email,$label] = $accounts[$name];
            $user = User::firstOrCreate(['email' => $email.'@klinik.test'], ['name' => $label, 'password' => $password, 'clinic_id' => $clinic->id, 'active' => true]);
            $user->syncRoles([$role]);
        }
        $c = ['clinic_id' => $clinic->id];
        $doctor = User::where('email', 'dokter@klinik.test')->first();
        foreach (['UM' => 'Poli Umum', 'GG' => 'Poli Gigi'] as $code => $name) {
            $poly = Polyclinic::firstOrCreate($c + ['code' => $code], ['name' => $name]);
            $staff = MedicalStaff::firstOrCreate($c + ['name' => $code === 'UM' ? 'dr. Aditya Putra' : 'drg. Nabila Sari'], ['user_id' => $doctor->id, 'profession' => 'Dokter', 'license' => 'SIP-DEMO-'.$code]);
            for ($day = 0; $day < 7; $day++) {
                DoctorSchedule::firstOrCreate($c + ['medical_staff_id' => $staff->id, 'polyclinic_id' => $poly->id, 'weekday' => $day], ['starts_at' => '08:00', 'ends_at' => '20:00', 'capacity' => 100]);
            }
        }
        MedicalStaff::firstOrCreate($c + ['name' => 'Siti Rahma'], ['user_id' => User::where('email', 'perawat@klinik.test')->value('id'), 'profession' => 'Perawat']);
        foreach ([['Konsultasi dokter umum', 'consultation', '75000'], ['Konsultasi dokter gigi', 'consultation', '100000'], ['Administrasi pasien', 'administration', '15000']] as [$name,$kind,$price]) {
            ServiceTariff::firstOrCreate($c + ['name' => $name], compact('kind', 'price'));
        }
        foreach (['J00' => 'Nasofaringitis akut', 'K30' => 'Dispepsia', 'R51' => 'Sakit kepala', 'I10' => 'Hipertensi esensial', 'K02.9' => 'Karies gigi', 'R50.9' => 'Demam tidak spesifik'] as $code => $name) {
            Diagnosis::firstOrCreate($c + compact('code'), compact('name'));
        }
        foreach (['Perawatan luka sederhana' => '45000', 'Pemeriksaan gula darah' => '30000', 'Scaling gigi' => '180000'] as $name => $price) {
            MedicalProcedure::firstOrCreate($c + compact('name'), compact('price'));
        }
        $category = MedicineCategory::firstOrCreate($c + ['name' => 'Obat umum']);
        $unit = Unit::firstOrCreate($c + ['name' => 'tablet']);
        Unit::firstOrCreate($c + ['name' => 'botol']);
        Supplier::firstOrCreate($c + ['name' => 'PT Sehat Farma Demo'], ['phone' => '021-555-0180', 'address' => 'Jakarta']);
        foreach (['cash' => 'Tunai', 'transfer' => 'Transfer bank', 'qris' => 'QRIS manual'] as $code => $name) {
            PaymentMethod::firstOrCreate($c + compact('code'), compact('name'));
        }
        foreach ([['PCM500', 'Paracetamol 500 mg', 'Paracetamol', '500', '1500'], ['CTM4', 'CTM 4 mg', 'Chlorpheniramine', '300', '1000'], ['VITC', 'Vitamin C 500 mg', 'Asam askorbat', '700', '2000'], ['ANT', 'Antasida tablet', 'Antasida', '800', '2500'], ['ORS', 'Oralit', 'Oralit', '1200', '3500'], ['AMLO5', 'Amlodipine 5 mg', 'Amlodipine', '1000', '3000']] as [$sku,$name,$generic,$buy,$sell]) {
            Medicine::firstOrCreate($c + compact('sku'), ['name' => $name, 'generic_name' => $generic, 'medicine_category_id' => $category->id, 'unit_id' => $unit->id, 'form' => 'Tablet', 'strength' => str_contains($name, 'mg') ? substr($name, strpos($name, ' ') + 1) : '', 'purchase_price' => $buy, 'selling_price' => $sell, 'minimum_stock' => 20]);
        }
        app(PermissionRegistrar::class)->forgetCachedPermissions();
    }
}
