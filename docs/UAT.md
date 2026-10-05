# Skenario UAT dan bukti pengujian

## Pemeriksaan otomatis

Regresi lokal 5 Oktober 2026: **29 tes lulus, 213 assertion**, termasuk MySQL concurrency. Build aset berhasil.

- WorkflowTest: FEFO lintas batch, shortage rollback, expired/quarantine, finalisasi/amendment, invoice snapshot, pembayaran penuh, idempotency, reversal, retur karantina, invoice pengganti, opname dan sequence antrean.
- AccessAndPagesTest: dashboard dan halaman untuk delapan role, seluruh master data, larangan akses klinis, lintas klinik, akun nonaktif, timeout, locked identifier dan aksi Livewire terlarang.
- ReportsTest: seluruh query laporan, PDF, pembacaan XLSX, download pemohon, clinic isolation dan pendapatan bersih.
- ConcurrencyTest: dua proses MySQL mulai bersama melalui barrier; RM dan antrean tetap unik, dispense dan pembayaran hanya tercatat sekali.
- BackupTest: snapshot terenkripsi, perubahan data, lalu pemulihan nilai asli dalam database test.
- MoneyTest: perhitungan desimal termasuk nilai besar dan pecahan kecil.

## Demo per role

| Pelaku | Skenario | Hasil yang diharapkan |
|---|---|---|
| Super Admin | Buat/nonaktifkan akun, pilih role | Permission diterapkan pada route dan aksi |
| Admin Klinik | Atur jadwal, tarif dan master obat | Referensi tersedia pada alur pelayanan |
| Pendaftaran | Buat pasien, cari duplikat, buat kunjungan | RM, antrean dan invoice draft terbentuk |
| Perawat | Catat keluhan, alergi dan vital | BMI tampil; status menunggu dokter |
| Dokter | SOAP, diagnosis, tindakan, resep, finalisasi | Catatan terkunci; resep masuk apotek |
| Apoteker | Verifikasi, siap, dispense | FEFO mengurangi batch dan mencatat ledger |
| Kasir | Terbitkan dan lunasi invoice | Total benar, kuitansi tersedia, kunjungan selesai |
| Kepala Klinik | Filter laporan dan ekspor | Hasil sesuai transaksi; tidak dapat menulis data klinis |

Uji juga transaksi tanpa resep, stok tidak cukup, obat kedaluwarsa, klik submit ganda, cancel/no-show, transfer tanpa referensi, void lalu bayar ulang, serta retur setelah lunas.

## Pemeriksaan antarmuka

Login dan dashboard telah diperiksa pada browser lokal; layout desktop 1440 piksel dan layout sempit telah dilihat. Daftar pasien dan modal input telah dibuka. Pengujian route/komponen mencakup delapan role, tetapi penerimaan UAT oleh petugas klinik/dosen tetap merupakan langkah manusia. Jangan menganggap pengujian otomatis sebagai persetujuan klinis.
