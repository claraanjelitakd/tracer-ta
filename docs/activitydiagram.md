# Dokumentasi UML Activity Diagram Sistem Tracer Study UKDW

Dokumen ini memuat spesifikasi dan pemodelan **UML Activity Diagram** untuk 3 (tiga) proses unik pada sistem Tracer Study Universitas Kristen Duta Wacana (UKDW), yaitu:
1. **Sinkronisasi Profil LinkedIn (Apify Scraper & Staging Review)**
2. **Pengiriman Token ke Email Pengguna Atasan untuk Isi Evaluasi Kinerja (Employer Survey)**
3. **Rekomendasi Kecocokan Perusahaan (Multi-Level Similarity Matching & Auto-Replace)**

---

## 1. Landasan Teori UML Activity Diagram

Penyusunan diagram dan alur sistem ini mengacu pada literatur akademik:
> **Referensi Buku:**  
> Sundaramoorthy, S. (2022). *UML Diagramming: A Case Study Approach* (1st ed.). CRC Press, Taylor & Francis Group. DOI: [10.1201/9781003287124](https://doi.org/10.1201/9781003287124).

### Komponen Standar UML Activity Diagram (Sundaramoorthy, 2022, Bab 1.1.3 & Tabel 1.4):
1. **Initial State (`●`)**: Titik awal (*start state*) yang memulai eksekusi alur aktivitas dalam sistem.
2. **Final State (`◉`)**: Titik penghentian akhir (*termination state*) yang menandai penyelesaian skenario secara sukses.
3. **Swimlanes (Partisi)**: Pembagian jalur kolom vertikal (*vertical swimlane*) atau horizontal yang merepresentasikan entitas, aktor pengguna (*Actor*), kelas kontrol (*Controller/Service*), maupun sistem eksternal (*External Cloud/Gateway*).
4. **Action State**: Operasi komputasi atau aktivitas bisnis diskret yang dilakukan oleh sistem atau aktor.
5. **Decision Node (`◇`)**: Titik percabangan logika (*conditional branch*) dengan satu masukan dan dua atau lebih keluaran berbasis *guard conditions* (`[kondisi]`, `[else]`).
6. **Merge Node (`◇`)**: Titik penggabungan beberapa aliran alternatif menjadi satu aliran tanpa sinkronisasi waktu tunggu.
7. **Synchronization (Fork & Join)**: 
   - **Fork**: Membagi satu aliran menjadi dua atau lebih aktivitas konkuren/paralel.
   - **Join**: Menggabungkan beberapa aktivitas konkuren menjadi satu aliran kembali setelah seluruh aktivitas pendahulunya selesai.
8. **Signal Send State**: Aksi pengiriman sinyal/pesan keluar dari sistem (contoh: transmisi HTTP POST ke API pihak ketiga atau pengiriman email via SMTP).
9. **Signal Accept State**: Aksi penerimaan sinyal eksternal yang memicu aktivitas baru (contoh: penerimaan respons webhook atau klik tautan bertoken oleh atasan).
10. **Flow Final (`⨂`)**: Penghentian abnormal pada cabang tertentu tanpa menghentikan eksekusi keseluruhan sistem.

---

## 2. PROSES 1: Sinkronisasi Profil LinkedIn (Apify Scraper & Staging Review)

### A. Deskripsi Alur & Logika Bisnis
Fitur sinkronisasi LinkedIn memanfaatkan pihak ketiga **Apify Cloud Actor (`data_forge_org~linkedin-scraper`)** untuk menarik data riwayat pekerjaan dan posisi alumni secara terstruktur.

#### Prinsip Arsitektur & Keamanan:
- **Cost Safety**: Setiap eksekusi hanya menargetkan tepat 1 alumni terpilih (1 alumni = 1 HTTP request ke Apify), mencegah pembengkakan biaya scraping tak terkendali.
- **Kerahasiaan Kredensial**: API Token Apify disimpan secara eksklusif di environment server dan ditransmisikan hanya via header HTTP `Authorization: Bearer {token}` (dilarang pada query string).
- **Staging Review Pattern**: Data profil mentah dari scraping disimpan terlebih dahulu di tabel `linkedin_sync_results` dengan status **`pending`**. Data primer alumni di tabel `biodatas` **tidak langsung ditimpa**. Super Admin / Admin Program Studi meninjau hasil scraping terlebih dahulu pada modal antarmuka (*diff review*) sebelum memutuskan untuk **Menyetujui (Approve)** atau **Menolak (Reject)**.

---

### B. Visualisasi UML Activity Diagram

```mermaid
flowchart TD
    %% SWIMLANES DEFINITION
    subgraph Admin_Actor["SWIMLANE: Administrator (Super Admin / Prodi)"]
        A_Start((●)) --> A_SelectAlumni["Pilih Alumni & Klik 'Sinkronkan LinkedIn'"]
        A_Wait["Menunggu Hasil Scraping"]
        A_ReviewModal["Buka Modal Review Perbandingan Data"]
        A_DecisionReview{"Keputusan Review?"}
        A_Approve["Klik 'Setujui (Approve)'"]
        A_Reject["Klik 'Tolak (Reject)'"]
    end

    subgraph Backend_System["SWIMLANE: Backend System (Controller & Provider)"]
        B_ValUrl{"Validasi URL LinkedIn?"}
        B_ThrowUrlErr["Kirim Respons HTTP 422 (URL Invalid)"]
        B_PreparePayload["Siapkan Payload JSON & Header Authorization"]
        B_SendReq[["Kirim Request HTTP Sinkron ke Apify"]]
        B_ReceiveResp["Terima HTTP Response Apify"]
        B_EvalStatus{"Status Respon Apify?"}
        B_HandleErr["Catat Log Error (404/401/429/500)"]
        B_SaveStaging["Simpan Payload JSON ke 'linkedin_sync_results' (Status: PENDING)"]
        B_AuditPending["Catat Log Audit 'linkedin_sync_apify'"]
        B_ApplyData["Ekstrak & Perbarui 'biodatas', Link Perusahaan Terkait"]
        B_MarkApproved["Ubah Status Staging menjadi 'APPROVED'"]
        B_MarkRejected["Ubah Status Staging menjadi 'REJECTED'"]
        B_AuditFinal["Catat Log Audit Reviewer"]
    end

    subgraph Apify_Service["SWIMLANE: External Service (Apify Cloud Actor)"]
        C_ExecActor["Jalankan Headless Scraper LinkedIn"]
        C_ReturnDataset[["Kirim JSON Dataset Kembali ke Tracer Study"]]
    end

    %% FLOW TRANSITIONS
    A_SelectAlumni --> B_ValUrl
    B_ValUrl -- "[URL Kosong / Format Tidak Valid]" --> B_ThrowUrlErr --> FlowFinal1((⨂))
    B_ValUrl -- "[URL Valid]" --> B_PreparePayload
    B_PreparePayload --> B_SendReq
    B_SendReq -.-> C_ExecActor
    C_ExecActor --> C_ReturnDataset
    C_ReturnDataset -.-> B_ReceiveResp
    B_ReceiveResp --> B_EvalStatus

    B_EvalStatus -- "[Gagal / Not Found / Kuota Habis]" --> B_HandleErr --> FlowFinal2((⨂))
    B_EvalStatus -- "[HTTP 200 OK & Data Ada]" --> B_SaveStaging
    B_SaveStaging --> B_AuditPending
    B_AuditPending --> A_ReviewModal
    A_ReviewModal --> A_DecisionReview

    A_DecisionReview -- "[Setujui Data]" --> A_Approve
    A_Approve --> B_ApplyData
    B_ApplyData --> B_MarkApproved
    B_MarkApproved --> B_AuditFinal

    A_DecisionReview -- "[Tolak Data]" --> A_Reject
    A_Reject --> B_MarkRejected
    B_MarkRejected --> B_AuditFinal

    B_AuditFinal --> EndSuccess(((◉)))
```

---

### C. Perintah & Routing Backend

#### 1. Endpoint Eksekusi Sinkronisasi Staging
- **Method**: `POST`
- **Route URL**: `/superadmin/linkedin-sync/{biodata}/sync`
- **Controller Action**: `App\Http\Controllers\SuperAdmin\LinkedIn\LinkedInSyncController@sync`

#### 2. Endpoint Persetujuan / Penolakan Data (Review)
- **Method**: `POST`
- **Route URL**: `/superadmin/linkedin-sync/{syncResult}/review`
- **Controller Action**: `App\Http\Controllers\SuperAdmin\LinkedIn\LinkedInSyncController@review`

---

### D. Struktur Data & Payload JSON

#### Request JSON ke Apify API:
```http
POST https://api.apify.com/v2/actors/data_forge_org~linkedin-scraper/run-sync-get-dataset-items
Authorization: Bearer <APIFY_API_TOKEN>
Content-Type: application/json
```
```json
{
  "profileUrls": [
    "https://www.linkedin.com/in/alumni-nama-ukdw"
  ],
  "includeProfilePosts": false,
  "includeComments": false,
  "includeCompanyPosts": false,
  "includeCompanyJobs": false,
  "includeJobDetails": false
}
```

#### Raw Response Dataset dari Apify:
```json
[
  {
    "fullName": "Joshua Andrean, S.Kom.",
    "headline": "Senior Software Engineer at PT Bank Central Asia Tbk",
    "publicIdentifier": "joshua-andrean",
    "location": {
      "city": "Jakarta",
      "country": "Indonesia"
    },
    "positions": [
      {
        "title": "Senior Software Engineer",
        "companyName": "PT Bank Central Asia Tbk",
        "start": { "year": 2023, "month": 3 },
        "end": null,
        "isCurrent": true,
        "location": "Jakarta, Indonesia"
      }
    ],
    "skills": ["PHP", "Laravel", "Vue.js", "Docker", "PostgreSQL"]
  }
]
```

#### Response JSON Sistem ke Frontend Super Admin:
```json
{
  "success": true,
  "status": "PENDING",
  "message": "Sinkronisasi profil via Apify berhasil. Data disimpan dalam status Pending Review.",
  "sync_result_id": 142,
  "sync_result": {
    "id": 142,
    "status": "pending",
    "scraped_at": "13/10/2026 14:15",
    "parsed": {
      "headline": "Senior Software Engineer at PT Bank Central Asia Tbk",
      "current_company": "PT Bank Central Asia Tbk",
      "current_position": "Senior Software Engineer",
      "skills_count": 5
    }
  }
}
```

---

### E. Pseudocode Implementasi Backend
```text
FUNCTION SyncLinkedInProfile(biodataId, currentUser):
    biodata = Query("SELECT * FROM biodatas WHERE id = :id", biodataId)
    
    // 1. Validasi URL profil LinkedIn
    IF biodata.linkedin_url IS NULL OR NOT IsValidLinkedInUrl(biodata.linkedin_url) THEN
        RETURN Response.Json(422, {"message": "Format URL LinkedIn tidak valid atau belum diisi."})
    ENDIF

    apifyToken = Config.Get("services.apify.api_token")
    actorId = Config.Get("services.apify.linkedin_actor_id")
    endpoint = "https://api.apify.com/v2/actors/" + actorId + "/run-sync-get-dataset-items"

    // 2. Transmisi HTTP Sinkron ke Apify
    TRY:
        httpResponse = HTTP.Post(
            Url: endpoint,
            Headers: {"Authorization": "Bearer " + apifyToken},
            Body: {
                "profileUrls": [biodata.linkedin_url],
                "includeProfilePosts": false
            },
            Timeout: 60
        )
        
        IF httpResponse.StatusCode != 200 THEN
            THROW Exception("Apify Provider Error: HTTP " + httpResponse.StatusCode)
        ENDIF

        rawDataset = httpResponse.Json()
        IF rawDataset IS EMPTY THEN
            THROW Exception("Profil LinkedIn tidak ditemukan atau bersifat privat.")
        ENDIF

        // 3. Simpan ke Staging (Status: Pending)
        syncResult = Database.Insert("linkedin_sync_results", {
            "biodata_id": biodata.id,
            "linkedin_url": biodata.linkedin_url,
            "scraped_data": rawDataset,
            "status": "pending",
            "scraped_at": CurrentTimestamp()
        })

        Database.Insert("log_activities", {
            "action": "linkedin_sync_apify",
            "user_id": currentUser.id,
            "subject_id": biodata.id
        })

        RETURN Response.Json(200, {"success": true, "sync_result": syncResult})
    CATCH Error e:
        RETURN Response.Json(500, {"success": false, "message": e.Message})
    END TRY
END FUNCTION
```

---

## 3. PROSES 2: Token ke Email Pengguna Atasan untuk Isi Evaluasi Kinerja

### A. Deskripsi Alur & Logika Bisnis
Evaluasi pengguna lulusan (*Employer Survey*) dirancang untuk memverifikasi kesesuaian dan kompetensi lulusan langsung oleh atasan tempat kerja alumni tanpa mewajibkan atasan membuat akun atau memasukkan kata sandi (*passwordless token-based authentication*).

#### Alur Kerja:
1. Alumni login dan memperbarui data profil pekerjaan pada menu **Profil & Karir**.
2. Alumni mengisi nama atasan, nomor kontak, serta email resmi atasan.
3. Saat alumni menekan tombol simpan, `SimpanProfilController` memvalidasi input.
4. Sistem memeriksa apakah format email atasan valid.
5. Sistem men-generate token keamanan unik 40 karakter menggunakan fungsi `Str::random(40)` dan menyimpannya di tabel `evaluasi_atasans`.
6. Sistem menyusun surat elektronik `UndanganEvaluasiAtasanMail` dengan tautan publik khusus: `https://tracerstudy.ukdw.ac.id/evaluasi-atasan/{token}`.
7. Email terkirim ke atasan via gateway SMTP.
8. Atasan membuka email dan mengklik tautan tanpa proses login akun.
9. Controller `EvaluasiAtasanController@show` memverifikasi token:
   - Jika token salah / tidak ada: menampilkan halaman `EvaluasiAtasanNotFound`.
   - Jika valid: memuat identitas alumni, perusahaan, dan 12 butir instrumen evaluasi kepuasan pengguna.
10. Atasan mengisi kuesioner evaluasi dan klik tombol **"Kirim Evaluasi"**.
11. Respon tersimpan di tabel `respons_evaluasi_atasan`, data perusahaan diperbarui, kuesioner dikunci (`is_submitted = true`), dan layar menampilkan pesan terima kasih.

---

### B. Visualisasi UML Activity Diagram

```mermaid
flowchart TD
    %% SWIMLANES DEFINITION
    subgraph Alumni_Actor["SWIMLANE: Alumni UKDW"]
        Al_Start((●)) --> Al_InputKarier["Input Data Pekerjaan, Perusahaan, & Email Atasan"]
        Al_Submit["Klik 'Simpan Profil'"]
    end

    subgraph Backend_App["SWIMLANE: Backend Application & DB"]
        B_ValProfile{"Email Atasan Valid?"}
        B_SaveBio["Simpan Profil Biodata Alumni"]
        B_GenToken["Generate Token Kriptografis 40 Karakter Str::random(40)"]
        B_SaveEval["Simpan / Update Record ke Tabel 'evaluasi_atasans'"]
        B_DispatchMail[["Dispatch Mailable: UndanganEvaluasiAtasanMail"]]
        B_TokenCheck{"EvaluasiAtasan::where('token', token) Ada?"}
        B_RenderNotFound["Tampilkan Halaman 'EvaluasiAtasanNotFound'"]
        B_LoadSurvey["Ambil Master 12 Pertanyaan Evaluasi & Render Form"]
        B_SaveResponses["Simpan Jawaban ke 'respons_evaluasi_atasan'"]
        B_UpdateCo["Update Data Perusahaan & Atasan"]
        B_LockForm["Set is_submitted = true, submitted_at = now()"]
    end

    subgraph Mail_Gateway["SWIMLANE: Mail Server / SMTP"]
        M_Send[["Kirim Email Berisi URL Akses Bertoken ke Inbox"]]
    end

    subgraph Atasan_Actor["SWIMLANE: Atasan / Pengguna Lulusan"]
        At_OpenEmail["Terima Email & Klik Tautan Evaluasi"]
        At_FillSurvey["Isi 12 Aspek Penilaian Kinerja Alumni & Saran"]
        At_SubmitEval["Klik Tombol 'Kirim Evaluasi'"]
        At_ShowThankYou["Lihat Halaman Konfirmasi Selesai"]
    end

    %% FLOW TRANSITIONS
    Al_InputKarier --> Al_Submit
    Al_Submit --> B_SaveBio
    B_SaveBio --> B_ValProfile

    B_ValProfile -- "[Tidak / Kosong]" --> FlowSkipMail((⨂))
    B_ValProfile -- "[Ya, Format Email Valid]" --> B_GenToken

    B_GenToken --> B_SaveEval
    B_SaveEval --> B_DispatchMail
    B_DispatchMail -.-> M_Send
    M_Send -.-> At_OpenEmail

    At_OpenEmail --> B_TokenCheck
    B_TokenCheck -- "[Token Tidak Ditemukan]" --> B_RenderNotFound --> FlowFinalNotFound((⨂))
    B_TokenCheck -- "[Token Valid]" --> B_LoadSurvey

    B_LoadSurvey --> At_FillSurvey
    At_FillSurvey --> At_SubmitEval
    At_SubmitEval --> B_SaveResponses

    B_SaveResponses --> B_UpdateCo
    B_UpdateCo --> B_LockForm
    B_LockForm --> At_ShowThankYou
    At_ShowThankYou --> EndEval(((◉)))
```

---

### C. Perintah & Routing Backend

#### 1. Pemicu Pembuatan Token & Pengiriman Email
- **Method**: `POST`
- **Route URL**: `/alumni/profil`
- **Controller Action**: `App\Http\Controllers\Alumni\Profil\SimpanProfilController@simpanProfil`

#### 2. Akses Publik Formulir Evaluasi via Token
- **Method**: `GET`
- **Route URL**: `/evaluasi-atasan/{token}`
- **Controller Action**: `App\Http\Controllers\EvaluasiAtasanController@show`

#### 3. Simpan Respon Evaluasi dari Atasan
- **Method**: `POST`
- **Route URL**: `/evaluasi-atasan/{token}`
- **Controller Action**: `App\Http\Controllers\EvaluasiAtasanController@store`

---

### D. Struktur Data & Payload JSON

#### Record Token di Database (`evaluasi_atasans`):
```json
{
  "id": 85,
  "biodata_id": 104,
  "atasan_id": 52,
  "perusahaan_id": 12,
  "token": "4a7b9c1d8e2f0a1b2c3d4e5f6a7b8c9d0e1f2a3b",
  "is_submitted": false,
  "submitted_at": null,
  "created_at": "2026-10-13T10:00:00Z"
}
```

#### URL Tautan Token dalam Email Atasan:
```text
https://tracerstudy.ukdw.ac.id/evaluasi-atasan/4a7b9c1d8e2f0a1b2c3d4e5f6a7b8c9d0e1f2a3b
```

#### Payload Respon Kuesioner Evaluasi:
```json
{
  "nama_perusahaan": "PT Bank Central Asia Tbk",
  "alamat_lengkap": "Jl. Jenderal Sudirman No. 45, Jakarta Pusat",
  "no_telp_fax": "021-2358800",
  "bentuk_perusahaan": "Perseroan Terbatas (PT)",
  "skala_perusahaan": "Nasional",
  "jumlah_pegawai": "> 500 orang",
  "nama_atasan": "Budi Hartono",
  "email_atasan": "budi.hartono@bca.co.id",
  "telepon_atasan": "081122334455",
  "jumlah_alumni_ukdw": "3 orang",
  "standar_gaji_pertama": "Rp 8.000.000 - Rp 12.000.000",
  "jawaban": {
    "integritas": 5,
    "keahlian_bidang": 4,
    "kemampuan_inggris": 4,
    "penggunaan_ti": 5,
    "komunikasi": 4,
    "kerjasama_tim": 5,
    "pengembangan_diri": 4,
    "kepemimpinan": 4,
    "etos_kerja": 5,
    "kesiapan_kerja": 5
  },
  "catatan_lainnya": "Lulusan memiliki integritas tinggi dan cepat beradaptasi dengan stack teknologi modern."
}
```

---

### E. Pseudocode Implementasi Backend
```text
FUNCTION HandleSimpanProfil(request, alumniUser):
    validated = Validate(request.all())
    biodata = alumniUser.biodata
    biodata.Update(validated)

    // Deteksi kebutuhan pembuatan token & pengiriman email evaluasi
    IF validated.email_atasan IS NOT NULL AND IsEmailValid(validated.email_atasan) THEN
        evaluasi = Database.FindOrCreate("evaluasi_atasans", 
            Where: {"biodata_id": biodata.id},
            Defaults: {
                "atasan_id": validated.atasan_id,
                "perusahaan_id": biodata.perusahaan_id,
                "token": GenerateSecureRandomString(40),
                "is_submitted": false
            }
        )

        // Kirimkan email bertoken via Queue/SMTP
        mailInstance = new UndanganEvaluasiAtasanMail(biodata, evaluasi)
        Mail.To(validated.email_atasan).Send(mailInstance)
    ENDIF

    RETURN RedirectBack()
END FUNCTION

FUNCTION HandleStoreEvaluasiPublic(token, request):
    evaluasi = Database.FindOneOrFail("evaluasi_atasans", {"token": token})
    validated = Validate(request.all())

    // 1. Update informasi master perusahaan
    perusahaan = evaluasi.perusahaan
    IF perusahaan IS NOT NULL THEN
        perusahaan.Update({
            "nama_perusahaan": validated.nama_perusahaan,
            "alamat": validated.alamat_lengkap,
            "no_telp_fax": validated.no_telp_fax
        })
    ENDIF

    // 2. Simpan setiap jawaban instrumen penilaian ke respons_evaluasi_atasan
    FOR EACH kode, nilai IN validated.jawaban:
        pertanyaan = Database.FindOne("pertanyaan_evaluasi_atasan", {"kode": kode})
        Database.UpdateOrCreate("respons_evaluasi_atasan",
            Where: {
                "evaluasi_atasan_id": evaluasi.id,
                "pertanyaan_id": pertanyaan.id
            },
            Values: {
                "nilai": nilai,
                "catatan": validated.catatan_lainnya
            }
        )
    END FOR

    // 3. Kunci formulir evaluasi
    evaluasi.Update({
        "is_submitted": true,
        "submitted_at": CurrentTimestamp()
    })

    RETURN Redirect("/evaluasi-atasan/" + token).With("success", "Terima kasih atas partisipasi Anda.")
END FUNCTION
```

---

## 4. PROSES 3: Rekomendasi Kecocokan Perusahaan (Similarity Matching & Auto-Replace)

### A. Deskripsi Alur & Logika Bisnis
Modul ini bertugas menyelesaikan redundansi data perusahaan yang diinput secara bebas oleh alumni (misalnya alumni mengetik *"PT. BCA"*, *"Bank Central Asia"*, atau salah ketik *"Bank Centrl Asia"*). Sistem menggunakan algoritma pencocokan kemiripan bertingkat (*Multi-level String Similarity Matching + Geographic Location Scoring*) pada domain service `PerusahaanVerificationService`.

#### 5 Tahapan Algoritma:
1. **Tahap 1: Text Preprocessing & Cleaning Pipeline (`cleanCompanyName`)**:
   - Memisahkan keterangan cabang di dalam tanda kurung `(...)`.
   - Mengubah teks menjadi huruf kecil (*lowercase*).
   - Menghilangkan tanda baca dan simbol non-alfanumerik menggunakan Regex `preg_replace`.
   - Membuang stop words badan usaha (`pt`, `cv`, `tbk`, `corp`, dll.), unit cabang (`kantor`, `pusat`, `cabang`, dll.), dan preposisi.
2. **Tahap 2: Ekstraksi Token & Pembuatan Akronim (`getTokens`, `makeAcronym`, `getAcronyms`)**:
   - Memotong kalimat menjadi token kata individual (`preg_split`).
   - Menghasilkan akronim otomatis dari huruf pertama tiap kata (contoh: *"bank central asia"* $\rightarrow$ *"bca"*).
3. **Tahap 3: Multi-Level String Matching**:
   - **Level 1 (Exact Match - 95 Poin)**: Nama string hasil pembersihan identik 100%.
   - **Level 2 (Akronim Cocok - 88 Poin)**: Teks pengajuan cocok dengan akronim master atau sebaliknya.
   - **Level 3 (Substring Terkandung - 75 s/d 90 Poin)**: Salah satu nama terkandung di dalam nama yang lain.
   - **Level 3B (Akronim Parsial Majemuk - 70 s/d 80 Poin)**: Salah satu kata nama majemuk merupakan akronim entitas (contoh: *"BCA Digital"* vs *"Bank Central Asia"*).
   - **Level 4 (Irisan Kata / Token Intersect - 50 s/d 75 Poin)**: Minimal sepertiga kata identik (rasio $\ge 33\%$) dan bukan kata sektor generik tunggal (*"bank"*, *"universitas"*, *"hotel"*).
   - **Level 5 (Fuzzy Distance / Deteksi Typo)**: Persentase kemiripan karakter huruf berbasis algoritma *Ratcliff/Obershelp* via `similar_text` $\ge 60\%$.
4. **Tahap 4: Pembobotan Geografis (Geographic Location Scoring)**:
   - Jika Kabupaten/Kota identik: **+20 Poin**.
   - Jika Provinsi identik: **+10 Poin** (Skor maksimal dibatasi 100).
5. **Tahap 5: Threshold Filtering & Ranking**:
   - Hanya memasukkan perusahaan dengan skor total $\ge 45$ (atau kota sama dengan skor nama $\ge 30$).
   - Diurutkan menurun (*descending*) dan diambil **Top-5 Rekomendasi**.
6. **Eksekusi Auto-Replace**:
   - Jika admin memilih rekomendasi, sistem menjalankan `replaceCompany()` dalam `DB::transaction`. Seluruh relasi `biodata.perusahaan_id` dialihkan ke master, data pending duplikat dihapus, dan dicatat di `log_activities`.

---

### B. Visualisasi UML Activity Diagram

```mermaid
flowchart TD
    %% SWIMLANES DEFINITION
    subgraph Admin_Actor["SWIMLANE: Administrator Verifikator"]
        Admin_Start((●)) --> Admin_OpenList["Buka Halaman Verifikasi Perusahaan"]
        Admin_ViewCard["Lihat Pengajuan Alumni & 5 Rekomendasi Kemiripan"]
        Admin_DecisionChoice{"Pilihan Aksi Admin?"}
        Admin_ClickReplace["Klik 'Gunakan Data Ini (Auto Replace)'"]
        Admin_ClickVerify["Klik 'Verifikasi Langsung'"]
        Admin_ClickReject["Klik 'Tolak Pengajuan'"]
    end

    subgraph Service_Engine["SWIMLANE: PerusahaanVerificationService"]
        S_FetchPending["Ambil Data Perusahaan Pending (Status: Menunggu Verifikasi)"]
        S_CleanText["Tahap 1: Text Preprocessing & Filter Stopwords Legalitas"]
        S_Tokenize["Tahap 2: Ekstraksi Token Kata & Generator Akronim Otomatis"]
        S_LoopMasters["Tahap 3: Loop Perbandingan ke Semua Master Terverifikasi"]
        
        S_EvalMatch{"Multi-Level Matching Evaluator"}
        S_ScoreExact["Level 1: Exact Match (95 Poin)"]
        S_ScoreAcr["Level 2: Akronim Cocok (88 Poin)"]
        S_ScoreSub["Level 3: Substring Terkandung (75-90 Poin)"]
        S_ScoreAcrPart["Level 3B: Akronim Parsial (70-80 Poin)"]
        S_ScoreToken["Level 4: Token Intersect Valid (50-75 Poin)"]
        S_ScoreFuzzy["Level 5: Ratcliff Typo Matching (similar_text)"]

        S_GeoBonus["Tahap 4: Tambah Bobot Geografis (Kota +20, Prov +10)"]
        S_FilterRank["Tahap 5: Filter Threshold >= 45 & Urutkan Top-5 Rekomendasi"]
    end

    subgraph Database_Storage["SWIMLANE: Database Transaction & Audit Trail"]
        DB_BeginTx["Mulai DB::beginTransaction()"]
        DB_MigrateAlumni["Alihkan Foreign Key 'biodata.perusahaan_id' ke Master"]
        DB_DeletePending["Hapus Record Pending Duplikat"]
        DB_WriteAudit["Tulis Riwayat ke 'log_activities'"]
        DB_CommitTx["Eksekusi DB::commit()"]
        DB_UpdateDirect["Ubah Status Perusahaan Menjadi 'Terverifikasi'"]
        DB_UpdateReject["Ubah Status Menjadi 'Ditolak' & Simpan Alasan"]
    end

    %% FLOW TRANSITIONS
    Admin_OpenList --> S_FetchPending
    S_FetchPending --> S_CleanText
    S_CleanText --> S_Tokenize
    S_Tokenize --> S_LoopMasters

    S_LoopMasters --> S_EvalMatch
    S_EvalMatch -- "[Identik 100%]" --> S_ScoreExact
    S_EvalMatch -- "[Akronim Sama]" --> S_ScoreAcr
    S_EvalMatch -- "[Frasa Terkandung]" --> S_ScoreSub
    S_EvalMatch -- "[Akronim Majemuk]" --> S_ScoreAcrPart
    S_EvalMatch -- "[Irisan Kata >= 33%]" --> S_ScoreToken
    S_EvalMatch -- "[Typo / Mirip Huruf]" --> S_ScoreFuzzy

    S_ScoreExact --> S_GeoBonus
    S_ScoreAcr --> S_GeoBonus
    S_ScoreSub --> S_GeoBonus
    S_ScoreAcrPart --> S_GeoBonus
    S_ScoreToken --> S_GeoBonus
    S_ScoreFuzzy --> S_GeoBonus

    S_GeoBonus --> S_FilterRank
    S_FilterRank --> Admin_ViewCard

    Admin_ViewCard --> Admin_DecisionChoice

    Admin_DecisionChoice -- "[Pilih Rekomendasi]" --> Admin_ClickReplace
    Admin_ClickReplace --> DB_BeginTx
    DB_BeginTx --> DB_MigrateAlumni
    DB_MigrateAlumni --> DB_DeletePending
    DB_DeletePending --> DB_WriteAudit
    DB_WriteAudit --> DB_CommitTx
    DB_CommitTx --> EndReplace(((◉)))

    Admin_DecisionChoice -- "[Benar-benar Baru]" --> Admin_ClickVerify
    Admin_ClickVerify --> DB_UpdateDirect
    DB_UpdateDirect --> EndVerify(((◉)))

    Admin_DecisionChoice -- "[Perusahaan Fiktif]" --> Admin_ClickReject
    Admin_ClickReject --> DB_UpdateReject
    DB_UpdateReject --> EndReject(((◉)))
```

---

### C. Perintah & Routing Backend

#### 1. Endpoint Tampilan Daftar Verifikasi & Rekomendasi
- **Super Admin**: `GET /superadmin/perusahaan`
- **Admin Fakultas**: `GET /fakultas/perusahaan`
- **Admin Prodi**: `GET /prodi/perusahaan`

#### 2. Endpoint Eksekusi Auto-Replace (Konsolidasi Duplikat)
- **Method**: `POST`
- **Route URL**: `/superadmin/perusahaan/{pendingCompany}/replace`
- **Controller Action**: `VerifikasiPerusahaanSuperAdminController@replace`
- **Payload**: `{"target_id": 12}`

#### 3. Endpoint Verifikasi Langsung (Data Baru Valid)
- **Method**: `POST`
- **Route URL**: `/superadmin/perusahaan/{company}/verify`
- **Controller Action**: `VerifikasiPerusahaanSuperAdminController@verify`

#### 4. Endpoint Tolak Pengajuan
- **Method**: `POST`
- **Route URL**: `/superadmin/perusahaan/{company}/reject`
- **Controller Action**: `VerifikasiPerusahaanSuperAdminController@reject`
- **Payload**: `{"alasan": "Nama instansi tidak valid"}`

---

### D. Struktur Data & Payload JSON

#### Output Rekomendasi Kemiripan Sistem:
```json
[
  {
    "id": 12,
    "nama_perusahaan": "PT Bank Central Asia Tbk",
    "alamat": "Jl. Jenderal Sudirman No. 45",
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
      "Akronim / Singkatan Cocok (BCA)",
      "Kota/Kabupaten Sama (Kota Yogyakarta)",
      "Provinsi Sama (D.I. Yogyakarta)"
    ]
  },
  {
    "id": 88,
    "nama_perusahaan": "PT Bank Mandiri (Persero) Tbk",
    "alamat": "Jl. Pangeran Mangkubumi No. 10",
    "propinsi_id": 14,
    "kabupaten_id": 204,
    "nama_provinsi": "D.I. Yogyakarta",
    "nama_kabupaten": "Kota Yogyakarta",
    "similarity_score": 50,
    "is_same_city": true,
    "is_same_province": true,
    "match_reasons": [
      "Memiliki 1 Kata Yang Sama (bank)",
      "Kota/Kabupaten Sama (Kota Yogyakarta)"
    ]
  }
]
```

#### Request Payload saat Admin Menjalankan Auto-Replace:
```json
{
  "target_id": 12
}
```

#### Response Payload Sukses Konsolidasi:
```json
{
  "success": true,
  "message": "Perusahaan berhasil dikonsolidasi dengan PT Bank Central Asia Tbk. Sebanyak 4 data alumni telah dialihkan secara otomatis.",
  "migrated_alumni_count": 4
}
```

---

### E. Pseudocode Implementasi Algoritma
```text
FUNCTION GetSimilarRecommendations(pendingCompany, limit = 5):
    cleanPending = CleanCompanyName(pendingCompany.nama_perusahaan)
    pendingTokens = GetTokens(cleanPending)
    pendingAcronyms = GetAcronyms(cleanPending, pendingCompany.nama_perusahaan)
    
    verifiedCompanies = Database.Query("SELECT * FROM perusahaans WHERE status = 'Terverifikasi'")
    recommendations = []

    FOR EACH verified IN verifiedCompanies:
        cleanVerified = CleanCompanyName(verified.nama_perusahaan)
        verifiedTokens = GetTokens(cleanVerified)
        verifiedAcronyms = GetAcronyms(cleanVerified, verified.nama_perusahaan)
        
        nameScore = 0
        reasons = []

        // LEVEL 1: Exact Match (95 Poin)
        IF cleanPending != "" AND cleanPending == cleanVerified THEN
            nameScore = 95
            reasons.Add("Nama Identik (Tanpa PT/CV)")

        // LEVEL 2: Akronim Cocok (88 Poin)
        ELSE IF cleanPending IN verifiedAcronyms OR cleanVerified IN pendingAcronyms THEN
            nameScore = 88
            reasons.Add("Akronim / Singkatan Cocok")

        // LEVEL 3: Substring Terkandung (75 - 90 Poin)
        ELSE IF Contains(cleanVerified, cleanPending) OR Contains(cleanPending, cleanVerified) THEN
            ratio = MinLength(cleanPending, cleanVerified) / MaxLength(cleanPending, cleanVerified)
            nameScore = 75 + Integer(ratio * 15)
            reasons.Add("Nama Mengandung Frasa Yang Sama")

        // LEVEL 3B: Akronim Parsial Entitas Majemuk (70 - 80 Poin)
        ELSE IF ArrayIntersect(pendingTokens, verifiedAcronyms) IS NOT EMPTY THEN
            nameScore = 75
            reasons.Add("Mengandung Akronim Entitas Terkait")

        // LEVEL 4 & 5: Token Intersect & Typo
        ELSE:
            intersect = ArrayIntersect(pendingTokens, verifiedTokens)
            overlapRatio = Length(intersect) / Max(Length(pendingTokens), Length(verifiedTokens))
            
            IF overlapRatio >= 0.33 AND NOT IsGenericSingleWord(intersect) THEN
                nameScore = 50 + Integer(overlapRatio * 25)
                reasons.Add("Memiliki Kata Yang Sama")
            ENDIF

            percentTypo = SimilarTextPercentage(cleanPending, cleanVerified)
            IF percentTypo >= 60 AND percentTypo > nameScore THEN
                nameScore = Integer(percentTypo)
                reasons.Add("Struktur Huruf Mirip (" + percentTypo + "%)")
            ENDIF
        END IF

        // Tahap 4: Pembobotan Geografis
        totalScore = nameScore
        IF nameScore >= 35 THEN
            IF pendingCompany.kabupaten_id == verified.kabupaten_id THEN
                totalScore += 20
                reasons.Add("Kota/Kabupaten Sama")
            ELSE IF pendingCompany.propinsi_id == verified.propinsi_id THEN
                totalScore += 10
                reasons.Add("Provinsi Sama")
            ENDIF
        ENDIF

        totalScore = Min(100, totalScore)

        // Tahap 5: Threshold Filtering
        IF totalScore >= 45 THEN
            recommendations.Add({
                "id": verified.id,
                "nama_perusahaan": verified.nama_perusahaan,
                "similarity_score": totalScore,
                "match_reasons": reasons
            })
        ENDIF
    END FOR

    SortDescending(recommendations, key: "similarity_score")
    RETURN Slice(recommendations, 0, limit)
END FUNCTION
```

---

## 5. Ringkasan Matriks Perbandingan 3 Proses

| Parameter | Proses 1: Sinkronisasi LinkedIn | Proses 2: Token Evaluasi Atasan | Proses 3: Rekomendasi Perusahaan |
| :--- | :--- | :--- | :--- |
| **Aktor Utama** | Super Admin / Admin Prodi | Alumni UKDW & Atasan Kerja | Admin Verifikator (Pusat/Fakultas/Prodi) |
| **Sifat Integrasi** | External Cloud API (Apify Actor) | SMTP Mail Server & Passwordless Token | Internal Intelligent String Algorithm |
| **Pola Arsitektur** | Staging Review Pattern (Pending $\rightarrow$ Approved) | Secure One-Time Token (Public Webhook) | Multi-Level Similarity with Geospatial Scoring |
| **Tabel Basis Data** | `linkedin_sync_results`, `biodatas` | `evaluasi_atasans`, `respons_evaluasi_atasan` | `perusahaans`, `biodatas`, `log_activities` |
| **Audit Trail** | Dicatat ke `log_activities` saat sync & review | Ditandai status `is_submitted` & `submitted_at` | Dicatat saat verifikasi langsung & auto-replace |
