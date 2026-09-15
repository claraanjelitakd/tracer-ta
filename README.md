# Tracer Study UKDW (SERU - Sistem Ekosistem Rekam Jejak Alumni)

Sistem Informasi Tracer Study Alumni Universitas Kristen Duta Wacana (UKDW). Dibangun menggunakan **Laravel 11** (Backend), **Vue 3** & **Inertia.js** (Frontend SPA), serta **Tailwind CSS**.

---

## Daftar Isi
1. [Instalasi & Menjalankan Aplikasi](#instalasi--menjalankan-aplikasi)
2. [Peta Struktur File & Direktori Proyek](#peta-struktur-file--direktori-proyek)
3. [Arsitektur Kuesioner & Alur Data](#arsitektur-kuesioner--alur-data)
   - [A. Kuesioner Utama Universitas](#a-kuesioner-utama-universitas)
   - [B. Kuesioner Khusus Program Studi](#b-kuesioner-khusus-program-studi)
   - [C. Sinkronisasi Otomatis Data Akademik](#c-sinkronisasi-otomatis-data-akademik)
4. [Modularisasi Komponen Vue Alumni](#modularisasi-komponen-vue-alumni)
5. [Manajemen Modul & Peran Pengguna (Roles)](#manajemen-modul--peran-pengguna-roles)
6. [Panduan Pencarian Cepat Kode (Quick Navigation)](#panduan-pencarian-cepat-kode-quick-navigation)

---

## Instalasi & Menjalankan Aplikasi

1. Clone repository
2. Jalankan `composer install`
3. Jalankan `npm install`
4. Copy `.env.example` ke `.env`
5. Generate app key: `php artisan key:generate`
6. Jalankan migrasi dan seeder: `php artisan migrate --seed`
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
│   │   │   ├── Otentikasi/                         # Modul Autentikasi Pengguna
│   │   │   │   ├── LoginController.php             # Login multi-role (Superadmin, Biro 3, Admin Prodi, Alumni)
│   │   │   │   └── UbahKataSandiController.php     # Wajib ganti sandi awal bagi alumni baru
│   │   │   └── Tamu/                               # Modul Pengunjung Publik
│   │   │       └── BerandaController.php           # Landing page publik, statistik capaian, & peta alumni
│   │   └── Middleware/
│   │       ├── CheckMustChangePassword.php         # Interseptor jika user wajib memperbarui password awal
│   │       ├── HandleInertiaRequests.php           # Shared data Inertia (auth user, flash session, navigasi)
│   │       └── RoleMiddleware.php                  # Pembatas hak akses berdasarkan role pengguna
│   ├── Models/                                     # Definisi Model Eloquent & Relasi Database
│   │   ├── User.php                                # Akun pengguna, role, relasi ke alumni & prodi_id
│   │   ├── Alumni.php                              # Entitas profil alumni, relasi ke user, akademik, tracers, dll.
│   │   ├── DataAkademik.php                        # Pangkalan data akademik (NIM, IPK, tahun lulus, predikat)
│   │   ├── DataOrangTua.php                        # Data kontak & profil orang tua / wali alumni
│   │   ├── Yudisium.php                            # Data kelulusan & status yudisium resmi dari universitas
│   │   ├── Prodi.php                               # Data master program studi UKDW
│   │   ├── Company.php                             # Profil perusahaan tempat alumni bekerja
│   │   ├── Atasan.php                              # Data atasan langsung alumni di perusahaan
│   │   ├── Province.php & Kabupaten.php            # Master data wilayah geografis Indonesia
│   │   ├── Ump.php                                 # Data referensi Upah Minimum Provinsi (UMP)
│   │   ├── Kuesioner.php                           # [BARU] Header kuesioner tracer study universitas
│   │   ├── KelompokPertanyaan.php                  # [BARU] Bagian/seksi kuesioner universitas
│   │   ├── RefSubpertanyaan2021.php                # [BARU] Butir pertanyaan kuesioner universitas 2021
│   │   ├── RefSubpertanyaanDetil.php               # [BARU] Opsi jawaban & nilai jump_to kuesioner univ
│   │   ├── Tracer.php                              # [BARU] Jawaban kuesioner tracer study alumni (tabel tracers)
│   │   ├── QuestionMapping.php                     # [BARU] Pemetaan profil alumni via VIEW v_question_mappings
│   │   ├── Questionnaire.php, QuestionSection.php  # [WRAPPER] Kompatibilitas model lama
│   │   ├── Question.php, QuestionOption.php, Response.php # [WRAPPER] Kompatibilitas model lama
│   │   ├── ProdiQuestionSection.php                # Bagian/seksi kuesioner khusus program studi
│   │   ├── ProdiQuestion.php                       # Butir pertanyaan kuesioner khusus program studi
│   │   ├── ProdiQuestionOption.php                 # Opsi jawaban kuesioner khusus program studi
│   │   └── ProdiResponse.php                       # Jawaban alumni untuk kuesioner khusus program studi
│   ├── Providers/
│   │   └── AppServiceProvider.php                  # Konfigurasi layanan global aplikasi
│   └── Services/                                   # Domain Services & Logika Bisnis Terpusat
│       ├── Kuesioner/
│       │   ├── KelengkapanTracerService.php        # Audit skor kelengkapan kuesioner universitas & profil
│       │   └── KuesionerSyncService.php            # Auto-sync data profil ke tracers & prodi_responses
│       └── LinkedIn/
│           └── LinkedInService.php                 # Integrasi data profil profesional LinkedIn
├── database/                                       # Skema & Data Awal Database
│   ├── migrations/                                 # Seluruh riwayat migrasi struktur tabel (DDL)
│   │   ├── 0001_01_01_000000_create_users_table.php
│   │   ├── 2026_09_01_171350_create_data_akademiks_table.php
│   │   ├── 2026_09_01_171352_create_alumnis_table.php
│   │   ├── 2026_09_01_184424_create_kuesioners_table.php
│   │   ├── 2026_09_01_184425_create_kelompok_pertanyaans_table.php
│   │   ├── 2026_09_01_184426_create_ref_subpertanyaan2021_table.php
│   │   ├── 2026_09_01_184427_create_ref_subpertanyaan_detil_table.php
│   │   ├── 2026_09_01_184428_create_tracers_table.php
│   │   ├── 2026_09_01_193004_create_v_question_mappings_view.php
│   │   ├── 2026_09_13_090000_add_prodi_id_to_users_table.php
│   │   └── 2026_09_13_092000_create_prodi_questionnaire_tables.php
│   └── seeders/                                    # Data benih (Seeder)
│       ├── DatabaseSeeder.php                      # Seeder master yang memanggil seluruh seeder
│       ├── UserSeeder.php                          # Akun demo (superadmin, biro3, admin prodi SI/Filsafat, alumni)
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
│   │   │   ├── form/searchable-select.vue          # Dropdown pencarian dinamis (provinsi, kabupaten, prodi)
│   │   │   ├── landing/                            # Komponen landing page (Hero, CTA, Map, Stats, Alur, dll)
│   │   │   └── ui/                                 # Komponen tombol loading & animasi Pigo
│   │   └── Pages/                                  # Halaman Tampilan Inertia.js (View Routes)
│   │       ├── AdminProdi/                         # Antarmuka Admin Program Studi
│   │       │   ├── Dashboard.vue                   # Dashboard analitik & ringkasan aktivitas alumni prodi
│   │       │   ├── Alumni/
│   │       │   │   ├── Index.vue                   # Direktori mahasiswa/alumni khusus prodi terkait
│   │       │   │   └── Show.vue                    # Detail audit 3-tab: Kuesioner Prodi, Univ, & Profil
│   │       │   ├── Pertanyaan/
│   │       │   │   └── Index.vue                   # Kelola butir pertanyaan & opsi kuesioner prodi
│   │       │   ├── Section/
│   │       │   │   └── Index.vue                   # Kelola bagian/seksi kuesioner & urutan per prodi
│   │       │   └── Components/
│   │       │       └── Navbar.vue                  # Header navigasi resmi admin prodi
│   │       ├── Alumni/                             # Antarmuka Alumni
│   │       │   ├── Dashboard.vue                   # Beranda alumni (indikator progres kelengkapan & aksi cepat)
│   │       │   ├── Kuesioner.vue                   # [INDUK] Kuesioner Tracer Study Universitas (Orkestrator Form)
│   │       │   ├── KuesionerProdi.vue              # Pengisian Kuesioner Khusus Prodi (Auto-prefill data akademik)
│   │       │   ├── Components/Kuesioner/           # [MODULAR] Komponen Pecahan Kuesioner Universitas:
│   │       │   │   ├── Navbar.vue                  # Header sticky kuesioner & tombol kembali ke dashboard
│   │       │   │   ├── Stepper.vue                 # Stepper bulatan tahapan 1 s/d N dengan auto-scroll
│   │       │   │   ├── Banner.vue                  # Banner kartu hijau judul bagian ("Bagian X dari Y")
│   │       │   │   ├── TabelF2.vue                 # [BARU] Matriks 7 baris x 5 skala penilaian metode pembelajaran F21-F27
│   │       │   │   ├── TabelF17.vue                # Komparasi dual-matrix F17 (Kemampuan Diri vs Kontribusi Kampus)
│   │       │   │   ├── KartuPertanyaan.vue         # Dispatcher varian input (rating, radio, checkbox, calc, dll)
│   │       │   │   └── Navigasi.vue                # Floating action buttons desktop & mobile bottom bar
│   │       │   └── Profil/
│   │       │       ├── Index.vue                   # Halaman kelola data diri & rekam jejak alumni
│   │       │       └── Components/                 # Komponen formulir profil:
│   │       │           ├── FormPribadi.vue         # Kontak, media sosial, & minat keahlian
│   │       │           ├── FormAkademik.vue        # Data perkuliahan, IPK, & nomor ijazah
│   │       │           ├── FormOrangTua.vue        # Data orang tua / wali
│   │       │           └── FormKarier.vue          # Pekerjaan, perusahaan, atasan, & gaji
│   │       ├── SuperAdmin/                         # Antarmuka Super Admin Universitas
│   │       │   ├── Dashboard.vue                   # Dashboard utama rekam jejak universitas
│   │       │   ├── Alumni/
│   │       │   │   ├── Index.vue                   # Master direktori seluruh mahasiswa & alumni UKDW
│   │       │   │   └── Show.vue                    # Audit detail alumni (Kuesioner Prodi, Univ, & Profil)
│   │       │   ├── Pertanyaan/
│   │       │   │   ├── Index.vue                   # Kelola bank instrumen pertanyaan universitas
│   │       │   │   └── Components/                 # Komponen modal: OptionModal, QuestionCard, QuestionModal
│   │       │   ├── Section/
│   │       │   │   ├── Index.vue                   # Kelola bagian/seksi kuesioner universitas
│   │       │   │   └── Components/SectionModal.vue # Modal kelola seksi universitas
│   │       │   └── Components/
│   │       │       └── Navbar.vue                  # Header navigasi resmi Super Admin
│   │       ├── AdminBiroTiga/                      # Antarmuka Admin Biro III (Kemahasiswaan & Alumni)
│   │       │   ├── Dashboard.vue                   # Monitoring responden per semester/tahun kelulusan
│   │       │   ├── AlumniIndex.vue                 # Direktori alumni tersaring yudisium 'Lulus'
│   │       │   ├── AlumniShow.vue                  # Audit data profil & respon kuesioner
│   │       │   └── Pertanyaan/Index.vue            # Monitoring instrumen kuesioner
│   │       ├── Otentikasi/                         # Antarmuka Login & Keamanan
│   │       │   ├── Login.vue                       # Form login resmi UKDW
│   │       │   └── UbahKataSandi.vue               # Form wajib ubah password default
│   │       └── Tamu/
│   │           └── Beranda.vue                     # Landing page utama publik
│   └── views/
│       └── app.blade.php                           # Template dasar HTML & container root Inertia SPA
└── routes/                                         # [ROUTING] Konfigurasi Rute Aplikasi
    ├── web.php                                     # Seluruh endpoint URL web aplikasi (Tamu, Auth, Alumni, Prodi, Superadmin)
    └── console.php                                 # Perintah konsol Artisan terjadwal
```

---

## Arsitektur Kuesioner & Alur Data

### A. Kuesioner Utama Universitas (Standar Tracer Study 2021)
- **Database**: `kuesioners` $\rightarrow$ `kelompok_pertanyaans` $\rightarrow$ `ref_subpertanyaan2021` $\rightarrow$ `ref_subpertanyaan_detil` $\rightarrow$ `tracers`.
- **Tabel Tracers**: Menyimpan jawaban alumni dengan kolom: `id`, `alumni_id`, `question_id`, `nim`, `kelompok` (char 3: 'BIO', 'F1', 'F2', 'F17', dst), `kode_pertanyaan`, `subpertanyaan`, `answer`, `answer_json`, `keterangan`, `tahun_lulus`.
- **Pemisahan Biodata**:
  - `BIO_TEMPAT_LAHIR`: Ditarik otomatis dari `data_akademiks.tempat_lahir`.
  - `BIO_TANGGAL_LAHIR`: Ditarik otomatis dari `data_akademiks.tanggal_lahir`.
  - Terhubung via database VIEW `v_question_mappings`.
- **Auto-Pull Profil Pekerjaan**:
  - `F2E` & `F5B` (Nama Perusahaan), `F2E1`..`F2E3` (Atasan: Nama, HP, Email), `F2F` & `F510` (Alamat Perusahaan), `F2G` & `F5C` (Posisi/Jabatan), `F2H` & `F5D` (Skala Perusahaan) ditarik otomatis dari profil alumni tanpa perlu diinput ulang.
- **Komponen Matriks**:
  - `TabelF2.vue`: Matriks 7 baris x 5 skala penilaian untuk metode pembelajaran (`F21` s.d. `F27`).
  - `TabelF17.vue`: Dual-matrix komparasi 7 baris x 5 skala penilaian untuk kompetensi (`F17a1`..`a7` vs `F17b1`..`b7`).
- **Alur Percabangan Jump Logic**:
  - `F504` ("Ya" $\rightarrow$ `F502`, `F505`, `F505A`; "Tidak" $\rightarrow$ `F506`).
  - `F3`, `F8`, `F10` mengatur kelanjutan ke seksi berikutnya secara dinamis.

### B. Kuesioner Khusus Program Studi
- **Database**: Terpisah secara independen agar setiap program studi dapat mengelola instrumen evaluasinya sendiri:
  1. `prodi_question_sections`: Bagian/seksi kuesioner berbasis `prodi_id`.
  2. `prodi_questions`: Butir pertanyaan khusus prodi dengan tipe input dinamis.
  3. `prodi_question_options`: Pilihan opsi jawaban butir prodi.
  4. `prodi_responses`: Jawaban tersimpan milik alumni untuk kuesioner program studinya.
- Telah diisi instrumen lengkap untuk **Program Studi Sistem Informasi** (9 Bagian, 52 Pertanyaan) dan **Filsafat Keilahian**.

### C. Sinkronisasi Otomatis Data Akademik
- Pertanyaan identitas alumni pada Kuesioner Prodi:
  - **`PSI-1-01` (Nama)** $\rightarrow$ Diambil otomatis dari `data_akademiks.nama` (fallback `users.name`).
  - **`PSI-1-02` (NIM)** $\rightarrow$ Diambil otomatis dari `alumnis.nim` (atau `data_akademiks.nim`).
  - **`PSI-1-03` (Tahun Kelulusan)** $\rightarrow$ Diambil otomatis dari `data_akademiks.tahun_akademik_lulus` / `tahun_lulus`.
- Ditangani secara terpusat oleh [`KuesionerSyncService::syncProdiResponses($alumni)`](file:///c:/study/tracerstudy/app/Services/Kuesioner/KuesionerSyncService.php).


---

## Modularisasi Komponen Vue Alumni

Sebelumnya file [`resources/js/Pages/Alumni/Kuesioner.vue`](file:///c:/study/tracerstudy/resources/js/Pages/Alumni/Kuesioner.vue) berukuran **1.621 baris kode** dalam 1 file monolitik. Kini telah didekomposisi menjadi arsitektur modular:

| Nama Komponen | Lokasi File | Peran & Tanggung Jawab |
|---|---|---|
| **Kuesioner (Induk)** | `Pages/Alumni/Kuesioner.vue` | Mengorkestrasi state form Inertia, alur *jump logic*, validasi per seksi, dan persistensi sesi `localStorage` (~490 baris). |
| **Navbar** | `Pages/Alumni/Components/Kuesioner/Navbar.vue` | Header atas dengan logo resmi UKDW dan navigasi tombol Kembali ke Dashboard. |
| **Stepper** | `Pages/Alumni/Components/Kuesioner/Stepper.vue` | Navigasi tahapan bulatan angka 1 s/d N, judul seksi, indikator centang selesai (`✓`), dan *auto-scroll*. |
| **Banner** | `Pages/Alumni/Components/Kuesioner/Banner.vue` | Banner hijau judul seksi aktif (*"Bagian X dari Y"*) dan petunjuk pengisian. |
| **TabelF17** | `Pages/Alumni/Components/Kuesioner/TabelF17.vue` | Tabel komparasi dua sisi instrumen F17 (*Kemampuan Diri* vs *Kontribusi Kampus*), badge komparasi otomatis, dan counter progres aspek. |
| **KartuPertanyaan** | `Pages/Alumni/Components/Kuesioner/KartuPertanyaan.vue` | Renderer butir pertanyaan beserta seluruh variasi input (`rating_5`, `searchable_select`, `radio`, `checkbox`, `radio_input`, `multiple_number`, `number`, `text`, layout 2 kolom). |
| **Navigasi** | `Pages/Alumni/Components/Kuesioner/Navigasi.vue` | Tombol navigasi desktop melayang (`< Kembali` dan `> Lanjut/Selesai`) serta *fixed bottom bar* di smartphone/mobile. |

---

## Manajemen Modul & Peran Pengguna (Roles)

1. **`superadmin`**:
   - Dashboard analitik seluruh universitas.
   - Kelola Pertanyaan Kuesioner Universitas (`/superadmin/pertanyaan`).
   - Kelola Section Kuesioner Universitas (`/superadmin/sections`).
   - Direktori Seluruh Alumni & Audit Detail Hasil Kuesioner (Kuesioner Univ, Kuesioner Prodi, Profil Mahasiswa) (`/superadmin/alumni/{id}`).
2. **`admin_prodi`**:
   - Terikat pada `users.prodi_id`.
   - Dashboard KPI khusus program studi (`/prodi/dashboard`).
   - Kelola Butir Pertanyaan Kuesioner Program Studi (`/prodi/pertanyaan`).
   - Kelola Bagian (Section) Kuesioner Program Studi (`/prodi/sections`).
   - Direktori Mahasiswa & Alumni khusus prodi (`/prodi/alumni`).
   - Audit Detail Jawaban & Profil Alumni Program Studi (`/prodi/alumni/{id}`).
3. **`alumni`**:
   - Dashboard kelengkapan data & progres kuesioner (`/alumni/dashboard`).
   - Pengisian Kuesioner Tracer Study Universitas (`/alumni/kuesioner`).
   - Pengisian Kuesioner Khusus Program Studi (`/alumni/kuesioner-prodi`).
   - Pembaruan Biodata, Data Akademik, Data Orang Tua, dan Karier/Perusahaan (`/alumni/profile`).

---

## Panduan Pencarian Cepat Kode (Quick Navigation)

- Ingin mengubah tampilan/input kuesioner tracer alumni? Buka [`resources/js/Pages/Alumni/Components/Kuesioner/KartuPertanyaan.vue`](file:///c:/study/tracerstudy/resources/js/Pages/Alumni/Components/Kuesioner/KartuPertanyaan.vue).
- Ingin mengubah tabel perbandingan F17? Buka [`resources/js/Pages/Alumni/Components/Kuesioner/TabelF17.vue`](file:///c:/study/tracerstudy/resources/js/Pages/Alumni/Components/Kuesioner/TabelF17.vue).
- Ingin melihat logika sinkronisasi data akademik? Buka [`app/Services/Kuesioner/KuesionerSyncService.php`](file:///c:/study/tracerstudy/app/Services/Kuesioner/KuesionerSyncService.php).
- Ingin melihat controller kuesioner prodi alumni? Buka [`app/Http/Controllers/Alumni/Kuesioner/KuesionerProdiController.php`](file:///c:/study/tracerstudy/app/Http/Controllers/Alumni/Kuesioner/KuesionerProdiController.php).
- Ingin mengedit tampilan Admin Prodi? Buka folder [`resources/js/Pages/AdminProdi/`](file:///c:/study/tracerstudy/resources/js/Pages/AdminProdi/).
- Ingin mengedit seeder kuesioner program studi? Buka [`database/seeders/ProdiQuestionnaireSeeder.php`](file:///c:/study/tracerstudy/database/seeders/ProdiQuestionnaireSeeder.php).

# tracer-ta
