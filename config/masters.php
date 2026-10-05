<?php

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

return [
    'polyclinics' => ['title' => 'Poli', 'model' => Polyclinic::class, 'fields' => [
        'name' => ['label' => 'Nama poli', 'type' => 'text', 'rules' => 'required|string|max:100'],
        'code' => ['label' => 'Kode poli', 'type' => 'text', 'rules' => 'required|alpha_dash|max:10'],
    ]],
    'medical_staff' => ['title' => 'Tenaga medis', 'model' => MedicalStaff::class, 'fields' => [
        'name' => ['label' => 'Nama lengkap', 'type' => 'text', 'rules' => 'required|string|max:150'],
        'user_id' => ['label' => 'Akun pengguna', 'type' => 'User', 'rules' => 'required|integer'],
        'profession' => ['label' => 'Profesi', 'type' => 'Dokter,Perawat', 'rules' => 'required|in:Dokter,Perawat'],
        'license' => ['label' => 'Nomor SIP/STR', 'type' => 'text', 'rules' => 'nullable|string|max:100'],
    ]],
    'doctor_schedules' => ['title' => 'Jadwal dokter', 'model' => DoctorSchedule::class, 'fields' => [
        'medical_staff_id' => ['label' => 'Dokter', 'type' => 'MedicalStaff', 'rules' => 'required|integer'],
        'polyclinic_id' => ['label' => 'Poli', 'type' => 'Polyclinic', 'rules' => 'required|integer'],
        'weekday' => ['label' => 'Hari (0 Minggu–6 Sabtu)', 'type' => 'number', 'rules' => 'required|integer|between:0,6'],
        'starts_at' => ['label' => 'Mulai', 'type' => 'time', 'rules' => 'required|date_format:H:i'],
        'ends_at' => ['label' => 'Selesai', 'type' => 'time', 'rules' => 'required|date_format:H:i|after:starts_at'],
        'capacity' => ['label' => 'Kuota harian', 'type' => 'number', 'rules' => 'required|integer|between:1,500'],
    ]],
    'service_tariffs' => ['title' => 'Layanan & tarif', 'model' => ServiceTariff::class, 'fields' => [
        'name' => ['label' => 'Nama layanan', 'type' => 'text', 'rules' => 'required|string|max:150'],
        'kind' => ['label' => 'Jenis', 'type' => 'consultation,administration', 'rules' => 'required|in:consultation,administration'],
        'price' => ['label' => 'Tarif (Rp)', 'type' => 'number', 'rules' => 'required|decimal:0,2|min:0|max:999999999'],
    ]],
    'diagnoses' => ['title' => 'Diagnosis ICD-10', 'model' => Diagnosis::class, 'fields' => [
        'code' => ['label' => 'Kode ICD-10', 'type' => 'text', 'rules' => 'required|string|max:20'],
        'name' => ['label' => 'Diagnosis', 'type' => 'text', 'rules' => 'required|string|max:200'],
    ]],
    'medical_procedures' => ['title' => 'Tindakan', 'model' => MedicalProcedure::class, 'fields' => [
        'name' => ['label' => 'Nama tindakan', 'type' => 'text', 'rules' => 'required|string|max:150'],
        'price' => ['label' => 'Tarif (Rp)', 'type' => 'number', 'rules' => 'required|decimal:0,2|min:0|max:999999999'],
    ]],
    'medicines' => ['title' => 'Obat', 'model' => Medicine::class, 'fields' => [
        'sku' => ['label' => 'SKU', 'type' => 'text', 'rules' => 'required|alpha_dash|max:50'],
        'name' => ['label' => 'Nama obat', 'type' => 'text', 'rules' => 'required|string|max:150'],
        'generic_name' => ['label' => 'Nama generik', 'type' => 'text', 'rules' => 'nullable|string|max:150'],
        'medicine_category_id' => ['label' => 'Kategori', 'type' => 'MedicineCategory', 'rules' => 'required|integer'],
        'unit_id' => ['label' => 'Satuan', 'type' => 'Unit', 'rules' => 'required|integer'],
        'form' => ['label' => 'Bentuk sediaan', 'type' => 'text', 'rules' => 'nullable|string|max:100'],
        'strength' => ['label' => 'Kekuatan dosis', 'type' => 'text', 'rules' => 'nullable|string|max:100'],
        'purchase_price' => ['label' => 'Harga beli (Rp)', 'type' => 'number', 'rules' => 'required|decimal:0,2|min:0|max:999999999'],
        'selling_price' => ['label' => 'Harga jual (Rp)', 'type' => 'number', 'rules' => 'required|decimal:0,2|min:0|max:999999999'],
        'minimum_stock' => ['label' => 'Stok minimum', 'type' => 'number', 'rules' => 'required|integer|between:0,1000000'],
    ]],
    'medicine_categories' => ['title' => 'Kategori obat', 'model' => MedicineCategory::class, 'fields' => [
        'name' => ['label' => 'Nama kategori', 'type' => 'text', 'rules' => 'required|string|max:100'],
    ]],
    'units' => ['title' => 'Satuan obat', 'model' => Unit::class, 'fields' => [
        'name' => ['label' => 'Nama satuan', 'type' => 'text', 'rules' => 'required|string|max:100'],
    ]],
    'suppliers' => ['title' => 'Supplier', 'model' => Supplier::class, 'fields' => [
        'name' => ['label' => 'Nama supplier', 'type' => 'text', 'rules' => 'required|string|max:150'],
        'phone' => ['label' => 'Telepon', 'type' => 'text', 'rules' => 'nullable|string|max:30'],
        'address' => ['label' => 'Alamat', 'type' => 'text', 'rules' => 'nullable|string|max:1000'],
    ]],
    'payment_methods' => ['title' => 'Metode pembayaran', 'model' => PaymentMethod::class, 'fields' => [
        'name' => ['label' => 'Nama metode', 'type' => 'text', 'rules' => 'required|string|max:100'],
        'code' => ['label' => 'Kode', 'type' => 'cash,transfer,qris', 'rules' => 'required|in:cash,transfer,qris'],
    ]],
];
