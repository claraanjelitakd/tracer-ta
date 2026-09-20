# UPDATE LOG - SERU (Sistem Ekosistem Rekam Jejak Alumni)

## [2026-09-21] Full Profile Parity (Biro 3, Fakultas, Prodi), Sidebar Cutoff Fix, Comprehensive Excel Fields & Harmonized Dashboards

- **Kelengkapan Penuh Detail Profil Mahasiswa (Paritas 100% Seluruh Stakeholder)**:
  - Mengintegrasikan 4 sub-komponen profil lengkap (`FormPribadi.vue`, `FormAkademik.vue`, `FormOrangTua.vue`, `FormKarier.vue`) ke halaman detail alumni **Admin Biro 3** ([AlumniShow.vue](file:///c:/study/tracerstudy/resources/js/Pages/AdminBiroTiga/AlumniShow.vue)), **Admin Fakultas** ([Show.vue](file:///c:/study/tracerstudy/resources/js/Pages/AdminFakultas/Alumni/Show.vue)), dan **Admin Program Studi** ([Show.vue](file:///c:/study/tracerstudy/resources/js/Pages/AdminProdi/Alumni/Show.vue)).
  - Menyediakan form data reaktif lengkap dengan sub-navigasi 4 tab (*1. Data Pribadi, 2. Data Akademik, 3. Data Orang Tua, 4. Karier & Perusahaan*) serta tombol *Simpan Perubahan Profil* yang terhubung ke endpoint backend masing-masing stakeholder (`/biro3/alumni/{id}/profile`, `/fakultas/alumni/{id}/profile`, `/prodi/alumni/{id}/profile`).
  - Mengirimkan data master referensi wilayah (`provinces`, `kabupatens`) dan master `companies` dari masing-masing controller ke halaman frontend.
- **Perbaikan Teks Terpotong pada Sidebar Seluruh Stakeholder**:
  - Memperbaiki pemotongan nama pengguna dan nama fakultas/prodi yang sebelumnya terpotong oleh kelas `truncate` (misal: `Admin Fakultas Teknol...` dan `FAKULTAS TEKNOLOGI INFORMA...`) pada 4 komponen sidebar:
    - [AdminFakultas/Components/Sidebar.vue](file:///c:/study/tracerstudy/resources/js/Pages/AdminFakultas/Components/Sidebar.vue)
    - [AdminProdi/Components/Sidebar.vue](file:///c:/study/tracerstudy/resources/js/Pages/AdminProdi/Components/Sidebar.vue)
    - [AdminBiroTiga/Components/Sidebar.vue](file:///c:/study/tracerstudy/resources/js/Pages/AdminBiroTiga/Components/Sidebar.vue)
    - [SuperAdmin/Components/Sidebar.vue](file:///c:/study/tracerstudy/resources/js/Pages/SuperAdmin/Components/Sidebar.vue)
  - Mengganti pembatasan `truncate` dengan `break-words leading-tight` / `leading-snug` sehingga seluruh nama tampil utuh, rapi, dan estetis.
- **Ekspor Excel (.xls) 100% Lengkap dengan Proteksi Teks & Multi-Level Fallback ([AlumniTracerExcelExporter.php](file:///c:/study/tracerstudy/app/Services/Export/AlumniTracerExcelExporter.php))**:
  - Memastikan seluruh field identitas alumni di berkas Excel terisi lengkap tanpa ada data kosong:
    - Identitas Diri & Kontak: NIM, Nama Lengkap, NIK (KTP), NPWP, No. Kartu Keluarga, No. BPJS, Nomor Telepon/WA, Email Pribadi, Email Kampus, dan Alamat Lengkap Saat Ini.
    - Rekam Jejak Akademik & Kelulusan: Program Studi, Fakultas, Tahun Lulus (dengan multi-level fallback dari `biodatas`, `yudisiums`, dan `data_akademik`), Semester Kelulusan, Angkatan Masuk, IPK, Total SKS, Status Yudisium, Judul Tugas Akhir, dan Dosen Pembimbing.
    - Informasi Karir: Nama Perusahaan, Posisi/Jabatan, Skala Perusahaan, Nama Atasan, dan Kontak Atasan.
  - Semua kode numerik panjang tetap terlindungi dengan format teks Microsoft Excel (`mso-number-format:'\@'`).
- **Penyelarasan Dashboard Utama Biro 3, Fakultas, dan Prodi Seragam dengan Super Admin**:
  - Menstandarisasi tata letak visual, palet warna solid UKDW (`#0D542B`, `#FDC700`, `#FFFFFF`), kartu KPI metrik 4-kolom, modul navigasi dua kolom, serta footer bersih di:
    - [AdminBiroTiga/Dashboard.vue](file:///c:/study/tracerstudy/resources/js/Pages/AdminBiroTiga/Dashboard.vue)
    - [AdminFakultas/Dashboard.vue](file:///c:/study/tracerstudy/resources/js/Pages/AdminFakultas/Dashboard.vue)
    - [AdminProdi/Dashboard.vue](file:///c:/study/tracerstudy/resources/js/Pages/AdminProdi/Dashboard.vue)
- **Quality Assurance**:
  - Seluruh 54 tests PHPUnit Feature & Unit lulus 100% (298 assertions).
  - Standarisasi Laravel Pint lolos 100%.

- **Standarisasi Ekspor Excel (.xls) Terformat dengan Proteksi Teks NIK/NPWP/NIM ([AlumniTracerExcelExporter.php](file:///c:/study/tracerstudy/app/Services/Export/AlumniTracerExcelExporter.php))**:
  - Mengembalikan format unduhan ke file spreadsheet **Excel (.xls)** asli dengan styling penuh (Header UKDW Green `#0D542B`, blok Identitas Alumni, batas tabel, dan badge warna status).
  - Menerapkan format teks eksplisit Microsoft Excel (`mso-number-format:'\@'`) pada kolom respon, NIK, NPWP, NIM, nomor telepon, dan kode pertanyaan untuk mencegah Excel mengonversi angka panjang 16-digit menjadi notasi ilmiah eksponensial (seperti `3,40401E+15`).
  - Memperbaiki pembacaan NIK dan NPWP melalui fallback `COALESCE(b.nik, da.nik)` dan `COALESCE(b.npwp, da.npwp)` pada kuesioner autofill dan profil tracer.
  - Menyatukan fungsionalitas ekspor ke dalam satu service `AlumniTracerExcelExporter` yang digunakan seragam oleh Super Admin, Biro 3, Prodi, dan Fakultas.
- **Pembuatan Database Views Terpisah & Modular (Optimasi Query & Performa Tinggi)**:
  - Mengatasi kendala loading lambat yang sebelumnya disebabkan oleh ratusan kueri evaluasi N+1 di dalam loop PHP (`KelengkapanTracerService::evaluasiKelengkapanTotal`).
  - Merancang 5 Database Views independen, terstruktur, dan memiliki pemisahan fungsi yang jelas:
    1. `v_alumni_profile_summary`: Rekap data profil, akademik, yudisium, fakultas, prodi, orang tua, pekerjaan, dan perusahaan.
    2. `v_alumni_tracer_univ_status`: Agregasi progres dan kelengkapan butir kuesioner wajib universitas.
    3. `v_alumni_tracer_prodi_status`: Agregasi progres dan kelengkapan butir kuesioner program studi.
    4. `v_alumni_audit_rekap`: View master terpadu yang menggabungkan ketiga view di atas untuk query instan (*single-query instant fetch*).
    5. `v_alumni_tracer_export`: View terformat untuk kebutuhan audit dan ekspor Excel/CSV.
  - Seluruh direktori alumni di semua level stakeholder kini membaca langsung dari `v_alumni_audit_rekap`, mengurangi waktu loading dari detik/menit menjadi milidetik (<50ms).
- **Replikasi Antarmuka & Sidebar Terpadu Admin Biro 3 ([AlumniIndex.vue](file:///c:/study/tracerstudy/resources/js/Pages/AdminBiroTiga/AlumniIndex.vue), [AlumniShow.vue](file:///c:/study/tracerstudy/resources/js/Pages/AdminBiroTiga/AlumniShow.vue), [Sidebar.vue](file:///c:/study/tracerstudy/resources/js/Pages/AdminBiroTiga/Components/Sidebar.vue))**:
  - Mengganti top navbar dengan **Sidebar Resmi UKDW** (`AdminBiroTiga/Components/Sidebar.vue`).
  - Menerapkan direktori DataTables terpadu lengkap dengan pencarian, filter tahun, semester, status, dan prodi.
  - Menerapkan tampilan Detail Mahasiswa dengan 3 Tab (1. Detail Profile, 2. Kuesioner Universitas, 3. Kuesioner Program Studi) serta mempertahankan kartu sinkronisasi LinkedIn (MCP Agent) dan ekspor Excel CSV.
- **Replikasi Antarmuka & Sidebar Terpadu Admin Prodi ([Index.vue](file:///c:/study/tracerstudy/resources/js/Pages/AdminProdi/Alumni/Index.vue), [Show.vue](file:///c:/study/tracerstudy/resources/js/Pages/AdminProdi/Alumni/Show.vue), [Sidebar.vue](file:///c:/study/tracerstudy/resources/js/Pages/AdminProdi/Components/Sidebar.vue))**:
  - Mengganti navbar dengan **Sidebar Resmi UKDW** (`AdminProdi/Components/Sidebar.vue`).
  - Menampilkan direktori alumni dan audit detail kuesioner yang dibatasi khusus untuk mahasiswa pada program studi yang login (`where prodi_id = auth()->user()->prodi_id`).
  - Tab 3 secara dinamis menampilkan nama Program Studi pada heading (*Kuesioner Program Studi: [Nama Prodi]*).
- **Implementasi Stakeholder Baru: Admin Fakultas ([DashboardController.php](file:///c:/study/tracerstudy/app/Http/Controllers/AdminFakultas/Dashboard/DashboardController.php), [DaftarAlumniFakultasController.php](file:///c:/study/tracerstudy/app/Http/Controllers/AdminFakultas/KelolaAlumni/DaftarAlumniFakultasController.php), [DetailAlumniFakultasController.php](file:///c:/study/tracerstudy/app/Http/Controllers/AdminFakultas/KelolaAlumni/DetailAlumniFakultasController.php))**:
  - Menambahkan kolom `fakultas_id` pada tabel `users` dan relasi `fakultas()` pada model `User`.
  - Membuat akun seeder untuk 7 Fakultas UKDW (`admin_fti`, `admin_fb`, `admin_fad`, `admin_biotek`, `admin_fk`, `admin_theologi`, `admin_fkh`).
  - Menyediakan modul lengkap Admin Fakultas:
    - `Sidebar.vue` & `Dashboard.vue`: Menampilkan metrik partisipasi tracer study seluruh prodi di fakultas tersebut.
    - `Alumni/Index.vue`: Direktori data alumni fakultas dengan dropdown filter program studi yang ada di fakultas tersebut.
    - `Alumni/Show.vue`: Detail profil dan audit kuesioner 3-tab untuk alumni fakultas.
- **Pemisahan Kontroler & Komponen Vue per Stakeholder**:
  - Seluruh kontroler dan file Vue dipisahkan secara rapi per folder stakeholder (`SuperAdmin`, `AdminBiroTiga`, `AdminProdi`, `AdminFakultas`) untuk kemudahan pemeliharaan (*clean architecture* & *maintainability*).
- **Quality Assurance**:
  - Seluruh routing terdaftar dan terverifikasi.
  - Kompilasi Vite production berhasil 100% (631 modul terkompilasi bersih).
  - Standarisasi kode PHP tervalidasi dengan Laravel Pint.

- **Stabilitas Tata Letak Bebas Pergeseran (*Layout Stays in Place*) ([Show.vue](file:///c:/study/tracerstudy/resources/js/Pages/SuperAdmin/Alumni/Show.vue))**:
  - Menghilangkan *negative margin* (`-mt-10`) dan tumpang tindih kontainer yang sebelumnya memicu efek ketarik (*pull/drag elastic bouncing*).
  - Menstandarkan tinggi *header solid* dengan border pemisah bawah yang rapi serta padding halaman utama statis dan teratur (`py-6 px-4 sm:px-6 lg:px-8`).
- **Konsistensi Ukuran & Lebar Form Profil (Tab 1: Detail Profile) ([Show.vue](file:///c:/study/tracerstudy/resources/js/Pages/SuperAdmin/Alumni/Show.vue))**:
  - Menghapus pembungkus kartu ganda (*double padding wrapper*) di sekeliling komponen form anak (`FormPribadi`, `FormAkademik`, `FormOrangTua`, `FormKarier`).
  - Menghasilkan keseragaman ukuran kartu 100% simetris, presisi, dan proporsional di semua bagian sub-tab profil tanpa nested margin.
- **Filter Bagian Berbasis Dropdown Dinamis & Nama Seksi Langsung ([Show.vue](file:///c:/study/tracerstudy/resources/js/Pages/SuperAdmin/Alumni/Show.vue))**:
  - Mengganti deretan tombol seksi horizontal yang menumpuk dengan komponen **Dropdown `<select>` modern**.
  - Menampilkan **nama seksi/bagian secara langsung** (misal: *"Kondisi Kerja Saat Ini"*, *"Mendapatkan Pekerjaan"*, dsb.) baik pada Kuesioner Universitas maupun Kuesioner Program Studi.
  - Menghilangkan label teknis mentah (*"Seksi 1: ..."*), menyelaraskan format kuesioner universitas sama seperti kuesioner prodi.
- **Desain Data Table Standar, Bersih & Profesional (Tanpa Warna Mencolok Berlebihan)**:
  - Membersihkan palet warna tabel: menggunakan header netral abu-abu terang (`bg-gray-50 text-gray-700 border-b border-gray-200`), baris data putih bersih standar (`bg-white hover:bg-gray-50/80 border-b border-gray-100`), dan tipografi hitam tegas yang mudah dibaca.
  - Header pemisah seksi menggunakan warna kuning lembut UKDW (`bg-amber-100/75 text-amber-950 font-bold`) dengan nama seksi langsung dan status kelengkapan.
  - Butir belum dijawab ditandai dengan badge status merah minimalis (`bg-rose-50 text-rose-700 border border-rose-200`) dan strip `-` merah lembut tanpa mewarnai seluruh baris tabel secara berlebihan.
  - Butir terjawab ditandai dengan badge hijau teratur (`bg-emerald-50 text-emerald-700 border border-emerald-200`) dan teks jawaban tebal.
- **Quality & Automated Testing**:
  - Seluruh 54 tests PHPUnit Feature & Unit lulus 100% (298 assertions).
  - Asset Vite frontend terkompilasi bersih (0 error).

## [2026-09-20] Redesain Detail Audit Alumni Super Admin (DataTables Excel-Style View, Urutan Tab Baru, Header Kuning & Highlight Merah)
- **Restrukturisasi Urutan Tab Detail Alumni ([Show.vue](file:///c:/study/tracerstudy/resources/js/Pages/SuperAdmin/Alumni/Show.vue))**:
  - Menyusun urutan tab terpadu:
    1. **1. Detail Profile** (Urutan 1, default aktif dengan form data pribadi, akademik, orang tua, karier).
    2. **2. Kuesioner Universitas** (Urutan 2, audit butir instrumen dan jawaban tracer study universitas).
    3. **3. Kuesioner Program Studi: [Nama Prodi]** (Urutan 3, memuat nama program studi dinamis pada heading dan tab).
- **Tampilan Tabel Rapi ala Spreadsheet Excel (DataTables View)**:
  - Menyajikan butir kuesioner dalam struktur tabel data profesional (*No, Kode, Pertanyaan / Instrumen, Tipe Input, Sifat, Status, Jawaban Alumni*).
  - **Baris Header Seksi & Question Header**: Diberi latar **Kuning UKDW `#FDC700`** mencolok tanpa kolom jawaban (*span full width*).
  - **Baris Belum Dijawab**: Diberi highlight warna **Merah Lembut (`bg-rose-50 border-l-4 border-l-rose-500`)** dengan status badge merah jelas dan strip jawaban `-`.
  - **Baris Terjawab**: Diberi aksen hijau emerald (`border-l-4 border-l-emerald-600`) dengan badge hijau `Terjawab` dan teks respon tebal.
  - Dilengkapi fitur pencarian cepat (*Quick Search Input*) per tab dan filter seksi instan.
- **Perbaikan Format Export Excel / CSV ([DetailAlumniSuperAdminController.php](file:///c:/study/tracerstudy/app/Http/Controllers/SuperAdmin/KelolaAlumni/DetailAlumniSuperAdminController.php))**:
  - Mengubah export menjadi berkas **CSV murni dengan UTF-8 BOM (`\xEF\xBB\xBF`)** sehingga saat dibuka dengan Microsoft Excel di Windows, file langsung terbuka rapi tanpa peringatan keamanan *"format and extension don't match"*.
- **Standardisasi Input Gaji / Take Home Pay ([FormKarier.vue](file:///c:/study/tracerstudy/resources/js/Pages/Alumni/Profil/Components/FormKarier.vue), [SimpanProfilController.php](file:///c:/study/tracerstudy/app/Http/Controllers/Alumni/Profil/SimpanProfilController.php), [KuesionerController.php](file:///c:/study/tracerstudy/app/Http/Controllers/Alumni/Kuesioner/KuesionerController.php))**:
  - Mengimplementasikan pola **`computed getter & setter` (Single Source of Truth)** pada form input gaji: nilai pada objek form, teks format ribuan di textbox (`new Intl.NumberFormat('id-ID')`), dan label `Terbaca: Rp ...` terhubung secara reaktif 100% dari satu variabel tanpa desinkronisasi.
  - Menghapus seluruh manipulasi atau pengali sembunyi-sembunyi (`* 1000`) di frontend dan di seluruh kontroler backend.
  - **Validasi Nominal Minimal Ribuan (Rp 1.000)**:
    - Input di bawah Rp 1.000 (misal: `709` atau `899`) langsung ditandai dengan border merah dan peringatan *minimal Rp 1.000*.
    - Proses submit dicegah baik di frontend (SweetAlert peringatan) maupun di backend ([SimpanProfilController.php](file:///c:/study/tracerstudy/app/Http/Controllers/Alumni/Profil/SimpanProfilController.php)) dengan pesan kesalahan validasi yang jelas.
- **Penyederhanaan Visual Indikator Tab Navigasi Profil ([Index.vue](file:///c:/study/tracerstudy/resources/js/Pages/Alumni/Profil/Index.vue))**:
  - Menghilangkan teks hitungan (*"1 belum"*, *"X belum"*) serta karakter ASCII mentah (`✓` / `✕`) pada tab navigasi.
  - Menggantinya dengan indikator visual langsung yang elegan:
    - **Data Lengkap**: Badge lingkaran hijau dengan ikon SVG centang mikro presisi.
    - **Data Belum Lengkap**: Titik status (*dot indicator*) rose/amber minimalis tanpa teks mengganggu.
- **Relokasi Peringatan "Ada Perubahan Belum Disimpan" ke Top Notification Banner ([Index.vue](file:///c:/study/tracerstudy/resources/js/Pages/Alumni/Profil/Index.vue))**:
  - Memindahkan peringatan modifikasi data dari bagian bawah formulir ke **Banner Notifikasi Mengambang di Bagian Atas Formulir (tepat di bawah tab navigasi)**.
  - Dilengkapi tombol aksi cepat **"Simpan Sekarang"** langsung di dalam banner notifikasi.
- **Quality & Automated Tests**:
  - 54 tests PHPUnit Feature & Unit lulus 100% (299 assertions).
  - Format kode PHP tervalidasi bersih dengan Laravel Pint.
  - Bundle frontend terkompilasi optimal tanpa error/warning melalui Vite.
- **Pembersihan Seluruh Emoticon & Emoji ([FormPribadi.vue](file:///c:/study/tracerstudy/resources/js/Pages/Alumni/Profil/Components/FormPribadi.vue), [FormKarier.vue](file:///c:/study/tracerstudy/resources/js/Pages/Alumni/Profil/Components/FormKarier.vue), [FormOrangTua.vue](file:///c:/study/tracerstudy/resources/js/Pages/Alumni/Profil/Components/FormOrangTua.vue), [Index.vue](file:///c:/study/tracerstudy/resources/js/Pages/Alumni/Profil/Index.vue))**:
  - Menghapus seluruh ikon emoji/emoticon (🏢, 🚀, 🎓, ⚡, 💡) di seluruh form profil untuk menciptakan antarmuka yang bersih, minimalis, dan profesional.
- **Color-Coded Status Tanpa Text Badges**:
  - Menghapus seluruh badge teks checklist/pil status (`✓ Terisi`, `Belum diisi`, `✓ URL Valid`, `✓ 16 Digit`, `✓ Terpilih`, `Belum dipilih`, dsb.).
  - Mengandalkan indikator warna border dan background input secara menyeluruh pada semua kolom (baik wajib maupun opsional):
    - **Belum Diisi / Format Tidak Valid**: Border dan latar rose/merah lembut (`border-rose-300 bg-rose-50/20 text-gray-900 focus:border-rose-500`).
    - **Sudah Terisi / Format Valid**: Border dan latar emerald/hijau (`border-emerald-300 bg-white text-gray-900 focus:border-[#005B3C]`).
- **Perbaikan Format Input Gaji & Multiple Number (Format Minimal Ribuan Adaptif) ([FormKarier.vue](file:///c:/study/tracerstudy/resources/js/Pages/Alumni/Profil/Components/FormKarier.vue), [KartuPertanyaan.vue](file:///c:/study/tracerstudy/resources/js/Pages/Alumni/Components/Kuesioner/KartuPertanyaan.vue))**:
  - Saat alumni pertama kali mengetik angka satuan (misal: `5`), kolom textbox langsung melengkapi akhiran ribuan menjadi `5.000` (disimpan `5000` dan terbaca `Rp 5.000 / bulan`).
  - Nilai di textbox tetap fleksibel dapat diedit dan di-*adjust* ke nominal berapapun (misal `5000000` menjadi `5.000.000` dan terbaca `Rp 5.000.000 / bulan`) dengan sinkronisasi 100% presisi antara input dan output.
- **Filter Lokasi Wilayah Sebelum Memilih Perusahaan ([FormKarier.vue](file:///c:/study/tracerstudy/resources/js/Pages/Alumni/Profil/Components/FormKarier.vue))**:
  - Pilihan wilayah (Negara untuk Luar Negeri; Provinsi dan Kabupaten/Kota untuk Dalam Negeri) kini dapat dipilih langsung di bagian atas untuk memfilter daftar perusahaan pada wilayah tersebut.
  - Jika tidak ada perusahaan yang terdaftar pada kota/negara yang dipilih, sistem menampilkan status *"Data perusahaan tidak ditemukan pada wilayah/filter yang dipilih."* disertai tombol pintas *"+ Tambah Perusahaan Baru"*.
  - Menambahkan tombol *"Reset Provinsi"* / *"Reset Kota"* / *"Reset Negara"* untuk mempermudah pencarian multi-wilayah.
- **Penyelarasan Tata Letak Simetris Pertanyaan Lamaran Kerja F6, F7, F7A ([Kuesioner.vue](file:///c:/study/tracerstudy/resources/js/Pages/Alumni/Kuesioner.vue), [KartuPertanyaan.vue](file:///c:/study/tracerstudy/resources/js/Pages/Alumni/Components/Kuesioner/KartuPertanyaan.vue))**:
  - Mengelompokkan trio butir pertanyaan kuantitas lamaran kerja (F6, F7, dan F7A) ke dalam **Grid 3 Kolom Sejajar** (`grid grid-cols-1 md:grid-cols-3 items-stretch`).
  - Menyelaraskan tinggi kartu (`h-full flex flex-col justify-between`), proporsi tipografi judul soal, dan posisi tombol *stepper number* di bagian bawah secara horizontal sejajar.
- **Quality & Verification**:
  - 53 tests PHPUnit lulus 100% (297 assertions).
  - Laravel Pint clean.
  - Vite build sukses (0 error, 0 warning).

## [2026-09-20] Redesain Profesional Profil Alumni, Validasi NPWP Regulasi PMK 112/2022, Single-Choice Role Switcher, & Standardisasi Kuesioner Prodi
- **Redesain Profesional & Eksekutif Profil Alumni ([Index.vue](file:///c:/study/tracerstudy/resources/js/Pages/Alumni/Profil/Index.vue), [FormPribadi.vue](file:///c:/study/tracerstudy/resources/js/Pages/Alumni/Profil/Components/FormPribadi.vue), [FormKarier.vue](file:///c:/study/tracerstudy/resources/js/Pages/Alumni/Profil/Components/FormKarier.vue), [FormOrangTua.vue](file:///c:/study/tracerstudy/resources/js/Pages/Alumni/Profil/Components/FormOrangTua.vue))**:
  - Menghapus banner pill merah/pink besar di atas form, menggantikannya dengan **indikator kelengkapan langsung pada kolom isian (*field-level indicators*)**.
  - Kolom wajib yang belum terisi ditandai dengan border lembut warna rose/merah dan badge status "Belum diisi / Wajib diisi".
  - Kolom yang telah terisi atau valid ditandai dengan border emerald dan badge status checklist "✓ Terisi / Valid" selaras palet warna resmi UKDW `#005B3C` dan `#FFD700`.
  - Tab navigasi menampilkan status ringkas dan presisi: badge `✓` emas/hijau untuk bagian lengkap, dan badge `✕` untuk bagian yang belum lengkap tanpa animasi berkedip.
- **Validasi NPWP Sesuai Regulasi Terbaru (UU HPP & PMK No. 112/PMK.03/2022)**:
  - Membatasi input NPWP khusus angka dengan panjang **15 atau 16 digit** sesuai aturan terbaru integrasi NIK sebagai NPWP Orang Pribadi.
  - Menyediakan tombol cepat **"⚡ Gunakan NIK (16 Digit)"** untuk menyalin 16 digit NIK secara otomatis ke kolom NPWP.
  - Memperketat evaluasi kelengkapan tab: Tab Identitas & Alamat tidak akan bertanda `✓` jika NPWP belum diisi atau formatnya belum valid (15/16 digit).
- **Pemilihan Peran & Aktivitas Eksklusif (*Single Choice Role Switcher*)**:
  - Pilihan kategori peran pada form karier (`Pekerja / Karyawan`, `Wirausaha / Founder`, dan `Melanjutkan Pendidikan`) bersifat eksklusif (*single choice*).
  - Saat alumni beralih peran (misalnya ke *Melanjutkan Pendidikan*), seluruh data perusahaan, atasan, posisi jabatan, dan gaji pada kategori lain secara otomatis di-reset menjadi `null` agar basis data tetap bersih dan tidak menyimpan data usang.
  - Kartu isian data perusahaan dan data atasan disesuaikan secara dinamis dan tidak diwajibkan untuk kategori studi lanjut.
- **Standardisasi & Validasi Format Kontak & Media Sosial**:
  - Validasi email wajib mengandung karakter `@` dan nama domain valid.
  - Validasi nomor telepon/WhatsApp dibatasi format 10–15 digit angka (dengan dukungan tanda `+`).
  - Standardisasi placeholder dan pembersihan format URL media sosial: LinkedIn URL (`https://linkedin.com/in/...`), LinkedIn Username (`username_linkedin`), Instagram URL (`https://instagram.com/...`), dan Facebook URL (`https://facebook.com/...`).
- **Standardisasi Tampilan Kuesioner Prodi ([KuesionerProdi.vue](file:///c:/study/tracerstudy/resources/js/Pages/Alumni/KuesionerProdi.vue), [ProdiQuestionnaireSeeder.php](file:///c:/study/tracerstudy/database/seeders/ProdiQuestionnaireSeeder.php))**:
  - Menyamakan sistem tombol "Lanjut" & "Kembali" serta navigasi stepper dengan Kuesioner Universitas (`Kuesioner.vue`): menggunakan FAB *floating action button* melayang di desktop dan *bottom navigation bar* di mobile.
  - Menyamakan lebar kontainer formulir menjadi `max-w-6xl xl:max-w-7xl`.
  - Mengoreksi 4 butir instrumen Prodi Sistem Informasi yang sebelumnya keliru terdaftar sebagai pertanyaan isian terbuka menjadi header deskripsi section.
- **Automated Tests & Quality**:
  - Seluruh 53 tests PHPUnit Feature & Unit lulus 100% (297 assertions).
  - Pemformatan kode PHP bersih dengan Laravel Pint (`vendor/bin/pint --format agent`).
  - Vite build selesai sukses tanpa warning/error.

## [2026-09-20] Navigasi Sidebar Terpadu Super Admin (Menggantikan Navigasi Atas)
- **Desain & Implementasi Sidebar Terpadu Super Admin ([Sidebar.vue](file:///c:/study/tracerstudy/resources/js/Pages/SuperAdmin/Components/Sidebar.vue))**:
  - Mengubah sistem navigasi modul Super Admin dari navigasi atas (*top navigation bar*) menjadi **Sidebar** navigasi tetap (*fixed desktop sidebar* `w-72`) di sisi kiri layar.
  - Sidebar dilengkapi dengan:
    - **Header & Branding**: Logo resmi UKDW, judul "Tracer Study UKDW", dan badge indikator "Super Admin".
    - **Navigasi Menu Utama**: Link menu terstruktur dengan ikon representatif, status aktif warna hijau resmi UKDW `#0D542B` dengan aksen kuning `#FDC700` (Dashboard, Kelola Kuesioner, Kelola Section, dan Data Alumni).
    - **Pintasan Publik**: Tombol pintas cepat "Lihat Beranda Publik" (`/`).
    - **Profil Pengguna & Logout Bawah**: Kartu identitas Super Admin dengan avatar inisial, nama, email, serta tombol keluar (*logout*) dengan modal konfirmasi SweetAlert2.
    - **Responsivitas Mobile / Tablet**: Topbar ringkas khusus mobile dengan tombol hamburger yang membuka *slide-over drawer* berserta *backdrop overlay* interaktif.
- **Integrasi Penuh di Seluruh Halaman Super Admin**:
  - [Dashboard.vue](file:///c:/study/tracerstudy/resources/js/Pages/SuperAdmin/Dashboard.vue)
  - [Pertanyaan/Index.vue](file:///c:/study/tracerstudy/resources/js/Pages/SuperAdmin/Pertanyaan/Index.vue)
  - [Section/Index.vue](file:///c:/study/tracerstudy/resources/js/Pages/SuperAdmin/Section/Index.vue)
  - [Alumni/Index.vue](file:///c:/study/tracerstudy/resources/js/Pages/SuperAdmin/Alumni/Index.vue)
  - [Alumni/Show.vue](file:///c:/study/tracerstudy/resources/js/Pages/SuperAdmin/Alumni/Show.vue)
- **Pembersihan Kode Usang**:
  - Menghapus komponen navigasi atas lama `resources/js/Pages/SuperAdmin/Components/Navbar.vue` yang sudah tidak digunakan.
- **Automated Tests & Asset Build**:
  - Seluruh 48 tests PHPUnit Feature & Unit lulus 100% (256 assertions).
  - Vite build selesai sukses (4.91s).

## [2026-09-20] Seeding Komprehensif Perusahaan (15 Kolom), Sinkronisasi & Standardisasi Studi Lanjut F18, Format Skala Langsung F17, dan Database-Driven Jump Logic
- **Seeding Komprehensif Master Data Perusahaan 15 Kolom ([PerusahaanSeeder.php](file:///c:/study/tracerstudy/database/seeders/PerusahaanSeeder.php))**:
  - Mengisi seluruh 15 atribut tabel `perusahaan` secara lengkap dan valid: `id`, `nama_perusahaan`, `propinsi_id`, `kabupaten_id`, `alamat`, `kota`, `provinsi`, `negara`, `jenis_lokasi` (Dalam Negeri / Luar Negeri), `jenis_perusahaan`, `jenis_perusahaan_lainnya`, `skala`, `kode_pos`, `status_verifikasi` (`verified`), serta timestamps.
  - Memastikan integritas relasi foreign key dengan `provinsi` dan `kabupaten_kota` serta mencakup ragam sektor (BUMN, Startup/Swasta, Instansi Pemerintah, Multilateral, hingga Perusahaan Luar Negeri).
- **Sinkronisasi Dua Arah & Standardisasi Studi Lanjut F18 (`F18a` s.d. `F18d`)**:
  - Menghubungkan profil studi lanjut alumni (`pendidikan_tingkat`, `perguruan_tinggi`, `pendidikan_prodi`) di [FormKarier.vue](file:///c:/study/tracerstudy/resources/js/Pages/Alumni/Profil/Components/FormKarier.vue) dengan pertanyaan kuesioner `F18b` dan `F18c` via [KuesionerSyncService.php](file:///c:/study/tracerstudy/app/Services/Kuesioner/KuesionerSyncService.php) dan [KuesionerController.php](file:///c:/study/tracerstudy/app/Http/Controllers/Alumni/Kuesioner/KuesionerController.php).
  - Merapikan tata letak tampilan kartu `F18` di [Kuesioner.vue](file:///c:/study/tracerstudy/resources/js/Pages/Alumni/Kuesioner.vue):
    - `F18a` (Sumber Biaya): Kartu tersendiri (single card).
    - `F18b` (Perguruan Tinggi) & `F18c` (Program Studi): Ditata berdampingan rapi dalam 2-column grid.
    - `F18d` (Tanggal Masuk): Menggunakan input tanggal native HTML5 (`type="date"`).
  - Memperbarui database view `v_alumni_kuesioner_autofill` untuk mendukung pemetaan `BIO_PENDIDIKAN_TINGKAT`.
- **Format Skala Langsung F17 & Konsistensi UI Kuesioner**:
  - Mengubah header skala kompetensi pada [TabelF17.vue](file:///c:/study/tracerstudy/resources/js/Pages/Alumni/Components/Kuesioner/TabelF17.vue) menjadi format langsung yang padat dan presisi: `Sangat Rendah 1 2 3 4 5 Sangat Tinggi`.
  - Menggunakan palet 2-warna resmi UKDW yang bersih: Hijau `#005B3C` (Kompetensi Saat Lulus) dan Kuning/Amber `#FDC700` (Kontribusi PT), serta menghapus redundansi indikator progres aspek.
  - Standardisasi tombol stepper jumlah (+ / -) pada [KartuPertanyaan.vue](file:///c:/study/tracerstudy/resources/js/Pages/Alumni/Components/Kuesioner/KartuPertanyaan.vue) dengan warna netral `bg-gray-100` dan hover hijau UKDW.
- **Database-Driven Jump Logic**:
  - Logika percabangan/lompatan pertanyaan (`jump_to`) sepenuhnya digerakkan oleh metadata database dari `ref_subpertanyaan_detil` tanpa *hardcoded question ID* di frontend.
- **Pembaruan ERD & Pemetaan Instrumen Pertanyaan**:
  - Memperbarui kamus data tabel `perusahaan` dan `biodata` pada [ERD.md](file:///c:/study/tracerstudy/ERD.md).
  - Melengkapi dokumen [DAFTAR_PERTANYAAN_MAPPING.md](file:///c:/study/tracerstudy/DAFTAR_PERTANYAAN_MAPPING.md) dengan seluruh butir kuesioner standar Dikti 2021 dan relasi sinkronisasinya.
- **Automated Tests**:
  - 48 tests PHPUnit Feature & Unit lulus 100% (247 assertions).


- **Standarisasi Input Posisi / Jabatan Wiraswasta & Startup (F5C) Menjadi Dropdown `<select>`**:
  - Mengubah kontrol input `posisi_wiraswasta` (`F5C`) pada [FormKarier.vue](file:///c:/study/tracerstudy/resources/js/Pages/Alumni/Profil/Components/FormKarier.vue) dari input teks + tombol pill cepat menjadi dropdown `<select>` standar yang konsisten dan rapi.
  - Opsi dropdown yang disediakan: `Owner`, `Founder`, `Co-Founder`, `Direktur Utama`, `Pengelola Usaha`, `Freelancer / Konsultan Mandiri`, serta opsi khusus `Lainnya`.
  - Jika alumni memilih `Lainnya`, sistem menampilkan kolom isian teks tambahan (`posisi_wiraswasta_lainnya`) yang langsung disinkronkan ke backend [SimpanProfilController.php](file:///c:/study/tracerstudy/app/Http/Controllers/Alumni/Profil/SimpanProfilController.php) dan [KuesionerSyncService.php](file:///c:/study/tracerstudy/app/Services/Kuesioner/KuesionerSyncService.php).
- **Navigasi Utama Terpadu Alumni (Unified `Navbar.vue`)**:
  - Membuat komponen navigasi terpusat di [Navbar.vue](file:///c:/study/tracerstudy/resources/js/Pages/Alumni/Components/Navbar.vue) untuk memastikan konsistensi tampilan (UI/UX), tata letak, dan branding resmi UKDW di seluruh modul alumni.
  - Diintegrasikan secara seragam pada 4 halaman utama alumni:
    1. **Dashboard Alumni** ([Dashboard.vue](file:///c:/study/tracerstudy/resources/js/Pages/Alumni/Dashboard.vue))
    2. **Kelola Profil Alumni** ([Index.vue](file:///c:/study/tracerstudy/resources/js/Pages/Alumni/Profil/Index.vue))
    3. **Kuesioner Tracer Study Universitas** ([Kuesioner.vue](file:///c:/study/tracerstudy/resources/js/Pages/Alumni/Kuesioner.vue))
    4. **Kuesioner Khusus Program Studi** ([KuesionerProdi.vue](file:///c:/study/tracerstudy/resources/js/Pages/Alumni/KuesionerProdi.vue))
  - Fitur Navbar: Logo resmi UKDW, indikator badge aktif antar halaman (Dashboard, Profil, Kuesioner Universitas, Kuesioner Prodi), nama/NIM alumni aktif, sub-navigasi mobile/tablet responsif, dan modal konfirmasi SweetAlert2 saat keluar (logout).
- **Dokumentasi Pemetaan Lengkap Instrumen Kuesioner ([DAFTAR_PERTANYAAN_MAPPING.md](file:///c:/study/tracerstudy/DAFTAR_PERTANYAAN_MAPPING.md))**:
  - Menyusun tabel referensi komprehensif yang memetakan seluruh butir pertanyaan Tracer Study Dikti (`F1` s/d `F18`, `F24A`, `F24B`, dll.), identitas alumni, dan kuesioner program studi.
  - Merinci relasi kode pertanyaan, tipe input, komponen Vue pengelola, tabel dan kolom basis data tujuan, hingga alur sinkronisasi dua arah otomatis (`KuesionerSyncService.php`).
- **Pembaruan Dokumentasi Utama**:
  - Memperbarui [README.md](file:///c:/study/tracerstudy/README.md) dengan panduan arsitektur instrumen tracer study, daftar komponen form Vue, dan alur navigasi terpadu.
- **Automated Tests**:
  - Seluruh 47 feature tests PHPUnit lulus 100% (243 assertions).

## [2026-09-19] F11 Jenis Perusahaan, Pemisahan Peran Pekerja vs Wiraswasta (F2G & F5C), dan Export Excel Jawaban Alumni
- **Integrasi F11 Jenis Instansi/Perusahaan (`perusahaan.jenis_perusahaan` & `jenis_perusahaan_lainnya`)**:
  - Menambahkan kolom `jenis_perusahaan` (50) dan `jenis_perusahaan_lainnya` (150) pada tabel `perusahaan` via migrasi `2026_09_19_224000_add_jenis_perusahaan_and_kategori_to_perusahaan_and_biodata.php`.
  - Mengupdate model [Perusahaan.php](file:///c:/study/tracerstudy/app/Models/Perusahaan.php) dan `SimpanProfilController.php`.
  - Menambahkan dropdown pilihan standar Dikti di [FormKarier.vue](file:///c:/study/tracerstudy/resources/js/Pages/Alumni/Profil/Components/FormKarier.vue) dan modal "Tambah Perusahaan Baru": `1 - Instansi pemerintah`, `2 - Organisasi non-profit/LSM`, `3 - Perusahaan swasta`, `4 - Wiraswasta/perusahaan sendiri`, `5 - Lainnya` (dilengkapi input isian teks spesifik), `6 - BUMN/BUMD`, `7 - Institusi/Organisasi Multilateral`.
  - Otomatis disinkronkan ke butir kuesioner `F11` pada tabel `tracer` via [KuesionerSyncService.php](file:///c:/study/tracerstudy/app/Services/Kuesioner/KuesionerSyncService.php).
- **Pemisahan Peran Kerja & Posisi Jabatan: Pekerja vs Wiraswasta (`F2G` vs `F5C`)**:
  - Menambahkan kolom `kategori_pekerjaan` dan `posisi_wiraswasta` pada tabel `biodata`.
  - **Pemisahan Status Kerja & Status Studi Lanjut (Independen)**:
    - Status Pekerjaan Utama dibagi menjadi 2 opsi saling eksklusif (*Single Choice*): **🏢 Pekerja / Karyawan** vs **🚀 Wirausaha / Founder**.
    - Ditambahkan opsi toggle: **🎓 "Sedang Melanjutkan Studi (S1 / S2 / S3 / Profesi / Spesialis)"**.
    - Mengaktifkan atau menonaktifkan status studi lanjut **tidak lagi mereset ataupun menyembunyikan data perusahaan, jabatan, dan atasan langsung**.
  - **Conditional Rendering Detail Perusahaan (*Progressive Disclosure*)**:
    - Kolom detail perusahaan (*Negara/Provinsi/Kabupaten, Jenis Perusahaan F11, Skala F2H/F5D, Alamat Kantor, dan Kode Pos*) **hanya ditampilkan jika nama perusahaan sudah dipilih atau ditambahkan**.
    - Jika perusahaan belum dipilih, form menampilkan tampilan yang bersih dengan pesan panduan pencarian perusahaan.
  - Pada form profil [FormKarier.vue](file:///c:/study/tracerstudy/resources/js/Pages/Alumni/Profil/Components/FormKarier.vue):
    - Menyediakan pemilih kategori peran kerja: **🏢 Pekerja / Karyawan / Profesional** vs **🚀 Wirausaha / Founder / Startup / Usaha Mandiri**.
    - Jika memilih **Pekerja**: Menampilkan dropdown posisi jabatan struktural `F2G` (`1 - Direksi`, `2 - Top Manager`, `3 - Middle Manager`, `4 - Low Manager`, `5 - Supervisor`, `6 - Staff`) dan mengosongkan `F5C`.
    - Jika memilih **Wiraswasta**: Menampilkan input teks posisi/jabatan wirausaha `F5C` dengan tombol pilihan cepat (*Owner, Founder, Co-Founder, Direktur Utama, Pengelola Usaha, Freelancer*), otomatis mengisi `F2G` sebagai level pimpinan, dan otomatis mengisi data atasan dengan data diri alumni.
- **Fitur Download Excel / CSV Jawaban Kuesioner per Alumni untuk Superadmin**:
  - Menambahkan method `exportExcel($id)` pada [DetailAlumniSuperAdminController.php](file:///c:/study/tracerstudy/app/Http/Controllers/SuperAdmin/KelolaAlumni/DetailAlumniSuperAdminController.php) dan rute `GET /superadmin/alumni/{id}/export-excel`.
  - Menghasilkan file Excel/CSV stream terformat rapi dengan UTF-8 BOM (`\xEF\xBB\xBF`) berisi kolom: `No`, `Kategori / Section`, `Kode Pertanyaan`, `Pertanyaan`, `Tipe Pertanyaan`, `Status Wajib`, `Status Jawaban`, `Jawaban Alumni` (mencakup kuesioner universitas dan kuesioner khusus program studi).
  - Menambahkan tombol aksi **Download Excel Jawaban** berwarna kuning khas UKDW di header halaman detail alumni [Show.vue](file:///c:/study/tracerstudy/resources/js/Pages/SuperAdmin/Alumni/Show.vue).
- **Automated Tests**:
  - Seluruh 47 feature tests PHPUnit lulus 100% (243 assertions).

## [2026-09-19] Anti-Duplikasi Jawaban Tracer, Perbaikan Unique Constraint Atasan, & Searchable Negara
- **Eliminasi Duplikasi Jawaban Tracer & Constraint Unik (`tracer_biodata_question_unique`)**:
  - Membuat migrasi `2026_09_19_221000_add_unique_constraints_to_tracer_table.php` yang membersihkan seluruh potensi baris ganda dan menambahkan indeks unik komposit `['biodata_id', 'question_id']` serta `['biodata_id', 'kode_pertanyaan']` pada tabel `tracer`.
  - Mengupdate [SimpanJawabanController.php](file:///c:/study/tracerstudy/app/Http/Controllers/Alumni/Kuesioner/SimpanJawabanController.php) dan [KuesionerSyncService.php](file:///c:/study/tracerstudy/app/Services/Kuesioner/KuesionerSyncService.php) dengan mekanisme *clean-before-update* dan `updateOrCreate` berbasis `(biodata_id, question_id)`.
  - Ketika alumni menjawab pertanyaan yang sudah pernah dijawab, sistem secara otomatis melakukan pembaruan (*in-place update*) pada baris yang sama tanpa pernah menduplikasi data di basis data.
- **Penanganan Unique Constraint Atasan (`atasan.atasan_email_unique`)**:
  - Menghapus indeks `unique` pada kolom `email` di tabel `atasan` via migrasi `2026_09_19_215000_remove_unique_from_atasan_email.php` dan memperbarui migrasi dasar `create_atasans_table.php`.
  - Mengupdate logika `SimpanJawabanController.php` dan `SimpanProfilController.php` agar pembaruan data atasan tidak lagi memicu error duplicate key MySQL (1062) ketika email atasan digunakan bersama atau diisi data dummy.
- **Integrasi Master Data Negara Dunia (`ref_negara` & `daftar_negara_dunia.csv`)**:
  - Membuat tabel master `ref_negara` (`id`, `nama_negara`, `ibu_kota`, `kode_iso2`, `benua`, `timestamps`) via migrasi `2026_09_19_220000_create_ref_negara_table.php`.
  - Membuat model [RefNegara.php](file:///c:/study/tracerstudy/app/Models/RefNegara.php) dan seeder `RefNegaraSeeder.php` yang membaca data 193 negara dunia secara otomatis dari `daftar_negara_dunia.csv`.
  - Mengalirkan master data negara ke [ProfilController.php](file:///c:/study/tracerstudy/app/Http/Controllers/Alumni/Profil/ProfilController.php) untuk kebutuhan dropdown & autocomplete form profil alumni.
  - **Searchable Dropdown Negara (Sesuai Template Provinsi & Kabupaten)** di [FormKarier.vue](file:///c:/study/tracerstudy/resources/js/Pages/Alumni/Profil/Components/FormKarier.vue):
    - Komponen pencarian negara interaktif dengan ikon pencarian, filter real-time, highlight negara aktif, kode ISO 2, dan benua.
    - Opsi negara untuk **Luar Negeri** mengecualikan `Indonesia` sehingga negara asal tidak bisa dipilih sebagai negara asing.
    - Pilihan negara dibatasi secara ketat (*strict selection*) hanya pada 193 negara resmi yang terdaftar di basis data master `ref_negara` (tidak dapat menginput negara sembarangan).
    - Modal SweetAlert2 "Tambah Perusahaan Baru" juga menggunakan dropdown negara resmi yang mengecualikan Indonesia dan divalidasi ketat di backend `SimpanProfilController.php`.
- **Dukungan Perusahaan Dalam Negeri vs Luar Negeri (`perusahaan.jenis_lokasi` & `perusahaan.negara`)**:
  - Menambahkan kolom `jenis_lokasi` (`Dalam Negeri` / `Luar Negeri`) dan `negara` pada tabel `perusahaan` via migrasi `2026_09_19_214000_add_jenis_lokasi_and_negara_to_perusahaan_table.php`.
  - Memperbarui model [Perusahaan.php](file:///c:/study/tracerstudy/app/Models/Perusahaan.php) dan seeder `PerusahaanSeeder.php` dengan data contoh perusahaan internasional (Google Singapore, Grab Singapore, Rakuten Tokyo).
  - Pada form profil [FormKarier.vue](file:///c:/study/tracerstudy/resources/js/Pages/Alumni/Profil/Components/FormKarier.vue):
    - Menyediakan tombol seleksi interaktif `🇮🇩 Dalam Negeri` dan `✈️ Luar Negeri`.
    - Jika memilih `Luar Negeri`, input Nama Negara (`company_negara`) ditampilkan, sedangkan dropdown Provinsi dan Kabupaten/Kota dinonaktifkan/disembunyikan serta dikosongkan (`null`).
    - Modal popup "Tambah Perusahaan Baru" (SweetAlert2) mendukung pemilihan lokasi Dalam/Luar Negeri dan input nama negara secara langsung.
  - Memperbarui [KuesionerSyncService.php](file:///c:/study/tracerstudy/app/Services/Kuesioner/KuesionerSyncService.php) agar alumni yang bekerja di luar negeri menghasilkan alamat perusahaan berformat nama negara, dan mengisi `F5a1`/`F5a2` secara adaptif.
- **Restrukturisasi Urutan Tab Profil Alumni (UX Streamlined Flow)**:
  - Mengubah urutan tab pada [Index.vue](file:///c:/study/tracerstudy/resources/js/Pages/Alumni/Profil/Index.vue) agar alumni langsung diarahkan ke riwayat pekerjaan setelah mengisi data diri:
    1. **Identitas & Alamat** (`pribadi`)
    2. **Karier & Pekerjaan** (`karier`)
    3. **Akademik & Yudisium** (`akademik`)
    4. **Data Orang Tua** (`orangtua`)
- **Penyempurnaan UI Format Angka Ribuan & Pembersihan Emoticon**:
  - Menghilangkan emoticon `💰` pada teks pratinjau gaji di form karier dan kuesioner.
  - Menambahkan panduan instruksi format ribuan yang jelas dan mudah dipahami.
- **Automated Tests**:
  - Seluruh 44 feature tests PHPUnit lulus 100% (224 assertions).

## [2026-09-19] Integrasi RefFakultas, Pengayaan Kolom Biodata, Take Home Pay, Penguncian NIK, dan Auto-Fill Atasan Owner
- **Master Fakultas (`ref_fakultas`) & Relasi ke Program Studi (`prodi`)**:
  - Membuat tabel `ref_fakultas` (`id`, `kode_fakultas`, `nama_fakultas`, `timestamps`).
  - Menambahkan kolom `fakultas_id` (FK ke `ref_fakultas`) pada tabel `prodi`.
  - Membuat model [RefFakultas.php](file:///c:/study/tracerstudy/app/Models/RefFakultas.php) dan memperbarui relasi di [Prodi.php](file:///c:/study/tracerstudy/app/Models/Prodi.php).
  - Membuat seeder `RefFakultasSeeder.php` yang mengisi 7 fakultas resmi UKDW dan memetakan `fakultas_id` pada seluruh `prodi` secara otomatis berdasarkan digit pertama `kode_prodi` (Kode 1: Bisnis, 2: Arsitektur & Desain, 3: Theologi, 4: Bioteknologi, 6: Kedokteran, 7: FTI, 8: FKH).
- **Pengayaan Kolom Tabel `biodata` & Relasi Langsung**:
  - Menambahkan kolom `tempat_lahir`, `tanggal_lahir`, `jenis_kelamin`, `golongan_darah`, `warga_negara`, `nisn`, `gaji`, serta foreign key `orang_tua_id` dan `yudisium_id` pada tabel `biodata`.
  - Memperbarui model [Biodata.php](file:///c:/study/tracerstudy/app/Models/Biodata.php) dengan deklarasi `$fillable` lengkap dan relasi `orangTua()`, `yudisium()`, serta `prodi.fakultas`.
- **Penguncian NIK Alumni (Read-Only)**:
  - NIK alumni diatur permanen (read-only/disabled) pada form frontend [FormPribadi.vue](file:///c:/study/tracerstudy/resources/js/Pages/Alumni/Profil/Components/FormPribadi.vue) dan dilindungi dari perubahan di backend [SimpanProfilController.php](file:///c:/study/tracerstudy/app/Http/Controllers/Alumni/Profil/SimpanProfilController.php).
- **Perluasan Posisi Jabatan & Auto-Fill Data Atasan Owner**:
  - Menambahkan opsi jabatan baru di [FormKarier.vue](file:///c:/study/tracerstudy/resources/js/Pages/Alumni/Profil/Components/FormKarier.vue): `Owner`, `Founder`, `Wiraswasta / Wirausaha`.
  - Jika alumni memilih posisi `Owner` / `Founder` / `Wiraswasta`, kolom Data Atasan Langsung (`nama_atasan`, `email_atasan`, `telepon_atasan`) otomatis terisi dengan data diri alumni (nama, email, nomor telepon) baik di sisi antarmuka (Vue watcher) maupun di backend Laravel.
- **Penyederhanaan Take Home Pay (`F505`) & Eliminasi Redundansi Kuesioner Section 4**:
  - Menyediakan input single Take Home Pay bulanan (`gaji`) pada form profil [FormKarier.vue](file:///c:/study/tracerstudy/resources/js/Pages/Alumni/Profil/Components/FormKarier.vue) dan kuesioner [KartuPertanyaan.vue](file:///c:/study/tracerstudy/resources/js/Pages/Alumni/Components/Kuesioner/KartuPertanyaan.vue).
  - Format ribuan (`.000`) muncul langsung di dalam kotak input teks dan dapat diedit fleksibel, menghapus badge terpisah `.000` dan tombol preset pilihan cepat agar tampilan bersih.
  - Memperbarui perhitungan pratinjau `💰 Terbaca: Rp ... / bulan` secara real-time saat alumni mulai mengetik angka tanpa harus menunggu form disimpan.
  - Memperbarui [KuesionerSyncService.php](file:///c:/study/tracerstudy/app/Services/Kuesioner/KuesionerSyncService.php) agar Take Home Pay dan seluruh data pekerjaan/atasan di Section 4 (`F5a1`, `F5a2`, `F510`, `F5B`, `F5C`, `F5D`, `F2E`, `F2E1`, `F2E2`, `F2E3`, `F2F`, `F2G`, `F2H`) otomatis tersinkronisasi dari profil biodata ke tabel `tracer`.
- **Automated Tests**:
  - Membuat feature test [AlumniProfileEnhancementTest.php](file:///c:/study/tracerstudy/tests/Feature/AlumniProfileEnhancementTest.php).
  - Seluruh 43 automated feature tests di PHPUnit lulus 100% (222 assertions).
- **Singularisasi Nama Tabel Basis Data Custom (Tanpa Akhiran `-s`)**:
  - Seluruh 27 berkas migrasi database diselaraskan menggunakan nama tabel tunggal/singular (Bahasa Indonesia & domain), tanpa akhiran `-s`, kecuali tabel bawaan Laravel framework (`users`, `cache`, `sessions`, `jobs`):
    - `biodatas` $\rightarrow$ `biodata`
    - `companies` $\rightarrow$ `perusahaan`
    - `provinces` $\rightarrow$ `propinsi`
    - `kabupatens` $\rightarrow$ `kabupaten`
    - `atasans` $\rightarrow$ `atasan`
    - `tracers` $\rightarrow$ `tracer`
    - `kuesioners` $\rightarrow$ `kuesioner`
    - `kelompok_pertanyaans` $\rightarrow$ `kelompok_pertanyaan`
    - `umps` $\rightarrow$ `ump`
    - `data_akademiks` $\rightarrow$ `data_akademik`
    - `data_orang_tuas` $\rightarrow$ `data_orang_tua`
    - `yudisiums` $\rightarrow$ `yudisium`
    - `prodis` $\rightarrow$ `prodi`
    - `prodi_question_sections` $\rightarrow$ `prodi_question_section`
    - `prodi_questions` $\rightarrow$ `prodi_question`
    - `prodi_question_options` $\rightarrow$ `prodi_question_option`
    - `prodi_responses` $\rightarrow$ `prodi_response`
    - `question_mappings` & database VIEW `v_question_mappings`
  - Seluruh model Eloquent dikonfigurasi dengan deklarasi eksplisit `protected $table = 'nama_tabel_singular';`.
  - Seluruh Foreign Key diselaraskan (`perusahaan_id`, `propinsi_id`, `kabupaten_id`, `biodata_id`, `kuesioner_id`, `kelompok_pertanyaan_id`, `prodi_question_section_id`, `prodi_question_id`, dll.).
- **Refactoring Penuh `Company` $\rightarrow$ `Perusahaan`**:
  - Membuat model [Perusahaan.php](file:///c:/study/tracerstudy/app/Models/Perusahaan.php) dan menghapus `Company.php` (tanpa menggunakan class extend/wrapper legacy).
  - Mengganti seluruh seeder (`PerusahaanSeeder.php`), controller (`SimpanProfilController`, `ProfilController`, `DetailAlumniSuperAdminController`, `DaftarAlumniController`, `DetailAlumniController`, `SimpanJawabanController`), services (`KuesionerSyncService`), relasi, dan tests.
- **Refactoring Penuh `Province` $\rightarrow$ `Propinsi`**:
  - Membuat model [Propinsi.php](file:///c:/study/tracerstudy/app/Models/Propinsi.php) dan menghapus `Province.php`.
  - Memperbarui relasi `Kabupaten`, `Perusahaan`, `Ump`, dan `Biodata`.
  - Menyediakan accessor compatibility (`province_id` $\rightarrow$ `propinsi_id`) untuk keandalan frontend.
- **Pembersihan Bersih `Alumni` $\rightarrow$ `Biodata`**:
  - Menghapus berkas `database/seeders/AlumniSeeder.php` yang redundan.
  - Memastikan seluruh controller, middleware (`HandleInertiaRequests`), services, seeder, dan test suite merujuk ke entitas `Biodata`.
- **Pengujian & Verifikasi Kualitas Kode**:
  - `php artisan migrate:fresh --seed` berjalan sukses 100% tanpa error.
  - Seluruh 38 skenario automated unit & feature tests di PHPUnit lulus 100% (203 assertions).
  - Standarisasi format kode PHP diformat dengan Laravel Pint (`vendor/bin/pint --format agent`).
  - Pembuatan bundle asset frontend Vite (`npm run build`) sukses tanpa error.
  - Pembaruan dokumen [ERD.md](file:///c:/study/tracerstudy/ERD.md) dan [README.md](file:///c:/study/tracerstudy/README.md).

## [2026-09-16] Refactoring Penuh Entitas Alumni Menjadi Biodata & Penyesuaian Kolom Seeding
- **Refactor Entitas `Alumni` $\rightarrow$ `Biodata` Tanpa Kehilangan Data & Foreign Key**:
  - Model `App\Models\Alumni` direfaktor menjadi `App\Models\Biodata` ([Biodata.php](file:///c:/study/tracerstudy/app/Models/Biodata.php)).
  - Tabel `alumnis` direfaktor menjadi `biodatas` yang memuat seluruh 28 kolom profil target (`id`, `user_id`, `nim`, `tahun_lulus`, `kode_prodi`, `nama`, `nomor_telepon`, `email`, `alamat`, `kabupaten_id`, `provinsi_id`, `kelurahan`, `kecamatan`, `kode_pos`, `agama`, `email_pribadi`, `nik`, `no_kk`, `no_bpjs`, `npwp`, `instagram_url`, `facebook_url`, `linkedin_url`, `linkedin_username`, `expert`, `minat`) serta seluruh kolom karier/pekerjaan existing (`company_id`, `atasan_id`, `posisi_jabatan`, `jenis_pekerjaan`, `zipcode`).
  - Seluruh relasi Foreign Key diselaraskan: `tracers.biodata_id` dan `prodi_responses.biodata_id`.
  - Alias `$user->alumni()` tetap disediakan pada [User.php](file:///c:/study/tracerstudy/app/Models/User.php) untuk backward compatibility.
- **Penyelarasan Kolom Seeding & Database Migrations**:
  - `DataAkademikSeeder.php`: diselaraskan dengan kolom `ip_kumulatif`, `status_mahasiswa = 'AR'`, kolom asal sekolah (`asal_sekolah`, `alamat_asal_sekolah`, `kota_kabupaten_asal_sekolah`, `provinsi_asal_sekolah`, `jurusan_asal_sekolah`), dan penghapusan `npwp` (karena dipindahkan ke `biodatas`).
  - `YudisiumSeeder.php`: diselaraskan dengan kolom `tahun_akademik_lulus` dan `tahun_lulus`.
  - `BiodataSeeder.php`: dibuat untuk melakukan seeding lengkap ke tabel `biodatas` mencakup seluruh data 28 field profil dan relasi ke perusahaan serta atasan.
  - `DatabaseSeeder.php` dan `QuestionMappingSeeder.php`: diselaraskan menggunakan `BiodataSeeder` dan `Biodata::all()`.
  - Database VIEW `v_question_mappings` diselaraskan memetakan butir pertanyaan ke tabel `biodatas` dan `yudisiums`.
- **Dokumentasi Terpusat Entity Relationship Diagram (ERD)**:
  - Membuat file terdedikasi [ERD.md](file:///c:/study/tracerstudy/ERD.md) yang memuat visualisasi diagram Mermaid `erDiagram`, kamus data komprehensif (*data dictionary*) untuk seluruh entitas (Autentikasi, Wilayah Master, Biodata/Akademik, Kuesioner Universitas, Kuesioner Prodi, Database Views), dan tabel matriks kardinalitas relasi antar entitas.
- **Verifikasi & Pengujian Otomatis**:
  - Eksekusi migrasi & seeding ulang `php artisan migrate:fresh --seed` berjalan sukses 100% tanpa error.
  - Seluruh 38 skenario automated unit & feature tests di `vendor/bin/phpunit` lulus 100% (203 assertions).
  - Standarisasi format kode PHP diperbarui via Laravel Pint (`vendor/bin/pint --format agent`).

## [2026-09-16] Penyelarasan Tipe Data Gaji F505 (multiple_number) & Standarisasi Template Input Kuesioner Lengkap di Vue
- **Standarisasi Tipe Data Pertanyaan Gaji / Take Home Pay (`F505`)**:
  - Pertanyaan `F505` (*"Berapa rata-rata pendapatan anda per bulan ? (take home pay)?"*) pada [RefSubpertanyaan2021Seeder.php](file:///c:/study/tracerstudy/database/seeders/RefSubpertanyaan2021Seeder.php) diselaraskan ke tipe `multiple_number`.
  - Menambahkan 3 butir rincian opsi gaji pada [RefSubpertanyaanDetilSeeder.php](file:///c:/study/tracerstudy/database/seeders/RefSubpertanyaanDetilSeeder.php):
    1. `F5051` (Urutan 1): *"Dari Pekerjaan Utama"*
    2. `F5052` (Urutan 2): *"Dari Lembur dan Tips"*
    3. `F5053` (Urutan 3): *"Dari Pekerjaan Lainnya"*
  - Backend [SimpanJawabanController.php](file:///c:/study/tracerstudy/app/Http/Controllers/Alumni/Kuesioner/SimpanJawabanController.php) dan [KuesionerController.php](file:///c:/study/tracerstudy/app/Http/Controllers/Alumni/Kuesioner/KuesionerController.php) secara otomatis menghitung total Take Home Pay, mengonversi satuan ribuan (`.000`), menyimpan struktur JSON ke kolom `answer_json`, dan menormalisasi angka saat formulir dimuat kembali.
- **Penyelarasan Template Input Form Kuesioner Lengkap di Vue ([KartuPertanyaan.vue](file:///c:/study/tracerstudy/resources/js/Pages/Alumni/Components/Kuesioner/KartuPertanyaan.vue))**:
  - Melengkapi seluruh template input form kuesioner siap pakai untuk semua tipe data Google Forms:
    1. `text`: Isian singkat 1 baris.
    2. `textarea`: Paragraf / isian multibaris.
    3. `number`: Isian angka (termasuk layout 2-kolom berdampingan untuk F6 & F7).
    4. `single_choice` / `radio`: Pilihan ganda radio button klasik (dengan dukungan opsi "Lainnya").
    5. `radio_input`: Pilihan ganda radio dengan isian angka langsung pada opsi (seperti F3 & F5).
    6. `radio_text`: Pilihan ganda radio dengan isian teks tambahan.
    7. `multiple_choice` / `checkbox`: Kotak centang pilihan majemuk.
    8. `dropdown`: Menu pilihan tarik-turun (`<select>`).
    9. `searchable_select`: Dropdown pencarian interaktif untuk master data besar.
    10. `rating_5`: Skala linier rating 1 s/d 5 (Sangat Rendah s/d Sangat Tinggi).
    11. `multiple_number`: Isian rincian gaji / angka majemuk dengan akhiran `.000` dan kalkulasi total otomatis.
    12. `date`: Input pemilihan tanggal.
    13. `time`: Input pemilihan waktu/jam.
    14. `file`: Area unggah berkas dengan feedback visual nama file.
- **Komentar Kode & Dokumentasi Terstruktur**:
  - Setiap konstanta, fungsi, template div HTML, dan prop Vue telah dilengkapi komentar penjelasan terstruktur dalam Bahasa Indonesia.
  - Seluruh 38 automated test cases di `tests/Feature` berjalan sukses 100% (206 assertions).

## [2026-09-15] Refactoring Penuh Model & Entitas Kuesioner (Penghapusan Model Wrapper Legacy)
- **Migrasi Total Seluruh Model & Referensi Kode**:
  - Seluruh wrapper model legacy (`Question`, `Questionnaire`, `QuestionSection`, `QuestionOption`, `Response`) serta legacy seeder (`QuestionSeeder`, `QuestionnaireSeeder`, `QuestionSectionSeeder`, `QuestionOptionSeeder`) telah dihapus secara permanen dari basis kode.
  - Seluruh Controller, Service, Relasi Eloquent, Inertia Props, Komponen Vue, dan Automated Feature Tests kini 100% menggunakan model dan tabel kanonikal:
    1. `Kuesioner` (`kuesioners`): Header kuesioner tracer study universitas.
    2. `KelompokPertanyaan` (`kelompok_pertanyaans`): Bagian/seksi kelompok pertanyaan.
    3. `RefSubpertanyaan2021` (`ref_subpertanyaan2021`): Butir pertanyaan tracer study 2021 (`kode_pertanyaan`, `subpertanyaan`, `wajib`, `order`, `type`).
    4. `RefSubpertanyaanDetil` (`ref_subpertanyaan_detil`): Pilihan opsi jawaban & target alur lompatan branching (`kode_opsi`, `option_text`, `jump_to`, `order`).
    5. `Tracer` (`tracers`): Data jawaban kuesioner alumni (`alumni_id`, `question_id`, `nim`, `kelompok`, `kode_pertanyaan`, `subpertanyaan`, `answer`, `answer_json`, `tahun_lulus`).
- **Pembersihan Model & Pengayaan Dokumentasi Kode**:
  - Model `Kuesioner`, `KelompokPertanyaan`, `RefSubpertanyaan2021`, `RefSubpertanyaanDetil`, dan `Tracer` dibersihkan dari query builder wrapper legacy dan mutator alias yang tidak lagi diperlukan.
  - Setiap konstanta, method, fungsi controller/service, props Vue, dan elemen HTML telah dilengkapi PHPDoc/JSDoc dan komentar penjelasan terstruktur dalam Bahasa Indonesia.
  - Seluruh 38 automated test cases di `tests/Feature` berjalan sukses 100% (206 assertions).

## [2026-09-15] Restrukturisasi Skema Kuesioner Tracer Study 2021, Penamaan Tabel Bahasa Indonesia, Tabel Tracers, Pemisahan BIO_TTL, dan Sinkronisasi Otomatis Profil
- **Penamaan Tabel, Model, Migrasi, dan Seeder ke Bahasa Indonesia**:
  - **Standarisasi Nama Tabel Database**:
    1. `kuesioners`: Menggantikan `questionnaires`, dikelola oleh model `App\Models\Kuesioner` dan seeder `KuesionerSeeder`.
    2. `kelompok_pertanyaans`: Menggantikan `question_sections`, dikelola oleh model `App\Models\KelompokPertanyaan` dan seeder `KelompokPertanyaanSeeder`.
    3. `ref_subpertanyaan2021`: Menggantikan `questions`, dikelola oleh model `App\Models\RefSubpertanyaan2021` dan seeder `RefSubpertanyaan2021Seeder`.
    4. `ref_subpertanyaan_detil`: Menggantikan `question_options`, dikelola oleh model `App\Models\RefSubpertanyaanDetil` dan seeder `RefSubpertanyaanDetilSeeder`.
    5. `tracers`: Menggantikan `responses`, dikelola oleh model `App\Models\Tracer`.
    6. `v_question_mappings`: Database VIEW yang menggantikan tabel `question_mappings`, dikelola oleh model `App\Models\QuestionMapping` dan seeder `QuestionMappingSeeder`.
- **Rekonstruksi Tabel Jawaban `tracers` & Penyelarasan Variabel Kolom**:
  - Tabel `tracers` memuat kolom terstandarisasi:
    - `id`: Primary key (BIGINT)
    - `alumni_id`: Foreign key ke tabel `alumnis`
    - `question_id`: Foreign key ke tabel `ref_subpertanyaan2021`
    - `nim`: Nomor Induk Mahasiswa alumni
    - `kelompok`: Kode kelompok instrumen kuesioner (`char(3)`), seperti `'BIO'`, `'F1'`, `'F2'`, `'F3'`, `'F4'`, `'F5'`, `'F6'`, `'F7'`, `'F8'`, `'F10'`, `'F11'`, `'F12'`, `'F14'`, `'F15'`, `'F16'`, `'F17'`, `'F18'` (bukan ID foreign key)
    - `kode_pertanyaan`: Kode unik pertanyaan (`char(20)`)
    - `subpertanyaan`: Teks pertanyaan yang diselaraskan dengan tabel induk
    - `answer`: Teks jawaban utama alumni (menggantikan `answer_text`)
    - `answer_json`: Struktur data array/JSON untuk jawaban majemuk, matriks, dan checkbox
    - `keterangan`: Keterangan subpertanyaan atau judul kelompok (`varchar(100)`)
    - `tahun_lulus`: Tahun kelulusan mahasiswa dari data akademik alumni
  - Seluruh variabel pemrosesan di `SimpanJawabanController.php` dan `KuesionerSyncService.php` disamakan persis dengan nama-nama kolom database ini.
- **Pemisahan Biodata `BIO_TTL` Menjadi Dua Butir Independen**:
  - `BIO_TTL` dipecah menjadi 2 butir pertanyaan di tabel `ref_subpertanyaan2021`:
    1. `BIO_TEMPAT_LAHIR`: Ditarik otomatis dari `data_akademiks.tempat_lahir`.
    2. `BIO_TANGGAL_LAHIR`: Ditarik otomatis dari `data_akademiks.tanggal_lahir`.
  - Keduanya terdaftar pada database VIEW `v_question_mappings` dan disinkronkan secara otomatis oleh `KuesionerSyncService`.
- **Auto-Pull Data Profil Alumni / Perusahaan / Atasan ke Kuesioner**:
  - Menghilangkan redundansi pengisian dan potensi inkonsistensi data dengan menarik langsung profil ke butir kuesioner:
    - `F2E` dan `F5B`: Otomatis ditarik dari nama perusahaan alumni (`companies.nama_perusahaan`).
    - `F2E1`, `F2E2`, `F2E3`: Otomatis ditarik dari data atasan langsung (`atasans.nama`, `telepon`, `email`).
    - `F2F` dan `F510`: Otomatis ditarik dari alamat lengkap perusahaan (`alamat`, `kabupaten`, `provinsi`, `kode pos`).
    - `F2G` dan `F5C`: Otomatis ditarik dari posisi/jabatan alumni (`alumnis.posisi_jabatan`).
    - `F2H` dan `F5D`: Otomatis ditarik dari skala tempat kerja (`companies.skala`).
- **Antarmuka Matriks & Branching Logic Kuesioner 2021**:
  - `TabelF2.vue`: Komponen matriks 7 baris x 5 opsi skala penilaian untuk metode pembelajaran `F21` s.d. `F27`.
  - `TabelF17.vue`: Komponen matriks dual-side komparasi 7 baris x 5 opsi untuk kompetensi `F17a1`..`F17a7` (saat lulus) vs `F17b1`..`F17b7` (saat ini).
  - Alur percabangan `F504`:
    - Opsi "Ya" (`jump_to`: `'F502'`) $\rightarrow$ Membuka `F502` (waktu dapat kerja), `F505` (gaji per bulan), dan `F505A` (kesesuaian UMR).
    - Opsi "Tidak" (`jump_to`: `'F506'`) $\rightarrow$ Membuka `F506` (waktu mencari kerja).
- **Pembersihan Migrasi & Eksekusi Seeder**:
  - Menghapus migrasi usang (`create_responses_table.php`, `create_questions_table.php`, dll.) sehingga hanya tersisa migrasi `create` yang bersih dan terurut.
  - `DatabaseSeeder.php` memanggil langsung `KuesionerSeeder`, `KelompokPertanyaanSeeder`, `RefSubpertanyaan2021Seeder` (68 pertanyaan), `RefSubpertanyaanDetilSeeder` (185 opsi), `QuestionMappingSeeder`, dan `ProdiQuestionnaireSeeder`.
  - Pengujian `php artisan test --compact` berhasil dengan **38 tests passed (206 assertions)**.

- **Pemisahan dan Rekonstruksi Kuesioner Khusus Program Studi (Database & Model Terpisah)**:
  - **Penghapusan Kolom `prodi_id` pada Kuesioner Utama**: Menghapus kolom `prodi_id` dari tabel kuesioner umum universitas (`questions`) melalui migrasi `2026_09_13_090001_remove_prodi_id_from_questions_table.php`, serta membersihkan seluruh elemen terkait di antarmuka Super Admin (`QuestionModal.vue`, `QuestionCard.vue`, `SuperAdmin/Pertanyaan/Index.vue`). Kuesioner universitas kini murni terpusat untuk kuesioner tingkat universitas.
  - **Arsitektur Tabel Kuesioner Prodi yang Mandiri & Hierarkis**:
    1. `prodi_question_sections`: Menyimpan bagian/seksi kuesioner berbasis `prodi_id` (`id`, `prodi_id`, `title`, `description`, `order`).
    2. `prodi_questions`: Menyimpan butir pertanyaan khusus prodi dengan tipe input dinamis (`single_choice`, `multiple_choice`, `text`, `number`, `date`, `rating_5`, `radio_input`, dll.).
    3. `prodi_question_options`: Menyimpan opsi pilihan jawaban butir pertanyaan khusus prodi.
    4. `prodi_responses`: Menyimpan respon dan jawaban alumni khusus kuesioner program studi mereka (`answer_text` dan `answer_json`).
  - **Seeding Kuesioner Prodi Sistem Informasi & Prodi Lain**:
    - Menjalankan `ProdiQuestionnaireSeeder` dengan 9 Section dan 52 Butir Pertanyaan Evaluasi Komprehensif khusus Program Studi Sistem Informasi (Mulai dari data diri, konsentrasi peminatan, profil lulusan, kurikulum, fasilitas lab, hingga jejaring alumni).
    - Memindahkan data butir kuesioner prodi lama (seperti Filsafat Keilahian) ke struktur tabel kuesioner prodi baru.

- **Pengambilan Otomatis (Auto-Prefill & Auto-Save) Data Akademik pada Kuesioner Prodi**:
  - **3 Pertanyaan Identitas Utama (`Nama`, `NIM`, `Tahun Kelulusan`)**:
    - Pertanyaan kode `PSI-1-01` (Nama), `PSI-1-02` (NIM), dan `PSI-1-03` (Tahun Kelulusan) pada Kuesioner Program Studi kini secara cerdas terhubung dan diambil otomatis dari pangkalan Data Akademik (`data_akademiks`) alumni.
  - **Tampilan Antarmuka Khusus di Frontend Alumni (`KuesionerProdi.vue`)**:
    - Ditampilkan dalam format input khusus `readonly` dengan badge hijau resmi: *"✓ Data Akademik - Kolom ini diambil secara otomatis dari pangkalan Data Akademik resmi Anda"*, sehingga alumni tidak perlu mengetik ulang data yang sudah tercatat di sistem.
  - **Sinkronisasi Otomatis di Backend (`KuesionerProdiController.php`)**:
    - Method `tampilkanKuesionerProdi()` secara otomatis memetakan data akademik alumni ke `$initialAnswers` dan langsung menyimpannya ke tabel `prodi_responses` (`updateOrCreate`).
    - Method `simpanJawaban()` menyertakan fallback otomatis untuk menjamin bahwa data identitas akademik tetap tersimpan utuh meskipun input dikirimkan kosong.

- **Harmonisasi Modul & Antarmuka Admin Prodi (Disetarakan dengan Super Admin)**:
  - **Dashboard Admin Prodi (`/prodi/dashboard`)**:
    - Didesain ulang menyerupai `/superadmin/dashboard` dengan header hijau solid resmi UKDW `#0D542B`.
    - 4 kartu metrik KPI elegan berlatar putih bersih (Total Alumni Prodi, Sudah Mengisi Tracer Univ, Sudah Mengisi Kuesioner Prodi, Profil Lengkap).
    - 2 modul navigasi cepat (Kelola Kuesioner Prodi & Direktori Alumni Prodi).
    - Tabel ringkasan 5 aktivitas alumni terbaru dengan badge status tracer universitas dan kuesioner prodi.
  - **Kelola Section Prodi (`/prodi/sections`)**:
    - Controller baru `KelolaSectionProdiController.php` dengan operasi CRUD lengkap, pencarian, dan fitur reorder posisi urutan section (naik/turun).
    - Halaman view `AdminProdi/Section/Index.vue` dengan header hijau UKDW, modal tambah/edit section, dan konfirmasi SweetAlert2.
  - **Kelola Pertanyaan Prodi (`/prodi/pertanyaan`)**:
    - Pembersihan total seluruh ikon 3D dan emoji (📁, 🗑️, ✎, ❓, ✕), digantikan dengan tombol teks elegan (`Edit`, `Hapus`, `+ Tambah Opsi`) dan badge rapi.
  - **Direktori Mahasiswa & Alumni Prodi (`/prodi/alumni`)**:
    - Didesain ulang menyerupai `/superadmin/alumni` dengan filter pencarian, filter tahun kelulusan, filter status tracer universitas, dan kuesioner prodi.
    - Tombol aksi "Lihat Detail" mengarah ke halaman audit alumni `/prodi/alumni/{id}`.
  - **Detail Alumni Prodi (`/prodi/alumni/{id}`)**:
    - Method `DaftarAlumniProdiController::show()` dengan proteksi otorisasi ketat berbasis `prodi_id`.
    - Halaman `AdminProdi/Alumni/Show.vue` dengan 3 Tab:
      1. Tab 1: Kuesioner Khusus Prodi (filter per-section, capaian pengisian, jawaban rinci).
      2. Tab 2: Kuesioner Tracer Universitas (filter section kuesioner universitas).
      3. Tab 3: Detail Profil Mahasiswa (Biodata Pribadi, Kontak, Data Akademik, Data Orang Tua, Pekerjaan & Perusahaan).

- **Penambahan Tab Audit Kuesioner Prodi pada Detail Alumni Super Admin (`/superadmin/alumni/{id}`)**:
  - `DetailAlumniSuperAdminController.php` kini memuat relasi kuesioner prodi beserta jawaban alumni dari tabel `prodi_responses`.
  - `SuperAdmin/Alumni/Show.vue` menampilkan metrik 3-kolom pada header (Profil, Kuesioner Universitas, Kuesioner Prodi) serta tab khusus *"Kuesioner Khusus Prodi"* sehingga Super Admin dapat mengaudit evaluasi program studi setiap alumni secara transparan.

- **Modularisasi Komponen Kuesioner Tracer Study Alumni (`resources/js/Pages/Alumni/Kuesioner.vue`)**:
  - Memecah file monolitik `Kuesioner.vue` (yang sebelumnya mencapai 1.621 baris kode) menjadi komponen-komponen terisolasi dan mandiri di dalam folder `resources/js/Pages/Alumni/Components/Kuesioner/`:
    1. `Navbar.vue`: Menangani header atas, logo UKDW, dan tombol kembali ke dashboard alumni.
    2. `Stepper.vue`: Menangani tahapan bulatan angka 1 s/d N, judul seksi, indikator centang selesai, dan auto-scroll horizontal.
    3. `Banner.vue`: Menangani kartu banner hijau judul bagian ("Bagian X dari Y") dan petunjuk pengisian.
    4. `TabelF17.vue`: Menangani tabel perbandingan kompetensi dual-matrix F17 (Kolom A vs Kolom B) secara mandiri.
    5. `KartuPertanyaan.vue`: Menangani rendering butir pertanyaan individual beserta seluruh varian input (`rating_5`, `searchable_select`, `radio`, `checkbox`, `radio_input`, `multiple_number`, `number`, `text`, serta layout 2 kolom berdampingan F6 & F7).
    6. `Navigasi.vue`: Menangani tombol navigasi melayang di desktop (< Kembali & > Lanjut/Selesai) dan fixed bottom bar di smartphone.
  - Komponen induk `Kuesioner.vue` kini ringkas dan terfokus (berkurang dari 1.621 baris menjadi ~490 baris), hanya bertugas mengorkestrasi state form Inertia, alur jump logic, dan persistensi sesi lokal (`localStorage`).

- **Peta Lengkap Struktur File & Direktori Pembaruan Ini**:
  ```text
  tracerstudy/
  ├── app/
  │   ├── Http/Controllers/
  │   │   ├── AdminProdi/                                             # [MODUL BARU] Pengelolaan Program Studi
  │   │   │   ├── Dashboard/DashboardController.php                  # KPI & statistik alumni prodi
  │   │   │   ├── KelolaAlumni/DaftarAlumniProdiController.php        # Direktori & audit detail alumni prodi (/prodi/alumni/{id})
  │   │   │   └── KelolaPertanyaan/
  │   │   │       ├── DaftarPertanyaanProdiController.php             # Bank soal kuesioner prodi
  │   │   │       ├── KelolaOpsiProdiController.php                   # CRUD pilihan opsi jawaban prodi
  │   │   │       ├── KelolaSectionProdiController.php                # CRUD seksi & urutan kuesioner prodi
  │   │   │       └── SimpanPertanyaanProdiController.php              # Simpan & edit butir soal prodi
  │   │   ├── Alumni/Kuesioner/
  │   │   │   ├── KuesionerController.php                             # Kuesioner Tracer Study Universitas
  │   │   │   ├── KuesionerProdiController.php                        # [BARU] Kuesioner Khusus Prodi & auto-prefill akademik
  │   │   │   └── SimpanJawabanController.php                         # Simpan jawaban kuesioner univ
  │   │   └── SuperAdmin/
  │   │       └── KelolaAlumni/DetailAlumniSuperAdminController.php   # Audit detail alumni (+ tab kuesioner prodi)
  │   ├── Models/
  │   │   ├── ProdiQuestionSection.php                                # [MODEL BARU] Seksi kuesioner prodi
  │   │   ├── ProdiQuestion.php                                       # [MODEL BARU] Butir pertanyaan prodi
  │   │   ├── ProdiQuestionOption.php                                 # [MODEL BARU] Opsi jawaban kuesioner prodi
  │   │   └── ProdiResponse.php                                       # [MODEL BARU] Jawaban kuesioner prodi alumni
  │   └── Services/Kuesioner/
  │       └── KuesionerSyncService.php                                # [BARU] Centralized auto-sync data akademik ke prodi_responses
  ├── database/
  │   ├── migrations/
  │   │   ├── 2026_09_13_090000_add_prodi_id_to_users_table.php       # Menghubungkan user admin_prodi ke prodis.id
  │   │   ├── 2026_09_13_092000_create_prodi_questionnaire_tables.php # 4 tabel mandiri kuesioner prodi
  │   │   └── 2026_09_13_093000_remove_prodi_id_from_questions_table.php # Pembersihan kolom prodi_id di questions
  │   └── seeders/
  │       └── ProdiQuestionnaireSeeder.php                            # Seeder 9 section & 52 butir instrumen Prodi SI & Filsafat
  └── resources/js/Pages/
      ├── AdminProdi/                                                 # [UI BARU] Antarmuka Admin Program Studi
      │   ├── Dashboard.vue                                           # Dashboard resmi admin prodi (estetika hijau UKDW)
      │   ├── Alumni/Index.vue                                        # Master data mahasiswa & alumni prodi
      │   ├── Alumni/Show.vue                                         # Audit detail 3-tab (Kuesioner Prodi, Univ, Profil)
      │   ├── Pertanyaan/Index.vue                                    # Kelola pertanyaan & opsi (bebas ikon slop)
      │   ├── Section/Index.vue                                       # Kelola bagian kuesioner prodi
      │   └── Components/Navbar.vue                                   # Navbar resmi admin prodi
      ├── Alumni/
      │   ├── Kuesioner.vue                                           # [DEKOMPOSISI] Induk form kuesioner universitas (~490 baris)
      │   ├── KuesionerProdi.vue                                      # Halaman kuesioner prodi alumni (auto-prefill readonly)
      │   └── Components/Kuesioner/                                   # [MODULAR] 6 Komponen Pecahan Kuesioner:
      │       ├── Navbar.vue                                          # Header atas & tombol kembali
      │       ├── Stepper.vue                                         # Stepper bulat 1..N & auto scroll
      │       ├── Banner.vue                                          # Banner hijau judul bagian
      │       ├── TabelF17.vue                                        # Dual-matrix F17 (Kemampuan Diri vs Kampus)
      │       ├── KartuPertanyaan.vue                                 # Dispatcher renderer butir soal & opsi input
      │       └── Navigasi.vue                                        # Floating action buttons desktop & mobile bar
      └── SuperAdmin/Alumni/Show.vue                                  # Tampilan audit detail alumni dengan tab Kuesioner Prodi
  ```

- **Pembersihan Desain Visual & Standarisasi Komentar Kode**:
  - Menghapus seluruh ikon/emoji berlebihan di seluruh modul Admin Prodi agar tampilan lebih formal, profesional, dan tidak "slop".
  - Menambahkan komentar kode penjelasan (*docblocks & inline comments*) yang deskriptif dan terstruktur dalam Bahasa Indonesia pada seluruh controller, model, migration, dan komponen Vue terkait.

## [2026-09-10] Perbaikan Perhitungan Data Terhapus pada Profil Alumni & Penambahan Indikator Persentase Progres di Dashboard Alumni
- **Perbaikan Bug Perhitungan Data Profil yang Dihapus (`SimpanProfilController` & `KelengkapanTracerService`)**:
  - **Penyebab Utama**:
    1. Saat alumni mengosongkan/menghapus atribut perusahaan (seperti alamat, skala, provinsi, atau kabupaten), pemanggilan `array_filter(...)` pada controller secara tidak sengaja membuang nilai `null`, sehingga database tidak memperbarui field tersebut dan data lama tetap tersimpan.
    2. Saat alumni menghapus `email_pribadi` atau `nama` di data akademik, evaluasi kelengkapan sebelumnya menggunakan fallback `?? $alumni->user?->email` / `?? $alumni->user?->name` sehingga nilai tetap terhitung terisi padahal sudah dihapus oleh user.
    3. Hubungan relasi Eloquent (`company`, `atasan`, `dataAkademik`) yang telah di-cache di memori model `$alumni` tidak ter-refresh saat method sync atau evaluasi dijalankan dengan `loadMissing(...)`.
    4. Kolom `nama_orang_tua` pada tabel `data_orang_tuas` sebelumnya berstatus `NOT NULL`, menyebabkan query error jika user mengosongkan data orang tua.
  - **Solusi & Perbaikan**:
    - Menambahkan migrasi database `2026_09_09_203027_make_nama_orang_tua_nullable_in_data_orang_tuas_table.php` agar field data orang tua dapat dikosongkan tanpa integrity violation.
    - Menghapus `array_filter` pada update perusahaan di `SimpanProfilController.php` dan `DetailAlumniSuperAdminController.php` sehingga field yang dikosongkan tersimpan sebagai `null` di database.
    - Menghapus fallback otomatis ke akun user pada `KelengkapanTracerService::evaluasiProfil()` dan `KuesionerSyncService::syncProfileResponses()`, sehingga ketika data dihapus/dikosongkan, field tersebut secara akurat dihitung sebagai belum lengkap (persentase berkurang dan jawaban terkait di tabel `responses` terhapus otomatis).
- **Standarisasi Seluruh Data Model Alumni (`alumnis`) Menjadi Wajib**:
  - Seluruh atribut model `Alumni` kini berstatus wajib (*mandatory*) dalam penghitungan kelengkapan profil pada `KelengkapanTracerService::evaluasiProfil()`, yaitu:
    1. Bidang Keahlian (*Expertise*) (`expert`)
    2. Minat & Ketertarikan (`minat`)
    3. LinkedIn Profil URL (`linkedin_url`)
    4. LinkedIn Username (`linkedin_username`)
    5. Instagram Profil URL (`instagram_url`)
    6. Facebook Profil URL (`facebook_url`)
    7. Kode Pos Perusahaan / Zipcode (`zipcode`)
    8. Posisi / Jabatan (`posisi_jabatan`)
    9. Jenis Pekerjaan Gerejawi (`jenis_pekerjaan`, khusus prodi Teologi)
  - Total data profil wajib kini berjumlah **32 data** (atau 33 untuk Teologi). Jika ada salah satu dari data tersebut yang kosong, status profil langsung berubah menjadi **Belum Lengkap** dengan persentase yang berkurang secara proporsional.
- **Penambahan Persentase Kelengkapan & Progress Bar di Dashboard Alumni (`/alumni/dashboard`)**:
  - Mengintegrasikan penghitungan komprehensif dari `KelengkapanTracerService::evaluasiKelengkapanTotal($alumni)` ke `App\Http\Controllers\Alumni\Dashboard\DashboardController.php`.
  - Mengirimkan props persentase: `profilePercentage`, `profileCompleted`, `profileFilledCount`, `profileTotalCount`, `questionnairePercentage`, `questionnaireCompleted`, `questionnaireAnsweredCount`, `questionnaireTotalCount`, dan `questionnaireMissing`.
  - **Tampilan Visual Dashboard Alumni (`Dashboard.vue`)**:
    - **Card Profil & Biodata**: Menampilkan persentase kelengkapan profil (misal: `100% Terisi` atau `80% Terisi`), progress bar warna hijau UKDW (`#0D542B`), rincian data terisi (`X dari 23 data terisi`), dan badge status "Sudah Lengkap" (hijau) atau "Belum Lengkap" (kuning `#FDC700`).
    - **Card Kuesioner Tracer Study**: Menampilkan persentase jawaban kuesioner wajib (misal: `0% Terjawab` atau `100% Terjawab`), progress bar warna hijau UKDW (`#0D542B`), rincian butir terjawab (`X dari 61 pertanyaan wajib`), dan badge status "Sudah Diselesaikan" (hijau) atau "Belum Diselesaikan" (kuning `#FDC700`).
    - Tombol aksi utama distandarisasi menggunakan warna hijau solid UKDW `#0D542B` tanpa gradasi warna biru atau ungu.
- **Pengujian Otomatis (Automated Testing)**:
  - Membuat unit/feature test `tests/Feature/AlumniDashboardPercentageTest.php` untuk memvalidasi rendering persentase di dashboard dan penurunan persentase saat data profil dikosongkan.
  - Seluruh 28 unit dan feature test pada test suite lulus 100% (`passed: 28, assertions: 163`).

## [2026-09-10] Penerapan Warna Solid Resmi UKDW (Hijau #0D542B & Kuning #FDC700 Sesuai Gambar Logo), Penghapusan Gradasi Warna, dan Pembersihan Efek Border Hover
- **Warna Solid Murni Resmi UKDW (Diambil Langsung dari Gambar Lambang UKDW)**:
  - **Hijau Resmi UKDW**: `#0D542B` (Warna solid hijau botol resmi pada background logo UKDW, menghapus seluruh efek gradasi warna berlebihan).
  - **Kuning Resmi UKDW**: `#FDC700` (Warna solid kuning emas pada tulisan *"UNIVERSITAS KRISTEN DUTA WACANA"*).
  - **Putih**: `#FFFFFF`.
- **Pembersihan Total Efek Hover Border & Warna Belang-belang**:
  - Menghapus efek `hover:border-...` pada kartu dan baris tabel di seluruh modul Super Admin (`/superadmin/alumni`, `/superadmin/pertanyaan`, `/superadmin/sections`, `/superadmin/dashboard`).
  - Menghapus latar hijau muda pastel (`bg-green-100`) dan efek hover kuning belang-belang pada baris opsi pertanyaan (`QuestionCard.vue`), digantikan oleh baris bersih netral (`bg-gray-50 hover:bg-gray-100`) tanpa border saat dihover.
  - Badge status *"Belum Selesai"* menggunakan kuning solid UKDW `#FDC700` dengan teks hitam tegas tanpa border.
  - Badge status *"Selesai"* menggunakan hijau solid UKDW `#0D542B` dengan teks putih bersih tanpa border.
- **Standarisasi 4 Halaman Utama Super Admin**:
  1. `/superadmin/dashboard`: Header solid `#0D542B`, 4 kartu KPI putih bersih tanpa efek hover border, modul navigasi rapi dengan tombol hijau solid.
  2. `/superadmin/alumni`: Header solid `#0D542B`, kartu metrik ringkas, filter pencarian elegan, dan tabel data tanpa garis border saat baris di-hover.
  3. `/superadmin/alumni/{id}`: Header solid `#0D542B`, badge "Yudisium: Lulus" berlatar kuning emas `#FDC700`, kotak jawaban alumni berlatar netral dengan aksen garis kiri `#0D542B`.
  4. `/superadmin/pertanyaan` & `/superadmin/sections`: Header solid `#0D542B`, kartu pertanyaan bersih tanpa warna pelangi, dan opsi pilihan jawaban yang rapi.
- **Eliminasi Border Bertumpuk-tumpuk & Efek Kaku**:
  - Menghapus seluruh border tebal ganda (`border-2 border-yellow-200 border-l-8`) pada kartu statistik, filter, dan tabel.
  - Menerapkan card putih bersih dengan border halus abu-abu (`border border-gray-100`), sudut lengkung modern (`rounded-2xl` / `rounded-3xl`), dan bayangan halus (`shadow-sm hover:shadow-md`) persis seperti pada modul Alumni.
- **Standarisasi Warna Kuning Lembut (Soft Amber / Background Sand)**:
  - Mengganti warna kuning menyala menjadi kuning lembut seperti latar belakang (`bg-amber-50 text-amber-900 border border-amber-200/70`) pada badge "Yudisium: Lulus", status "Belum Selesai", dan peringatan butir kuesioner belum dijawab.
- **Penyelarasan 4 Halaman Utama Super Admin**:
  1. `/superadmin/dashboard`: Header gradien emerald UKDW (`#005B3C` ke `#007b55`), 4 kartu KPI putih bersih elegan tanpa border ganda, dan 2 modul navigasi profesional.
  2. `/superadmin/alumni`: Header gradien emerald, ringkasan cepat glassmorphism di header, 4 kartu KPI minimalis, filter pencarian bersih, dan tabel DataTables yang lapang dan mudah dibaca.
  3. `/superadmin/alumni/{id}`: Badge Yudisium berlatar kuning lembut, ringkasan audit kuesioner putih bersih, kotak jawaban alumni rapi dengan aksen kiri tegas (`border-l-4 border-l-[#005B3C] bg-emerald-50/40`), dan formulir profil 4-tab yang serasi.
  4. `/superadmin/sections` & `/superadmin/pertanyaan`: Standarisasi warna tombol dan badge jenis pertanyaan ke palet emerald, amber lembut, dan netral tanpa warna pelangi.
- **Standarisasi Palet Murni Tiga Warna (Strict 3-Color Theme)**:
  - Menerapkan palet resmi UKDW di seluruh antarmuka Super Admin (`/superadmin`, `/superadmin/dashboard`, `/superadmin/alumni`, `/superadmin/alumni/{id}`):
    1. **Hijau Resmi UKDW**: `#005B3C` (Header banner, tombol aksi utama, border tebal kotak jawaban, badge kelengkapan, highlight navigasi aktif).
    2. **Kuning Resmi UKDW**: `#FACC15` (Badge penanda belum selesai/wajib, border aksen) & `#FEFCE8` / `#FEF08A` (Latar belakang pembeda antar card agar kontras dan mudah dibaca).
    3. **Putih**: `#FFFFFF` (Latar konten kartu, card butir pertanyaan, latar kotak jawaban, teks pada elemen hijau).
  - Menghapus seluruh warna asing (seperti biru, ungu, oranye) pada komponen kartu metrik, navigasi, dan tabel.
- **Redesign Halaman Dashboard Super Admin (`Dashboard.vue`) & Rute Redirect**:
  - Menambahkan rute `Route::redirect('/superadmin', '/superadmin/dashboard')` pada `routes/web.php` sehingga akses ke `http://localhost:8000/superadmin` langsung mengarah ke Dashboard tanpa error.
  - Mengubah 4 kartu KPI metrik di Dashboard dengan border kiri tebal hijau `#005B3C` (`border-l-8`) dan latar putih/kuning yang jelas perbedaannya.
  - Menghapus seluruh icon gambar/emoji (`#`, `§`, `👤`, `✓`) dan menggantinya dengan badge teks terstruktur yang rapi.
  - Memperbarui 2 modul navigasi cepat ("Direktori Mahasiswa & Hasil Tracer" dan "Instrumen Kuesioner") dengan tema 3 warna.
- **Penebalan & Penegasan Border Kotak Jawaban Alumni (`Show.vue`)**:
  - Kotak jawaban kuesioner alumni dipertegas dengan border hijau tebal `border-2 border-[#005B3C] border-l-[10px] border-l-[#005B3C] bg-white rounded-xl shadow-xs`.
  - Dilengkapi badge hijau mini `Terisi` dan teks jawaban hitam pekat berbobot `font-black` untuk keterbacaan optimal.
  - Setiap butir pertanyaan diletakkan dalam kartu mandiri berlatar putih dengan border kuning lembut di atas background section `#FEFCE8`, menjamin setiap card terlihat jelas perbedaannya.
- **Pembersihan Ikon Gambar pada Navbar & Direktori Mahasiswa**:
  - Menyederhanakan `Navbar.vue` dengan menghilangkan icon svg dekoratif pada tombol keluar dan menerapkan navigasi aktif berbasis aksen hijau-kuning.
  - Menghapus warna merah pada tombol reset filter di `Index.vue` agar seragam dengan tema 3 warna.
- **Seeding Pertanyaan Studi Lanjut `F24` & Opsi Jawaban**:
  - Menambahkan instrumen pertanyaan studi lanjut dan pembiayaan kuliah pada `QuestionSeeder.php` dan `QuestionOptionSeeder.php`:
    1. `F24A`: "Sebutkan sumberdana dalam pembiayaan kuliah S1 di UKDW :" (tipe `single_choice`, status wajib diisi seluruh alumni, 7 opsi jawaban: Biaya Sendiri/Keluarga, Beasiswa ADIK, Beasiswa BIDIKMISI, Beasiswa PPA, Beasiswa Afirmasi, Beasiswa Perusahaan/Swasta, dan Lainnya).
    2. `F24B`: "Jika Anda melanjutkan ke jenjang pascasarjana (S2), sebutkan sumberdana dalam pembiayaan kuliah S2 Anda :" (tipe `single_choice`, status wajib diisi seluruh alumni, 3 opsi jawaban: 0: Tidak melanjutkan S2, 1: Melanjutkan dengan biaya sendiri, 2: Melanjutkan dengan beasiswa).
- **Layanan Evaluasi Kelengkapan Terpadu (`KelengkapanTracerService.php`)**:
  - Mengimplementasikan `App\Services\Kuesioner\KelengkapanTracerService`:
    1. `evaluasiProfil()`: Mengaudit kelengkapan 100% data profil (Nama, Tempat/Tgl Lahir, Agama, Jenis Kelamin, NIK, No KK, NISN, BPJS, NPWP, Alamat Domisili, Kelurahan, Kecamatan, Kab/Prov, Kode Pos, Nomor Telepon/HP, Email Pribadi, IPK, Tahun Kelulusan, Data Orang Tua, Data Perusahaan, Data Atasan, dan Posisi Jabatan).
    2. `evaluasiKuesionerWajib()`: Mengaudit butir kuesioner wajib mencakup `F8` (status bekerja), pertanyaan pekerjaan $\le$ 6 bulan (`F3`/`F5`), lokasi bekerja (`F2F`), jenis perusahaan (`F11`), nama perusahaan (`F2E`), posisi jabatan (`F2G`), studi lanjut (`F24A` dan `F24B`), keselarasan bidang studi (`F14`), tingkat pendidikan sesuai (`F15`), dan seluruh 54 butir evaluasi kompetensi `F17-1` s/d `F17-54`.
    3. Pertanyaan selain daftar wajib di atas secara otomatis diklasifikasikan sebagai **Opsional** (misal `F4`, `F6`, `F7`, `F9`, `F10`, `F12`, `F13`, `F16`, `F18`, `F19`, `F20`, `F21`, `F22`, `F23`) dan tidak membatalkan status kelulusan pengisian tracer study jika tidak diisi.
    4. `evaluasiKelengkapanTotal()`: Menghasilkan status final "Selesai" vs "Belum Selesai" beserta persentase progress dan daftar item yang belum lengkap.
- **Penyempurnaan Tampilan UI & Card Profesional (Tema Kuning UKDW Landing Page & Hijau Emerald)**:
  - **Direktori Mahasiswa (`Index.vue`)**:
    1. Tampilan card statistik ringkas dan profesional dengan border aksen hijau & kuning UKDW (`#FACC15` / `#005B3C`).
    2. Mengeliminasi ikon-ikon yang tidak penting agar halaman bersih, lapang, dan mudah dibaca.
    3. Mengimplementasikan fungsionalitas DataTables dengan pemilihan jumlah data per halaman (5, 10, 25, 50), penomoran urut, navigasi halaman (Pagination), dan keterangan jumlah data aktif.
  - **Halaman Detail & Audit Kuesioner (`Show.vue`)**:
    1. **Card Ringkasan Audit Kuesioner**: Disederhanakan menampilkan persentase saja (misal `0% Terjawab (0/61)`) tanpa deretan teks panjang butir soal di dalam card.
    2. **Navigasi Filter Section Interaktif**: Pertanyaan wajib yang belum dijawab dimunculkan langsung sebagai badge penanda di tombol navigasi section (`• X belum`) sehingga Super Admin dapat langsung mengklik section yang bersangkutan untuk meninjau butir soal yang belum terjawab.
    3. **Card Section Kuesioner Berlatar Kuning UKDW**: Header card kuesioner menggunakan warna kuning lembut UKDW (`#FEFCE8` dengan border `#FDE047`) yang elegan ala landing page.
- **Formulir Profil Lengkap 100% & Fitur Edit Profil oleh Super Admin**:
  - Menyediakan 4 tab formulir profil lengkap persis sama dengan portal alumni (Data Pribadi, Data Akademik, Data Orang Tua, dan Karier & Perusahaan) yang dapat diisi dan diperbarui secara langsung oleh Super Admin untuk membantu alumni yang kesulitan memperbarui data.
  - Menambahkan endpoint `POST /superadmin/alumni/{id}/profile` pada `DetailAlumniSuperAdminController.php` beserta sinkronisasi otomatis ke respon tracer `F1` s/d `F2H` via `KuesionerSyncService`.
- **Navigasi & Automated Testing**:
  - Menu **"Data Alumni"** aktif pada `Navbar.vue` Super Admin.
  - Automated feature test suite di `tests/Feature/SuperAdminDaftarAlumniTest.php` (5 pengujian mencakup index, filter, show, authorization, dan update profile alumni oleh superadmin) lulus 100% (26 feature test suite Laravel lulus tanpa error).
  - Standarisasi format kode PHP menggunakan Laravel Pint.

## [2026-09-10] Pembaruan UI/UX Alur Lompatan (Jump Logic): Dropdown Bertingkat (Cascading 2-Kolom: Section di Kiri & Pertanyaan di Kanan)
- **Pembaruan Antarmuka Dropdown Alur Lompatan / Percabangan (`OptionModal.vue`)**:
  - Mengimplementasikan sistem **Dropdown Bertingkat 2 Kolom (*Cascading Flyout Master-Detail*)**:
    1. **Kolom Kiri (Pilih Section)**: Menampilkan daftar seluruh bagian kuesioner (`Section 1: Identitas`, `Section 2: ...`) lengkap dengan badge total soal dan panah indikator bertingkat. Saat salah satu section diklik, section tersebut di-highlight aktif dengan latar hijau emerald `#005B3C`.
    2. **Kolom Kanan (Daftar Pertanyaan Terkait)**: Menampilkan butir pertanyaan dari section yang dipilih di kolom kiri. Sesuai instruksi pengguna, tampilan **dibatasi tepat 5 pertanyaan dalam 1 layar pandang** (`h-[260px]`), dan jika terdapat lebih dari 5 butir soal, pengguna dapat melakukan **scroll** secara halus (`overflow-y-auto`).
    3. **Format Mini Data Table**: Setiap baris di kolom kanan memuat badge kode kuesioner monospaced (`F1`, `F8`, `F17-1`), teks pertanyaan rapi 2 baris, dan indikator status `✓ Terpilih` / hover prompt `Pilih`.
  - **Fitur Pencarian Real-time (Searchable)**: Kotak input pencarian di bagian atas pop-up menyaring pertanyaan secara instan lintas section. Jika section aktif tidak memiliki hasil yang cocok, sistem otomatis mengarahkan ke section pertama yang memiliki kecocokan.
  - **Pilihan Alur Default Cepat**: Tombol cepat *"Alur Normal (Default)"* di bagian atas pop-up serta tombol (✕) pada kolom pemicu untuk memudahkan pengembalian alur kuesioner ke alur normal tanpa lompatan.
  - **Aksesibilitas & Pengalaman Pengguna**: Penyesuaian lebar modal menjadi `max-w-2xl`, auto-focus pada input pencarian saat pop-up dibuka, dan penutupan otomatis saat klik di luar area modal (*click-outside*).
- **Penyempurnaan Data Backend (`DaftarPertanyaanController.php`)**:
  - Memuat relasi `section` pada query `$availableJumpTargets` (`question_section_id`, `section_title`, `section_order`) agar siap dikelompokkan secara terstruktur di frontend.

## [2026-09-10] Modul CRUD & Pengaturan Urutan (Reorder) QuestionSection Superadmin, Integrasi UI/UX, dan Tabel UMP 2026
- **Pengembangan Modul CRUD & Pengaturan Urutan Bagian Kuesioner (`QuestionSection`)**:
  - Membuat controller backend baru [`KelolaSectionController.php`](file:///c:/study/tracerstudy/app/Http/Controllers/SuperAdmin/KelolaSection/KelolaSectionController.php) di `app/Http/Controllers/SuperAdmin/KelolaSection/` yang mencakup:
    1. `index()`: Mengambil daftar section terurut berserta relasi instrumen kuesioner induk dan kalkulasi `withCount('questions')`.
    2. `store()`: Menambahkan bagian baru dengan otomatisasi penentuan nomor urutan (`order`).
    3. `update()`: Menyunting judul bagian dan kuesioner induk.
    4. `destroy()`: Menghapus bagian kuesioner dengan pembersihan kaskade opsi dan butir pertanyaan demi integritas data.
    5. `reorder()`: Mengatur ulang urutan posisi section (naik/turun secara directional menggunakan *order swapping* maupun pembaruan array massal).
  - Mendaftarkan rute terpadu superadmin di `routes/web.php` (`superadmin.sections.*`).
- **Standardisasi Tipe Pertanyaan Menyerupai Google Forms & Otomatisasi Opsi Rating**:
  - Memperluas pilihan tipe pertanyaan pada [`QuestionModal.vue`](file:///c:/study/tracerstudy/resources/js/Pages/SuperAdmin/Pertanyaan/Components/QuestionModal.vue) menjadi terstruktur rapi dengan kategori:
    1. *Teks & Isian*: Jawaban singkat (`text`), Paragraf (`textarea`), Isian Angka (`number`).
    2. *Pilihan & Opsi*: Pilihan ganda radio (`single_choice`), Pilihan ganda + Isian Angka F3/F5 (`radio_input`), Pilihan ganda + Isian Teks Lainnya (`radio_text`), Kotak Centang (`multiple_choice`), Drop-down (`dropdown`).
    3. *Skala & Penilaian*: Skala linier / Rating 1-5 (`rating_5`), Isian Rincian Gaji F13 (`multiple_number`), Kisi pilihan ganda (`matrix`), Petak evaluasi ganda (`matrix_dual`).
    4. *Tanggal & Berkas*: Tanggal (`date`), Waktu (`time`), Upload file (`file`).
  - **Otomatisasi Opsi Skala Rating**: Pada [`SimpanPertanyaanController.php`](file:///c:/study/tracerstudy/app/Http/Controllers/SuperAdmin/KelolaPertanyaan/SimpanPertanyaanController.php), saat admin membuat pertanyaan bertipe `rating_5` (atau memperbarui ke tipe tersebut), sistem backend secara otomatis membuatkan 5 pilihan skala penilaian standar (1: Sangat Rendah s/d 5: Sangat Tinggi) sehingga admin tidak perlu mengetik opsi rating manual satu per satu.
  - Menambahkan *dynamic hint* di bawah kolom tipe pertanyaan yang menjelaskan fungsi dan perilaku tiap tipe.
  - Memperbarui [`QuestionCard.vue`](file:///c:/study/tracerstudy/resources/js/Pages/SuperAdmin/Pertanyaan/Components/QuestionCard.vue) agar mengenali penambahan opsi dan menampilkan badge warna yang tepat untuk seluruh tipe baru (seperti `radio_input`, `radio_text`, `rating_5`, `matrix`, dll.).
- **Antarmuka Pengguna Vue 3 Modul Kelola Section (`SuperAdmin/Section`)**:
  - Membuat halaman utama [`resources/js/Pages/SuperAdmin/Section/Index.vue`](file:///c:/study/tracerstudy/resources/js/Pages/SuperAdmin/Section/Index.vue):
    1. Menampilkan daftar kartu bagian dengan visual emerald UKDW `#005B3C` yang elegan, kartu rounded `[2rem]`, badge nomor urut, kuesioner induk, dan total butir soal.
    2. Fitur pencarian instan berdasarkan judul bagian atau nama kuesioner.
    3. Tombol navigasi langsung ke daftar butir soal masing-masing section (`?sec_id={id}`).
    4. Kontrol pemindahan urutan posisi naik (▲) dan turun (▼) dengan status tombol nonaktif otomatis saat berada di batas atas/bawah.
    5. Dialog peringatan SweetAlert2 jika section yang akan dihapus masih memuat butir pertanyaan aktif.
  - Membuat komponen modal [`resources/js/Pages/SuperAdmin/Section/Components/SectionModal.vue`](file:///c:/study/tracerstudy/resources/js/Pages/SuperAdmin/Section/Components/SectionModal.vue) untuk alur pembuatan dan pengeditan section dengan validasi formulir dan umpan balik sukses/gagal.
- **Integrasi Navigasi Terpadu Superadmin**:
  - Menambahkan menu navigasi resmi **Kelola Section** pada [`Navbar.vue`](file:///c:/study/tracerstudy/resources/js/Pages/SuperAdmin/Components/Navbar.vue) (versi Desktop dan Mobile).
  - Menambahkan tombol pintas **Kelola Section** pada halaman [`resources/js/Pages/SuperAdmin/Pertanyaan/Index.vue`](file:///c:/study/tracerstudy/resources/js/Pages/SuperAdmin/Pertanyaan/Index.vue) agar admin dapat beralih cepat antara manajemen butir soal dan manajemen bab/section.
- **Penyusunan Tabel UMP 2026 Terhubung Foreign Key `kode_provinsi`**:
  - Membuat migrasi tabel `umps` dengan relasi foreign key langsung ke kolom unique `kode_provinsi` pada tabel `provinces`.
  - Model `Ump.php` dengan relasi `belongsTo(Province::class, 'kode_provinsi', 'kode_provinsi')` serta relasi timbal balik `umps()` dan `ump()` di `Province.php`.
  - Membuat `UmpSeeder.php` berisi 38 data nominal UMP 2026 resmi seluruh provinsi di Indonesia dengan logika `updateOrCreate`.
  - Menyiapkan `UmpFactory.php` untuk kebutuhan data mock testing.
- **Automated Testing & Standarisasi Kode**:
  - Membuat pengujian komprehensif di [`tests/Feature/SuperAdminKelolaSectionTest.php`](file:///c:/study/tracerstudy/tests/Feature/SuperAdminKelolaSectionTest.php) (6 pengujian mencakup index, store, update, destroy, directional reorder, dan bulk reorder) dan [`tests/Feature/UmpTest.php`](file:///c:/study/tracerstudy/tests/Feature/UmpTest.php).
  - Seluruh 20 unit/feature test suite lulus 100%.
  - Seluruh berkas PHP diformat sesuai standar dengan Laravel Pint.

## [2026-09-09] Refaktorisasi Modular Modul Kelola Pertanyaan Superadmin, Pemisahan Komponen Vue, Navigasi Terpadu & Integrasi SweetAlert2
- **Pemisahan Controller Backend Menjadi Modular (Single-Responsibility)**:
  - Memecah controller monolitik `KelolaPertanyaanController.php` menjadi 3 controller modular di `app/Http/Controllers/SuperAdmin/KelolaPertanyaan/`:
    1. `DaftarPertanyaanController.php`: Menangani aksi `index()` untuk memuat daftar butir pertanyaan, relasi sections, program studi, opsi, dan peta lompatan branching (`jump_to`).
    2. `SimpanPertanyaanController.php`: Menangani operasi `store()`, `update()`, `destroy()`, dan `reorder()` urutan naik/turun pertanyaan.
    3. `KelolaOpsiController.php`: Menangani operasi `storeOption()`, `updateOption()`, dan `destroyOption()`.
  - Memperbarui `routes/web.php` untuk mengarahkan seluruh rute kuesioner superadmin ke controller modular baru.
- **Pemisahan Komponen Vue pada Modul Kelola Pertanyaan (`SuperAdmin/Pertanyaan`)**:
  - Memecah file tunggal `Index.vue` (811 baris) menjadi sub-komponen terstruktur di `resources/js/Pages/SuperAdmin/Pertanyaan/Components/`:
    1. `SectionTabs.vue`: Navigasi tab horizontal antar-section kuesioner dengan badge nomor urut dan jumlah soal.
    2. `QuestionCard.vue`: Kartu butir pertanyaan individu dengan kontrol aksi naik/turun urutan, edit/hapus soal, serta daftar opsi jawaban.
    3. `QuestionModal.vue`: Modal dialog popup tambah dan edit butir pertanyaan kuesioner.
    4. `OptionModal.vue`: Modal dialog popup tambah dan edit pilihan opsi jawaban dan alur percabangan (*jump logic*).
    5. `Index.vue`: Komponen Orchestrator utama yang ringkas, bersih, dan mengelola sinkronisasi query string `?sec_id=` dan pencarian.
- **Komponen Navigasi Terpadu Resmi Superadmin (`Navbar.vue`)**:
  - Membuat satu file navigasi resmi terpadu di `resources/js/Pages/SuperAdmin/Components/Navbar.vue` yang digunakan bersama oleh Dashboard dan Kelola Kuesioner.
  - Menampilkan branding UKDW, menu aktif, info pengguna, dan tombol logout dengan konfirmasi SweetAlert2.
- **Penerapan SweetAlert2 Menyeluruh**:
  - Menggantikan semua fungsi bawaan browser (`window.confirm`) dengan dialog interaktif SweetAlert2 bertema warna hijau emerald UKDW (`#005B3C`).
  - Dilengkapi dialog konfirmasi hapus pertanyaan dan opsi jawaban, feedback sukses simpan/update, serta error alert validasi.
- **Redesign Tampilan UI Menyerupai Profil Alumni & Perbaikan Tab Terpotong (Clipping)**:
  - Menerapkan kartu rounded modern, bayangan halus, header gradien emerald UKDW `#005B3C`, dan tipografi Instrument Sans yang konsisten.
  - Memperbaiki masalah tampilan terpotong (*clipping/overlap bug*) pada `SectionTabs.vue`:
    1. Menghilangkan `sticky top-20` yang menutupi judul bagian (*Section Header*), *search bar*, dan tombol `+ Tambah Pertanyaan` saat halaman di-scroll atau pada layar beresolusi laptop/tablet.
    2. Menambahkan *custom thin scrollbar* (3px) yang elegan pada baris navigasi tab horizontal untuk menggantikan scrollbar bawaan Windows yang tebal dan memotong konten di bawahnya.
    3. Menambahkan fungsi *auto-scroll-into-view* reaktif agar tab yang aktif (misal Section 4, 5, dst.) otomatis bergeser ke tengah area pandang horizontal saat dipilih atau saat halaman dibuka via parameter URL `?sec_id=`.
- **Dokumentasi Kuesioner Dinamis & Aturan F17**:
  - Menambahkan bab penjelasan arsitektur kuesioner realtime dan aturan pasangan butir genap F17 ke `documentation.txt`.

## [2026-09-09] Fitur Total Salary Reaktif, Format Pengisian Ribuan (Akhiran .000 Otomatis) & Perlindungan Kesalahan Data pada Kuesioner F13
- **Kalkulasi & Tampilan Total Salary Reaktif (Pertanyaan `multiple_number` / F13)**:
  - Menambahkan kartu ringkasan visual **Total Pendapatan (Total Salary)** dengan desain gradien hijau emerald yang elegan dan ikon finansial di bawah rincian pendapatan F13.
  - Nilai total dikalkulasi secara reaktif secara *real-time* saat alumni mengetikkan atau mengubah angka pada komponen gaji mana pun (Pekerjaan Utama, Lembur & Tips, Pekerjaan Lainnya).
  - Dilengkapi label `*Otomatis dihitung & tersimpan ke database`.
- **Format Input Satuan Ribuan dengan Suffix `.000` Otomatis**:
  - Mengubah input komponen pendapatan menjadi input group modern dengan prefix `Rp` di sisi kiri, angka rata kanan monospace tebal, dan badge suffix `.000` di sisi kanan.
  - Memberikan petunjuk pengisian yang jelas: `* Nominal diisi dalam satuan ribuan rupiah (akhiran .000 otomatis). Contoh: masukkan 5000 untuk Rp 5.000.000, atau 750 untuk Rp 750.000`.
  - Menambahkan konversi langsung di bawah masing-masing opsi: `Konversi: Rp 5.000.000`.
- **Proteksi Cerdas Pencegahan Kesalahan Data (*Anti Double-Multiplication*)**:
  - Frontend: Jika alumni mengetik angka sangat besar (>= 1.000.000, misal mengetik 5.000.000 karena belum terbiasa dengan akhiran .000), muncul peringatan instan: `⚠️ Nilai terbaca di atas Rp 1 Miliar. Jika maksud Anda Rp 5.000.000, cukup ketik 5000`.
  - Backend: `SimpanJawabanController.php` secara cerdas mendeteksi angka: jika `< 1.000.000` dikalikan 1000 ke Rupiah penuh, namun jika `>= 1.000.000` tetap disimpan apa adanya untuk mencegah data membengkak menjadi miliaran rupiah.
- **Penyimpanan & Pembaruan Otomatis Nilai Total di Database**:
  - Kolom `answer_json` kini menyimpan rincian nominal rupiah penuh per kode opsi plus key akumulasi `'total'` (contoh: `{"F13-01": 6500000, "F13-02": 750000, "F13-03": 0, "total": 7250000}`).
  - Kolom `answer_text` memuat rincian terformat rupiah beserta total pendapatan (`"Dari Pekerjaan Utama: Rp 6.500.000, ..., Total Pendapatan: Rp 7.250.000"`).
  - Jika alumni mengedit salah satu nilai pendapatan dan menyimpan kembali, total salary di database otomatis ter-update dan tersinkronisasi.
- **Normalisasi Nilai saat Memuat Kembali Kuesioner**:
  - `KuesionerController.php` dan `Kuesioner.vue` (`getInitialAnswers`) secara otomatis membagi 1000 nilai tersimpan di database agar saat alumni membuka kembali kuesioner, angka di dalam kotak input berakhiran `.000` tetap konsisten (misal: 6.500.000 tampil sebagai 6500).
- **Pengujian Otomatis (Feature Test)**:
  - Membuat `tests/Feature/AlumniKuesionerMultipleNumberTest.php` dengan 4 skenario uji (penyimpanan ribuan & kalkulasi total, pembaruan total saat data diubah, normalisasi ke ribuan saat kuesioner dimuat, dan proteksi input nominal penuh). Seluruh tes lolos 100% (25 assertions).
- **Penyempurnaan Tampilan Evaluasi Kompetensi F17 (Berdampingan Bersih & Anti-Overlap)**:
  - Menyederhanakan tampilan agar intuitif, profesional, dan bebas dari instruksi berlebihan (*no bloated AI text/emojis*).
  - Menghilangkan *sticky header* yang sebelumnya menyebabkan tombol pilihan baris terpotong/tertutup (*clipping bug*).
  - Struktur tabel komparasi berdampingan:
    - **Kolom (A) Kemampuan Diri Anda**: Taraf penguasaan kompetensi saat lulus dengan aksen hijau emerald dan skala 1 (Rendah) s/d 5 (Tinggi).
    - **Kolom Tengah**: Nama aspek kompetensi yang bersih, dilengkapi *pill indicator* minimalis (`A > B`, `A = B`, `A < B`) jika kedua kolom telah diisi.
    - **Kolom (B) Kontribusi Kampus UKDW**: Peran kurikulum dan perkuliahan almamater dengan aksen biru royal dan skala 1 (Rendah) s/d 5 (Tinggi).
  - Menjaga proporsi visual yang seimbang, *padding* leluasa, dan pengalaman pengisian yang lancar di berbagai ukuran layar.

## [2026-09-08] Standarisasi Dokumentasi Edukatif Kode & Pemetaan Eksplisit Backend-Inertia-Frontend
- **Penambahan Komentar Penjelasan Baris demi Baris pada Backend (Controller)**:
  - `Alumni/Dashboard/DashboardController.php`: Memberikan komentar penjelasan detail pada setiap baris logika pengecekan pengguna login, pencarian data alumni, penghitungan jumlah respon kuesioner dari database, pengecekan status kelengkapan profil, serta pemetaan paket data (props) ke Inertia.
  - `AdminBiroTiga/Dashboard/DashboardController.php`: Memberikan dokumentasi menyeluruh per-variabel pada seluruh metrik KPI utama (`totalAlumni`, `totalResponden`, `persentaseRespon`, `totalPertanyaan`, `totalProdi`, `alumniLinkedIn`), kalkulasi ringkasan partisipasi per-prodi (`prodiSummaries`), dan 5 alumni terbaru (`recentAlumni`).
- **Penambahan Dokumentasi Asal-Usul Data & Aksi pada Frontend (Vue)**:
  - `Alumni/Dashboard.vue`: Menjelaskan asal mula props dari controller, method logout dengan `router.post('/logout')`, serta rute tujuan untuk setiap tombol `<Link>` profil dan kuesioner.
  - `AdminBiroTiga/Dashboard.vue`: Mendokumentasikan setiap struktur objek props yang diterima (`stats`, `prodiSummaries`, `recentAlumni`) dan penggunaannya pada kartu metrik maupun tabel data.
  - **Arsitektur Parent-Child Component Profil Alumni**:
    - `Alumni/Profil/Index.vue` (Parent): Menjelaskan siklus hidup data mulai dari penerimaan props `formData` dari `ProfilController.php`, pembentukan `useForm`, distribusi ke komponen anak, hingga pengiriman data simpan ke `SimpanProfilController.php`.
    - `Components/FormAkademik.vue` (Child): Menjelaskan silsilah asal objek `form` yang dioper dari parent `Index.vue`, daftar field akademik database yang ditampung, serta alasan penguncian input resmi kampus (*read-only*).
    - `Components/FormPribadi.vue`, `Components/FormOrangTua.vue`, & `Components/FormKarier.vue` (Child): Mendokumentasikan peran masing-masing form anak dan operan props form serta master data wilayah dari controller.
- **Pembaruan Aturan Agen & Dokumentasi**:
  - Menambahkan aturan baru `.agents/rules/code-documentation-guidelines.md` yang mewajibkan komentar per-variabel di Controller dan komentar silsilah props/method di file Vue.
  - Memperbarui file `documentation.txt` pada bagian Step 9 mengenai alur arsitektur Backend-Inertia-Frontend dan hierarki Parent-Child.

- **Perbaikan Masalah Tombol Logout di Dashboard Alumni & Seluruh Modul**:
  - Mengidentifikasi akar masalah: pemanggilan composable `useForm().post('/logout')` di dalam callback function click handler Vue 3 menyebabkan error injeksi konteks (`inject() can only be used inside setup()`), sehingga request tidak terkirim.
  - Memperbaiki seluruh method logout di seluruh komponen aplikasi (`Alumni/Dashboard.vue`, `SuperAdmin/Dashboard.vue`, `SuperAdmin/Pertanyaan/Index.vue`, `AdminProdi/Dashboard.vue`, `AdminBiroTiga/Dashboard.vue`, `AdminBiroTiga/Pertanyaan/Index.vue`, `AdminBiroTiga/AlumniIndex.vue`, `AdminBiroTiga/AlumniShow.vue`) dengan menggunakan `router.post('/logout')` dari `@inertiajs/vue3`.
- **Dukungan Rute Logout Aman & Fleksibel**:
  - Mengubah rute logout di `routes/web.php` menjadi `Route::match(['get', 'post'], '/logout', ...)` agar dapat dipanggil baik via POST maupun GET.
  - Memastikan jika sesi pengguna telah kedaluwarsa saat tombol logout ditekan, pengguna tidak akan terkunci di redirect error melainkan langsung terarah kembali dengan mulus ke Beranda (Home / Landing Page `/`).
  - Menambahkan komentar dokumentasi lengkap pada method `LoginController::prosesLogout`.
- **Migrasi Modul Kelola Pertanyaan & Alur Percabangan ke Superadmin**:
  - Memindahkan seluruh fitur manajemen kuesioner dari Biro 3 ke Superadmin sesuai hak wewenang universitas.
  - Membuat controller baru `App\Http\Controllers\SuperAdmin\KelolaPertanyaan\KelolaPertanyaanController` dengan komentar lengkap dan dokumentatif di setiap method.
  - Membuat halaman tampilan `resources/js/Pages/SuperAdmin/Pertanyaan/Index.vue` dengan navbar institusional Superadmin, navigasi section per-bagian (horizontal tabs), penyusunan urutan (reorder up/down), CRUD pertanyaan, dan alur percabangan (*Google Forms jump logic*).
  - Memperbarui rute di `routes/web.php` di bawah middleware `role:superadmin` (`/superadmin/pertanyaan`, `/superadmin/pertanyaan/reorder`, `/superadmin/pertanyaan/{id}`, opsi jawaban).
- **Pembaruan Dasbor Superadmin & Biro 3**:
  - Mengembangkan `SuperAdmin/DashboardController.php` dan `resources/js/Pages/SuperAdmin/Dashboard.vue` dengan ringkasan metrik instrumen, total pertanyaan, section, partisipasi responden, serta kartu gerbang langsung menuju modul kelola kuesioner.
  - Memperbarui `AdminBiroTiga/Dashboard.vue` dengan memfokuskan peran Biro 3 pada Manajemen Data Alumni, Sinkronisasi LinkedIn, dan Pemantauan Partisipasi Responden, serta menghapus tautan kelola pertanyaan dari navbar Biro 3.
- **Komentar Kode Menyeluruh**:
  - Menambahkan komentar deskriptif pada setiap rute, controller, dan komponen Vue terkait sesuai pedoman pengembangan.

## [2026-09-08] Fitur Penambahan Perusahaan Baru: SweetAlert2 Modal Popup Bersih, Validasi Wajib Wilayah & Kode Pos Langsung Simpan Database
- **Popup Tambah Perusahaan Menggunakan SweetAlert2 (Clean & Standar)**:
  - Menggantikan modal kustom dengan popup dialog **SweetAlert2** (`Swal.fire`) yang rapi, presisi, dan proporsional.
  - Bebas dari glitch overflow, terpotong, atau efek blur yang tidak diinginkan.
  - Dilengkapi input Nama Perusahaan (*), Dropdown Provinsi (*), Dropdown Kabupaten/Kota dinamis (*), Kode Pos (*), Skala, dan Alamat.
  - Pesan validasi interaktif ditampilkan langsung di dalam popup modal (`Swal.showValidationMessage`) tanpa menutup popup jika ada kolom wajib yang belum terisi.
- **Validasi Ketat Input Perusahaan Baru**:
  - Mewajibkan pengisian:
    1. **Nama Perusahaan / Instansi** (*)
    2. **Provinsi Perusahaan** (*)
    3. **Kabupaten / Kota Perusahaan** (*) (otomatis menyesuaikan provinsi yang dipilih)
    4. **Kode Pos Perusahaan** (*)
    5. Skala dan Alamat Jalan (opsional/pelengkap).
  - Mengimplementasikan validasi frontend dan backend untuk memastikan data lokasi perusahaan baru terisi lengkap meskipun status verifikasi adalah `Menunggu Verifikasi`.
- **Penyimpanan Langsung ke Database & Integrasi Otomatis**:
  - Menambahkan migrasi `2026_09_08_163000_add_kode_pos_to_companies_table.php` dan memperbarui model `Company.php` dengan kolom `kode_pos`.
  - Menambahkan route `POST /alumni/company` dan method controller `SimpanProfilController::tambahPerusahaanBaru(Request $request)` untuk menyimpan perusahaan baru ke database secara instan.
  - Setelah perusahaan berhasil disimpan:
    - Data perusahaan langsung ditambahkan ke daftar opsi dropdown (`localCompanies.unshift`).
    - Input form profil karier otomatis terpilih dan terisi dengan perusahaan baru tersebut (nama, provinsi, kabupaten/kota, alamat, skala, dan kode pos).
    - Menampilkan notifikasi sukses SweetAlert yang rapi dan menutup popup modal.
- **CSRF Token Protection**:
  - Menambahkan `<meta name="csrf-token" content="{{ csrf_token() }}">` pada layout `resources/views/app.blade.php` untuk memastikan seluruh request fetch aman dan terautentikasi.

## [2026-09-08] Penyempurnaan Profil Alumni: Penambahan NPWP, Pemisahan Data Paten Universitas vs Editable, Relasi Company Wilayah, & Kelengkapan Seeder 100%
- **Penambahan Kolom NPWP (Nomor Pokok Wajib Pajak)**:
  - Membuat migrasi baru `2026_09_08_160000_add_npwp_to_data_akademiks_table.php` untuk menambahkan kolom `npwp` (varchar 30, nullable) pada tabel `data_akademiks`.
  - Mendaftarkan `'npwp'` ke properti `$fillable` di model `DataAkademik.php`.
  - Menambahkan input NPWP pada kartu **Identitas Diri** di `FormPribadi.vue` dan mengintegrasikannya ke `ProfilController` serta `SimpanProfilController`.
- **Penghapusan Kolom Ekstra Dosen Pembimbing & Penguji**:
  - Kolom `dosen_pembimbing_3`, `dosen_penguji_3`, dan `dosen_penguji_4` yang tidak digunakan resmi dihapus dari skema database (`yudisiums`) dan migrasi. Dosen kini terstandarisasi menjadi Pembimbing 1, Pembimbing 2, Penguji 1, dan Penguji 2.
- **Seluruh Data Yudisium & Skripsi Resmi Paten (Tidak Dapat Diubah Alumni)**:
  - Data yudisium (Judul TA Indonesia & Inggris, Repositori/URL Publikasi, Jenis/Status Publikasi, Dosen Pembimbing 1-2, Dosen Penguji 1-2, Predikat Yudisium, dan Status Yudisium) ditarik resmi dari pangkalan data kampus dan berstatus paten. Seluruh input disajikan dalam mode terkunci (disabled abu-abu) dan di-bypass dari logika penyimpanan di `SimpanProfilController.php`.
- **Pembersihan Tampilan Non-AI Slop (Minimalis, Profesional, & Bersih)**:
  - Menghapus seluruh badge ikon gembok `🔒 Paten Universitas`, tulisan pengumuman panjang, dan dekorasi blur berlebihan.
  - Seluruh field paten / terkunci disajikan dengan estetika institusional bersih: input abu-abu halus (`bg-gray-100 text-gray-700 border-gray-200 cursor-not-allowed rounded-xl`) tanpa ornamen visual yang berisik.
- **Searchable Select Dropdown Perusahaan, Provinsi, & Kabupaten (Bergaya Gambar 2)**:
  - Mengubah input nama perusahaan menjadi **Searchable Select Dropdown** yang dapat diklik langsung kapan saja untuk membuka daftar perusahaan.
  - Dropdown dilengkapi dengan kotak pencarian sticky di atas (`🔍 Ketik untuk mencari...`) dan daftar opsi yang scrollable dengan penanda aktif hijau lembut (`bg-green-50 text-[#005B3C] font-semibold`), persis seperti pada Gambar 2.
  - Tetap menyediakan tombol `+ Tambah` di samping kanan input untuk membuka modal penambahan perusahaan baru jika instansi belum terdaftar di database.
  - Menerapkan pola searchable select yang sama pada pilihan Provinsi Perusahaan dan Kabupaten/Kota Perusahaan dengan penutup otomatis saat klik di luar (click outside).
  - **Data yang Dapat Diubah Alumni**:
    - Biodata diri (Nama, NIK, NPWP, Tempat/Tgl Lahir, Agama, Jenis Kelamin, Golongan Darah, Kewarganegaraan).
    - Kontak & Alamat Domisili lengkap (Telepon, Email, Alamat, Provinsi, Kabupaten/Kota, Kecamatan, Kelurahan, Kode Pos).
    - Skripsi, Tugas Akhir, Repositori, Publikasi Ilmiah, Dosen Pembimbing & Penguji (`FormAkademik.vue`).
    - Data lengkap Orang Tua / Wali (`FormOrangTua.vue`).
    - Karier, Perusahaan, Atasan, dan Media Sosial (`FormKarier.vue`).
- **Sinkronisasi Kunci Form Data Orang Tua & Akademik**:
  - Memperbaiki ketidaksesuaian kunci (mismatch key) di `FormOrangTua.vue` (`nama_orang_tua`, `pekerjaan_orang_tua`, `nomor_telepon_orang_tua`, dll) dan `FormAkademik.vue` (`angkatan_masuk`, `dosen_pembimbing_1`, `dosen_penguji_1`, `keterangan_hasil_yudisium`) agar data tersinkronisasi 100% dua arah dengan backend.
- **Relasi Wilayah Perusahaan (Company) Terhubung Lewat ID**:
  - `CompanySeeder.php` diperbarui untuk menempatkan perusahaan di 3 wilayah spesifik:
    1. **DI Yogyakarta**: Kabupaten **Sleman** (ID provinsi DIY kode `34`, ID kabupaten Sleman kode `34.04`). Perusahaan: *PT Gameloft Indonesia*, *PT Niagahoster*, *PT Djarum Sleman*, *CV Javan Cipta Solusi*.
    2. **DKI Jakarta**: **Kota Jakarta Pusat** (ID provinsi DKI Jakarta kode `31`, ID kabupaten Kota Jakarta Pusat kode `31.71`). Perusahaan: *PT Bank Central Asia Tbk*, *PT Telekomunikasi Indonesia Tbk*, *PT Tokopedia*, *PT Astra International Tbk*.
    3. **Aceh**: Kabupaten **Aceh Selatan** (ID provinsi Aceh kode `11`, ID kabupaten Aceh Selatan kode `11.01`). Perusahaan: *PT Perkebunan Nusantara I (PTPN)*, *PT Bank Aceh Syariah Tapaktuan*, *CV Samudera Selatan Digital*.
  - Seluruh relasi `province_id` dan `kabupaten_id` terhubung via foreign key yang valid.
- **Kelengkapan Seeder Data Alumni 100% Rata Terisi**:
  - `DataAkademikSeeder.php`: Mengisi 100% data untuk seluruh 10 alumni (termasuk NIK, KK, NISN, BPJS, NPWP, domisili lengkap terhubung ke ID wilayah, dan data paten kelulusan).
  - `DataOrangTuaSeeder.php`: Mengisi lengkap data orang tua untuk seluruh 10 alumni.
  - `YudisiumSeeder.php`: Mengisi lengkap data skripsi bahasa Indonesia & Inggris, repositori, jenis & status publikasi, nama dosen pembimbing & penguji, serta predikat kelulusan untuk seluruh 10 alumni.
  - `AlumniSeeder.php`: Mengaitkan `company_id` (terdistribusi ke Sleman, Jakarta Pusat, dan Aceh Selatan), data atasan (`atasan_id`), posisi jabatan, keahlian (`expert`), minat (`minat`), dan tautan media sosial.
  - `DatabaseSeeder.php`: Mengatur urutan pemanggilan seeder agar `CompanySeeder` dijalankan sebelum `AlumniSeeder`.

- **Tata Letak Kartu Kotak Berdampingan (Kanan-Kiri) Khusus Pertanyaan F6 & F7**:
  - Mengelompokkan kartu pertanyaan `F6` ("Berapa perusahaan dilamar?") dan `F7` ("Berapa perusahaan merespons?") menjadi grid 2 kolom berdampingan (`grid grid-cols-1 md:grid-cols-2 gap-6`).
  - Masing-masing disajikan dalam kartu kotak `rounded-[2rem] shadow-sm` proporsional dengan input angka besar di tengah sehingga tampilan lebih rapi, hemat ruang vertikal, dan ergonomis.
- **Dukungan Input Dinamis untuk Opsi "Lainnya / Tuliskan" di Semua Tipe Pilihan**:
  - Menyediakan kotak isian teks (`input text`) dinamis yang otomatis muncul saat alumni mencentang atau memilih opsi jawaban yang mengandung kata `"Lainnya"` atau `"Tuliskan"` pada tipe `multiple_choice` / `checkbox` maupun `single_choice` / `radio`.
  - Input teks terintegrasi langsung ke state pengiriman jawaban `SimpanJawabanController` dan tersimpan rapi ke tabel `responses`.
- **Pembersihan Teks Pertanyaan F10 & Verifikasi Database Seeding**:
  - Menghapus teks instruksi legacy `"KEMUDIAN LANJUT KE F17"` dari database. Teks pertanyaan `F10` di database dan seeder kini bersih murni: `"Apakah anda aktif mencari pekerjaan dalam 4 minggu terakhir? Pilihlah Satu Jawaban."`.
  - Memverifikasi bahwa seluruh alur branching `jump_to` (lompat ke `F17-1` atau lanjut normal) dikendalikan 100% dari kolom `jump_to` di tabel `question_options` di database tanpa hardcode teks di frontend maupun seeder.

## [2026-09-08] Perbaikan Hover Pilihan Kuesioner, Eliminasi Border Kasar, & Persistensi State/Draft saat Refresh Halaman
- **Perbaikan Visual Opsi Pilihan Saat Hover (Anti-Whiteout)**:
  - Mengatasi masalah teks dan placeholder input di dalam opsi pilihan kuesioner yang memutih saat kursor mouse di-hover (seperti pada pertanyaan F3).
  - Mengganti utilitas CSS bentrok Tailwind (`peer-checked:bg-[#005B3C] peer-checked:text-white hover:bg-green-50`) dengan binding reaktif Vue murni.
  - Opsi belum terpilih: latar `bg-gray-50/90 text-gray-700 hover:bg-emerald-50 hover:text-[#005B3C]`.
  - Opsi terpilih: latar `bg-[#005B3C] text-white shadow-md hover:bg-[#00482f]`.
  - Kotak input dinamis pada tipe `radio_input`: Latar putih kontras tinggi `bg-white text-gray-900 placeholder-gray-400 font-bold border-0 shadow-inner` sehingga teks input dan placeholder terbaca dengan sangat jelas.
- **Penghapusan Border Kasar (Clean & Modern UI)**:
  - Menghapus border tebal (`border-4`, `border-2`) pada kartu pertanyaan, header banner, stepper navigasi bagian, dan floating action button di halaman kuesioner alumni.
  - Tampilan diganti dengan *subtle shadow* (`shadow-sm`, `shadow-xs`) dan sudut halus (`rounded-[2rem]`) yang bersih dan rapi.
- **Persistensi Penuh Tampilan & Draft Saat Refresh Halaman (Tidak Pindah Tampilan & Data Tidak Hilang)**:
  - **Kuesioner Alumni (`/alumni/kuesioner`)**:
    - Menyimpan section kuesioner aktif ke URL query (`?sec=...`) dan `localStorage`. Saat halaman di-refresh (F5), alumni tetap berada di bagian/section yang sedang dikerjakan, tidak terlempar ke Section 1.
    - Menyimpan draft jawaban yang sedang diisi secara real-time (*debounced* 250ms) ke `localStorage`. Jika halaman di-refresh sebelum tombol simpan ditekan, seluruh draft isian tidak akan hilang.
    - Menambahkan `preserveState: true` dan `preserveScroll: true` pada seluruh navigasi step.
  - **Profil Alumni (`/alumni/profile`)**:
    - Menyimpan tab aktif (`pribadi`, `akademik`, `orangtua`, `karier`) ke URL query (`?tab=...`) dan `localStorage`. Saat di-refresh, pengguna tetap berada di tab yang sama.
    - Menyimpan draft input perubahan form profil ke `localStorage` agar data yang belum tersubmit tidak hilang saat halaman di-refresh secara tidak sengaja.
  - **Kelola Pertanyaan Biro 3 (`/biro3/pertanyaan`)**:
    - Menyimpan ID section aktif ke URL query (`?sec_id=...`) dan `localStorage`. Saat admin me-refresh halaman, section yang sedang dikelola tidak berpindah kembali ke Bagian 1.

- **Penggantian Navigasi Tab Menjadi Dropdown**:
  - Menghilangkan navigasi horizontal tab per-semester di bagian atas tabel, menggantikannya dengan komponen **Dropdown Tahun / Semester Yudisium** yang terintegrasi di panel filter bersama Program Studi dan Pencarian.
- **Penyajian Data Terisolasi per Tahun Kelulusan (Tanpa Tumpukan Semua Semester)**:
  - Mengubah sistem kueri di `DaftarAlumniController.php` agar secara default langsung memilih tahun/semester kelulusan terbaru (`$daftarSemester->first()`), dan tabel HANYA memuat data alumni pada periode yang dipilih tersebut.
  - Mencegah kelebihan beban data (*information overload*) dengan meniadakan mode tampil semua semester sekaligus.
- **Filter Ketat Status Yudisium Lulus (`proses_yudisium = 'Lulus'`)**: 
  - Backend controller `DaftarAlumniController.php` memfilter data alumni secara eksklusif hanya bagi alumni yang status yudisiumnya telah dinyatakan `'Lulus'` pada relasi `yudisium` (`yudisiums.proses_yudisium = 'Lulus'`) atau `dataAkademik.status_yudisium`.
  - Alumni yang berstatus Belum Yudisium, Proses, atau Tidak Lulus tidak akan ditampilkan dalam direktori kelulusan Biro 3.
- **Standarisasi Menyeluruh Navigasi & Identitas Visual Biro 3**:
  - Menyeragamkan seluruh tata letak bilah navigasi atas (Navbar) di seluruh portal Biro 3 (`/biro3/dashboard`, `/biro3/alumni`, `/biro3/alumni/{id}`, `/biro3/pertanyaan`).
  - Menggunakan navbar putih bersih dengan efek `backdrop-blur`, logo resmi UKDW, teks resmi biro, menu tab konsisten (`Dashboard`, `Data Alumni`, `Kelola Pertanyaan`), dan tombol Keluar abu-abu bersih.
  - Menggunakan tema warna institusi formal: Hijau resmi UKDW (`#005B3C`), Kuning Landing Page (`#FACC15` / `yellow-400`), dan Putih.
  - Membasmi seluruh unsur warna orange (`#D97706`, `#F59E0B`), merah mencolok, dan icon berlebihan (anti AI-slop).
- **Penambahan Informasi Akademik Lengkap**:
  - Menampilkan NIM, Nama, Program Studi, IPK Kelulusan, Judul Tugas Akhir / Skripsi, Status Yudisium (Badge: Lulus), dan aksi detail alumni.
- **Pembaruan Detail Alumni (`AlumniShow.vue`)**:
  - Menyelaraskan navbar atas dan header banner dengan breadcrumb navigasi yang elegan.
  - Menampilkan informasi status kelulusan yudisium dan semester kelulusan akademik secara terintegrasi.

## [2026-09-07] Refinement Kelola Pertanyaan & Panel Biro 3: Desain Ala Profil Alumni, Navigasi Tab Per-Section, & Warna Kuning Landing Page
- **Desain Profesional Ala Profil Alumni**: Merombak antarmuka `Index.vue` kelola pertanyaan dengan estetika formal profesional yang bersih (banner gradient hijau UKDW `#005B3C` ke `#007b55`, kartu kontainer terangkat `rounded-2xl`, tipografi proporsional, dan tombol aksi kuning landing page `#FACC15` / `yellow-400`).
- **Navigasi Section di Atas (Horizontal Tabs)**: Menyediakan tab navigasi bagian/section kuesioner horizontal di bagian atas (seperti navigasi tab di Profil Alumni). Admin dapat memilih bagian (Bagian 1, Bagian 2, dst) secara cepat.
- **Pengelompokan & Filter Soal per Section**: Halaman hanya menampilkan butir pertanyaan dari section yang sedang aktif (tidak menumpuk 20+ soal sekaligus sehingga tidak membuat pusing).
- **Pemindahan Pertanyaan Terisolasi per Section**: Tombol pemindah urutan (▲ / ▼) pada kartu bubble kini dibatasi hanya menukar urutan butir pertanyaan di dalam section yang sama, baik di frontend maupun di kueri backend (`KelolaPertanyaanController.php`).
- **Pembersihan Total Warna Orange & Merah Mencolok**: Menghapus seluruh warna orange (`#D97706`, `#F59E0B`, amber) dan merah mencolok di seluruh panel Biro 3 (`Index.vue`, `Dashboard.vue`, `AlumniIndex.vue`, `AlumniShow.vue`). Menggantinya dengan warna kuning resmi landing page (`#FACC15` / `yellow-400`) dan hijau resmi UKDW.
- **Pembersihan Icon Berlebihan (Anti AI-Slop)**: Menghilangkan emoji berlebihan dan ikon-ikon dekoratif yang tidak perlu, menyajikan tampilan tabel dan formulir yang bersih, elegan, dan berstandar aplikasi perguruan tinggi.

## [2026-09-07] Implementasi Dashboard Resmi & Portal Kendali Biro 3 (Formal, Tegas, Hijau-Kuning-Putih)
- **Halaman Dashboard Utama Biro 3 (`/biro3/dashboard`)**: Membangun portal landing resmi untuk Administrator Biro 3 agar saat login tidak langsung dilempar masuk ke submenu alumni, melainkan disambut dengan ringkasan indikator kinerja utama.
- **Konsep Desain Formal & Tegas**: Menerapkan tema institusional resmi universitas yang tegas dan berwibawa menggunakan warna resmi UKDW (Hijau Tua `#005B3C` / `#004D32`, Kuning Emas `#D97706` / `#F59E0B`, dan Putih Bersih). Menghindari desain kasual/playful bergelombang ala alumni dengan sudut tegas (`rounded`), pembagian panel modular, dan tipografi administratif rapi.
- **Kartu Metrik Eksekutif (KPI)**: Menampilkan 4 kartu indikator utama: Total Basis Data Alumni (beserta jumlah data LinkedIn), Partisipasi Kuesioner (responden terverifikasi & progress bar tingkat respons), Total Instrumen Pertanyaan, serta Cakupan Program Studi Terintegrasi.
- **Dua Modul Gerbang Navigasi Utama**:
  1. *Modul 01 - Basis Data & Direktori Alumni*: Akses langsung ke manajemen alumni, pencarian prodi/NIM, dan sinkronisasi LinkedIn (`/biro3/alumni`).
  2. *Modul 02 - Konfigurasi Instrumen & Jump Logic*: Akses langsung ke pengelolaan pertanyaan kuesioner, opsi jawaban, dan scoping prodi (`/biro3/pertanyaan`).
- **Tabel Pemantauan Partisipasi per Program Studi**: Menyajikan rekapitulasi data kelulusan, jumlah responden kuesioner, tingkat partisipasi persentase dengan progress bar, dan badge status evaluasi capaian per prodi.
## [2026-09-08] Standarisasi Format Data Tabel responses & Sinkronisasi Ekspor
- **Standarisasi Kolom `responses`**:
  - `answer_json`: Dikhususkan menyimpan JSON array murni untuk pertanyaan pilihan ganda (`multiple_choice`/`checkbox`), associative array/object untuk `radio_input` dan `multiple_number`, serta null untuk pertanyaan berjawaban tunggal.
  - `answer_text`: Dikhususkan menyimpan format string/varchar bersih yang siap dibaca dan diekspor (Excel/CSV) tanpa perlu parsing JSON di setiap baris laporan.
- **Dukungan Opsi "Lainnya"**:
  - Jika alumni memilih opsi "Lainnya" dan mengetikkan teks sendiri:
    - Pada pertanyaan `single_choice`/`radio`: tersimpan sebagai varchar rapi `"Lainnya: [isi teks]"` di `answer_text`.
    - Pada pertanyaan `multiple_choice`/`checkbox`: teks dimasukkan ke dalam JSON array di `answer_json` (`["Opsi 1", "Lainnya: [isi teks]"]`) dan digabungkan dengan koma di `answer_text` (`"Opsi 1, Lainnya: [isi teks]"`).
- **Format Khusus `radio_input` & `multiple_number`**:
  - Pertanyaan `radio_input` (seperti F3 dan F5): menghasilkan kalimat utuh di `answer_text` (misal: `"Kira-kira 4 bulan sebelum lulus"`).
  - Pertanyaan `multiple_number` (seperti F13): menghasilkan rincian nominal rupiah terformat dengan label opsi di `answer_text` (misal: `"Dari Pekerjaan Utama: Rp 6.500.000, Dari Lembur dan Tips: Rp 750.000, Dari Pekerjaan Lainnya: Rp 0"`).
- **Sinkronisasi Otomatis Pertanyaan Profil (F1 s/d F2H)**:
  - Pertanyaan F1 s/d F2H yang ditarik dari profil alumni (`data_akademiks`, `alumnis`, `companies`, `atasans`) otomatis tersinkronisasi dan tersimpan di tabel `responses` untuk setiap alumni melalui `KuesionerSyncService`.
  - `QuestionMappingSeeder` diperbarui sehingga saat seeding dijalankan, tabel `responses` langsung terisi lengkap untuk seluruh data alumni.
- **Prefill Dua Arah (*Roundtrip Persistence*)**:
  - `KuesionerController.php` diperbarui agar dapat memecah kembali nilai `"Lainnya: [teks]"` ke state form frontend (`_custom`), memastikan data tidak hilang atau rusak saat halaman direfresh atau dibuka kembali.
- **Isolasi Input Mandiri pada Tipe `radio_input` (F3 & F5)**:
  - Memperbaiki model input pada kartu bertipe `radio_input` agar terikat ke `inputs[opt.id]` per opsi (bukan satu variabel `input` global). Mengeliminasi bug di mana angka yang diketik di opsi "Sebelum lulus" otomatis ikut terisi ke opsi "Sesudah lulus" saat berganti pilihan.
- **Eliminasi Mutlak Nilai NULL pada Tabel `responses`**:
  - `SimpanJawabanController.php` kini menyaring pertanyaan yang belum dijawab / belum sampai di section aktif sehingga tidak ada lagi record kosong (`answer_text: null, answer_json: null`) yang tersimpan ke database.
  - Nilai rincian numerik (F13) yang tidak diisi otomatis terisi angka `0` (bukan `null`).
  - Membersihkan seluruh record dummy yang sebelumnya bernilai null di database sehingga tabel `responses` bersih 100% dari nilai null.

- **Alur Login & Rute Terpadu**:
  - Memperbarui `LoginController.php` agar peran `admin_biro3` otomatis dialihkan ke `/biro3/dashboard`.
  - Mendaftarkan rute `Route::get('/biro3/dashboard', ...)->name('biro3.dashboard')` pada `routes/web.php`.
- **Penyeragaman Navigasi Atas (*Header Bar*)**:
  - Menyeragamkan header institusional di seluruh halaman Biro 3 (`Dashboard.vue`, `AlumniIndex.vue`, `Pertanyaan/Index.vue`, dan `AlumniShow.vue`) dengan navigasi 3 tab: **Dashboard**, **Data Alumni**, dan **Kelola Pertanyaan**.
- **Automated Feature Testing**: Menambahkan berkas pengujian `tests/Feature/BiroTigaDashboardTest.php` untuk memvalidasi proteksi tamu, pengalihan login `admin_biro3`, dan rendering komponen Inertia.

## [2026-09-07] Scoping Pertanyaan Prodi Dinamis & Reseeding Bersih
- **Kolom `prodi_id` pada Tabel `questions`**: Menambahkan kolom `prodi_id` (nullable foreign key ke `prodis.id` dengan `nullOnDelete`) langsung di migrasi utama `create_questions_table.php` dan model `Question.php`.
- **Dukungan Khusus Pertanyaan F2E**: Mengaitkan pertanyaan `F2E` ("Jenis Pekerjaan & Nama Perusahaan / Instansi / Institusi - Gerejawi / Non Gerejawi") secara dinamis ke Program Studi Filsafat Keilahian (kode prodi `31`) di `QuestionSeeder.php`. Pertanyaan umum lainnya tetap bernilai `prodi_id = null`.
- **Filtering Backend Dinamis (Tanpa Hardcode Frontend)**: Menyesuaikan kueri pada `KuesionerController.php` dengan klausul `whereNull('prodi_id')->orWhere('prodi_id', $alumni->prodi_id)`. Alumni non-31 tidak akan menerima pertanyaan F2E, sedangkan alumni prodi 31 menerimanya secara otomatis tanpa modifikasi kondisional di Vue.
- **Integrasi Admin Biro 3**: Menambahkan dropdown target program studi dan visual badge khusus prodi pada panel kelola pertanyaan (`KelolaPertanyaanController.php` & `Index.vue`).
- **Migrate Fresh & Reseed**: Menjalankan `php artisan migrate:fresh --seed` secara bersih dan terverifikasi di seluruh tingkatan data.
- **Komentar & Dokumentasi**: Melengkapi PHPDoc dan komentar arsitektur di Model, Migrasi, Seeder, Controller, dan dokumen README.

## [2026-09-07] Penyempurnaan Tampilan Kuesioner (Tanpa Garis Shadow Tebal Bawah)
- **Restorasi Layout & Estetika**: Mengembalikan layout dan struktur kuesioner yang disukai (background hijau segar `#E8F5E9`, kartu rounded, stepper tahapan, floating round navigation buttons, dan tabel komparasi F17 berdampingan).
- **Penghapusan Garis Shadow Tebal Bawah**: Menghapus seluruh efek bayangan 3D bergaris tebal di bawah (`shadow-[0_8px_0_0_...]`, `shadow-[0_6px_0_0_...]`, `shadow-[0_4px_0_0_...]`) pada banner, kartu soal, pill options, stepper, dan tombol melayang sehingga tampilan bersih dan rapi.
- **Penghapusan Toast Gamifikasi**: Menghilangkan animasi toast pop-up ("HEBAT!", "MANTAP!", dll) saat pergantian bagian agar pengalaman pengisian kuesioner nyaman dan tidak mengganggu.

## [2026-09-07] Refactoring Jump Logic: Unifikasi ke `question_options.jump_to`
- **Drop Kolom `jump_logic`**: Menghapus kolom legacy `jump_logic` (JSON) dari tabel `questions` via migrasi baru `2026_09_07_000001_remove_jump_logic_from_questions_table.php`.
- **Pembersihan Model & Seeder**: Menghapus atribut `jump_logic` dari model `Question.php` dan `QuestionSeeder.php`.
- **Seeder `QuestionOptionSeeder`**: Memastikan seluruh opsi percabangan memiliki atribut `jump_to` yang lengkap dan presisi (F3-03 -> F8, F8-01 -> F11, F8-02 -> F9, F10-01 s/d F10-05 -> F17-1).
- **Refactoring Vue `Kuesioner.vue`**: Menghapus fallback `jump_logic` dan memastikan sistem percabangan pertanyaan murni membaca `jump_to` dari `QuestionOption` secara dinamis tanpa hardcode.
- **Pembaruan Dokumentasi**: Menyesuaikan dokumen `README.md` dan `documentation.txt` dengan arsitektur baru.

## [2026-09-02] Step 5: Kuesioner UI/UX & Google Forms Logic
- **UI/UX Matriks**: Mengubah soal tipe Matriks F17 (Dual) dan F19-F22 (Single) menjadi komponen tabel yang responsif, *sticky column*, dan pewarnaan kontras.
- **Pill Buttons & Checkbox**: Mengganti *radio button* bawaan browser menjadi tombol bergaya *Pill* interaktif.
- **Validasi Input Murni**: Menambahkan event `@keydown` pada semua tipe *number* dan *multiple_number* untuk memblokir input non-numerik murni (misal: memblokir huruf 'e', tanda plus/minus, titik, koma).
- **Google Forms Jumping Logic**: Merombak total arsitektur `QuestionSectionSeeder` dan `QuestionSeeder` untuk mengisolasi pertanyaan dengan efek *jump* (misal: F3) ke dalam 1 halaman *section* sendiri.
- **Fix JSON Bug**: Menghilangkan `json_encode` di Seeder yang menyebabkan *double-encoding* pada atribut *jump_logic*.

## [2026-09-02] Step 4: Core Engine Kuesioner Dinamis
- **Arsitektur DB**: Membuat tabel hierarkis: `questionnaires`, `question_sections`, `questions`, `question_options`, dan `responses`.
- **Relasi Pemetaan Ekstra**: Membuat tabel `question_mappings` untuk mencatat relasi secara eksplisit (beserta *foreign key*) antara pertanyaan di kuesioner dengan kolom di tabel target (seperti kolom identitas di tabel `alumnis`).
- **Seeder Lengkap**: Memetakan seluruh instrumen Kuesioner Tracer Study dari F1 sampai F22 ke dalam format data relasional (termasuk *jump_logic* JSON).
- **Controller**: Membangun `QuestionnaireController` untuk me-*render* kuesioner dinamis secara *nested* ke Vue dan menyimpan respons (baik teks murni maupun JSON untuk *checkbox* / matriks).
- **Middleware Update**: Menambahkan `auth.user` data ke `HandleInertiaRequests` untuk mencegah *white screen crash* pada komponen Vue yang bergantung pada identitas pengguna.

## [2026-09-02] Step 3: Database Refactoring, Models, & UI/UX Enhancements
- **Migrasi**: Menghapus kolom spesifik alumni (`phone`, `address`, `ipk`, `sac_points`) dari tabel `users`.
- **Migrasi Baru**: Membuat tabel `alumnis` yang memuat F1 (NIM), F2A (Nama), F2B (Telepon), F2C (Email), F2D (Alamat Sekarang), `ipk`, dan `sac_points` yang berelasi `One-to-One` dengan `users`.
- **Model**: Menambahkan relasi `hasOne(Alumni::class)` di model `User` dan `belongsTo(User::class)` di model `Alumni`.
- **Seeder**: Memperbarui `DatabaseSeeder` dengan akun alumni percobaan (NIM: 72230607, Password: 01012001) yang wajib mengganti password. Akun admin diset agar tidak wajib ganti password.
- **UI/UX**: Menambahkan transisi, efek *hover*, dan *micro-animations* pada seluruh komponen *Landing Page* (`HeroSection`, `TracerStudySection`, `AlumniCard`, `CTASection`) agar lebih hidup dan interaktif.
- **Komentar Kode**: Memperbarui dokumentasi di setiap fungsi, *routes*, dan *controller*.

## [2026-09-01] Step 2: Foundation & Authentication
- Membuat skema tabel `users` untuk memuat *role* dan *must_change_password*.
- Membuat `DatabaseSeeder` untuk *roles* (admin_biro3, admin_prodi, superadmin).
- Membangun `AuthController` untuk manajemen Login, Logout, dan Penggantian Password (paksa).
- Membangun *Middlewares*: `RoleMiddleware` dan `CheckMustChangePassword`.
- Membuat seluruh kerangka Vue (Landing Page dan Dashboard).
- Memecah *Landing Page* menjadi modular (Navbar, Hero, TracerStudy, Statistics, Alumni, dll).

## [2026-09-01] Step 1: Inisiasi Proyek
- Membuat proyek Laravel 11.
- Mengonfigurasi Tailwind CSS v4, Vue 3, dan Inertia.js.
- Menyusun panduan arsitektur (Backend-Only Logic, UI Components).
