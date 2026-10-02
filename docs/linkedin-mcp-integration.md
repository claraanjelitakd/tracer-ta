# Arsitektur & Panduan Sinkronisasi LinkedIn (Driver-Based Resolution)

Dokumen ini menjelaskan arsitektur resmi, konfigurasi driver, alur kerja, dan penanganan data untuk fitur **Sinkronisasi Profil LinkedIn** pada aplikasi Tracer Study Alumni UKDW (SERU).

---

## 1. Ringkasan & Filosofi Desain

Sistem SERU mengadopsi pola **Driver-Based Resolution Pattern** (mirip dengan driver Mail, Cache, atau Session di Laravel).
- **Zero-Scraping Policy**: Tidak lagi menggunakan scraping browser lokal, headless Chrome, patchright, maupun script wrapper Python yang tidak stabil dan melanggar ToS LinkedIn.
- **Environment Driven**: Sumber data LinkedIn ditentukan sepenuhnya oleh variabel lingkungan `LINKEDIN_DRIVER` di `.env` tanpa mengubah satu baris pun kode logika bisnis controller, DTO, mapper, basis data, atau UI Vue.
- **Zero File Deletion Policy**: Ketika berpindah dari driver `mock` ke driver API resmi (`api`), struktur file DTOs, Contracts, Exceptions, dan Mappers **TETAP UTUH** dan tidak perlu dihapus.

---

## 2. Peta Struktur File & Arsitektur

```text
app/Services/LinkedIn/
├── Contracts/
│   └── LinkedInProfileProvider.php         # Interface penyedia data profil (fetchProfileByUsername, fetchProfileByUrl)
├── DTOs/
│   ├── LinkedInEducation.php               # DTO riwayat pendidikan
│   ├── LinkedInExperience.php              # DTO riwayat pekerjaan/posisi
│   └── LinkedInProfile.php                 # DTO agregat lengkap data profil
├── Exceptions/
│   ├── LinkedInApiException.php            # Exception saat error komunikasi API
│   └── LinkedInProfileNotFoundException.php# Exception saat profil tidak ditemukan (404)
├── Mappers/
│   └── LinkedInProfileMapper.php           # Pemetaan DTO ke model Biodata & Perusahaan (4 aturan posisi aktif)
├── Providers/
│   ├── ApiLinkedInProvider.php             # Provider API resmi LinkedIn (Server-to-Server)
│   └── MockLinkedInProvider.php            # Provider Mock data lokal (Development & Testing)
└── LinkedInProfileService.php              # Service utama orkestrasi, transaksi database, & audit trail
```

---

## 3. Konfigurasi Driver (`.env`)

Konfigurasi diatur dalam file `.env` dan dibaca melalui `config/linkedin.php`:

```env
# Mode Mock (Pengembangan & Pengujian)
LINKEDIN_DRIVER=mock
LINKEDIN_MOCK_PATH=storage/app/mock/linkedin
LINKEDIN_API_BASE_URL=
LINKEDIN_API_KEY=

# Mode API Resmi (Produksi Mendatang)
# LINKEDIN_DRIVER=api
# LINKEDIN_API_BASE_URL=https://api.linkedin.com/v2
# LINKEDIN_API_KEY=rahasia_bearer_token_resmi
```

### Resolusi Driver di `AppServiceProvider`
Binding interface [`LinkedInProfileProvider`](file:///c:/study/tracerstudy/app/Services/LinkedIn/Contracts/LinkedInProfileProvider.php) dilakukan secara otomatis di [`AppServiceProvider.php`](file:///c:/study/tracerstudy/app/Providers/AppServiceProvider.php):

```php
$this->app->bind(LinkedInProfileProvider::class, function () {
    $driver = config('linkedin.driver', 'mock');

    return match ($driver) {
        'mock' => new MockLinkedInProvider(
            config('linkedin.mock_path', storage_path('app/mock/linkedin'))
        ),
        'api' => new ApiLinkedInProvider(
            config('linkedin.api_base_url'),
            config('linkedin.api_key')
        ),
        default => throw new \InvalidArgumentException("Driver LinkedIn tidak didukung: {$driver}"),
    };
});
```

---

## 4. Alur Kerja Sinkronisasi

### A. Alur SuperAdmin (Single & Bulk Sync)
1. **Navigasi**: Superadmin membuka menu **Sinkronisasi LinkedIn** (`/superadmin/linkedin-sync`).
2. **Filter & Analitik**: Memilih tahun kelulusan resmi alumni (berasal dari relasi `Yudisium` dan `DataAkademik`).
3. **Aksi Single Sync**: Menekan tombol petir pada alumni tertentu. Permintaan dieksekusi secara asinkron (AJAX POST) tanpa full page reload.
4. **Aksi Bulk Sync**: Menekan tombol **Sinkronkan Massal** per angkatan. Progress bar live menampilkan progres bertahap, dan popup SweetAlert2 merangkum hasil sinkronisasi (*Berhasil*, *Gagal*, *Dilewati*).

### B. Alur Admin Biro 3 (Single Preview Sync)
1. Biro 3 membuka detail alumni pada menu **Kelola Alumni**.
2. Tombol **Sinkronkan LinkedIn** memanggil [`SinkronisasiLinkedinController.php`](file:///c:/study/tracerstudy/app/Http/Controllers/AdminBiroTiga/KelolaAlumni/SinkronisasiLinkedinController.php) yang kini murni menggunakan [`LinkedInProfileService.php`](file:///c:/study/tracerstudy/app/Services/LinkedIn/LinkedInProfileService.php).

---

## 5. Integritas Data & Kebijakan Verifikasi Mutlak

1. **Aturan Penentuan Posisi Aktif (*Current Position*)**:
   [`LinkedInProfileMapper`](file:///c:/study/tracerstudy/app/Services/LinkedIn/Mappers/LinkedInProfileMapper.php) menerapkan 4 aturan prioritas berurutan:
   - Posisi dengan `endMonthYear == null` (sedang menjabat).
   - Jika terdapat lebih dari 1 posisi aktif, ambil posisi dengan `startMonthYear` paling baru.
   - Jika semua posisi telah berakhir, ambil riwayat pengalaman paling pertama dalam daftar.
   - Jika tidak ada riwayat pengalaman, gunakan headline profil.

2. **Kebijakan Verifikasi Perusahaan**:
   - Jika institusi perusahaan belum ada di basis data master, sistem secara otomatis mendaftarkan entitas perusahaan baru.
   - **MUTLAK**: Status perusahaan baru SELALU disetel ke:
     ```php
     'status_verifikasi' => 'Menunggu Verifikasi'
     ```
     Hal ini mewajibkan admin memverifikasi institusi tersebut sebelum diakui sebagai Master Perusahaan resmi UKDW.

3. **Perlindungan Data Otoritatif**:
   Data primer kependudukan dan akademik (`NIM`, `NIK`, `NPWP`, `email_pribadi`, `nomor_telepon`, `alamat_saat_ini`, `tanggal_lulus`, IPK) **TIDAK PERNAH DITIMPA** oleh data LinkedIn. Hanya kolom karier (`posisi_jabatan`, `perusahaan_id`) yang diperbarui.

4. **Audit Trail Otomatis**:
   Setiap sinkronisasi dicatat ke tabel `log_activities` melalui `LogActivity::record()` dengan aksi `'linkedin_sync'`, merekam pelaku, biodata target, dan ringkasan data baru tanpa mengekspos kredensial API.

---

## 6. Riwayat Perubahan & Migrasi dari Script MCP Lama

| Komponen Lama | Komponen Baru (SERU Production) | Keuntungan |
| :--- | :--- | :--- |
| `scripts/linkedin_mcp_client.py` (Python scraper) | **Dihapus** | Tidak ada dependensi Python / browser di server |
| `LinkedInService.php` (Subprocess shell caller) | [`LinkedInProfileService.php`](file:///c:/study/tracerstudy/app/Services/LinkedIn/LinkedInProfileService.php) | Menggunakan PHP native, dependency injection, DTO, dan arsitektur bersih |
| Single Sync Only | Single Sync & Bulk Batch Sync | Produktivitas pengelolaan data alumni meningkat drastis |
| Scraping manual rentan blokir | Mock Driver + Official API Driver Ready | 100% patuh aturan keamanan dan reliabilitas produksi |
