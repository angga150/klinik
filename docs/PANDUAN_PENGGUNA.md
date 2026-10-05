# Panduan pengguna

## Alur pelayanan

1. **Admin Klinik:** buka Master data. Siapkan poli, akun dokter, tenaga medis, jadwal sesuai hari, tarif, obat, supplier, dan metode bayar. Master yang sudah digunakan cukup dinonaktifkan.
2. **Pendaftaran:** buka Data pasien, cari nomor RM/nama/NIK/telepon. Daftarkan pasien bila belum ada; periksa peringatan duplikasi. Pada Kunjungan & antrean, pilih pasien, poli, dokter yang memiliki jadwal hari ini, tarif konsultasi, dan penjamin. Nomor antrean dan invoice draft dibuat otomatis.
3. **Perawat:** buka kunjungan menunggu perawat. Isi keluhan dan seluruh tanda vital. BMI dihitung sistem. Simpan untuk mengirim ke antrean dokter.
4. **Dokter:** buka kunjungan yang ditugaskan, mulai pemeriksaan, isi SOAP, diagnosis utama/tambahan, tindakan dan resep. Setiap obat memerlukan jumlah, dosis, frekuensi, durasi, serta cara pemakaian. Simpan draft atau finalisasi. Pilihan diagnosis pertama menjadi diagnosis utama.
5. **Apoteker:** buka Pelayanan apotek. Verifikasi resep, tandai siap, lalu konfirmasi penyerahan. Stok baru dikurangi saat penyerahan berdasarkan FEFO. Bila stok kurang, seluruh penyerahan ditolak tanpa perubahan parsial; tambah penerimaan atau batalkan resep dengan alasan yang terdokumentasi.
6. **Kasir:** buka Billing & kasir / kunjungan. Terbitkan invoice setelah pemeriksaan dan proses resep selesai. Terima pembayaran penuh; tunai dapat lebih besar dan sistem menghitung kembalian. Transfer/QRIS harus tepat dan memiliki referensi. Cetak kuitansi PDF.
7. **Kepala Klinik:** buka dashboard dan laporan. Pilih periode, poli/dokter untuk laporan klinis, lalu ekspor. Worker harus berjalan agar file muncul pada daftar unduhan.

## Koreksi dan integritas

- Rekam medis final: dokter menambahkan amendment berisi alasan dan catatan koreksi. Teks asli tetap utuh; tagihan tidak berubah otomatis.
- Diskon: Admin Klinik/Super Admin memasukkan nominal dan alasan saat invoice masih draft.
- Salah pembayaran: administrator melakukan void dengan alasan; transaksi asli dan reversal tersimpan, saldo kembali terbuka.
- Retur pasien: apoteker memilih alokasi batch dan jumlah. Obat masuk karantina, bukan saldo layak jual. Catat ID retur yang ditampilkan.
- Koreksi tagihan retur: administrator melakukan void pembayaran terlebih dahulu jika lunas, kemudian membuat invoice pengganti dengan ID retur terkait. Jumlah obat dikurangi menggunakan harga snapshot lama.
- Opname/adjustment: isi saldo fisik akhir, bukan selisih. Sistem menghitung selisih dan mencatat ledger. Retur supplier memakai jumlah yang dikembalikan.
- Kunjungan dapat dibatalkan/tidak hadir sebelum pemeriksaan dokter. Pembatalan juga memperbarui antrean dan invoice draft.

## Hak akses

Super Admin memiliki seluruh permission demo. Admin Klinik mengatur master dan koreksi finansial; tidak menulis rekam medis. Pendaftaran mengelola pasien/kunjungan. Perawat mengisi pemeriksaan awal. Dokter menulis rekam medis pada kunjungan yang ditugaskan. Apoteker mengelola resep/stok. Kasir menangani pembayaran. Kepala Klinik membaca dashboard/laporan.

Matriks lengkap tersedia di Pengguna & akses dan `config/clinic.php`. Delapan role adalah profil tetap MVP; administrator membuat akun dan memilih role. Tidak ada pembacaan password pengguna.

## Reset password lokal

Menu Lupa kata sandi mengirim tautan melalui mailer log. Administrator development dapat membaca email simulasi di `storage/logs/laravel.log`. Tautan reset tidak boleh dibagikan. Mode log ini untuk demo lokal, bukan pengiriman email produksi.
