# User Flow Sistem Tracer Study per Peran Pengguna

Dokumen ini berisi alur pengguna (*User Flow*) lengkap untuk setiap peran (*user role*) yang terdapat pada Sistem **Tracer Study**.

---

## Daftar Peran Pengguna (User Roles)

1. [1. User Flow: Alumni / Lulusan](#1-user-flow-alumni--lulusan)
2. [2. User Flow: Admin Program Studi (Admin Prodi)](#2-user-flow-admin-program-studi-admin-prodi)
3. [3. User Flow: Admin Fakultas](#3-user-flow-admin-fakultas)
4. [4. User Flow: Admin Biro III / Kemahasiswaan & Alumni](#4-user-flow-admin-biro-iii--kemahasiswaan--alumni)
5. [5. User Flow: Superadmin](#5-user-flow-superadmin)
6. [6. User Flow: Publik / Anonim](#6-user-flow-publik--anonim)

---

## 1. User Flow: Alumni / Lulusan

Alumni adalah pengguna utama pengisi kuesioner tracer study, pembaru data akademik/pekerjaan, serta pengguna fitur integrasi seperti LinkedIn.

### Diagram Mermaid: Alumni Flow

```mermaid
flowchart TD
    A[Mulai: Akses Halaman Utama] --> B{Sudah Login?}
    B -- Belum --> C[Halaman Login]
    C --> D{Metode Login}
    D -- NIM/SSO -- E[Otentikasi Akun Alumni]
    D -- LinkedIn OAuth -- F[Otentikasi via LinkedIn]
    E --> G[Dashboard Alumni]
    F --> G

    G --> H[Check Status Pengisian Kuesioner]
    H --> I{Kuesioner Selesai?}
    I -- Belum --> J[Pengisian Kuesioner Wajib Kemdikbud / Diktiristek]
    J --> K[Pengisian Kuesioner Spesifik Prodi]
    K --> L[Simpan & Submit Kuesioner]
    L --> M[Update Status Kuesioner: Completed]

    I -- Sudah --> N[Lihat Ringkasan & Hasil Kuesioner]

    G --> O[Fitur Tambahan Alumni]
    O --> P[Update Profil & Riwayat Pekerjaan]
    O --> Q[Sinkronisasi LinkedIn Profile / MCP]
    O --> R[Lihat Rekomendasi Karir / Lowongan]
    O --> S[Unduh Bukti Pengisian Kuesioner]

    M --> N
    P --> T[Selesai / Logout]
    Q --> T
    R --> T
    S --> T
    N --> T
```

### Rincian Alur Alumni:
1. **Otentikasi & Akses**: Alumni melakukan login menggunakan NIM/Password atau akun LinkedIn (OAuth).
2. **Pengisian Kuesioner Utama**:
   - Sistem memeriksa kelayakan pengisian berdasarkan tahun kelulusan/yudisium.
   - Pengisian kuesioner standar Kemendikbudristek (status pekerjaan, pendapatan, jeda cari kerja, relevansi kurikulum).
   - Pengisian pertanyaan tambahan khusus Program Studi.
3. **Manajemen Profil & LinkedIn**:
   - Memperbarui data biodata, kontak, dan riwayat pekerjaan terkini.
   - Melakukan sinkronisasi data dari LinkedIn.
4. **Output & Bukti**:
   - Mengunduh sertifikat/bukti pengisian kuesioner.

---

## 2. User Flow: Admin Program Studi (Admin Prodi)

Admin Prodi berfokus pada pemantauan pengisian tracer study alumni di tingkat prodi, validasi data, serta analisis statistik prodi.

### Diagram Mermaid: Admin Prodi Flow

```mermaid
flowchart TD
    A[Mulai: Login Admin Prodi] --> B[Dashboard Prodi]
    B --> C{Pilih Menu}

    C --> D[Monitoring Response Rate Alumni Prodi]
    D --> D1[Filter berdasarkan Tahun Lulus & Angkatan]
    D1 --> D2[Kirim Pengingat / Remind WhatsApp / Email ke Alumni]

    C --> E[Validasi & Verifikasi Data Alumni]
    E --> E1[Cek Data Keselarasan Horisontal & Vertikal]
    E1 --> E2[Verifikasi Bukti Status Pekerjaan]

    C --> F[Kelola Kuesioner Spesifik Prodi]
    F --> F1[Tambah / Edit Pertanyaan Tambahan Prodi]

    C --> G[Laporan & Eksport Data Prodi]
    G --> G1[Export Data Excel / CSV format PDDikti & Accreditation]
    G1 --> G2[Generate Grafik Akreditasi BAN-PT / LAM]

    D2 --> H[Selesai]
    E2 --> H
    F1 --> H
    G2 --> H
```

### Rincian Alur Admin Prodi:
1. **Dashboard & Monitoring**: Melihat persentase partisipasi alumni (*Response Rate*) pada prodi yang dikelola.
2. **Blasting & Reminder**: Mengirimkan notifikasi pengingat ke alumni yang belum mengisi kuesioner.
3. **Verifikasi Data**: Memeriksa validitas data pekerjaan dan gaji alumni untuk keperluan borang akreditasi prodi.
4. **Custom Questions**: Mengelola kuesioner internal khusus prodi.
5. **Reporting**: Menghasilkan file ekspor data standar borang akreditasi.

---

## 3. User Flow: Admin Fakultas

Admin Fakultas bertugas memantau kinerja tracer study lintas program studi di lingkungan fakultas.

### Diagram Mermaid: Admin Fakultas Flow

```mermaid
flowchart TD
    A[Mulai: Login Admin Fakultas] --> B[Dashboard Fakultas]
    B --> C{Pilih Akses Menu}

    C --> D[Overview Statistik Lintas Prodi]
    D --> D1[Komparasi Response Rate antar Prodi]
    D1 --> D2[Analisis Rata-rata Gaji & Masa Tunggu per Prodi]

    C --> E[Pengawasan & Validasi Data Fakultas]
    E --> E1[Audit Sample Data Alumni Fakultas]

    C --> F[Pusat Laporan Eksekutif Fakultas]
    F --> F1[Export Rekap Fakultas untuk Dekanat]

    D2 --> G[Selesai]
    E1 --> G
    F1 --> G
```

---

## 4. User Flow: Admin Biro III / Kemahasiswaan & Alumni

Admin Biro III bertanggung jawab mengelola tracer study secara keseluruhan di tingkat universitas, integrasi ke PDDikti/Kemendikbud, serta administrasi tingkat pusat.

### Diagram Mermaid: Admin Biro III Flow

```mermaid
flowchart TD
    A[Mulai: Login Admin Biro III] --> B[Dashboard Universitas]
    B --> C{Menu Operasional}

    C --> D[Pengaturan Periode & Wave Tracer Study]
    D --> D1[Buka / Tutup Periode Tracer Study]

    C --> E[Integrasi & Sync Data Kemendikbud / PDDikti]
    E --> E1[Export Format DIKTI]
    E1 --> E2[Validasi NIK, NIM, & Kode PT/Prodi]

    C --> F[Master Data Alumni & Yudisium]
    F --> F1[Import Data Kelulusan / Yudisium Terbaru]

    C --> G[Pesan Broadcast & Notification Center]
    G --> G1[Kirim Email / WA Blast Massal ke Alumni]

    D1 --> H[Selesai]
    E2 --> H
    F1 --> H
    G1 --> H
```

---

## 5. User Flow: Superadmin

Superadmin mengelola konfigurasi sistem, peran & hak akses pengguna, log aktivitas, serta infrastruktur aplikasi.

### Diagram Mermaid: Superadmin Flow

```mermaid
flowchart TD
    A[Mulai: Login Superadmin] --> B[Dashboard System Admin]
    B --> C{Manajemen Sistem}

    C --> D[User & Role Management]
    D --> D1[Kelola Akun Admin Prodi, Fakultas, Biro III]
    D1 --> D2[Atur Permission & Akses Role]

    C --> E[System Configuration & Master Reference]
    E --> E1[Kelola Kategori Pertanyaan & Opsi Jawab Standard]

    C --> F[Audit Log & Security Monitoring]
    F --> F1[Cek System Logs & Integrasi OAuth / LinkedIn]

    C --> G[Database Backup & System Maintenance]

    D2 --> H[Selesai]
    E1 --> H
    F1 --> H
    G --> H
```

---

## 6. User Flow: Publik / Anonim

Pengunjung publik (calon mahasiswa, stakeholder, atau pengguna umum) dapat mengakses statistik publik tanpa perlu login.

### Diagram Mermaid: Publik Flow

```mermaid
flowchart TD
    A[Landing Page Tracer Study] --> B{Pilih Informasi}
    B --> C[Lihat Portal Statistik Publik]
    C --> C1[Visualisasi Tingkat Penyerapan Kerja]
    C --> C2[Visualisasi Sebaran Bidang Kerja Alumni]
    B --> D[Halaman Informasi & FAQ]
    B --> E[Akses Login System]

    C1 --> F[Selesai]
    C2 --> F
    D --> F
    E --> G[Masuk ke Flow Otentikasi]
```

---

## 7. User Flow: Atasan Langsung / Pengguna Lulusan (Public Token-Based Evaluation Flow)

Pimpinan/atasan langsung di perusahaan tempat alumni bekerja dapat memberikan evaluasi kepuasan dan penilaian kinerja lulusan secara aman melalui tautan publik ber-token unik tanpa perlu registrasi atau login.

### Diagram Mermaid: Evaluasi Atasan Flow

```mermaid
flowchart TD
    A[Alumni Mengisi Data Atasan di Profil Karier] --> B[Sistem Kirim Email Undangan Resmi ke Atasan]
    B --> C[Atasan Menerima Email dengan Tautan Unik Token]
    C --> D[Atasan Klik Tautan: /evaluasi-atasan/token]
    D --> E{Validasi Token Sistem}
    E -- Token Tidak Valid/Kedaluwarsa --> F[Halaman 404 / Tautan Tidak Ditemukan]
    E -- Token Valid --> G[Buka Formulir Evaluasi Full Page]
    
    G --> H[Periksa Data Identitas Alumni Yang Dinilai]
    G --> I[Bagian I: Lengkapi/Perbarui Profil Instansi & Kontak Atasan]
    I --> J[Tersimpan Otomatis ke Master Tabel Perusahaan & Atasan]
    
    G --> K[Bagian II: Evaluasi Kesiapan & Kinerja Lulusan]
    K --> K1[Nilai Tingkat Kesiapan Alumni dalam Bekerja]
    K --> K2[Nilai 12 Aspek Kinerja Lulusan pada Matriks Likert]
    K --> K3[Tuliskan Catatan Tambahan / Saran Khusus Opsional]
    
    K1 & K2 & K3 --> L[Klik Tombol: Kirim Respon Evaluasi Kinerja]
    L --> M[Sistem Validasi Kelengkapan Jawaban]
    M -- Ada Butir Terlewat --> N[SweetAlert2: Peringatan Aspek Belum Lengkap]
    N --> K
    M -- Lengkap --> O[Simpan Jawaban ke respon_evaluasi_atasan]
    O --> P[Update evaluasi_atasan: is_submitted = true, submitted_at = now]
    P --> Q[Tampilkan Status Berhasil & Kartu Ucapan Terima Kasih Resmi]
    Q --> R[Selesai]
```

### Rincian Alur Atasan:
1. **Pemicu Undangan**: Setiap kali alumni menyimpan data riwayat pekerjaan yang mencakup nama dan email atasan langsung di halaman profil, sistem secara otomatis menerbitkan record `evaluasi_atasan` dengan token 64 karakter unik dan mengirimkan surat undangan evaluasi resmi via email.
2. **Akses Formulir Tanpa Hambatan**: Atasan membuka tautan langsung dari email (`/evaluasi-atasan/{token}`). Tidak dibutuhkan proses registrasi atau login akun.
3. **Penyempurnaan Profil Instansi (Bagian I)**: Atasan dapat memverifikasi atau melengkapi nama instansi, alamat, no telp/fax, website, bentuk perusahaan, skala usaha, jumlah pegawai, jumlah alumni UKDW, dan standar gaji pertama. Data perusahaan dan atasan disinkronkan ke tabel master `perusahaan` dan `atasan`.
4. **Penilaian Kesiapan & Kinerja Dinamis (Bagian II)**:
   - Menilai tingkat kesiapan alumni dalam bekerja (pilihan ganda).
   - Menilai 12 aspek kinerja utama (Integritas, Keahlian Ilmu, Komunikasi, Kerjasama Tim, Pengembangan Diri, Kreativitas, Bahasa Asing, Penggunaan Teknologi, Manajerial, Analisis, Laporan, Inovasi) pada tabel ber-header lengket (*sticky header*) yang tetap memuat nama alumni saat discroll.
   - Menyampaikan saran/masukan konstruktif untuk universitas.
5. **Konfirmasi & Audit**: Respon tersimpan ke tabel `respon_evaluasi_atasan` dan status berubah menjadi *"Sudah Selesai Diisi"*.

---

## Ringkasan Matriks Akses Peran (RBAC Matrix)

| Fitur / Modul | Alumni | Admin Prodi | Admin Fakultas | Admin Biro III | Superadmin | Atasan (Token) | Publik |
| :--- | :---: | :---: | :---: | :---: | :---: | :---: | :---: |
| **Landing Page & Stats Publik** | ✅ | ✅ | ✅ | ✅ | ✅ | ❌ | ✅ |
| **Isi Kuesioner Alumni** | ✅ | ❌ | ❌ | ❌ | ❌ | ❌ | ❌ |
| **Update Profile & Data Atasan** | ✅ | ❌ | ❌ | ❌ | ❌ | ❌ | ❌ |
| **Isi Form Evaluasi Kinerja (Token)** | ❌ | ❌ | ❌ | ❌ | ❌ | ✅ | ❌ |
| **Monitoring Response Rate Prodi**| ❌ | ✅ | ✅ | ✅ | ✅ | ❌ | ❌ |
| **Audit & Kelola Evaluasi Atasan** | ❌ | ❌ | ❌ | ❌ | ✅ | ❌ | ❌ |
| **Validasi & Borang Akreditasi** | ❌ | ✅ | ✅ | ✅ | ✅ | ❌ | ❌ |
| **Pengaturan Wave / Periode** | ❌ | ❌ | ❌ | ✅ | ✅ | ❌ | ❌ |
| **Export Data Format DIKTI** | ❌ | ❌ | ❌ | ✅ | ✅ | ❌ | ❌ |
| **User & Role Management** | ❌ | ❌ | ❌ | ❌ | ✅ | ❌ | ❌ |

---
*Dokumen ini dibuat untuk referensi alur pengguna (UX) Sistem Tracer Study.*
