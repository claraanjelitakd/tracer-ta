# Tracer Study UKDW (SERU - Sistem Ekosistem Rekam Jejak Alumni)

Sistem Informasi Tracer Study Alumni Universitas Kristen Duta Wacana (UKDW). Dibangun menggunakan **Laravel 11** (Backend), **Vue 3** & **Inertia.js** (Frontend SPA), serta **Tailwind CSS**.

---

## Daftar Isi
1. [Instalasi & Menjalankan Aplikasi](#instalasi--menjalankan-aplikasi)
2. [Peta Struktur File & Direktori Proyek](#peta-struktur-file--direktori-proyek)
3. [Entity Relationship Diagram (ERD) & Skema Database](#entity-relationship-diagram-erd--skema-database)
4. [Arsitektur Kuesioner & Alur Data](#arsitektur-kuesioner--alur-data)
   - [A. Kuesioner Utama Universitas](#a-kuesioner-utama-universitas)
   - [B. Kuesioner Khusus Program Studi](#b-kuesioner-khusus-program-studi)
   - [C. Entitas Biodata & Sinkronisasi Otomatis Data Profil](#c-entitas-biodata--sinkronisasi-otomatis-data-profil)
5. [Modularisasi Komponen Vue Alumni](#modularisasi-komponen-vue-alumni)
6. [Manajemen Modul & Peran Pengguna (Roles)](#manajemen-modul--peran-pengguna-roles)
7. [Panduan Pencarian Cepat Kode (Quick Navigation)](#panduan-pencarian-cepat-kode-quick-navigation)

---

## Instalasi & Menjalankan Aplikasi

1. Clone repository
2. Jalankan `composer install`
3. Jalankan `npm install`
4. Copy `.env.example` ke `.env`
5. Generate app key: `php artisan key:generate`
6. Jalankan migrasi dan seeder: `php artisan migrate:fresh --seed`
7. Jalankan local development server:
   ```bash
   # Terminal 1: Backend Laravel
   php artisan serve

   # Terminal 2: Frontend Vite
   npm run dev
   ```

---

## Peta Struktur File & Direktori Proyek

Berikut adalah peta struktur seluruh file dan direktori utama proyek SERU Tracer Study UKDW untuk memudahkan pencarian file, fungsi, dan komponen:

```text
tracerstudy/
├── app/                                            # [BACKEND] Inti Logika Aplikasi (Laravel)
│   ├── Http/
│   │   ├── Controllers/
│   │   │   ├── AdminProdi/                         # Modul Administrator Program Studi (role: admin_prodi)
│   │   │   │   ├── Dashboard/
│   │   │   │   │   └── DashboardController.php     # Menghitung KPI, statistik respon, & aktivitas alumni prodi
│   │   │   │   ├── KelolaAlumni/
│   │   │   │   │   └── DaftarAlumniProdiController.php # Menampilkan direktori & detail audit alumni prodi
│   │   │   │   └── KelolaPertanyaan/
│   │   │   │       ├── DaftarPertanyaanProdiController.php # Menampilkan & menyaring butir pertanyaan prodi
│   │   │   │       ├── KelolaOpsiProdiController.php       # Tambah, perbarui, & hapus opsi pilihan jawaban
│   │   │   │       ├── KelolaSectionProdiController.php    # CRUD seksi kuesioner prodi & fitur ubah urutan
│   │   │   │       └── SimpanPertanyaanProdiController.php  # Simpan, edit, & hapus butir pertanyaan prodi
│   │   │   ├── Alumni/                             # Modul Alumni (role: alumni)
│   │   │   │   ├── Dashboard/
│   │   │   │   │   └── DashboardController.php     # Progres kuesioner universitas, prodi, & kelengkapan profil
│   │   │   │   ├── Kuesioner/
│   │   │   │   │   ├── KuesionerController.php       # Menampilkan kuesioner tracer study universitas (F1-F22)
│   │   │   │   │   ├── KuesionerProdiController.php  # Menampilkan & menyimpan kuesioner khusus program studi
│   │   │   │   │   └── SimpanJawabanController.php   # Menyimpan jawaban kuesioner tracer study universitas
│   │   │   │   └── Profil/
│   │   │   │       ├── ProfilController.php        # Menampilkan halaman kelola biodata & riwayat alumni
│   │   │   │       └── SimpanProfilController.php  # Simpan pembaruan data pribadi, akademik, orang tua, & karier
│   │   │   ├── SuperAdmin/                         # Modul Super Admin (role: superadmin)
│   │   │   │   ├── Dashboard/
│   │   │   │   │   └── DashboardController.php     # Metrik universitas, chart statistik, & monitoring sistem
│   │   │   │   ├── KelolaAlumni/
│   │   │   │   │   ├── DaftarAlumniSuperAdminController.php # Direktori seluruh alumni lintas fakultas/prodi
│   │   │   │   │   └── DetailAlumniSuperAdminController.php # Audit lengkap respon kuesioner univ, prodi, & profil
│   │   │   │   ├── KelolaPertanyaan/
│   │   │   │   │   ├── DaftarPertanyaanController.php  # Kelola butir instrumen kuesioner universitas
│   │   │   │   │   ├── KelolaOpsiController.php        # Kelola opsi jawaban & konfigurasi alur jump_to
│   │   │   │   │   └── SimpanPertanyaanController.php  # Simpan & perbarui butir instrumen universitas
│   │   │   │   └── KelolaSection/
│   │   │   │       └── KelolaSectionController.php     # CRUD seksi/bagian kuesioner universitas
│   │   │   ├── AdminBiroTiga/                      # Modul Admin Biro III (role: admin_biro3)
│   │   │   │   ├── Dashboard/
│   │   │   │   │   └── DashboardController.php     # Monitoring responden per semester/tahun kelulusan
│   │   │   │   └── KelolaAlumni/
│   │   │   │       ├── DaftarAlumniController.php  # Direktori alumni tersaring yudisium 'Lulus'
│   │   │   │       ├── DetailAlumniController.php  # Detail profil alumni & rekam jejak karier
│   │   │   │       └── SinkronisasiLinkedinController.php # Scraping & sinkronisasi data LinkedIn
│   │   │   ├── Otentikasi/                         # Modul Autentikasi Pengguna
│   │   │   │   ├── LoginController.php             # Login multi-role (Superadmin, Biro 3, Admin Prodi, Alumni)
│   │   │   │   └── UbahKataSandiController.php     # Wajib ganti sandi awal bagi alumni baru
│   │   │   └── Tamu/                               # Modul Pengunjung Publik
│   │   │       └── BerandaController.php           # Landing page publik, statistik capaian, & peta alumni
│   │   └── Middleware/
│   │       ├── CheckMustChangePassword.php         # Interseptor jika user wajib memperbarui password awal
│   │       ├── HandleInertiaRequests.php           # Shared data Inertia (auth user, flash session, navigasi)
│   │       └── RoleMiddleware.php                  # Pembatas hak akses berdasarkan role pengguna
│   ├── Models/                                     # Definisi Model Eloquent & Relasi Database (Nama Tunggal/Singular)
│   │   ├── User.php                                # Akun pengguna, role, relasi ke biodata & prodi_id
│   │   ├── Biodata.php                             # Entitas profil biodata alumni (tabel `biodata`), relasi ke akademik, tracers, dll.
│   │   ├── DataAkademik.php                        # Pangkalan data akademik (tabel `data_akademik`: NIM, IPK, tahun lulus, asal sekolah)
│   │   ├── DataOrangTua.php                        # Data kontak & profil orang tua / wali alumni (tabel `data_orang_tua`)
│   │   ├── Yudisium.php                            # Data kelulusan & status yudisium resmi dari universitas (tabel `yudisium`)
│   │   ├── Prodi.php                               # Data master program studi UKDW (tabel `prodi`)
│   │   ├── Perusahaan.php                          # Profil perusahaan tempat alumni bekerja (tabel `perusahaan`)
│   │   ├── Atasan.php                              # Data atasan langsung alumni di perusahaan (tabel `atasan`)
│   │   ├── Propinsi.php                            # Master data wilayah provinsi Indonesia (tabel `propinsi`)
│   │   ├── Kabupaten.php                           # Master data kabupaten/kota Indonesia (tabel `kabupaten`)
│   │   ├── Ump.php                                 # Data referensi Upah Minimum Provinsi (tabel `ump`)
│   │   ├── Kuesioner.php                           # Header kuesioner tracer study universitas (tabel `kuesioner`)
│   │   ├── KelompokPertanyaan.php                  # Bagian/seksi kuesioner universitas (tabel `kelompok_pertanyaan`)
│   │   ├── RefSubpertanyaan2021.php                # Butir pertanyaan kuesioner universitas 2021 (tabel `ref_subpertanyaan2021`)
│   │   ├── RefSubpertanyaanDetil.php               # Opsi jawaban & nilai jump_to kuesioner univ (tabel `ref_subpertanyaan_detil`)
│   │   ├── Tracer.php                              # Jawaban kuesioner tracer study alumni (tabel `tracer`)
│   │   ├── QuestionMapping.php                     # Pemetaan profil biodata via tabel `question_mappings` & VIEW `v_question_mappings`
│   │   ├── AlumniAuditRekap.php                    # Model Eloquent untuk Database View `v_alumni_audit_rekap`
│   │   ├── AlumniProfileSummary.php                # Model Eloquent untuk Database View `v_alumni_profile_summary`
│   │   ├── AlumniTracerUnivStatus.php              # Model Eloquent untuk Database View `v_alumni_tracer_univ_status`
│   │   ├── AlumniTracerProdiStatus.php             # Model Eloquent untuk Database View `v_alumni_tracer_prodi_status`
│   │   ├── AlumniKuesionerAutofill.php             # Model Eloquent untuk Database View `v_alumni_kuesioner_autofill`
│   │   ├── ProdiQuestionSection.php                # Bagian/seksi kuesioner khusus program studi (tabel `prodi_question_section`)
│   │   ├── ProdiQuestion.php                       # Butir pertanyaan kuesioner khusus program studi (tabel `prodi_question`)
│   │   ├── ProdiQuestionOption.php                 # Opsi jawaban kuesioner khusus program studi (tabel `prodi_question_option`)
│   │   └── ProdiResponse.php                       # Jawaban alumni untuk kuesioner khusus program studi (tabel `prodi_response`)
│   ├── Providers/
│   │   └── AppServiceProvider.php                  # Konfigurasi layanan global aplikasi
│   └── Services/                                   # Domain Services & Logika Bisnis Terpusat
│       ├── Alumni/
│       │   └── AdminAlumniProfileService.php       # [BARU] Service terpusat penyimpanan & pembacaan profil alumni oleh admin (Super Admin, Biro 3, Fakultas, Prodi)
│       ├── Export/
│       │   └── AlumniTracerExcelExporter.php       # Generator ekspor Excel (.xls) per alumni dengan 4 blok identitas lengkap & tabel kuesioner
│       ├── Kuesioner/
│       │   ├── KelengkapanTracerService.php        # Audit skor kelengkapan kuesioner universitas & profil
│       │   └── KuesionerSyncService.php            # Auto-sync data profil ke tracer & prodi_response
│       └── LinkedIn/
│           └── LinkedInService.php                 # Integrasi data profil profesional LinkedIn
├── database/                                       # Skema & Data Awal Database
│   ├── migrations/                                 # Seluruh riwayat migrasi struktur tabel DDL (Nama Tunggal/Singular)
│   └── seeders/                                    # Data benih (Seeder)
│       ├── DatabaseSeeder.php                      # Seeder master yang memanggil seluruh seeder
│       ├── UserSeeder.php                          # Akun demo (superadmin, biro3, admin prodi SI/Filsafat, alumni)
│       ├── WilayahSeeder.php                       # Seeder 38 Provinsi & Kabupaten/Kota
│       ├── UmpSeeder.php                           # Seeder data standar UMP 38 provinsi 2026
│       ├── PerusahaanSeeder.php                    # Seeder master instansi/perusahaan
│       ├── BiodataSeeder.php                       # Seeder 28 field profil biodata & karier
│       ├── DataAkademikSeeder.php                  # Seeder data akademik & asal sekolah
│       ├── DataOrangTuaSeeder.php                  # Seeder kontak orang tua/wali
│       ├── YudisiumSeeder.php                      # Seeder data kelulusan & tugas akhir
│       ├── KuesionerSeeder.php                     # Seeder kuesioner 2021
│       ├── KelompokPertanyaanSeeder.php            # Seeder 10 kelompok pertanyaan
│       ├── RefSubpertanyaan2021Seeder.php          # Seeder 68 butir subpertanyaan 2021
│       ├── RefSubpertanyaanDetilSeeder.php         # Seeder 185 opsi jawaban & jump logic
│       ├── QuestionMappingSeeder.php               # Seeder sinkronisasi data profil
│       └── ProdiQuestionnaireSeeder.php            # Seeder instrumen Prodi SI & Filsafat
├── resources/                                      # [FRONTEND] Antarmuka Pengguna (Vue 3, Inertia & Asset)
│   ├── js/
│   │   ├── app.js                                  # Bootstrapper Vue 3, Inertia SPA, & konfigurasi progress bar
│   │   ├── components/                             # Komponen Reusable Global
│   │   │   ├── form/searchable-select.vue          # Dropdown pencarian dinamis (propinsi, kabupaten, prodi)
│   │   │   ├── landing/                            # Komponen landing page (Hero, CTA, Map, Stats, Alur, dll)
│   │   │   └── ui/                                 # Komponen tombol loading & animasi Pigo
│   │   └── Pages/                                  # Halaman Tampilan Inertia.js (View Routes)
│   │       ├── AdminProdi/                         # Antarmuka Admin Program Studi
│   │       ├── Alumni/                             # Antarmuka Alumni
│   │       ├── SuperAdmin/                         # Antarmuka Super Admin Universitas
│   │       ├── AdminBiroTiga/                      # Antarmuka Admin Biro III (Kemahasiswaan & Alumni)
│   │       ├── Otentikasi/                         # Antarmuka Login & Keamanan
│   │       └── Tamu/                               # Landing page utama publik
│   └── views/
│       └── app.blade.php                           # Template dasar HTML & container root Inertia SPA
└── routes/                                         # [ROUTING] Konfigurasi Rute Aplikasi
    ├── web.php                                     # Seluruh endpoint URL web aplikasi (Tamu, Auth, Alumni, Prodi, Superadmin, Biro 3)
    └── console.php                                 # Perintah konsol Artisan terjadwal
```

---

## Entity Relationship Diagram (ERD) & Skema Database

Dokumentasi lengkap diagram relasi antar tabel (ERD Mermaid), kamus data (*data dictionary*), dan spesifikasi kolom tersedia secara terdedikasi di:
👉 **[`ERD.md`](file:///c:/study/tracerstudy/ERD.md)**

Ringkasan relasi utama:
- **`ref_fakultas` (1:N) $\rightarrow$ `prodi`**: Master data 7 fakultas UKDW terhubung ke program studi.
- **`prodi` (1:N) $\rightarrow$ `biodata`**: Program studi alumni UKDW.
- **`users` (1:1) $\rightarrow$ `biodata`**: Profil alumni (identitas lengkap, kontak, kependudukan, karier, Take Home Pay).
- **`data_akademik` (1:1) $\rightarrow$ `biodata`**: Pangkalan data akademik resmi terhubung via `nim`.
- **`data_orang_tua` (1:1) $\rightarrow$ `biodata`**: Data orang tua/wali alumni terhubung via `orang_tua_id` & `nim`.
- **`yudisium` (1:1) $\rightarrow$ `biodata`**: Data yudisium, dosen, dan tugas akhir terhubung via `yudisium_id` & `nim`.
- **`biodata` (1:N) $\rightarrow$ `tracer`**: Respon pengisian kuesioner tracer study tingkat universitas.
- **`biodata` (1:N) $\rightarrow$ `prodi_response`**: Respon pengisian kuesioner evaluasi program studi.
- **`prodi` (1:N) $\rightarrow$ `prodi_question_section` $\rightarrow$ `prodi_question`**: Manajemen kuesioner mandiri prodi.
- **`propinsi` (1:N) $\rightarrow$ `kabupaten`**: Master data batas wilayah administratif Republik Indonesia.
- **`propinsi` (1:1) $\rightarrow$ `ump`**: Standar Upah Minimum Provinsi tahun 2026.
- **`perusahaan` (1:N) $\rightarrow$ `biodata`**: Master data instansi tempat bekerja alumni.

---

## Arsitektur Kuesioner & Alur Data

### A. Kuesioner Utama Universitas (Standar Tracer Study 2021)
- **Database**: `kuesioner` $\rightarrow$ `kelompok_pertanyaan` $\rightarrow$ `ref_subpertanyaan2021` $\rightarrow$ `ref_subpertanyaan_detil` $\rightarrow$ `tracer`.
- **Tabel Tracer**: Menyimpan jawaban alumni dengan kolom: `id`, `biodata_id`, `question_id`, `nim`, `kelompok` (char 3: 'BIO', 'F1', 'F2', 'F17', dst), `kode_pertanyaan`, `subpertanyaan`, `answer`, `answer_json`, `keterangan`, `tahun_lulus`.
- **Pemisahan Biodata**:
  - `BIO_TEMPAT_LAHIR`: Ditarik otomatis dari `data_akademik.tempat_lahir`.
  - `BIO_TANGGAL_LAHIR`: Ditarik otomatis dari `data_akademik.tanggal_lahir`.
  - Terhubung via database VIEW `v_question_mappings` dan tabel `question_mappings`.
- **Auto-Pull Profil Pekerjaan**:
  - `F2E` & `F5B` (Nama Perusahaan), `F2E1`..`F2E3` (Atasan: Nama, HP, Email), `F2F` & `F510` (Alamat Perusahaan), `F2G` & `F5C` (Posisi/Jabatan), `F2H` & `F5D` (Skala Perusahaan) ditarik otomatis dari profil biodata tanpa perlu diinput ulang.
- **Komponen Matriks & Rincian Gaji (multiple_number)**:
  - `TabelF2.vue`: Matriks 7 baris x 5 skala penilaian untuk metode pembelajaran (`F21` s.d. `F27`).
  - `TabelF17.vue`: Dual-matrix komparasi 7 baris x 5 skala penilaian untuk kompetensi (`F17a1`..`a7` vs `F17b1`..`b7`).
  - `F505` (`multiple_number`): Rincian Take Home Pay (Pekerjaan Utama, Lembur & Tips, Pekerjaan Lainnya) dengan input ribuan rupiah (`.000`), preview nominal Rupiah, dan kalkulasi total otomatis.
- **Urutan 10 Seksi Kuesioner Tracer Study**:
  1. Seksi 1: Identitas & Biodata Mahasiswa (Dikelola terpusat di form Profil)
  2. Seksi 2: Status Pekerjaan Saat Ini (`F8`, `F10`)
  3. Seksi 3: Waktu Mulai & Cara Mencari Pekerjaan (`F3`, `F4`)
  4. Seksi 4: Mendapatkan Pekerjaan & Data Pekerjaan (`F504`, `F502`, `F505`, `F505A`, `F506`)
  5. Seksi 5: Riwayat Lamaran Pekerjaan (`F6`, `F7`, `F7A`)
  6. Seksi 6: Studi Lanjut (`F18`, `F18a`, `F18b`, `F18c`, `F18d`)
  7. Seksi 7: Pembiayaan Kuliah (`F12`)
  8. Seksi 8: Keselarasan & Relevansi Pekerjaan (`F14`, `F15`, `F16`)
  9. Seksi 9: Evaluasi Kompetensi Lulusan (`F17`, `F17a1..a7`, `F17b1..b7`)
  10. Seksi 10: Penekanan Metode Pembelajaran (`F2`, `F21` s.d. `F27`)

- **Ekspor Data Jawaban ke Format Microsoft Excel (.xls)**:
  - Tersedia di detail alumni Super Admin (`/superadmin/alumni/{id}/export-excel`).
  - Menghasilkan dokumen Spreadsheet XML terstruktur lengkap dengan header metadata alumni (NIM, Nama, Prodi, Fakultas, Tahun Lulus), pewarnaan identitas hijau resmi UKDW `#005B3C`, indikator status jawaban, dan mencakup instrumen kuesioner universitas serta prodi.

- **Proteksi Data Perusahaan Terverifikasi**:
  - Jika perusahaan berstatus `Terverifikasi`, detail institusi (alamat, skala, jenis perusahaan, zipcode) terkunci di sisi alumni untuk menjaga integritas master data, kecuali jika alumni berposisi sebagai Owner / Founder / Wiraswasta atau perusahaan baru ditambahkan.

### B. Kuesioner Khusus Program Studi
- **Database**: Terpisah secara independen agar setiap program studi dapat mengelola instrumen evaluasinya sendiri:
  1. `prodi_question_section`: Bagian/seksi kuesioner berbasis `prodi_id`.
  2. `prodi_question`: Butir pertanyaan khusus prodi dengan tipe input dinamis.
  3. `prodi_question_option`: Pilihan opsi jawaban butir prodi.
  4. `prodi_response`: Jawaban tersimpan milik alumni untuk kuesioner program studinya (`biodata_id`, `prodi_question_id`, `answer_text`, `answer_json`).
- Telah diisi instrumen lengkap untuk **Program Studi Sistem Informasi** (9 Bagian, 52 Pertanyaan) dan **Filsafat Keilahian**.

### C. Entitas Biodata & Sinkronisasi Otomatis Data Profil
- Entitas profil alumni menggunakan model `App\Models\Biodata` pada tabel `biodata` yang memuat 28 field identitas, domisili, kontak, dokumen kependudukan, karier, dan media sosial.
- Pertanyaan identitas alumni pada Kuesioner Prodi:
  - **`PSI-1-01` (Nama)** $\rightarrow$ Diambil otomatis dari `biodata.nama` (fallback `data_akademik.nama` / `users.name`).
  - **`PSI-1-02` (NIM)** $\rightarrow$ Diambil otomatis dari `biodata.nim` (atau `data_akademik.nim`).
  - **`PSI-1-03` (Tahun Kelulusan)** $\rightarrow$ Diambil otomatis dari `biodata.tahun_lulus` / `yudisium.tahun_lulus` / `data_akademik.tahun_akademik_lulus`.
- Ditangani secara terpusat oleh [`KuesionerSyncService::syncProdiResponses($biodata)`](file:///c:/study/tracerstudy/app/Services/Kuesioner/KuesionerSyncService.php).

---

## Modularisasi Komponen Vue Alumni

Komponen pengisian kuesioner dipecah secara modular untuk memudahkan pemeliharaan kode:

| Nama Komponen | Lokasi File | Peran & Tanggung Jawab |
|---|---|---|
| **Kuesioner (Induk)** | `Pages/Alumni/Kuesioner.vue` | Mengorkestrasi state form Inertia, alur *jump logic*, validasi per seksi, dan persistensi sesi `localStorage`. |
| **Navbar** | `Pages/Alumni/Components/Kuesioner/Navbar.vue` | Header atas dengan logo resmi UKDW dan navigasi tombol Kembali ke Dashboard. |
| **Stepper** | `Pages/Alumni/Components/Kuesioner/Stepper.vue` | Navigasi tahapan bulatan angka 1 s/d N, judul seksi, indikator centang selesai (`✓`), dan *auto-scroll*. |
| **Banner** | `Pages/Alumni/Components/Kuesioner/Banner.vue` | Banner hijau judul seksi aktif (*"Bagian X dari Y"*) dan petunjuk pengisian. |
| **TabelF17** | `Pages/Alumni/Components/Kuesioner/TabelF17.vue` | Tabel komparasi dua sisi instrumen F17 (*Kemampuan Diri* vs *Kontribusi Kampus*), badge komparasi otomatis, dan counter progres aspek. |
| **KartuPertanyaan** | `Pages/Alumni/Components/Kuesioner/KartuPertanyaan.vue` | Renderer butir pertanyaan beserta seluruh variasi template input (`text`, `textarea`, `number`, `single_choice`/`radio`, `radio_input`, `radio_text`, `multiple_choice`/`checkbox`, `dropdown`, `searchable_select`, `rating_5`, `multiple_number`, `date`, `time`, `file`, layout 2 kolom). |
| **Navigasi** | `Pages/Alumni/Components/Kuesioner/Navigasi.vue` | Tombol navigasi desktop melayang (`< Kembali` dan `> Lanjut/Selesai`) serta *fixed bottom bar* di smartphone/mobile. |

---

## Log Pembaruan (Changelog)

- **v2.4 (Terbaru)**:
  - Menghilangkan seluruh `jump_to` pada seeder (`RefSubpertanyaanDetilSeeder`) agar semua butir kuesioner dapat ditinjau dan diisi secara utuh.
  - Menyusun ulang 10 seksi kuesioner sesuai alur logis baru: `F8` &rarr; `F3` &rarr; `F10` &rarr; `F4` &rarr; `F504` &rarr; `F6` &rarr; `F7` &rarr; `F7A` &rarr; `F18` &rarr; `F12` &rarr; `F14` &rarr; `F15` &rarr; `F17` &rarr; `F2` &rarr; `F16`.
  - Mengimplementasikan proteksi data perusahaan terverifikasi di form karier alumni.
  - Memperbaiki prefill nominal Take Home Pay (`F505`) dan rekomendasi kesesuaian UMR (`F505A`).
  - Mengubah format ekspor data jawaban di Super Admin dari CSV menjadi Spreadsheet Microsoft Excel (`.xls`) dengan styling hijau UKDW dan metadata lengkap.

---

## Manajemen Modul & Peran Pengguna (Roles)

1. **`superadmin`**:
   - Dashboard analitik seluruh universitas.
   - Kelola Pertanyaan Kuesioner Universitas (`/superadmin/pertanyaan`).
   - Kelola Section Kuesioner Universitas (`/superadmin/sections`).
   - Direktori Seluruh Alumni & Audit Detail Hasil Kuesioner (Kuesioner Univ, Kuesioner Prodi, Profil Mahasiswa) (`/superadmin/alumni/{id}`).
   - Unduh Rekap Laporan Jawaban Alumni dalam format Microsoft Excel (`.xls`).
2. **`admin_biro3`**:
   - Dashboard analitik responden kelulusan per periode.
   - Kelola Direktori Alumni tersaring yudisium 'Lulus' berbasis Database View cepat (`/biro3/alumni`).
   - Audit Profil Alumni & Sinkronisasi Scraping Profil LinkedIn (`/biro3/alumni/{id}`).
3. **`admin_fakultas`**:
   - Terikat pada `users.fakultas_id`.
   - Dashboard KPI khusus seluruh prodi dalam fakultas (`/fakultas/dashboard`).
   - Direktori Alumni Fakultas dengan dropdown filter prodi internal (`/fakultas/alumni`).
   - Audit Detail Jawaban 3-Tab & Profil Alumni Fakultas (`/fakultas/alumni/{id}`).
4. **`admin_prodi`**:
   - Terikat pada `users.prodi_id`.
   - Dashboard KPI khusus program studi (`/prodi/dashboard`).
   - Kelola Butir Pertanyaan Kuesioner Program Studi (`/prodi/pertanyaan`).
   - Kelola Bagian (Section) Kuesioner Program Studi (`/prodi/sections`).
   - Direktori Mahasiswa & Alumni khusus prodi (`/prodi/alumni`).
   - Audit Detail Jawaban & Profil Alumni Program Studi (`/prodi/alumni/{id}`).
5. **`alumni`**:
   - Dashboard kelengkapan data & progres kuesioner (`/alumni/dashboard`).
   - Pengisian Kuesioner Tracer Study Universitas (`/alumni/kuesioner`).
   - Pengisian Kuesioner Khusus Program Studi (`/alumni/kuesioner-prodi`).
   - Pembaruan Biodata, Data Akademik, Data Orang Tua, dan Karier/Perusahaan (`/alumni/profile`).

---

## Dokumen Pemetaan Pertanyaan & Arsitektur Form
Untuk referensi lengkap seluruh butir pertanyaan Tracer Study Dikti (`F1` s/d `F18`, `F24A`, `F24B`), letak komponen Vue, kolom basis data, dan aturan sinkronisasi:
👉 **[DAFTAR_PERTANYAAN_MAPPING.md](file:///c:/study/tracerstudy/DAFTAR_PERTANYAAN_MAPPING.md)**

---

## Panduan Pencarian Cepat Kode (Quick Navigation)

- Ingin mengubah tampilan navigasi utama alumni (Dashboard, Profil, Kuesioner Univ & Prodi)? Buka [`resources/js/Pages/Alumni/Components/Navbar.vue`](file:///c:/study/tracerstudy/resources/js/Pages/Alumni/Components/Navbar.vue).
- Ingin melihat peta lengkap kode pertanyaan Dikti & letak Vue formnya? Buka [`DAFTAR_PERTANYAAN_MAPPING.md`](file:///c:/study/tracerstudy/DAFTAR_PERTANYAAN_MAPPING.md).
- Ingin mengubah form profil dan karier alumni? Buka [`resources/js/Pages/Alumni/Profil/Components/FormKarier.vue`](file:///c:/study/tracerstudy/resources/js/Pages/Alumni/Profil/Components/FormKarier.vue).
- Ingin mengubah tampilan/input kuesioner tracer alumni? Buka [`resources/js/Pages/Alumni/Components/Kuesioner/KartuPertanyaan.vue`](file:///c:/study/tracerstudy/resources/js/Pages/Alumni/Components/Kuesioner/KartuPertanyaan.vue).
- Ingin mengubah tabel perbandingan F17? Buka [`resources/js/Pages/Alumni/Components/Kuesioner/TabelF17.vue`](file:///c:/study/tracerstudy/resources/js/Pages/Alumni/Components/Kuesioner/TabelF17.vue).
- Ingin melihat logika sinkronisasi data profil vs kuesioner? Buka [`app/Services/Kuesioner/KuesionerSyncService.php`](file:///c:/study/tracerstudy/app/Services/Kuesioner/KuesionerSyncService.php).
- Ingin melihat controller kuesioner prodi alumni? Buka [`app/Http/Controllers/Alumni/KuesionerProdi/KuesionerProdiController.php`](file:///c:/study/tracerstudy/app/Http/Controllers/Alumni/KuesionerProdi/KuesionerProdiController.php).
- Ingin mengedit tampilan Admin Prodi? Buka folder [`resources/js/Pages/AdminProdi/`](file:///c:/study/tracerstudy/resources/js/Pages/AdminProdi/).
- Ingin mengedit seeder kuesioner program studi? Buka [`database/seeders/ProdiQuestionnaireSeeder.php`](file:///c:/study/tracerstudy/database/seeders/ProdiQuestionnaireSeeder.php).

