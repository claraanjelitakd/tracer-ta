# Changelog Tracer Study

Semua perubahan besar pada sistem dicatat dalam dokumen ini.

## [2026-09-21]
- **Kelengkapan 4 Sub-Tab Database Views (`v_alumni_profile_summary` & `v_alumni_audit_rekap`)**: Merekonstruksi `v_alumni_profile_summary` menjadi 78 kolom lengkap mencakup seluruh field dari 4 tab profil (Data Pribadi, Data Akademik lengkap asal sekolah/prodi/fakultas, Data Orang Tua/Wali, dan Data Karier/Perusahaan/Atasan) dengan mengeluarkan entitas yudisium sesuai kebutuhan. Memperbarui `v_alumni_audit_rekap` menjadi 89 kolom untuk master audit seluruh stakeholder.
- **Full Profile Parity Seluruh Stakeholder**: Mengintegrasikan 4 sub-komponen profil lengkap (`FormPribadi`, `FormAkademik`, `FormOrangTua`, `FormKarier`) ke halaman detail alumni Admin Biro 3, Admin Fakultas, dan Admin Program Studi, lengkap dengan tombol simpan profil terhubung ke backend masing-masing.
- **Perbaikan Pemotongan Teks Sidebar**: Mengganti batasan `truncate` dengan `break-words leading-tight/leading-snug` pada sidebar Super Admin, Biro 3, Fakultas, dan Prodi sehingga nama pengguna dan instansi tampil utuh.
- **Kelengkapan Penuh Berkas Excel (.xls)**: Menjamin seluruh atribut identitas (NIM, Nama, NIK, NPWP, No. KK, No. BPJS, Telepon, Email, Alamat, Tahun Lulus, Semester Lulus, IPK, SKS, Judul TA, Perusahaan, dan Atasan) terisi 100% tanpa nilai kosong menggunakan multi-level fallback, dengan proteksi format teks Microsoft Excel (`mso-number-format:'\@'`).
- **Harmonisasi Dashboard Utama Seluruh Stakeholder**: Menstandarkan tampilan dashboard Admin Biro 3, Admin Fakultas, dan Admin Prodi mengikuti rancangan visual, KPI metrik, modul navigasi, dan palet warna solid UKDW milik Super Admin.


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
