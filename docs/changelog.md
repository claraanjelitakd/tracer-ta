# Update Log Tracer Study

## 20 September 2026
- **Database & Seeding**: Seeding lengkap 15 atribut tabel `perusahaan` (`PerusahaanSeeder.php`), foreign key provinsi/kabupaten, dan perbaikan view `v_alumni_kuesioner_autofill` untuk `BIO_PENDIDIKAN_TINGKAT`.
- **Feature (Studi Lanjut F18)**: Sinkronisasi 2 arah profil studi lanjut (`pendidikan_tingkat`, `perguruan_tinggi`, `pendidikan_prodi`) ke kuesioner `F18b` dan `F18c` (`KuesionerSyncService` & `KuesionerController`).
- **UI/UX (Kuesioner)**: Tampilan kartu `F18` ditata bersih (`F18b` & `F18c` 2 kolom, `F18d` input type date).
- **UI/UX (Tabel F17)**: Format skala langsung `Sangat Rendah 1 2 3 4 5 Sangat Tinggi` dengan palet 2-warna UKDW (`#005B3C` dan `#FDC700`).
- **Logic**: Percabangan kuesioner (`jump_to`) kini database-driven tanpa hardcoded question ID di client.
- **Documentation & ERD**: Pembaruan menyeluruh pada `ERD.md`, `DAFTAR_PERTANYAAN_MAPPING.md`, dan `UPDATE_LOG.md`.

## 4 September 2026
- **Fix**: Menambahkan relasi user pada eager loading Alumni::with() di DaftarAlumniController dan DetailAlumniController untuk mencegah error render Vue.
- **Feature**: Mendesain ulang Login.vue menggunakan referensi SIMASTER UGM (split screen, bg overlay, username/NIM).
- **Feature**: Mengubah indikator loading di Login.vue dari spinner tombol menjadi Popup Alert Vue yang interaktif.
- **UI/UX**: Mengubah tampilan global \pigo-loader.vue\ menjadi popup modern (glassmorphism) yang seragam dengan halaman login.
- **Bug Fix**: Memperbaiki navbar di halaman *Landing Page* (Tamu) yang menyebabkan *stuck* karena melempar *user* kembali ke \/login\. Tombol kini berubah cerdas menjadi 'Dashboard' dan mengarah ke _dashboard_ masing-masing role.
- **Keamanan**: Menambahkan konfigurasi \SESSION_EXPIRE_ON_CLOSE=true\ di \.env\ agar sesi/kuki otomatis dihapus saat browser ditutup (menyelesaikan masalah nyangkut login otomatis).
