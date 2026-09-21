# Update Log Tracer Study

## 21 September 2026
- **Fix (Tombol Tambah & Edit Pertanyaan)**: Memperbaiki referensi variabel `initialKeterangan` pada modal SweetAlert2 di `SuperAdmin/Pertanyaan/Index.vue` serta mengisi form value secara aman via `didOpen` agar tombol **Tambah Pertanyaan** dan tombol pensil **Edit** dapat dibuka dengan normal tanpa error JavaScript.
- **Backend (Validasi Tipe & Keterangan)**: Menambahkan tipe `radio`, `checkbox`, dan field `keterangan` (batasan nilai/constraint) ke dalam validasi `SimpanPertanyaanController.php` pada method `store` dan `update`.
- **UI/UX (Eliminasi Duplikasi Tombol Tambah Opsi)**: Menghapus kontainer duplikat tombol tambah opsi pada baris pertanyaan, memusatkan penambahan opsi pada 1 tombol saja yaitu ikon `+` di kolom Aksi.
- **Feature (Restriksi Opsi Berdasarkan Tipe Soal)**: Membatasi tombol tambah opsi dan kartu opsi hanya untuk 9 tipe yang mendukung opsi (`radio`, `radio_input`, `radio_text`, `multiple_choice`, `rating_5`, `multiple_number`, `matrix`, `matrix_dual`, `multiple_textbox`), serta membersihkan tipe duplikat/tidak terpakai.
- **UI/UX (Cascading Jump Logic)**: Mengubah dropdown alur lompatan (*jump logic*) menjadi 2 langkah bertingkat (Langkah 1: Pilih Section, Langkah 2: Pilih Pertanyaan Target) pada Super Admin dan Admin Biro 3 sehingga tidak menampilkan daftar panjang 70+ soal sekaligus.
- **Database & Seeding (Gaji F505)**: Menyederhanakan opsi pendapatan per bulan (*Take Home Pay* / F505) menjadi 1 opsi tunggal (`F5051`), membersihkan opsi lama `F5052` dan `F5053`, serta menambahkan catatan batasan/constraint minimal Rp 1.000 (kelipatan ribuan).
- **UI/UX (Kuning Khusus Header)**: Menandai butir kuesioner tipe `header` dengan warna kuning lembut (`bg-[#FEF9C3]`), aksen border kuning (`border-l-[#EAB308]`), dan badge kuning *Header Kuesioner*.
- **UI/UX (Super Admin Table Redesign)**: Mengubah tampilan **Kelola Section** dan **Kelola Pertanyaan** menjadi format data table modern, bersih, minimalis, dan elegan dengan tombol aksi berbasis ikon SVG bersih (Detail/Mata, Edit/Pensil, Kelola Opsi, Hapus/Sampah, Reorder Naik/Turun).
- **Feature (Dropdown Filter Section & Target)**: Menampilkan seluruh soal (**ALL**) secara *default* dengan dropdown filter section, filter lokasi tampil, serta pencarian teks terintegrasi.
- **Database & Architecture (`tampil_di`)**: Menambahkan kolom `tampil_di` (`kuesioner`, `profile`, `both`) pada tabel `ref_subpertanyaan2021` agar visibilitas butir soal antara kuesioner universitas dan halaman profil alumni dapat dikonfigurasi dinamis langsung dari Super Admin.
- **UI/UX (Super Admin Sidebar)**: Membersihkan pintasan publik (*Lihat Beranda Publik*) dari sidebar SuperAdmin untuk navigasi yang lebih terfokus dan rapi.
- **Testing**: Penambahan automated feature tests `SuperAdminKelolaPertanyaanTest` (59 tests passed).

## 20 September 2026
- **UI/UX (Super Admin Sidebar)**: Mengubah sistem navigasi modul Super Admin dari navigasi atas (*top bar*) menjadi navigasi **Sidebar tetap** (`Sidebar.vue`) di sisi kiri (`w-72`) dengan drawer mobile dan tautan menu terpusat.
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
