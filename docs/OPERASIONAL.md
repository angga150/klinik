# Operasional demo lokal

## Server, worker, scheduler

Jalankan `composer dev` atau tiga proses pada README. Server memakai port 8765. Worker harus hidup saat mengekspor laporan; scheduler menyegarkan peringatan stok/kedaluwarsa pukul 07:00 Asia/Jakarta. Jalankan `php artisan clinic:inventory-alerts` untuk pembaruan segera.

Periksa kegagalan job dengan `php artisan queue:failed`. Setelah memperbaiki penyebab, `php artisan queue:retry all`. UI menampilkan status gagal untuk ekspor yang kehabisan percobaan; pengguna dapat membuat permintaan ekspor baru.

## Backup terenkripsi

```powershell
php artisan clinic:backup
```

Perintah mengembalikan path `backups/clinic-....enc` di `storage/app/private`. Snapshot database terenkripsi dengan APP_KEY aplikasi. Simpan `.env`/APP_KEY secara terpisah dan aman; backup tidak dapat dibuka tanpa key yang sama. Backup menyertakan metadata ekspor tetapi bukan berkas ekspor yang bisa dibuat ulang. Cache, session, queue dan token reset tidak disalin.

## Restore demo

Hentikan worker dan scheduler; aplikasi harus dalam maintenance mode. Gunakan hanya backup dari versi skema yang sama dan database yang sudah dimigrasi.

```powershell
php artisan down
php artisan clinic:restore backups/NAMA-BACKUP.enc --force
php artisan up
php artisan clinic:inventory-alerts
```

Restore mengganti data demo dan mengakhiri sesi aktif. Implementasi menggunakan transaksi DML agar kegagalan insert dapat di-rollback. APP_ENV harus local/testing. Restore telah diuji pada database test, bukan dengan menimpa data demo aktif.

## Verifikasi instalasi

- `/up` harus merespons sehat.
- Login dengan akun demo, buka dashboard dan Kunjungan & antrean.
- Buat satu kunjungan sampai lunas melalui UI, lalu periksa audit dan laporan.
- Jalankan `php artisan test`, `php vendor/bin/pint --test`, dan `npm run build`.
- Database pengujian adalah `sim_klinik_enterprise_test`; jangan ganti dengan database aplikasi.

## Deployment berikutnya

Prototype ini disiapkan untuk Laragon lokal. Deployment internet/produksi belum termasuk. Sebelum penggunaan nyata diperlukan konfigurasi HTTPS, mail transport, backup offsite, peninjauan akses/privasi, validasi klinis, pengujian beban, dan SOP operasional.
