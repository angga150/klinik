<?php

return [
    'required' => ':attribute wajib diisi.', 'required_if' => ':attribute wajib diisi pada kondisi ini.', 'required_unless' => ':attribute wajib diisi.', 'present' => ':attribute harus disertakan.',
    'string' => ':attribute harus berupa teks.', 'integer' => ':attribute harus berupa bilangan bulat.', 'numeric' => ':attribute harus berupa angka.', 'boolean' => ':attribute tidak valid.',
    'email' => 'Format :attribute tidak valid.', 'date' => ':attribute harus berupa tanggal yang valid.', 'date_format' => 'Format :attribute harus :format.', 'uuid' => ':attribute tidak valid.',
    'unique' => ':attribute sudah digunakan.', 'exists' => ':attribute tidak ditemukan.', 'in' => 'Pilihan :attribute tidak valid.', 'array' => ':attribute harus berupa daftar.', 'distinct' => ':attribute tidak boleh duplikat.',
    'digits' => ':attribute harus terdiri dari :digits digit.', 'alpha_dash' => ':attribute hanya boleh berisi huruf, angka, garis bawah, dan tanda hubung.',
    'decimal' => ':attribute maksimal dua angka desimal.', 'confirmed' => 'Konfirmasi :attribute tidak sesuai.', 'current_password' => 'Kata sandi saat ini tidak sesuai.',
    'before_or_equal' => ':attribute tidak boleh melewati :date.', 'after' => ':attribute harus setelah :date.', 'after_or_equal' => ':attribute harus pada atau setelah :date.',
    'min' => ['string' => ':attribute minimal :min karakter.', 'numeric' => ':attribute minimal :min.', 'array' => ':attribute minimal :min item.'],
    'max' => ['string' => ':attribute maksimal :max karakter.', 'numeric' => ':attribute maksimal :max.', 'array' => ':attribute maksimal :max item.'],
    'between' => ['numeric' => ':attribute harus antara :min dan :max.'],
    'attributes' => ['name' => 'Nama', 'email' => 'Email', 'password' => 'Kata sandi', 'nik' => 'NIK', 'birth_date' => 'Tanggal lahir', 'sex' => 'Jenis kelamin', 'payer' => 'Penjamin', 'patient_id' => 'Pasien', 'polyclinic_id' => 'Poli', 'medical_staff_id' => 'Dokter', 'service_tariff_id' => 'Tarif', 'medicine_id' => 'Obat', 'supplier_id' => 'Supplier', 'batch_number' => 'Nomor batch', 'expires_on' => 'Kedaluwarsa', 'quantity' => 'Jumlah', 'purchase_price' => 'Harga beli', 'selling_price' => 'Harga jual', 'reason' => 'Alasan', 'received' => 'Uang diterima', 'payment_method_id' => 'Metode pembayaran', 'subjective' => 'Subjective', 'objective' => 'Objective', 'assessment' => 'Assessment', 'plan' => 'Plan', 'diagnosis_ids' => 'Diagnosis', 'content' => 'Catatan koreksi', 'from' => 'Tanggal awal', 'to' => 'Tanggal akhir'],
];
