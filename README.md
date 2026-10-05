# SIM Klinik Enterprise

Prototype akademis klinik rawat jalan, apotek, inventori, dan billing. Laravel 13, Livewire 4, Tailwind CSS 4, MySQL. Semua data demo fiktif.

## Jalankan proyek yang sudah terpasang

```powershell
cd C:\laragon\www\simrs
composer dev
```

Buka **http://127.0.0.1:8765**. Perintah tersebut menjalankan web server, Vite, worker ekspor, dan scheduler. MySQL Laragon harus aktif. Port 8765 dipilih karena port 8000 digunakan proyek lain.

Alternatif, jalankan masing-masing di terminal terpisah setelah `npm run build`:

```powershell
php artisan serve --host=127.0.0.1 --port=8765
php artisan queue:work --tries=3 --timeout=180
php artisan schedule:work
```

## Akun demo

Semua akun memakai kata sandi dari **`DEMO_PASSWORD` dalam `.env` lokal**. Kata sandi tidak disimpan di dokumentasi/Git. Kredensial situs referensi tidak digunakan sebagai akun aplikasi.

| Email | Role |
|---|---|
| superadmin@klinik.test | Super Admin |
| admin@klinik.test | Admin Klinik |
| pendaftaran@klinik.test | Petugas Pendaftaran |
| perawat@klinik.test | Perawat |
| dokter@klinik.test | Dokter |
| apoteker@klinik.test | Apoteker |
| kasir@klinik.test | Kasir |
| kepala@klinik.test | Kepala Klinik |

## Instalasi baru

1. Siapkan PHP 8.3+, Composer, Node.js yang kompatibel dengan Vite, MySQL 8.4. Aktifkan ekstensi `bcmath`, `pdo_mysql`, `mbstring`, `intl`, `dom`, `xml`, `zip`, `gd`, `fileinfo`, dan `openssl`.
2. Jalankan `composer install` dan `npm ci`; versi hasil implementasi dikunci oleh lockfile.
3. Salin `.env.example` menjadi `.env`; sesuaikan database, APP_URL, dan isi `DEMO_PASSWORD` minimal 12 karakter.
4. Buat database `sim_klinik_enterprise` dan `sim_klinik_enterprise_test` dengan charset utf8mb4.
5. Jalankan:

```powershell
php artisan key:generate
php artisan migrate --seed
php artisan clinic:inventory-alerts
npm run build
composer dev
```

Seeder membuat 8 akun, 2 poli, jadwal 7 hari, master referensi, 32 pasien, 44 kunjungan berbagai tahap, dan batch obat. Seeder tidak mengubah kata sandi akun yang sudah ada dan tidak mengulang kunjungan bila database telah berisi kunjungan.

Data kunjungan demo mengikuti tanggal saat seeding. Setelah berganti hari, dashboard hari ini dapat kosong; data tetap tersedia pada daftar kunjungan dan laporan. Untuk demo baru, daftarkan kunjungan melalui UI. Jangan menjalankan `migrate:fresh` pada data yang ingin dipertahankan.

## Fitur

- Login/logout, reset password, batas percobaan login, timeout 30 menit, pengguna aktif/nonaktif, 8 role dan matriks permission.
- Master klinik, poli, tenaga medis, jadwal, konsultasi/administrasi, tindakan, ICD-10 contoh, obat, kategori, satuan, supplier, metode bayar.
- Pasien, nomor RM dan NIK unik, peringatan kemungkinan duplikat, kunjungan, antrean, pembatalan, bukti pendaftaran PDF.
- Triage, vital sign/BMI, SOAP, diagnosis, tindakan, kontrol, finalisasi, amendment, riwayat kunjungan.
- Resep terverifikasi, dispense FEFO, ledger stok, penerimaan batch, adjustment, opname, retur supplier, retur pasien ke karantina.
- Invoice snapshot, diskon berizin, pembayaran penuh, tunai/kembalian, transfer/QRIS manual, void/reversal, invoice pengganti terkait retur, kuitansi PDF.
- Dashboard, laporan pelayanan/stok/keuangan, ekspor PDF/XLSX melalui queue, audit aktivitas, peringatan stok dan kedaluwarsa.
- Backup database lokal terenkripsi dengan APP_KEY dan perintah restore untuk lingkungan demo.

## Pemeriksaan

```powershell
php artisan test
php vendor/bin/pint app config database routes tests bootstrap/app.php bootstrap/providers.php --test
npm run build
```

Test menggunakan **database MySQL khusus `sim_klinik_enterprise_test`**, termasuk subprocess simultan untuk nomor RM, antrean, dispense, dan pembayaran. Database pengujian harus terpisah dari database aplikasi. Test concurrency membuat ulang tabel database test.

Pengujian mencakup workflow, rollback stok, FEFO, harga snapshot, amendment, retur/reversal, role/clinic isolation, Livewire, ekspor PDF/XLSX, dan backup/restore. Lihat [panduan pengguna](docs/PANDUAN_PENGGUNA.md), [arsitektur/ERD](docs/ARSITEKTUR.md), [operasional](docs/OPERASIONAL.md), serta [skenario UAT](docs/UAT.md).

## Batas prototype

Satu klinik aktif; pengguna terikat `clinic_id`. Tidak ada appointment, racikan, cicilan, penjualan obat bebas, API publik, BPJS/SATUSEHAT, atau deployment internet. Transfer/QRIS dicatat manual. Diagnosis dan terapi demo bukan panduan medis. Validasi klinis, keamanan produksi, dan persetujuan UAT manusia tetap diperlukan sebelum pemakaian nyata.
