# Changelog Tracer Study

Semua perubahan besar pada sistem dicatat dalam dokumen ini.

## [2026-10-11]
- **Standardisasi Desain Stepper Horizontal Kuesioner Alumni pada Detail Alumni Multi-Role**:
  - Mengimplementasikan komponen Stepper horizontal terpadu (mengadopsi estetika kuesioner universitas alumni `Stepper.vue`) pada seluruh antarmuka detail alumni:
    - `SuperAdmin/Alumni/Show.vue`
    - `AdminProdi/Alumni/Show.vue`
    - `AdminFakultas/Alumni/Show.vue`
    - `AdminBiroTiga/AlumniShow.vue`
  - Aksen visual resmi UKDW: Lingkaran aktif berwarna emas UKDW (`#FFD700`) teks hijau botol (`#005B3C`), lingkaran tuntas berwarna hijau tua almamater (`#005B3C`) dengan tanda centang putih bersih (`✓`), lingkaran belum selesai abu-abu bersih (`bg-gray-100 text-gray-400`) dengan nomor tahapan, mini badge centang di kanan atas lingkaran aktif saat 100% lengkap, serta baris garis penghubung hijau/abu-abu.
  - Menghapus Card Ringkasan Status Audit redundan (3 kolom progress bar) dan tombol `&larr; Kembali ke Daftar` di header untuk memperluas ruang pandang data inti dan menjaga konsistensi alur pengguna. Navigasi kembali terpusat melalui tautan Breadcrumbs.
  - Menyatukan data profil alumni pada Biro 3 ke dalam 1 tabel kontinu terpadu per seksi dengan filter seksi instan, mengeliminasi sub-tab bersarang ganda.
- **Penyelarasan Kolom Status & Aksi Terpadu Direktori Mahasiswa (Index)**:
  - Menyederhanakan kolom status menjadi format terpadu 3-in-1 (Profil, Kuesioner Universitas, Evaluasi Atasan) yang menampilkan persentase kelengkapan dan badge status jelas tanpa memakan ruang berlebih.
  - Menyediakan toolbar aksi cepat di setiap baris data mahasiswa: Tombol Detail (`👁️`), Hubungi via WhatsApp (`💬`) dilengkapi template pesan dinamis, URL kuesioner & cara login alumni, Kirim Email Pengingat Flash (`✉️`), serta Sinkronisasi LinkedIn Direct & Modal Riwayat Scraping (`🔗` / `H`).
- **Mailable & Fitur Kirim Email Pengingat Kuesioner Alumni**:
  - Menambahkan kelas Mailable `App\Mail\PengingatKuesionerAlumniMail` dan template email Blade responsif `resources/views/emails/pengingat_kuesioner_alumni.blade.php`.
  - Integrasi endpoint kirim email langsung per alumni di semua controller direktori alumni dengan pop-up notifikasi SweetAlert2.
- **Penyelarasan Algoritma Tahun Kelulusan & Filter Dashboard/Direktori**:
  - Menyamakan kalkulasi dan filter tahun kelulusan mahasiswa/alumni antara dashboard rekapitulasi, direktori alumni, dan service kelengkapan tracer study di seluruh tingkatan peran (Super Admin, Biro 3, Fakultas, Prodi).
- **Pengujian & Kualitas Kode**:
  - Menambahkan automated feature tests `tests/Feature/BiroTigaDaftarAlumniTest.php` dan memperbarui `tests/Feature/SuperAdminDaftarAlumniTest.php`. Seluruh tes lulus (100% passed).
  - Standarisasi format kode Laravel Pint (`vendor/bin/pint --dirty --format agent`).
  - Kompilasi Vite production bundle lulus bersih tanpa galat.

## [2026-10-09]
- **Penyelarasan Desain & Antarmuka Direktori Alumni Multi-Role**:
  - Menghapus kolom Status yang tidak relevan/redundan pada tabel alumni di Super Admin, Admin Fakultas, dan Admin Prodi sehingga visual tabel lebih lega, rapi, dan konsisten.
  - Menyamakan struktur halaman Detail Alumni (`Show.vue`) pada Admin Fakultas dan Admin Prodi menjadi 5 tab komprehensif identik dengan Super Admin: Tab 1 (Kuesioner Universitas), Tab 2 (Kuesioner Prodi), Tab 3 (Data Profil Lengkap), Tab 4 (Hasil Scraping & Trace LinkedIn), dan Tab 5 (Evaluasi Pengguna Lulusan / Atasan).
  - Melakukan ekstraksi service helper bersama [`app/Services/Alumni/LinkedinTraceHelper.php`](file:///c:/study/tracerstudy/app/Services/Alumni/LinkedinTraceHelper.php) untuk menyatukan logika perbandingan field database vs hasil scraping, pemetaan atribut, dan audit trail histori sinkronisasi LinkedIn di semua controller stakeholder.
- **Perbaikan Bug Tata Letak Layar Alumni Terpotong (Sidebar Padding Fix)**:
  - Memperbaiki padding responsif `lg:pl-72` pada elemen `<main>` di [`resources/js/Pages/AdminProdi/Alumni/Index.vue`](file:///c:/study/tracerstudy/resources/js/Pages/AdminProdi/Alumni/Index.vue) dan [`resources/js/Pages/AdminFakultas/Alumni/Index.vue`](file:///c:/study/tracerstudy/resources/js/Pages/AdminFakultas/Alumni/Index.vue).
  - Mengatasi masalah konten yang tertimpa oleh fixed sidebar `w-72`, sehingga judul direktori, 4 kartu metrik statistik alumni, foto avatar, nama lengkap, dan data tabel dapat diakses dengan jelas dan proporsional di semua resolusi layar desktop.
- **Penyempurnaan Pipeline Normalisasi Teks & Algoritma Rekomendasi Kecocokan Perusahaan (`PerusahaanVerificationService.php`)**:
  - Penambahan pembersihan string tanda kurung `(...)` (seperti `(Kantor Pusat)`, `(Persero)`) pada fungsi `cleanCompanyName()` untuk memastikan pencocokan nama berfokus pada entitas pokok badan usaha.
  - Perluasan daftar stop words unit kantor, cabang, dan wilayah (`'kantor', 'pusat', 'cabang', 'branch', 'subcabang', 'kcu', 'kc', 'kcp', 'unit', 'witel', 'regional', 'wilayah', 'area', 'divisi', 'division', 'office', 'hq', 'head', 'representative'`).
  - Pembuatan akronim natural otomatis: Mengakomodasi nama panjang berformat standar seperti `PT Bank Central Asia Tbk` $\rightarrow$ `bca`, cocok dengan `PT BCA INDONESIA` pada Level 2 (Skor 88%).
  - Penambahan Level 3B (Compound Token Acronym Intersect): Mendeteksi akronim parsial pada nama majemuk seperti `BCA Digital` $\leftrightarrow$ `Bank Central Asia` (Skor 70-80%).
  - Ambang batas Token Overlap Ratio disesuaikan menjadi minimal 33% (sepertiga bagian nama) untuk menangani variasi nama dengan panjang token berbeda secara fleksibel dan proporsional.
  - Filter Klasifikasi Sektor Industri Generik Tunggal: Menambahkan `$genericIndustryWords` (`'bank', 'universitas', 'univ', 'institut', 'sekolah', 'rs', 'hospital', 'hotel', 'studio', 'restoran', 'resto', 'cafe', 'toko', 'media', 'lab', 'laboratorium'`) agar entitas seperti `Bank Mandiri` tidak lagi merekomendasikan seluruh bank lain di Indonesia hanya karena kata "Bank", namun tetap merekomendasikan `CV Theresia Inovasi Mandiri` (58%).
- **Penerapan Kebijakan Ketat Larangan Git Push Otomatis (`.agents/rules/git-push-policy.md` & `AGENTS.md`)**:
  - Menyusun aturan tetap dalam proyek bahwa agen AI dilarang keras melakukan `git push` mandiri tanpa instruksi eksplisit pengguna pada sesi pesan tersebut. Semua hasil pekerjaan wajib diuji dan dilaporkan secara lokal terlebih dahulu.
- **Automated Testing & Kualitas Kode**:
  - 124 feature & unit tests lulus (100% passed, 747 assertions).
  - Pint formatting lolos standar PSR-12/Laravel Pint.
  - Vite build bundle sukses tanpa galat.

## [2026-10-08]
- **Deep Linking Terarah Detail Alumni ke LinkedIn Sync**: Menghubungkan banner *Audit & Trace Hasil Scraping LinkedIn* pada Detail Alumni (`/superadmin/alumni/{id}`) langsung ke `/superadmin/linkedin-sync?alumni_id={id}&search={nim}` dengan auto-select tahun lulus alumni, filter pencarian otomatis, highlight cincin hijau emerald ("Profil Dipilih"), dan auto-scroll viewport ke baris profil alumni.
- **Tombol Aksi Cepat Ikon Mata di LinkedIn Sync**: Menyematkan tombol aksi ikon mata pada setiap baris alumni di tabel LinkedIn Sync (`/superadmin/linkedin-sync`) untuk navigasi instan ke halaman Detail Alumni lengkap (`/superadmin/alumni/{id}`).
- **Standarisasi Kolom `skills` & `experience`**: Melakukan migrasi penggantian nama kolom `expert` menjadi `skills` dan `minat` menjadi `experience` pada tabel `biodata`, serta mereplikasi pembaruan pada view database `v_alumni_profile_summary` dan `v_alumni_audit_rekap`.
- **Ekspor Excel & ZIP per Prodi**: Penyempurnaan ekspor data profil dan lembar evaluasi atasan ke format spreadsheet Excel per prodi pada filter tahun terbaru.
- **Automated Testing & Code Quality**: 123 automated feature & unit tests lulus (745 assertions), linting Pint agent lulus, dan Vite build sukses tanpa kendala.

## [2026-10-06]
- **Integrasi Apify LinkedIn Provider & Multi-Provider Architecture**: Menambahkan driver `ApifyLinkedInProvider` untuk integrasi web scraping pihak ketiga menggunakan Actor `data_forge_org~linkedin-scraper` via autentikasi aman Bearer token (`APIFY_API_TOKEN`). Mendukung pemilihan driver via `LINKEDIN_PROVIDER=official`, `LINKEDIN_PROVIDER=apify`, atau `LINKEDIN_DRIVER=mock` di `.env`.
- **Tabel Staging & Histori Audit (`linkedin_sync_results`)**: Hasil scraping diisolasi ke tabel staging `linkedin_sync_results` berstatus `pending` lengkap dengan payload mentah JSON (`raw_response`), parameter input (`input_params`), perbandingan data (`mapped_data`), serta jejak auditor (`reviewed_by`, `reviewed_at`).
- **Alur Review & Persetujuan SuperAdmin (Approval Workflow)**: Antarmuka review di SuperAdmin untuk membandingkan data scraping vs data existing dengan aksi Approve (menerapkan jabatan dan mendaftarkan perusahaan dengan status `Menunggu Verifikasi`) dan Reject (menolak hasil tanpa mengubah data utama).
- **Download Permanen Foto Profil LinkedIn ke Disk Lokal**: Mengunduh foto profil LinkedIn secara fisik ke `public/uploads/profile/profile_{nim}_{timestamp}.jpg` agar tidak bergantung pada CDN LinkedIn sementara yang memiliki expiry token (`?e=...`).
- **Form Foto Profil & Unggah Mandiri di Biodata Alumni**: Menambahkan kartu preview avatar foto profil interaktif pada `FormPribadi.vue` dan handler upload pada `SimpanProfilController.php` dengan badge sumber data (*LinkedIn Sync* / *Unggah Mandiri*).
- **Tab 4 Detail Alumni: Tabel Audit & Trace Pemetaan Scraping (`Show.vue`)**: Menyediakan tab khusus *"Hasil Scraping & Trace LinkedIn"* pada halaman Detail Alumni SuperAdmin (`/superadmin/alumni/{id}`) yang memuat pencarian instan, JSON viewer interaktif, riwayat sinkronisasi, dan tabel perbandingan atribut scraping vs target kolom database vs nilai riil terkini.
- **Sinkronisasi Otomatis Status Karier & Opsi Jabatan Kustom (`FormKarier.vue`)**: Normalisasi `kategori_pekerjaan` menjadi `'Pekerja'` agar kartu status kerja langsung terpilih otomatis, serta menambahkan penanganan dinamis jabatan kustom yang belum ada di referensi baku kampus dengan toggle switch `+ Tulis Jabatan Kustom`.
- **Perbaikan Rute URL Aliasing `/superadmin/linkedin`**: Menambahkan alias rute `/superadmin/linkedin` ke `LinkedInSyncController@index` guna mengatasi error 404 ketika pengguna mengakses tautan pendek menu LinkedIn.
- **Automated Testing**: Menambahkan 23 skenario feature test di `tests/Feature/ApifyLinkedInProviderTest.php` dan 17 skenario di `tests/Feature/SuperAdminLinkedInSyncTest.php` (100% passed).

## [2026-10-03]
- **Fleksibilitas Aksi & Auto Replace Master Perusahaan**: Membuka akses aksi verifikasi (*Verify*, *Edit*, *Auto Replace*, *Reject*) pada data perusahaan berstatus **Terverifikasi** di SuperAdmin, Admin Prodi, dan Admin Fakultas, serta menghapus batasan `->where('status_verifikasi', 'Menunggu Verifikasi')` di controller untuk mencegah error 404.
- **Penyempurnaan Modal Edit Akun & Pre-select Fakultas**: Memperbaiki komputasi `currentFakultasId` pada modal **Sunting Data Akun Pengguna** (`SuperAdmin/ManajemenAkun/Index.vue`) agar Fakultas alumni/admin terisi otomatis secara akurat, serta menambahkan listener penyesuaian otomatis saat Program Studi diubah.
- **Fitur Sinkronisasi LinkedIn (Driver-Based Switch via .ENV)**: Halaman Sinkronisasi LinkedIn SuperAdmin dengan 5 kartu metrik analitik, aksi sinkronisasi individual & massal per angkatan, dan abstraksi `LINKEDIN_DRIVER` (`mock` / `api`) via `AppServiceProvider`.
- **Sentralisasi Data CSV (/data) & Seeder Wilayah/Negara**: Memindahkan berkas master CSV ke `/data`, menyempurnakan `RefNegaraSeeder.php` dan `WilayahSeeder.php` dengan stream `fgetcsv` & proteksi *unique constraint*.

## [2026-09-30]
- **Fitur Otomatis Reset Password via Gmail / SMTP**: Integrasi pengiriman email pemulihan kata sandi berbasis SMTP Google App Passwords dengan Mailable `ResetPasswordMail.php`, template HTML Blade `reset_password.blade.php`, Controller `LupaKataSandiController.php`, form modal interaktif di `Login.vue`, dan halaman Inertia Vue `ResetPassword.vue`.
- **Pengutamaan Prioritas Email Pribadi Biodata**: Mengubah logika accessor `getEmailAttribute()` pada model `User.php` dan `Biodata.php` agar mengutamakan `email_pribadi` pada tabel `biodata` (email aktif alumni terkini).
- **Keseragaman Tampilan Master Data Perusahaan**: Menyeragamkan halaman Master Data & Verifikasi Perusahaan di 3 tingkatan peran (SuperAdmin, Admin Fakultas, dan Admin Prodi) dengan tab navigasi filter status (*Semua Perusahaan*, *Menunggu Verifikasi*, *Master Terverifikasi*) serta fitur rekomendasi kecocokan nama otomatis (*fuzzy matching & token overlap*).
- **Tabel Log Aktivitas & Audit Trail SuperAdmin**: Menambahkan rute `/superadmin/logs`, Controller `LogAktivitasController.php`, dan komponen Vue `SuperAdmin/Logs/Index.vue` lengkap dengan widget statistik, pencarian realtime, filter jenis aksi, dan modal detail JSON (*old values* & *new values*).
- **Migrasi Kolom `created_by_user_id` & `created_by_prodi_id`**: Menambahkan migrasi `2026_09_30_125458_add_created_by_user_id_to_perusahaan_table.php` untuk menjamin integritas tabel `perusahaan`.

## [2026-09-28]
- **Penyesuaian Warna Per Section Landing Page**: Background section Sambutan WR III diatur hijau botol UKDW pekat (`bg-gradient-to-b from-[#00381A] via-[#004D25] to-[#003318]`) dan background section Alur Partisipasi diatur kuning cerah UKDW (`bg-gradient-to-b from-[#FFC700] via-[#FBBF24] to-[#F59E0B]`) berpadu kartu kontras tinggi mutiara.
- **Eliminasi Total Warna Biru di Landing Page**: Menghilangkan seluruh warna dan aksen biru/cyan di komponen landing page, digantikan dengan hijau UKDW (`#004D25`) dan emas (`#FFC700`).
- **Navbar Tema iOS Glossy Bening**: Navbar secara dinamis mendeteksi scroll layar; saat di-scroll otomatis bertransisi menjadi frosted glass glossy bening khas iOS (`backdrop-blur-2xl backdrop-saturate-200 border-b border-white/20 shadow-lg`).
- **Desain Otentikasi Non-Tumpul (Eliminasi AI Rounded Look)**: Mengubah bentuk kartu, form input, tombol, dan modal di `Login.vue` dan `UbahKataSandi.vue` dari rounded bulat tumpul (`rounded-[2.5rem]`, `rounded-full`) menjadi sudut tegas enterprise (`rounded-xl`, `rounded-lg`, `rounded-md`).
- **Autentikasi Alumni Password Tanggal Lahir & Siklus Ubah Sandi**: Menghubungkan proses login alumni di `LoginController.php` dengan kata sandi bawaan tanggal lahir format `ddmmyyyy` (`15082001`). Alumni dengan password default wajib mengubah sandi di `/change-password`, dan setelah berhasil diubah, login berikutnya langsung menuju dashboard seperti biasa.
- **Migrasi Kolom Foto Alumni (`add_foto_to_biodata_table`)**: Menambahkan kolom `foto` (nullable string) pada tabel `biodata` yang dialokasikan untuk path foto profil alumni (`/uploads/profile`) serta memperbarui model `Biodata.php`.
- **Eksplisit Relasi Yudisium & Skripsi (`Biodata.php`)**: Memperbarui relasi `yudisium()` pada model `Biodata` ke `belongsTo(Yudisium::class, 'yudisium_id')` untuk menghubungkan data kelulusan, judul tugas akhir (`judul_ta`), URL repositori karya ilmiah (`url_publikasi`), dan jenis publikasi (`jenis_publikasi`).
- **Agregasi Geografis & Master Wilayah Beranda (`BerandaController.php`)**: Menghitung agregasi spasial `alumniWilayah` (sebaran domisili, sebaran kantor/perusahaan, dan ringkasan per provinsi berisi daftar nama alumni serta daftar instansi untuk tooltip hover peta) dan menyediakan referensi `allProvinsi` serta `allKabupaten`.
- **Modularisasi Komponen Landing Page (`resources/js/components/landing/`)**: Memecah halaman landing page publik menjadi 10 komponen terisolasi (`hero-section.vue`, `alumni-map-section.vue`, `testimonial-slider-section.vue`, `stats-transformation-section.vue`, `sektor-alumni-section.vue`, `career-pillars-section.vue`, `user-guide-section.vue`, `berkas-section.vue`, `blog-section.vue`, `tentang-section.vue`).
- **Peta Interaktif 2 Kolom & SweetAlert2 Detail**: Peta Leaflet GeoJSON 38 provinsi di sisi kiri dan daftar alumni grid 2x2 di sisi kanan dengan filter multi-dimensi, hover tooltip informatif, serta dialog SweetAlert2 lengkap dengan foto profil, judul skripsi, dan link publikasi repositori.
- **Etalase Karya & Tugas Akhir Alumni**: Menggantikan slider testimoni dengan etalase karya ilmiah dan skripsi alumni UKDW dengan tautan langsung repositori kampus.
- **Standardisasi Maskot Pigo & Aset Gambar**: Menempatkan aset resmi di `public/uploads/pigo/`, membuat loader `pigo-loader.vue`, dan menyelaraskan logo resmi UKDW pada seluruh navbar.

## [2026-09-26]
- **Eliminasi Total Caption Video & Masking Bawah (`hero-section.vue`)**: Memotong area bawah YouTube video canvas (`-translate-y-[36%] scale-115`) dan menambahkan masking gradasi gelap bawah (`h-44`) untuk memastikan tidak ada takarir/caption YouTube yang tampak di layar.
- **Tata Letak 2 Kolom Sejajar (Filter Atas, Peta Kiri & Alumni Kanan) (`alumni-map-section.vue`)**: Menerapkan filter bar atas (Cari nama, jabatan, perusahaan, kode pos... | Dropdown Semua Kab/Kota | Tombol Lihat Semua), lalu di bawahnya dibagi 2 kolom: Kiri Peta OpenStreetMap Leaflet dan Kanan Daftar Alumni dalam grid 2 baris x 2 kolom (4 alumni per halaman) dengan paginasi ringkas.
- **Hover Peta Super Informatif (Menampilkan Nama PT & Nama Alumni)**: Tooltip Leaflet saat hover ke provinsi menampilkan data riil: Nama Provinsi, Total Alumni, Daftar Nama Perusahaan/Mitra, dan Daftar Nama Alumni di provinsi tersebut.
- **Kartu Alumni Langsung Nilai Tanpa Label Repetitif**: Menghilangkan label "Jabatan" dan "Perusahaan/Instansi", langsung menampilkan nama alumni, jabatan, perusahaan, kota wilayah, serta judul TA ringkas.
- **Detail Berbasis SweetAlert2**: Tombol Detail membuka pop-up SweetAlert2 lengkap dengan NIM, prodi, tahun lulus, alamat lengkap, kode pos/zipcode, judul TA & link publikasi repositori aktif, studi lanjut, medsos, serta tombol *"Sorot di Peta"*.
- **Pembersihan Elemen**: Menghapus badge & tulisan "Alumni Terverifikasi UKDW", menghapus opsi toggle "Domisili", dan menghapus tombol pilihan cepat.
- **Penggantian Section "Satisfied Alumni Speaks" Menjadi "Karya & Tugas Akhir Alumni UKDW" (`testimonial-slider-section.vue`)**: Mengganti slider testimoni dengan etalase riset dan tugas akhir alumni UKDW lengkap dengan judul skripsi, nama penulis, instansi karier, dan tautan repositori karya ilmiah.
- **Peta Sebaran Alumni Interaktif (Leaflet.js + OpenStreetMap + GeoJSON 38 Provinsi)**: Membangun modul peta interaktif berbasis OpenStreetMap dan GeoJSON 38 provinsi serta 518 kabupaten/kota (titik awal `[-2.55, 118.02]`, zoom 5). Dilengkapi visualisasi choropleth hijau-kuning, fitur klik polygon untuk zoom-in ke level kabupaten/kota, panel rincian wilayah, serta tombol reset ke peta nasional.
- **Kombinasi 2 Warna Utama (Hijau Hutan & Kuning Cerah)**: Menyelaraskan seluruh elemen visual antarmuka publik dengan kombinasi warna hijau pekat UKDW (`#0D542B` / `#00381e`), aksen kuning emas (`#FACC15` / `#EAB308`), dan badge soft mint `• Alumni Terverifikasi` (`bg-[#ECFDF5] border-[#A7F3D0] text-[#065F46]`).
- **Penyajian Data Dinamis & Riil Database**: Mengganti seluruh konten statis/dummy landing page dengan data riil dari database melalui `BerandaController`: statistik 120+ alumni, 32+ mitra perusahaan, 88+ responden, 12 prodi terintegrasi, 94% serapan kerja, dan testimonial riil dengan nama, prodi, jabatan, dan instansi alumni asli.
- **Migrasi & Standardisasi Aset Maskot Pigo & Logo UKDW**: Memindahkan seluruh aset gambar resmi ke `public/uploads/pigo/` (`loading-pigo.png`, `pigo-laptop.png`, `pigo-update-data.png`, `hallo-alumni.png`, `anak-it.png`, `pigo-dokter.png`) dan `public/uploads/logo/logo-ukdw.png`. Mengintegrasikan maskot pada `pigo-loader.vue`, `Login.vue`, `UbahKataSandi.vue`, serta logo resmi di seluruh bilah navigasi stakeholder.
- **Redesain Navigasi Kotak & Tipis (Flat Admin Style)**: Menghapus gradasi dan kotak ikon berlapis pada sidebar, mengubah bentuk menu menjadi kotak ramping (`rounded-lg`, `px-3 py-2`), serta meratakan header dashboard seluruh stakeholder (Prodi, Fakultas, SuperAdmin, Biro 3) menjadi header putih datar tanpa banner hijau raksasa agar terkunci stabil saat layar diperkecil.
- **SweetAlert2 Edit & Approval Perusahaan**: Mengubah fungsi sunting perusahaan menjadi pop-up dialog interaktif SweetAlert2 dengan filter dinamis provinsi/kabupaten dan validasi preConfirm di Admin Prodi & Admin Fakultas. Menghapus floating modal kustom lama.
- **Penyelarasan Warna & Bentuk Badges Status**: Mendesain ulang badge status "Menunggu Verifikasi" (warna krem #FFFBEB, border kuning #FDE68A, dot oranye #F59E0B) dan "Master Terverifikasi" (warna mint #ECFDF5, border #A7F3D0) dengan sudut rounded-2xl sesuai desain referensi.
- **Apple Glossy Sidebars di Semua Role Non-Alumni**: Menerapkan estetika frosted glass (backdrop-blur-2xl, bg-white/85), glossy gradient active pills (#0D542B ke #157a41 dengan pantulan kilau halus), dan fixed pinned viewport (h-screen overflow-hidden) pada Admin Prodi, Admin Fakultas, Super Admin, dan Admin Biro 3.
- **Layout Responsif "Stay on Resize"**: Memastikan layout desktop fixed sidebar (lg:pl-72, min-w-0, overflow-x-auto) konsisten di semua stakeholder sehingga layar tetap stabil dan rapi saat dikecilkan/dizoom.
- **Koreksi Typo & Kolom Relasi ERD**: Menambahkan `created_by` dan `created_prodi_id` pada tabel `perusahaan`, memperbarui diagram relasi Mermaid serta tabel matriks relasi, dan melengkapi kamus data `users` dengan `admin_fakultas` dan `fakultas_id`.

## [2026-09-21]
- **Perbaikan Akses Modal Tambah & Edit Pertanyaan**: Memperbaiki variabel `initialKeterangan` yang belum terdefinisi pada fungsi `openQuestionModal()` di `SuperAdmin/Pertanyaan/Index.vue` serta mengisi form value secara aman via hook `didOpen` SweetAlert2 agar tombol "Tambah Pertanyaan" dan tombol pensil "Edit" dapat diakses normal.
- **Validasi Backend Simpan Pertanyaan**: Menambahkan tipe `radio`, `checkbox`, dan field `keterangan` (batasan nilai/constraint) ke dalam daftar aturan validasi `store` dan `update` di `SimpanPertanyaanController.php`.
- **Eliminasi Duplikasi Tombol Tambah Opsi**: Menghapus duplikasi tombol tambah opsi pada baris butir pertanyaan, kini hanya menggunakan satu tombol yaitu ikon `+` warna hijau di kolom Aksi.
- **Restriksi Opsi Berdasarkan Tipe Pertanyaan**: Membatasi tombol tambah opsi dan kartu opsi hanya untuk 9 tipe yang mendukung opsi (`radio`, `radio_input`, `radio_text`, `multiple_choice`, `rating_5`, `multiple_number`, `matrix`, `matrix_dual`, `multiple_textbox`), serta menghapus tipe yang tidak terpakai/duplikat.
- **Cascading Jump Logic (Alur Lompatan 2-Langkah)**: Mengubah dropdown alur lompatan (*jump logic*) yang sebelumnya menampilkan daftar flat 70+ soal menjadi dropdown bertingkat: Langkah 1 memilih Bagian/Section target, kemudian Langkah 2 memilih butir pertanyaan target di dalam bagian tersebut untuk navigasi yang ringkas dan cepat pada Super Admin dan Biro 3.
- **Penyederhanaan Seeder Gaji / Take Home Pay (F505)**: Memastikan `F505` hanya memiliki 1 opsi jawaban tunggal (`F5051` - *Rata-rata Pendapatan per Bulan (Take Home Pay)*) dan membersihkan data opsi `F5052` serta `F5053` dari database dan seeder.
- **Batasan Nilai / Constraint**: Menambahkan dukungan kolom batasan nilai / constraint isian pada form pertanyaan (contoh: batasan minimal Rp 1.000 kelipatan ribuan untuk gaji) yang ditampilkan langsung di bawah kalimat pertanyaan.
- **Aksen Kuning Khusus Header Kuesioner**: Memberikan penanda warna kuning lembut (`bg-[#FEF9C3]`), aksen border kuning (`border-l-[#EAB308]`), dan badge kuning *Header Kuesioner* untuk butir pertanyaan bertipe `header`.
- **Redesign Tabel Kelola Section & Pertanyaan (Super Admin)**: Mengubah antarmuka Kelola Bagian Kuesioner (`/superadmin/sections`) dan Kelola Butir Pertanyaan (`/superadmin/pertanyaan`) menjadi tampilan data tabel modern, minimalis, dan elegan dengan tombol aksi berbasis ikon SVG bersih (Detail Mata, Edit Pensil, Kelola Opsi, Hapus Sampah, dan Reorder Naik/Turun).
- **Default Tampilan All & Filter Dropdown Section**: Menampilkan semua pertanyaan (**ALL**) secara awal dengan menu tarik-turun (*dropdown filter*) untuk memilih bagian kuesioner tertentu, filter lokasi tampil, serta pencarian teks terpadu.
- **Konfigurasi Lokasi Tampil Butir Pertanyaan (`tampil_di`)**: Menambahkan kolom `tampil_di` (`kuesioner`, `profile`, `both`) pada tabel `ref_subpertanyaan2021` dan form Super Admin sehingga pengaturan kemunculan pertanyaan pada kuesioner universitas atau halaman profil alumni dapat diatur secara dinamis dari database tanpa hardcode.
- **Pembersihan Sidebar Super Admin**: Menghapus tautan jalan pintas publik (*Lihat Beranda Publik*) dari sidebar SuperAdmin agar navigasi lebih terfokus.


- **Standarisasi Ekspor Excel (.xls) Terformat dengan Proteksi Teks NIK/NPWP/NIM**:
  - Mengembalikan format unduhan ke file spreadsheet **Excel (.xls)** asli dengan styling penuh (Header UKDW Green `#0D542B`, blok Identitas Alumni, batas tabel, dan badge warna status).
  - Menerapkan format teks eksplisit Microsoft Excel (`mso-number-format:'\@'`) pada kolom respon, NIK, NPWP, NIM, nomor telepon, dan kode pertanyaan agar terhindar dari notasi ilmiah eksponensial (seperti `3,40401E+15`).
  - Memperbaiki pembacaan NIK dan NPWP melalui fallback `COALESCE(b.nik, da.nik)` dan `COALESCE(b.npwp, da.npwp)` pada kuesioner autofill dan profil tracer.
- **Database Views Terpisah & Modular (Solusi N+1 & Query Cepat)**:
  - Membuat 5 Database Views terpisah per fungsi: `v_alumni_profile_summary`, `v_alumni_tracer_univ_status`, `v_alumni_tracer_prodi_status`, `v_alumni_audit_rekap`, `v_alumni_tracer_export`.
  - Direktori alumni di seluruh stakeholder kini mengeksekusi single query ke view tanpa loop PHP lambat.
- **Admin Biro 3**:
  - Mengganti navbar dengan Sidebar Resmi UKDW (`AdminBiroTiga/Components/Sidebar.vue`).
  - Direktori DataTables terpadu dan Tampilan Detail 3-Tab dengan sinkronisasi LinkedIn dan ekspor Excel/CSV.
- **Admin Prodi**:
  - Mengganti navbar dengan Sidebar Resmi UKDW (`AdminProdi/Components/Sidebar.vue`).
  - Direktori dan Detail Alumni 3-Tab dengan pembatasan khusus prodi login.
- **Admin Fakultas (Stakeholder Baru)**:
  - Menambahkan `fakultas_id` pada tabel `users` dan seeder untuk 7 fakultas UKDW.
  - Modul lengkap Fakultas: Sidebar, Dashboard, Direktori Alumni (dengan dropdown filter prodi dalam fakultas), dan Detail Alumni 3-Tab.
- **Pemisahan File per Stakeholder**:
  - Seluruh controller dan file Vue dipisah per folder aktor untuk kemudahan perawatan (*maintainability*).

**Modul Super Admin (/superadmin/alumni/{id}):**
- **Tata Letak Statis & Stabil**: Menghilangkan *negative margin* (`-mt-10`) dan tumpang tindih kontainer untuk menghilangkan pergeseran layar (*pull/drag elastic bouncing*).
- **Konsistensi Form Profil**: Menghapus pembungkus kartu ganda sehingga form data pribadi, akademik, orang tua, dan karier memiliki lebar dan padding 100% konsisten dan simetris.
- **Filter Dropdown Seksi Langsung Nama**: Mengganti deretan tombol seksi horizontal dengan dropdown `<select>` yang menampilkan langsung nama bagian/seksi pada Kuesioner Universitas maupun Program Studi.
- **Standar Data Table Bersih**: Menghilangkan warna mencolok berlebihan pada tabel, menggantinya dengan gaya tabel data standar (header abu-abu terang, baris data putih bergaris tipis, badge status merah/hijau minimalis, dan header pemisah kuning lembut UKDW).

## [2026-09-20] - Redesain Audit Detail Alumni Super Admin (DataTables Excel-Style View, Urutan Tab Baru, Header Kuning & Highlight Merah)
**Modul Super Admin (/superadmin/alumni/{id}):**
- Urutan Tab Baru: 1. Detail Profile (default), 2. Kuesioner Universitas, 3. Kuesioner Program Studi: [Nama Prodi].
- DataTables View ala Excel: Tabel data dengan Header Section/Question Kuning UKDW `#FDC700`, baris belum dijawab berlatar merah lembut (`bg-rose-50 border-l-4 border-l-rose-500`), dan baris terjawab hijau/bersih.
- Fitur pencarian cepat (*Quick Search*) per tab kuesioner.
- Format Download Excel: Menggunakan CSV UTF-8 BOM murni tanpa dialog peringatan mismatch pada Microsoft Excel.
**Profil Alumni UI/UX & Validasi:**
- Standardisasi Take Home Pay / Gaji: Pola computed getter/setter (Single Source of Truth), peniadaan pengali `* 1000` tersembunyi di semua layer, dan validasi minimal ribuan (>= Rp 1.000) dengan border merah serta notifikasi modal.
- Tab Navigasi Minimalis: Menghilangkan teks "X belum" dan karakter ASCII mentah, digantikan badge lingkaran hijau (lengkap) dan dot status rose/amber halus (belum lengkap).
- Top Notification Banner: Memindahkan peringatan "Ada Perubahan Belum Disimpan" ke banner atas mengambang dengan tombol aksi cepat "Simpan Sekarang".
- Color-coded field states (merah untuk belum terisi, hijau untuk valid) dan pembersihan seluruh emoticon/emoji.
- Filter lokasi perusahaan bertingkat (Negara / Provinsi & Kabupaten) sebelum memilih perusahaan, dengan fallback *"Data perusahaan tidak ditemukan"* dan penambahan perusahaan baru.
- Redesain profesional & eksekutif: Menghapus banner pill merah/pink di atas formulir profil, digantikan dengan indikator langsung pada kolom (*field-level indicators*) berupa border rose halus untuk kolom wajib yang kosong dan border emerald checklist untuk kolom valid.
- Validasi NPWP sesuai UU HPP & PMK No. 112/PMK.03/2022: Dibatasi 15 atau 16 digit angka (integrasi NIK sebagai NPWP Orang Pribadi), tombol cepat "⚡ Gunakan NIK (16 Digit)", dan evaluasi kelengkapan tab secara ketat.
- Pemilihan peran eksklusif (*single choice*): Kategori `Pekerja / Karyawan`, `Wirausaha / Founder`, dan `Melanjutkan Pendidikan` saling mereset atribut peran lain ke `null` saat berpindah.
- Validasi kontak: Email wajib mengandung `@` dan domain valid, nomor telepon 10–15 digit, dan standarisasi URL LinkedIn, Instagram, Facebook.

**Kuesioner Prodi:**
- Menyamakan tombol Lanjut/Kembali (FAB melayang desktop & sticky bottom mobile) dan lebar kontainer (`max-w-6xl xl:max-w-7xl`) dengan Kuesioner Universitas.
- Mengoreksi 4 butir pertanyaan Prodi SI menjadi header deskripsi section.

## [2026-09-20] - Navigasi Sidebar Terpadu Super Admin
**UI/UX & Navigasi:**
- Mengganti navigasi atas (*top navbar*) pada modul Super Admin dengan **Sidebar Terpadu** (`Sidebar.vue`) tetap di sisi kiri layar (`w-72`).
- Menyediakan navigasi menu terstruktur (Dashboard, Kelola Kuesioner, Kelola Section, Data Alumni, dan Pintasan Publik).
- Desain responsif dengan *slide-over drawer* dan *backdrop* khusus perangkat mobile/tablet.
- Menghubungkan seluruh halaman Super Admin (`Dashboard.vue`, `Pertanyaan/Index.vue`, `Section/Index.vue`, `Alumni/Index.vue`, `Alumni/Show.vue`).
- Menghapus komponen `Navbar.vue` lama di modul Super Admin.

## [2026-09-20] - Seeding Perusahaan 15 Kolom, Sinkronisasi Studi Lanjut F18, UI F17 Skala Langsung, & Jump Logic Database-Driven
**Database & Seeding:**
- Mengisi 15 kolom lengkap pada `database/seeders/PerusahaanSeeder.php` termasuk provinsi_id, kabupaten_id, kota, provinsi, negara, jenis_lokasi, jenis_perusahaan, skala, kode_pos, dan status_verifikasi.
- Memperbarui database view `v_alumni_kuesioner_autofill` untuk mendukung pemetaan `BIO_PENDIDIKAN_TINGKAT`.

**Studi Lanjut (F18):**
- Sinkronisasi otomatis data profil studi lanjut (`pendidikan_tingkat`, `perguruan_tinggi`, `pendidikan_prodi`) ke butir kuesioner `F18b` dan `F18c` via `KuesionerSyncService` & `KuesionerController`.
- Penataan rapi kartu `F18` di `Kuesioner.vue` (`F18a` single card, `F18b` & `F18c` 2-column grid, `F18d` input date).

**Frontend & Kuesioner UI:**
- Mengubah format skala kompetensi `TabelF17.vue` menjadi `Sangat Rendah 1 2 3 4 5 Sangat Tinggi` dengan palet 2-warna UKDW (Hijau `#005B3C` & Kuning `#FDC700`).
- Konsistensi tombol stepper (+/-) pada `KartuPertanyaan.vue` dan pembersihan teks rating.
- Logika percabangan kuesioner (`jump_to`) kini 100% didorong oleh relasi basis data tanpa hardcoded ID di frontend.

**Dokumentasi:**
- Memperbarui `ERD.md`, `DAFTAR_PERTANYAAN_MAPPING.md`, `UPDATE_LOG.md`, dan `docs/changelog.md`.

## [2026-09-04] - Restrukturisasi Arsitektur & Pembersihan Logika
**Arsitektur Kode & Controller:**
- Menghapus semua fungsi `Closure` di `routes/web.php` untuk memisahkan *Routing* secara murni.
- Membuat struktur folder Controller bersarang (Nested) per Aktor/User, lalu dipecah per-Fungsi.
- Menerapkan penamaan `UpperCamelCase` dan **Bahasa Indonesia** untuk semua folder, class, dan fungsi di *Controller*.
- Memisahkan Controller Monolitik (`AuthController`, `AlumniProfileController`, `QuestionnaireController`, `Biro3\AlumniController`) menjadi Controller berprinsip *Single Responsibility*.

**Daftar Controller Baru (Bahasa Indonesia):**
- `App\Http\Controllers\Tamu\BerandaController`
- `App\Http\Controllers\Otentikasi\LoginController`
- `App\Http\Controllers\Otentikasi\UbahKataSandiController`
- `App\Http\Controllers\Alumni\Dashboard\DashboardController`
- `App\Http\Controllers\Alumni\Profil\ProfilController`
- `App\Http\Controllers\Alumni\Kuesioner\KuesionerController`
- `App\Http\Controllers\Alumni\Kuesioner\SimpanJawabanController`
- `App\Http\Controllers\AdminBiroTiga\Dashboard\DashboardController`
- `App\Http\Controllers\AdminBiroTiga\KelolaAlumni\DaftarAlumniController`
- `App\Http\Controllers\AdminBiroTiga\KelolaAlumni\DetailAlumniController`
- `App\Http\Controllers\AdminBiroTiga\KelolaAlumni\SinkronisasiLinkedinController`
- `App\Http\Controllers\AdminProdi\Dashboard\DashboardController`
- `App\Http\Controllers\SuperAdmin\Dashboard\DashboardController`

**Frontend (Vue):**
- Membersihkan `resources/js/Pages/alumni/kuesioner/index.vue` dari logika JavaScript yang rumit (GSAP, Jump Logic) untuk menaati aturan "Zero Logic Frontend". Data disiapkan 100% matang dari Backend.
- Membersihkan `resources/js/Pages/alumni/profile/index.vue` dari logika animasi GSAP.
- Menambahkan komentar dokumentasi di *Frontend* yang mengindikasikan larangan penulisan `const` dan fungsi JS secara ekstensif.

**Dokumentasi:**
- Semua *Controller* baru dilengkapi dengan DocBlocks (Komentar terstruktur) dalam bahasa Indonesia untuk memudahkan pemeliharaan kode.
- File `docs/changelog.md` ini dibuat untuk melacak pembaruan di masa mendatang.
