# Dokumentasi Algoritma Rekomendasi Kecocokan Perusahaan (Similarity Matching)

Dokumen ini menjelaskan secara teknis dan konseptual bagaimana sistem pencocokan kemiripan nama perusahaan bekerja pada modul **Verifikasi & Konsolidasi Perusahaan Tracer Study UKDW**.

---

## 1. Menjawab Pertanyaan: "Apakah Menggunakan TRIM atau Apa?"

> **Jawaban Tegas:** **BUKAN sekadar `trim()`!**

- **Fungsi `trim()` bawaan PHP**:
  Hanya bertugas memotong karakter spasi putih (*whitespace*) di awal dan akhir teks mentah (misalnya string `"  PT Telkom  "` menjadi `"PT Telkom"`). Pada controller, `trim()` hanya digunakan untuk merapikan input kotak pencarian (*search bar*).
- **Algoritma yang Sebenarnya Digunakan**:
  Sistem rekomendasi pada panggilan:
  ```php
  $company->recommendations = $this->verificationService->getSimilarRecommendations($company, 5);
  ```
  menggunakan **Algoritma Pembersihan Teks (Text Preprocessing) + Multi-level String Similarity Matching + Pembobotan Lokasi Geografis (Geographic Location Scoring)**.

---

## 2. Letak File & Arsitektur Kode

| Komponen | Path File | Keterangan |
| :--- | :--- | :--- |
| **Domain Service** | [`app/Services/Perusahaan/PerusahaanVerificationService.php`](file:///c:/study/tracerstudy/app/Services/Perusahaan/PerusahaanVerificationService.php) | Berisi inti logika perhitungan kemiripan (`getSimilarRecommendations`), pembersihan teks (`cleanCompanyName`), tokenisasi (`getTokens`), dan pembuatan akronim (`makeAcronym`). |
| **Controller SuperAdmin** | [`app/Http/Controllers/SuperAdmin/Perusahaan/VerifikasiPerusahaanSuperAdminController.php`](file:///c:/study/tracerstudy/app/Http/Controllers/SuperAdmin/Perusahaan/VerifikasiPerusahaanSuperAdminController.php) | Memanggil service untuk melampirkan 5 data rekomendasi per perusahaan pending. |
| **Controller Fakultas** | [`app/Http/Controllers/AdminFakultas/VerifikasiPerusahaan/VerifikasiPerusahaanFakultasController.php`](file:///c:/study/tracerstudy/app/Http/Controllers/AdminFakultas/VerifikasiPerusahaan/VerifikasiPerusahaanFakultasController.php) | Verifikasi lingkup prodi di bawah fakultas terkait. |
| **Controller Prodi** | [`app/Http/Controllers/AdminProdi/VerifikasiPerusahaan/VerifikasiPerusahaanProdiController.php`](file:///c:/study/tracerstudy/app/Http/Controllers/AdminProdi/VerifikasiPerusahaan/VerifikasiPerusahaanProdiController.php) | Verifikasi lingkup prodi pengguna. |
| **Feature Test** | [`tests/Feature/VerifikasiPerusahaanTest.php`](file:///c:/study/tracerstudy/tests/Feature/VerifikasiPerusahaanTest.php) | Unit & Feature testing untuk akurasi rekomendasi dan auto-replace. |

---

## 3. Alur & Tahapan Kerja Algoritma

Algoritma bekerja secara berurutan dalam 5 tahapan utama:

```
[Nama Pengajuan Alumni]
       │
       ▼
1. Preprocessing & Normalisasi Teks (Hapus Simbol & Stopwords Badan Usaha)
       │
       ▼
2. Ekstraksi Token Kata & Pembuatan Akronim Otomatis
       │
       ▼
3. Multi-Level String Matching (Exact ➔ Acronym ➔ Substring ➔ Token Intersect ➔ similar_text)
       │
       ▼
4. Penambahan Bobot Geografis (Kota/Kabupaten: +20, Provinsi: +10)
       │
       ▼
5. Threshold Filtering (Skor >= 45) & Ranking Top-5 Rekomendasi
```

---

### Tahap 1: Text Preprocessing & Stopword Stripping (`cleanCompanyName`)
Sebelum teks dibandingkan, nama perusahaan disterilisasi agar perbandingan fokus pada nama inti entitas:
1. Mengubah seluruh huruf menjadi huruf kecil (*lowercase* via `strtolower`).
2. Menghapus tanda baca, simbol, titik, koma, kurung, garis miring menggunakan Regular Expression:
   ```php
   $clean = preg_replace('/[.,\-_()\[\]\/\\\\]+/', ' ', $lower);
   ```
3. Membuang **Stop Words** (istilah bentuk badan usaha & institusi umum):
   - `'pt'`, `'p.t'`, `'cv'`, `'c.v'`, `'tbk'`, `'persero'`, `'corp'`, `'corporation'`
   - `'inc'`, `'ltd'`, `'co'`, `'company'`, `'indonesia'`, `'group'`, `'holding'`
   - `'perum'`, `'yayasan'`, `'kantor'`, `'dinas'`, `'kementerian'`, `'badan'`, `'lembaga'`, dll.
4. **Contoh Hasil:**
   - `"PT. Bank Central Asia, Tbk"` $\rightarrow$ `"bank central asia"`
   - `"PT Gojek Indonesia"` $\rightarrow$ `"gojek"`

---

### Tahap 2: Ekstraksi Token & Pembuatan Akronim (`getTokens`, `makeAcronym`)

#### A. Apa itu "Token" dan Dari Mana Didapat?
> **Token** adalah istilah teknis pemrograman untuk **satu potong kata** hasil membelah kalimat panjang.

Proses pemotongan teks menjadi token dilakukan oleh fungsi `getTokens()` menggunakan fungsi bawaan PHP:
```php
return preg_split('/\s+/', $clean, -1, PREG_SPLIT_NO_EMPTY) ?: [];
```
* **`$clean`**: String nama yang sudah bersih dari PT/CV (misal: `"bank central asia"`).
* **`/\s+/`**: Pola regex yang berarti **spasi** (satu atau banyak spasi).
* **`preg_split`**: Membelah string setiap kali menemukan spasi menjadi elemen array.
* **Hasil (Array Token):**
  ```php
  $tokens = [
      0 => "bank",      // Token ke-1
      1 => "central",   // Token ke-2
      2 => "asia"       // Token ke-3
  ];
  ```

#### B. Pembuatan Akronim Otomatis (`makeAcronym`)
Fungsi `makeAcronym()` mengambil huruf pertama dari setiap token (`$token[0]`), lalu menggabungkannya:
* Dari `"bank"` diambil `'b'`, dari `"central"` diambil `'c'`, dari `"asia"` diambil `'a'`.
* Menghasilkan string akronim: `"bca"`.

---

### Di Mana Proses Pencocokan (*Matching*) Dilakukan?

Proses matching dilakukan di dalam **perulangan `foreach ($verifiedCompanies as $verified)`**:
Sistem membandingkan data pengajuan alumni (`$pendingCompany`) dengan **setiap** master perusahaan terverifikasi di database (`$verified`):

| Level | Titik Kode Pencocokan | Logika / Fungsi | Contoh Kasus |
| :---: | :--- | :--- | :--- |
| **Level 1** | `$cleanPending === $cleanVerified` | Operator identik string `===` | `"tokopedia"` vs `"tokopedia"` |
| **Level 2** | `strtolower($cleanPending) === strtolower($verifiedAcronym)` | Pencocokan string akronim | `"bca"` vs `"bca"` |
| **Level 3** | `str_contains($cleanVerified, $cleanPending)` | Pengecekan frasa terkandung | `"Gojek"` di dalam `"Gojek Super App"` |
| **Level 4** | `array_intersect($pendingTokens, $verifiedTokens)` | Irisan kata yang sama | `["bank", "mandiri"]` dari kedua nama |
| **Level 5** | `similar_text($cleanPending, $cleanVerified, $percent)` | Persentase kemiripan karakter huruf | `"centrl"` vs `"central"` (Typo 97%) |

---

### Tahap 3: Penilaian Nama Multi-Tingkat (Multi-Level Name Matching)

Sistem menggunakan struktur `if ... elseif ... else` sebagai **hierarki prioritas**. Artinya, sistem akan menguji kecocokan dari yang paling pasti (identik) terlebih dahulu. Jika tidak cocok, baru turun ke tingkat berikutnya hingga ke analisis irisan kata dan typo:

```php
if ($cleanPending !== '' && $cleanPending === $cleanVerified) {
    // LEVEL 1: Exact Match (95 Poin)
} elseif (...) {
    // LEVEL 2: Akronim / Singkatan (88 Poin)
} elseif (...) {
    // LEVEL 3: Substring / Frasa Terkandung (75 - 90 Poin)
} else {
    // LEVEL 4: Irisan Kata (Token Intersect)
    // LEVEL 5: Deteksi Typo (similar_text)
}
```

Berikut rincian logika setiap baris kode:

#### 1. Level 1 - Exact Match (Skor: 95 Poin)
```php
if ($cleanPending !== '' && $cleanPending === $cleanVerified) {
    $nameScore = 95;
    $reasons[] = 'Nama Identik (Tanpa PT/CV)';
}
```
* **Maksud Kode:** Memeriksa apakah setelah semua embel-embel badan usaha dibersihkan, kedua string sama persis 100%.
* **Contoh Kasus:** 
  * Alumni menginput: `"PT. Tokopedia"` $\rightarrow$ nama bersih: `"tokopedia"`.
  * Master database: `"Tokopedia"` $\rightarrow$ nama bersih: `"tokopedia"`.
  * Keduanya identik $\rightarrow$ langsung mendapat skor 95 poin.

#### 2. Level 2 - Akronim / Singkatan Cocok (Skor: 88 Poin)
```php
elseif (
    (! empty($verifiedAcronym) && strtolower($cleanPending) === strtolower($verifiedAcronym)) ||
    (! empty($pendingAcronym) && strtolower($cleanVerified) === strtolower($pendingAcronym)) ||
    (in_array(strtolower($cleanPending), $verifiedTokens, true)) ||
    (in_array(strtolower($cleanVerified), $pendingTokens, true))
) {
    $nameScore = 88;
    $reasons[] = 'Akronim / Singkatan Cocok';
}
```
* **Maksud Kode:** Menguji 4 kemungkinan singkatan:
  1. Input alumni adalah akronim dari master database (misal `"bca"` vs akronim dari `"bank central asia"`).
  2. Master database berupa singkatan dan input alumni nama panjangnya.
  3. Input alumni adalah salah satu kata dalam token master.
  4. Master database adalah salah satu kata dalam token input alumni.
* **Contoh Kasus:** Alumni hanya menulis `"BCA"`, sistem otomatis mencocokkannya dengan `"PT Bank Central Asia, Tbk"` dan memberi skor 88 poin.

#### 3. Level 3 - Substring / Frasa Terkandung (Skor: 75 s/d 90 Poin)
```php
elseif (
    ($cleanPending !== '' && str_contains($cleanVerified, $cleanPending)) ||
    ($cleanVerified !== '' && str_contains($cleanPending, $cleanVerified))
) {
    $minLen = min(strlen($cleanPending), strlen($cleanVerified));
    $maxLen = max(strlen($cleanPending), strlen($cleanVerified));
    $ratio = $maxLen > 0 ? ($minLen / $maxLen) : 0;
    $nameScore = 75 + (int) ($ratio * 15);
    $reasons[] = 'Nama Mengandung Frasa Yang Sama';
}
```
* **Maksud Kode:**
  * `str_contains(...)`: Mengecek apakah nama pengajuan berada di dalam nama master, atau sebaliknya.
  * Skor dasar: **75 poin**.
  * Skor bonus rasio: Dihitung dari perbandingan panjang karakter yang lebih pendek dibagi yang lebih panjang dikalikan 15. Jika panjangnya hampir sama, skor mendekati 90 poin.
* **Contoh Kasus:** Alumni menginput `"Gojek"` (5 huruf), master bertuliskan `"Gojek Super App"` (15 huruf). Karena `"Gojek"` ada di dalamnya, sistem memberikan skor rekomendasi $75 + (5/15 \times 15) = 80$ poin.

#### 4. Level 4 - Irisan Kata / Token Intersect (Skor proporsional maks 75 Poin)
```php
$intersect = array_intersect($pendingTokens, $verifiedTokens);
$overlapCount = count($intersect);
if ($overlapCount > 0) {
    $totalTokens = max(count($pendingTokens), count($verifiedTokens));
    $tokenScore = (int) (($overlapCount / $totalTokens) * 75);
    if ($tokenScore > $nameScore) {
        $nameScore = $tokenScore;
        $reasons[] = "Memiliki {$overlapCount} Kata Yang Sama (".implode(', ', $intersect).')';
    }
}
```
* **Maksud Kode:** Jika nama tidak saling terkandung utuh, sistem memecah nama menjadi kumpulan kata dan mencari kata apa saja yang sama (`array_intersect`).
* **Contoh Kasus:**
  * Pengajuan: `"Bank Mandiri Syariah"` (3 kata).
  * Master: `"Bank Mandiri Taspen"` (3 kata).
  * Kata yang sama: `["bank", "mandiri"]` (2 kata).
  * Skor: $(2 / 3) \times 75 = 50$ poin.

#### 5. Level 5 - Fuzzy Distance / Deteksi Typo (Skor hingga 100 Poin)
```php
similar_text($cleanPending, $cleanVerified, $percent);
if ($percent >= 60 && (int) $percent > $nameScore) {
    $nameScore = (int) $percent;
    $reasons[] = 'Struktur Huruf Mirip ('.round($percent).'%)';
}
```
* **Maksud Kode:**
  * Menggunakan fungsi bawaan PHP `similar_text` yang menghitung persentase kemiripan karakter huruf menggunakan algoritma *Ratcliff/Obershelp*.
  * Jika persentase kemiripan $\ge 60\%$ dan lebih tinggi dari skor irisan kata sebelumnya, skor nama akan dinaikkan ke persentase tersebut.
* **Contoh Kasus Typo:**
  * Pengajuan: `"Bank Centrl Asia"` (typo kurang huruf 'a').
  * Master: `"Bank Central Asia"`.
  * `similar_text` mendeteksi 16 dari 17 karakter cocok, menghasilkan skor **97%**.
  * Alasan yang muncul di antarmuka admin: `"Struktur Huruf Mirip (97%)"`.

---

### Tabel Ringkasan Simulasi Kasus Nyata

| Kasus | Input Alumni | Master Terverifikasi | Level yang Menangani | Alasan (*Reasons*) | Skor Nama |
| :--- | :--- | :--- | :--- | :--- | :---: |
| **Identik** | `PT. Tokopedia` | `Tokopedia` | Level 1 | `Nama Identik (Tanpa PT/CV)` | **95** |
| **Singkatan** | `BCA` | `PT Bank Central Asia` | Level 2 | `Akronim / Singkatan Cocok` | **88** |
| **Frasa** | `Gojek` | `Gojek Super App` | Level 3 | `Nama Mengandung Frasa Yang Sama` | **80** |
| **Irisan Kata** | `Bank Mandiri Syariah` | `Bank Mandiri Taspen` | Level 4 | `Memiliki 2 Kata Yang Sama (bank, mandiri)` | **50** |
| **Typo** | `Bank Centrl Asia` | `Bank Central Asia` | Level 5 | `Struktur Huruf Mirip (97%)` | **97** |

---

### Tahap 4: Pembobotan Lokasi Geografis (Geographic Location Bonus)

Perusahaan dengan nama mirip akan memiliki probabilitas kecocokan yang jauh lebih tinggi jika berdomisili di wilayah yang sama:
- **Kota/Kabupaten Sama** (`kabupaten_id` identik) & Skor Nama $\ge 35$:
  $$\text{totalScore} += 20 \text{ poin}$$
  Alasan: `"Kota/Kabupaten Sama (Nama Kabupaten)"`
- **Provinsi Sama** (`propinsi_id` identik) & Skor Nama $\ge 35$:
  $$\text{totalScore} += 10 \text{ poin}$$
  Alasan: `"Provinsi Sama (Nama Provinsi)"`
- Nilai total dibatasi maksimal 100 poin (`min(100, $totalScore)`).

---

### Tahap 5: Ambang Batas (Threshold) & Perankingan (Ranking)

1. **Filter Ambang Batas**:
   Sebuah perusahaan terverifikasi hanya akan dimasukkan ke daftar saran jika memenuhi salah satu syarat:
   - Skor Total $\ge 45$, **ATAU**
   - Berada di Kota/Kabupaten yang sama dengan Skor Nama $\ge 30$.
2. **Peringkat (Sorting)**:
   Array diurutkan menurun (*descending*) berdasarkan `similarity_score` tertinggi (`usort`).
3. **Limitasi**:
   Hanya diambil sejumlah limit teratas (pada controller diatur `5` rekomendasi terbaik).

---

## 4. Struktur Output Data Rekomendasi

Setiap item rekomendasi menghasilkan payload berikut untuk dirender di antarmuka Vue:

```json
{
  "id": 12,
  "nama_perusahaan": "PT Bank Central Asia Tbk",
  "alamat": "Jl. Sudirman No. 45",
  "propinsi_id": 14,
  "kabupaten_id": 204,
  "nama_provinsi": "D.I. Yogyakarta",
  "nama_kabupaten": "Kota Yogyakarta",
  "sektor": "Perbankan & Keuangan",
  "skala": "Nasional",
  "jenis_perusahaan": "Swasta",
  "similarity_score": 98,
  "is_same_city": true,
  "is_same_province": true,
  "match_reasons": [
    "Akronim / Singkatan Cocok",
    "Kota/Kabupaten Sama (Kota Yogyakarta)",
    "Provinsi Sama (D.I. Yogyakarta)"
  ]
}
```

---

## 5. Hubungan dengan Fitur Auto-Replace Perusahaan

Ketika SuperAdmin, Admin Fakultas, atau Admin Prodi melihat rekomendasi ini di tabel verifikasi:
1. Admin dapat mengklik tombol **"Gunakan Data Ini (Auto Replace)"**.
2. Endpoint `/superadmin/perusahaan/{pendingCompany}/replace` akan dijalankan.
3. Melalui `PerusahaanVerificationService::replaceCompany()`:
   - Seluruh alumni yang terhubung dengan record `pendingCompany` dialihkan otomatis ke master `verifiedCompany`.
   - Record duplikat `pendingCompany` dihapus dari database.
   - Tindakan ini tercatat lengkap pada **Audit Trail (`log_activities`)**.

---

## 6. Penjelasan Lengkap Service (`PerusahaanVerificationService.php`)

Service ini beralamat di [`app/Services/Perusahaan/PerusahaanVerificationService.php`](file:///c:/study/tracerstudy/app/Services/Perusahaan/PerusahaanVerificationService.php) dan didaftarkan via *Dependency Injection* ke Controller:

```php
public function __construct(
    protected PerusahaanVerificationService $verificationService
) {}
```

### Kamus Method Utama dalam Service:

1. **`getPendingForProdi(int $prodiId, array $filters = []): LengthAwarePaginator`**
   - Mengambil data pengajuan perusahaan yang berelasi dengan prodi tertentu (berdasarkan `created_by_prodi_id`, biodata alumni, atau pembuat data).
   - Mendukung filter status (`all`, `Menunggu Verifikasi`, `Terverifikasi`, `Ditolak`) dan pencarian keyword (nama PT, alamat, alumni, NIM).

2. **`getPendingForFakultas(int $fakultasId, array $filters = []): LengthAwarePaginator`**
   - Mengambil data pengajuan perusahaan di seluruh program studi di bawah naungan fakultas terkait (`whereIn('prodi_id', $prodiIds)`).

3. **`getSimilarRecommendations(Perusahaan $pendingCompany, int $limit = 6): array`**
   - **Inti Algoritma Pencocokan**: Membandingkan perusahaan pending dengan seluruh master perusahaan terverifikasi.
   - Menghasilkan daftar array rekomendasi yang sudah dihitung skornya, diberi label alasan kecocokan, dan diurutkan secara descending.

4. **`searchVerified(string $keyword, int $limit = 10): Collection`**
   - Endpoint pencarian fleksibel untuk autocomplete/dropdown saat admin ingin mencari manual perusahaan terverifikasi di luar rekomendasi otomatis.

5. **`replaceCompany(Perusahaan $pendingCompany, Perusahaan $verifiedCompany): array`**
   - Memindahkan seluruh relasi foreign key `biodata.perusahaan_id` dari perusahaan pending ke master terverifikasi di dalam `DB::transaction`.
   - Menghapus record pending yang redundan.
   - Mencatat log aktivitas ke tabel `log_activities`.

6. **`verifyDirectly(Perusahaan $company): bool`**
   - Menyetujui langsung perusahaan baru menjadi master berstatus `Terverifikasi` tanpa penggabungan (jika perusahaan memang benar-benar baru).

7. **`updateAndVerify(Perusahaan $company, array $validatedData, bool $verifyNow = true): Perusahaan`**
   - Mengizinkan admin memperbaiki salah ketik/typo alamat/nama dari pengajuan alumni, lalu menyetujuinya sekaligus.

8. **`rejectCompany(Perusahaan $company, ?string $alasan = null): bool`**
   - Menolak pengajuan perusahaan yang tidak valid atau fiktif.

---

## 7. Penjelasan Lengkap Controllers yang Menggunakan Service

Terdapat 3 Controller dengan tingkatan hak akses berbeda yang mengonsumsi service ini:

### A. SuperAdmin: `VerifikasiPerusahaanSuperAdminController`
* **File:** [`app/Http/Controllers/SuperAdmin/Perusahaan/VerifikasiPerusahaanSuperAdminController.php`](file:///c:/study/tracerstudy/app/Http/Controllers/SuperAdmin/Perusahaan/VerifikasiPerusahaanSuperAdminController.php)
* **Hak Akses:** Otoritas tertinggi universitas (`role: superadmin`).
* **Fitur:**
  * Dapat melihat pengajuan seluruh prodi (`scope: all`) atau menyaring per prodi tertentu.
  * Menjalankan transformasi paginasi untuk melampirkan 5 rekomendasi:
    ```php
    $companies->getCollection()->transform(function ($company) {
        $company->recommendations = $this->verificationService->getSimilarRecommendations($company, 5);
        return $company;
    });
    ```
* **Daftar Endpoint/Action:**
  * `GET  /superadmin/perusahaan` $\rightarrow$ Tampilan tabel verifikasi.
  * `POST /superadmin/perusahaan/{company}/verify` $\rightarrow$ Approve langsung.
  * `POST /superadmin/perusahaan/{company}/replace` $\rightarrow$ Konsolidasi ke master terverifikasi.
  * `PUT  /superadmin/perusahaan/{company}` $\rightarrow$ Edit & verifikasi data.
  * `POST /superadmin/perusahaan/{company}/reject` $\rightarrow$ Tolak pengajuan.

### B. Admin Fakultas: `VerifikasiPerusahaanFakultasController`
* **File:** [`app/Http/Controllers/AdminFakultas/VerifikasiPerusahaan/VerifikasiPerusahaanFakultasController.php`](file:///c:/study/tracerstudy/app/Http/Controllers/AdminFakultas/VerifikasiPerusahaan/VerifikasiPerusahaanFakultasController.php)
* **Hak Akses:** Admin di tingkat fakultas (`role: admin_fakultas`).
* **Fitur:**
  * Otomatis terfilter hanya untuk pengajuan perusahaan dari prodi-prodi dalam fakultas yang bersangkutan.
  * Menyajikan data rekomendasi kemiripan yang sama agar data di tingkat fakultas tetap terstandardisasi.

### C. Admin Program Studi: `VerifikasiPerusahaanProdiController`
* **File:** [`app/Http/Controllers/AdminProdi/VerifikasiPerusahaan/VerifikasiPerusahaanProdiController.php`](file:///c:/study/tracerstudy/app/Http/Controllers/AdminProdi/VerifikasiPerusahaan/VerifikasiPerusahaanProdiController.php)
* **Hak Akses:** Admin program studi masing-masing (`role: admin_prodi`).
* **Fitur:**
  * Memverifikasi pengajuan tempat kerja dari alumni lulusan prodinya sendiri.
  * Memanfaatkan rekomendasi sistem untuk menyatukan input alumni dengan master data perusahaan nasional.

