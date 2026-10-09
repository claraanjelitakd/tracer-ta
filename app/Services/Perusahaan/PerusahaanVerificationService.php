<?php

namespace App\Services\Perusahaan;

use App\Models\Biodata;
use App\Models\LogActivity;
use App\Models\Perusahaan;
use App\Models\Prodi;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Pagination\LengthAwarePaginator;
use Illuminate\Support\Facades\DB;

/**
 * Service untuk menangani verifikasi, rekomendasi kesamaan, dan penggantian otomatis
 * data perusahaan/institusi tempat kerja alumni yang diajukan oleh alumni.
 */
class PerusahaanVerificationService
{
    /**
     * Kata-kata umum atau bentuk badan usaha yang diabaikan saat normalisasi nama perusahaan.
     *
     * @var array<int, string>
     */
    protected array $stopWords = [
        'pt', 'p.t', 'cv', 'c.v', 'tbk', 'persero', 'corp', 'corporation', 'inc', 'ltd',
        'co', 'company', 'indonesia', 'group', 'holding', 'perum', 'yayasan', 'kantor',
        'dinas', 'kementerian', 'badan', 'lembaga', 'ud', 'u.d', 'fa', 'firma',
    ];

    /**
     * Mengambil daftar perusahaan yang menunggu verifikasi untuk Program Studi tertentu.
     *
     * @param  array<string, mixed>  $filters
     */
    public function getPendingForProdi(int $prodiId, array $filters = []): LengthAwarePaginator
    {
        $query = Perusahaan::query()
            ->with([
                'propinsi',
                'kabupaten',
                'creator.biodata.prodi:id,nama_prodi',
                'createdProdi:id,nama_prodi',
                'biodata' => function ($q) {
                    $q->select('id', 'user_id', 'nim', 'prodi_id', 'posisi_jabatan', 'kategori_pekerjaan', 'perusahaan_id')
                        ->with(['dataAkademik:nim,nama,tahun_lulus', 'prodi:id,nama_prodi']);
                },
            ]);

        $status = $filters['status'] ?? 'all';
        if ($status !== 'all') {
            $query->where('status_verifikasi', $status);
        }

        // Filter apakah hanya pengajuan dari alumni prodi ini atau semua
        $scope = $filters['scope'] ?? 'prodi';
        if ($scope === 'prodi') {
            $query->where(function ($q) use ($prodiId) {
                $q->where('created_by_prodi_id', $prodiId)
                    ->orWhereHas('biodata', fn ($b) => $b->where('prodi_id', $prodiId))
                    ->orWhereHas('creator', fn ($u) => $u->where('prodi_id', $prodiId));
            });
        }

        if (! empty($filters['search'])) {
            $search = trim($filters['search']);
            $query->where(function ($q) use ($search) {
                $q->where('nama_perusahaan', 'like', "%{$search}%")
                    ->orWhere('alamat', 'like', "%{$search}%")
                    ->orWhereHas('propinsi', fn ($p) => $p->where('nama_provinsi', 'like', "%{$search}%"))
                    ->orWhereHas('kabupaten', fn ($k) => $k->where('nama_kabupaten', 'like', "%{$search}%"))
                    ->orWhereHas('biodata', fn ($b) => $b->where('nim', 'like', "%{$search}%")->orWhereHas('dataAkademik', fn ($da) => $da->where('nama', 'like', "%{$search}%")))
                    ->orWhereHas('creator', fn ($u) => $u->where('name', 'like', "%{$search}%")->orWhere('username', 'like', "%{$search}%"));
            });
        }

        $perPage = (int) ($filters['per_page'] ?? 10);

        return $query->latest('updated_at')->paginate($perPage)->withQueryString();
    }

    /**
     * Mengambil daftar perusahaan yang menunggu verifikasi untuk lingkup Fakultas tertentu.
     *
     * @param  array<string, mixed>  $filters
     */
    public function getPendingForFakultas(int $fakultasId, array $filters = []): LengthAwarePaginator
    {
        $prodiIds = Prodi::where('fakultas_id', $fakultasId)->pluck('id')->all();

        $query = Perusahaan::query()
            ->with([
                'propinsi',
                'kabupaten',
                'creator.biodata.prodi:id,nama_prodi',
                'createdProdi:id,nama_prodi',
                'biodata' => function ($q) {
                    $q->select('id', 'user_id', 'nim', 'prodi_id', 'posisi_jabatan', 'kategori_pekerjaan', 'perusahaan_id')
                        ->with(['dataAkademik:nim,nama,tahun_lulus', 'prodi:id,nama_prodi']);
                },
            ]);

        $status = $filters['status'] ?? 'all';
        if ($status !== 'all') {
            $query->where('status_verifikasi', $status);
        }

        if (! empty($filters['prodi_id']) && in_array((int) $filters['prodi_id'], $prodiIds)) {
            $targetProdiId = (int) $filters['prodi_id'];
            $query->where(function ($q) use ($targetProdiId) {
                $q->where('created_by_prodi_id', $targetProdiId)
                    ->orWhereHas('biodata', fn ($b) => $b->where('prodi_id', $targetProdiId))
                    ->orWhereHas('creator', fn ($u) => $u->where('prodi_id', $targetProdiId));
            });
        } else {
            $query->where(function ($q) use ($prodiIds) {
                $q->whereIn('created_by_prodi_id', $prodiIds)
                    ->orWhereHas('biodata', fn ($b) => $b->whereIn('prodi_id', $prodiIds))
                    ->orWhereHas('creator', fn ($u) => $u->whereIn('prodi_id', $prodiIds));
            });
        }

        if (! empty($filters['search'])) {
            $search = trim($filters['search']);
            $query->where(function ($q) use ($search) {
                $q->where('nama_perusahaan', 'like', "%{$search}%")
                    ->orWhere('alamat', 'like', "%{$search}%")
                    ->orWhereHas('propinsi', fn ($p) => $p->where('nama_provinsi', 'like', "%{$search}%"))
                    ->orWhereHas('kabupaten', fn ($k) => $k->where('nama_kabupaten', 'like', "%{$search}%"))
                    ->orWhereHas('biodata', fn ($b) => $b->where('nim', 'like', "%{$search}%")->orWhereHas('dataAkademik', fn ($da) => $da->where('nama', 'like', "%{$search}%")))
                    ->orWhereHas('creator', fn ($u) => $u->where('name', 'like', "%{$search}%")->orWhere('username', 'like', "%{$search}%"));
            });
        }

        $perPage = (int) ($filters['per_page'] ?? 10);

        return $query->latest('updated_at')->paginate($perPage)->withQueryString();
    }

    /**
     * Menghitung total pengajuan perusahaan menunggu verifikasi.
     */
    public function countPendingForProdi(int $prodiId): int
    {
        return Perusahaan::where('status_verifikasi', 'Menunggu Verifikasi')
            ->where(function ($q) use ($prodiId) {
                $q->where('created_by_prodi_id', $prodiId)
                    ->orWhereHas('biodata', fn ($b) => $b->where('prodi_id', $prodiId))
                    ->orWhereHas('creator', fn ($u) => $u->where('prodi_id', $prodiId));
            })
            ->count();
    }

    /**
     * Menghitung total pengajuan perusahaan menunggu verifikasi di tingkat fakultas.
     */
    public function countPendingForFakultas(int $fakultasId): int
    {
        $prodiIds = Prodi::where('fakultas_id', $fakultasId)->pluck('id')->all();

        return Perusahaan::where('status_verifikasi', 'Menunggu Verifikasi')
            ->where(function ($q) use ($prodiIds) {
                $q->whereIn('created_by_prodi_id', $prodiIds)
                    ->orWhereHas('biodata', fn ($b) => $b->whereIn('prodi_id', $prodiIds))
                    ->orWhereHas('creator', fn ($u) => $u->whereIn('prodi_id', $prodiIds));
            })
            ->count();
    }

    /**
     * Menemukan daftar rekomendasi perusahaan terverifikasi yang mirip dengan perusahaan pengajuan (pending).
     *
     * CATATAN ALGORITMA (Bukan sekadar trim() biasa):
     * Metode ini BUKAN sekadar menggunakan fungsi trim() string bawaan PHP, melainkan menerapkan
     * "Multi-level String Similarity Matching & Geographic Scoring Algorithm" dengan tahapan:
     * 1. Preprocessing: Pembersihan tanda baca & pembuangan Stop Words badan usaha (PT, CV, Corp, dsb.).
     * 2. Level 1 (Skor 95): Exact Match nama bersih (tanpa embel-embel badan usaha).
     * 3. Level 2 (Skor 88): Akronim / Singkatan Match (contoh: "BCA" cocok dengan "Bank Central Asia").
     * 4. Level 3 (Skor 75-90): Substring / Containment Match (nama satu terkandung dalam nama lainnya).
     * 5. Level 4 (Skor proporsional, max 75): Overlap Token Kata (irisan kata yang identik).
     * 6. Level 5 (Skor fuzzy): Levenshtein / similar_text (kemiripan urutan karakter jika ada typo/salah ketik).
     * 7. Bonus Lokasi: +20 poin jika Kota/Kabupaten sama, +10 poin jika Provinsi sama.
     * 8. Ambang Batas: Hanya skor >= 45 (atau >= 30 jika satu kota) yang ditampilkan sebagai rekomendasi.
     *
     * @param  Perusahaan  $pendingCompany  Perusahaan baru yang diajukan oleh alumni (status Menunggu Verifikasi)
     * @param  int  $limit  Jumlah maksimal perusahaan rekomendasi yang ditampilkan (default: 6)
     * @return array<int, array<string, mixed>>
     */
    public function getSimilarRecommendations(Perusahaan $pendingCompany, int $limit = 6): array
    {
        // [BARIS 1] Mengambil string nama mentah perusahaan dari model $pendingCompany dan memotong spasi depan/belakang dengan trim().
        // Variabel $rawPendingName (string): Nama mentah yang diketik alumni (contoh: "  PT. Bank Central Asia, Tbk  " -> "PT. Bank Central Asia, Tbk").
        $rawPendingName = trim((string) $pendingCompany->nama_perusahaan);

        // [BARIS 2] Jika nama kosong, langsung hentikan proses dan kembalikan array kosong (tidak ada rekomendasi).
        if ($rawPendingName === '') {
            return [];
        }

        // [BARIS 3] Memanggil method cleanCompanyName() untuk membuang tanda baca dan kata PT/CV/Tbk.
        // Variabel $cleanPending (string): Kalimat bersih huruf kecil tanpa stopwords (output: "bank central asia").
        $cleanPending = $this->cleanCompanyName($rawPendingName);

        // [BARIS 4] Memanggil method getTokens() yang memotong kalimat bersih menjadi array kata-kata (token).
        // Variabel $pendingTokens (array): Kumpulan kata penyusun nama (output: ["bank", "central", "asia"]).
        $pendingTokens = $this->getTokens($rawPendingName);

        // [BARIS 5] Memanggil method makeAcronym() yang mengambil huruf inisial depan setiap kata token.
        // Variabel $pendingAcronym (string): Singkatan otomatis dalam huruf kecil (output: "bca").
        $pendingAcronym = $this->makeAcronym($rawPendingName);

        // [BARIS 6] Menjalankan query Eloquent untuk mengambil seluruh data master perusahaan yang sudah 'Terverifikasi'
        // Variabel $verifiedCompanies (Collection): Kumpulan objek model Perusahaan terverifikasi dari tabel database.
        $verifiedCompanies = Perusahaan::query()
            ->with(['propinsi:id,nama_provinsi', 'kabupaten:id,nama_kabupaten'])
            ->where('status_verifikasi', 'Terverifikasi')
            ->where('id', '!=', $pendingCompany->id)
            ->get();

        // [BARIS 7] Inisialisasi wadah penampung array hasil perhitungan skor kemiripan
        // Variabel $scored (array): Tempat menyimpan daftar calon perusahaan rekomendasi yang lolos seleksi.
        $scored = [];

        // [BARIS 8] Perulangan (looping) untuk membandingkan perusahaan pengajuan alumni dengan SETIAP master perusahaan terverifikasi
        foreach ($verifiedCompanies as $verified) {
            // [BARIS 8.1] Mengambil nama mentah master perusahaan dan memotong spasi pinggir
            $rawVerifiedName = trim((string) $verified->nama_perusahaan);

            // [BARIS 8.2] Membersihkan nama master dari stop words (PT/CV) -> output string: misal "bank central asia"
            $cleanVerified = $this->cleanCompanyName($rawVerifiedName);

            // [BARIS 8.3] Memotong nama master menjadi array kata (token) -> output array: misal ["bank", "central", "asia"]
            $verifiedTokens = $this->getTokens($rawVerifiedName);

            // [BARIS 8.4] Membuat akronim dari nama master -> output string: misal "bca"
            $verifiedAcronym = $this->makeAcronym($rawVerifiedName);

            // [BARIS 8.5] Variabel penampung nilai skor nama (default 0)
            $nameScore = 0;

            // [BARIS 8.6] Variabel penampung teks alasan mengapa cocok (untuk ditampilkan di badge UI)
            $reasons = [];

            // =========================================================================
            // TAHAP 1: PENILAIAN NAMA (MULTI-LEVEL NAME SIMILARITY MATCHING)
            // =========================================================================
            // Penilaian dilakukan bertingkat (hierarki prioritas) dari tingkat kesamaan
            // paling pasti (identik) hingga deteksi kemiripan kata dan salah ketik (typo).

            // -------------------------------------------------------------------------
            // LEVEL 1: EXACT MATCH NAMA BERSIH (SKOR: 95 POIN)
            // -------------------------------------------------------------------------
            // Logika: Jika setelah dibersihkan dari "PT", "CV", "Tbk", dll., kedua nama sama 100%.
            // Contoh nyata:
            //   - Input Alumni   : "PT. Tokopedia"         -> bersih: "tokopedia"
            //   - Master Database: "Tokopedia Indonesia"   (jika Indonesia masuk stopwords) -> bersih: "tokopedia"
            //   - Hasil          : Sama persis! Diberi skor 95 (sangat direkomendasikan).
            if ($cleanPending !== '' && $cleanPending === $cleanVerified) {
                $nameScore = 95;
                $reasons[] = 'Nama Identik (Tanpa PT/CV)';
            }

            // -------------------------------------------------------------------------
            // LEVEL 2: PENCOCOKAN AKRONIM / SINGKATAN (SKOR: 88 POIN)
            // -------------------------------------------------------------------------
            // Logika: Mencocokkan singkatan inisial huruf depan dengan nama panjangnya.
            // Contoh nyata:
            //   - Kasus A: Alumni mengetik "BCA", di database ada "Bank Central Asia"
            //              Akronim dari "Bank Central Asia" dibuat jadi "bca", cocok dengan input alumni!
            //   - Kasus B: Alumni mengetik "Bank Central Asia", di database tersimpan "BCA".
            //   - Kasus C: Kata singkatan ada di dalam daftar kata master (misal "Shopee" di "PT Shopee Express").
            elseif (
                (! empty($verifiedAcronym) && strtolower($cleanPending) === strtolower($verifiedAcronym)) ||
                (! empty($pendingAcronym) && strtolower($cleanVerified) === strtolower($pendingAcronym)) ||
                (in_array(strtolower($cleanPending), $verifiedTokens, true)) ||
                (in_array(strtolower($cleanVerified), $pendingTokens, true))
            ) {
                $nameScore = 88;
                $reasons[] = 'Akronim / Singkatan Cocok';
            }

            // -------------------------------------------------------------------------
            // LEVEL 3: SUBSTRING / FRASA TERKANDUNG (SKOR: 75 s/d 90 POIN)
            // -------------------------------------------------------------------------
            // Logika: Salah satu nama menjadi bagian dari nama lainnya.
            // Contoh nyata:
            //   - Input Alumni   : "Gojek"
            //   - Master Database: "Gojek Super App"
            //   -> Kata "Gojek" terkandung utuh di dalam "Gojek Super App".
            //   Perhitungan skor:
            //     - Skor dasar: 75 poin
            //     - Tambahan skor: Berdasarkan rasio panjang teks (minLen / maxLen * 15).
            //       Semakin mirip panjang kedua teksnya, semakin tinggi skornya mendekati 90 poin.
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

            // -------------------------------------------------------------------------
            // LEVEL 4 & 5: IRISAN KATA (TOKEN INTERSECT) & DETEKSI TYPO (FUZZY SIMILARITY)
            // -------------------------------------------------------------------------
            // Jika nama tidak identik, bukan singkatan, dan tidak saling terkandung utuh,
            // maka kita analisis kata per kata (Level 4) dan susunan hurufnya jika typo (Level 5).
            else {
                // [LANGKAH 1] array_intersect(): Fungsi bawaan PHP untuk mencari IRISAN kata yang ada di kedua array.
                // Variabel $intersect: Menyimpan array kata-kata yang sama persis antara pengajuan ($pendingTokens) dan master ($verifiedTokens).
                // Contoh: pending=['bank','mandiri','syariah'], verified=['bank','mandiri','taspen'] -> $intersect=['bank','mandiri'].
                $intersect = array_intersect($pendingTokens, $verifiedTokens);

                // [LANGKAH 2] count(): Menghitung jumlah elemen/kata di dalam array $intersect.
                // Variabel $overlapCount: Angka integer berapa kata yang cocok (tumpang tindih / overlap). Contoh: 2.
                $overlapCount = count($intersect);

                // [LANGKAH 3] Mengecek apakah ada minimal 1 kata yang sama.
                if ($overlapCount > 0) {
                    // [LANGKAH 4] max(): Mengambil jumlah kata terbanyak di antara nama pengajuan atau master.
                    // Variabel $totalTokens: Angka integer total kata maksimum pembanding (misal max(3, 3) = 3).
                    $totalTokens = max(count($pendingTokens), count($verifiedTokens));

                    // [LANGKAH 5] Menghitung persentase irisan kata dikali bobot maksimal 75 poin, lalu di-cast ke integer (int).
                    // Variabel $tokenScore: Nilai skor kemiripan kata. Rumus: (overlap / total) * 75. Contoh: (2 / 3) * 75 = 50.
                    $tokenScore = (int) (($overlapCount / $totalTokens) * 75);

                    // [LANGKAH 6] Jika nilai irisan kata ini lebih besar dari nilai nama saat ini, perbarui nilainya.
                    if ($tokenScore > $nameScore) {
                        // Simpan nilai baru ke variabel $nameScore
                        $nameScore = $tokenScore;

                        // Tambahkan keterangan alasan kecocokan ke array $reasons untuk ditampilkan di UI
                        $reasons[] = "Memiliki {$overlapCount} Kata Yang Sama (".implode(', ', $intersect).')';
                    }
                }

                // [LANGKAH 7] similar_text(): Fungsi bawaan PHP untuk menghitung kemiripan karakter huruf (Ratcliff/Obershelp).
                // Parameter ke-3 ($percent) adalah pass-by-reference, otomatis diisi nilai persentase (float 0 - 100) oleh PHP.
                // Variabel $percent: Menampung angka persentase kemiripan huruf jika ada salah ketik/typo (misal "centrl" vs "central" -> 96.9).
                similar_text($cleanPending, $cleanVerified, $percent);

                // [LANGKAH 8] Cek apakah persentase >= 60% dan lebih tinggi dari skor irisan kata sebelumnya.
                if ($percent >= 60 && (int) $percent > $nameScore) {
                    // Konversi nilai persen ke integer dan simpan sebagai skor nama utama
                    $nameScore = (int) $percent;

                    // Tambahkan alasan ke array $reasons untuk UI admin
                    $reasons[] = 'Struktur Huruf Mirip ('.round($percent).'%)';
                }
            }

            // =========================================================================
            // TAHAP 2: PENILAIAN LOKASI GEOGRAFIS (GEOGRAPHIC LOCATION BONUS)
            // =========================================================================
            // Menambahkan bobot nilai jika pengajuan berada di area yang sama
            $isSameProvince = false;
            $isSameCity = false;

            if ($pendingCompany->propinsi_id && $pendingCompany->propinsi_id === $verified->propinsi_id) {
                $isSameProvince = true;
                $reasons[] = 'Provinsi Sama ('.$verified->propinsi?->nama_provinsi.')';
            }

            if ($pendingCompany->kabupaten_id && $pendingCompany->kabupaten_id === $verified->kabupaten_id) {
                $isSameCity = true;
                $reasons[] = 'Kota/Kabupaten Sama ('.$verified->kabupaten?->nama_kabupaten.')';
            }

            // Bonus bobot jika lokasi sama (hanya jika nama sudah memiliki kesamaan minimum >= 35)
            $totalScore = $nameScore;
            if ($isSameCity && $nameScore >= 35) {
                $totalScore += 20; // Bonus +20 poin jika Kota/Kabupaten identik
            } elseif ($isSameProvince && $nameScore >= 35) {
                $totalScore += 10; // Bonus +10 poin jika Provinsi identik
            }

            // Cap nilai maksimal di 100
            $totalScore = min(100, $totalScore);

            // =========================================================================
            // TAHAP 3: FILTER AMBANG BATAS (THRESHOLD FILTERING)
            // =========================================================================
            // Syarat masuk rekomendasi: Skor Total >= 45 ATAU (Satu Kota dan Skor Nama >= 30)
            if ($totalScore >= 45 || ($isSameCity && $nameScore >= 30)) {
                $scored[] = [
                    'id' => $verified->id,
                    'nama_perusahaan' => $verified->nama_perusahaan,
                    'alamat' => $verified->alamat,
                    'propinsi_id' => $verified->propinsi_id,
                    'kabupaten_id' => $verified->kabupaten_id,
                    'nama_provinsi' => $verified->propinsi?->nama_provinsi ?? '-',
                    'nama_kabupaten' => $verified->kabupaten?->nama_kabupaten ?? '-',
                    'sektor' => $verified->sektor ?? '-',
                    'skala' => $verified->skala ?? '-',
                    'jenis_perusahaan' => $verified->jenis_perusahaan ?? '-',
                    'similarity_score' => $totalScore,
                    'is_same_city' => $isSameCity,
                    'is_same_province' => $isSameProvince,
                    'match_reasons' => $reasons,
                ];
            }
        }

        // =========================================================================
        // TAHAP 4: PENGURUTAN (RANKING) & LIMIT HASIL REKOMENDASI
        // =========================================================================
        // Urutkan berdasarkan similarity_score tertinggi secara descending
        usort($scored, fn ($a, $b) => $b['similarity_score'] <=> $a['similarity_score']);

        return array_slice($scored, 0, $limit);
    }

    /**
     * Mencari master data perusahaan terverifikasi dengan kata kunci fleksibel.
     *
     * @return Collection<int, Perusahaan>
     */
    public function searchVerified(string $keyword, int $limit = 10): Collection
    {
        $term = trim($keyword);
        if ($term === '') {
            return new Collection;
        }

        return Perusahaan::query()
            ->with(['propinsi:id,nama_provinsi', 'kabupaten:id,nama_kabupaten'])
            ->where('status_verifikasi', 'Terverifikasi')
            ->where(function ($q) use ($term) {
                $q->where('nama_perusahaan', 'like', "%{$term}%")
                    ->orWhere('alamat', 'like', "%{$term}%")
                    ->orWhereHas('kabupaten', fn ($k) => $k->where('nama_kabupaten', 'like', "%{$term}%"))
                    ->orWhereHas('propinsi', fn ($p) => $p->where('nama_provinsi', 'like', "%{$term}%"));
            })
            ->take($limit)
            ->get();
    }

    /**
     * Mengganti perusahaan yang diajukan dengan master perusahaan terverifikasi (Auto Replace).
     * Seluruh alumni yang bekerja di perusahaan pending ini dialihkan ke perusahaan terverifikasi,
     * lalu record pending yang redundan dibersihkan.
     *
     * @return array<string, mixed>
     */
    public function replaceCompany(Perusahaan $pendingCompany, Perusahaan $verifiedCompany): array
    {
        return DB::transaction(function () use ($pendingCompany, $verifiedCompany) {
            $alumnis = Biodata::where('perusahaan_id', $pendingCompany->id)->get();
            $affectedCount = $alumnis->count();
            $pendingName = $pendingCompany->nama_perusahaan;
            $verifiedName = $verifiedCompany->nama_perusahaan;

            // Alihkan relasi biodata alumni ke perusahaan terverifikasi
            Biodata::where('perusahaan_id', $pendingCompany->id)->update([
                'perusahaan_id' => $verifiedCompany->id,
            ]);

            // Hapus record duplicate / pending yang sudah digantikan
            $pendingCompany->delete();

            // Catat ke Audit Trail log_activities
            LogActivity::record(
                'GANTI_PERUSAHAAN_MASTER',
                "Mengganti perusahaan pengajuan '{$pendingName}' ke master terverifikasi '{$verifiedName}' untuk {$affectedCount} alumni.",
                $verifiedCompany,
                ['pending_id' => $pendingCompany->id, 'pending_name' => $pendingName],
                ['verified_id' => $verifiedCompany->id, 'verified_name' => $verifiedName, 'alumni_dialihkan' => $affectedCount]
            );

            return [
                'status' => 'success',
                'affected_count' => $affectedCount,
                'replaced_name' => $verifiedCompany->nama_perusahaan,
            ];
        });
    }

    /**
     * Memverifikasi perusahaan yang diajukan langsung tanpa penggantian.
     */
    public function verifyDirectly(Perusahaan $company): bool
    {
        $res = $company->update([
            'status_verifikasi' => 'Terverifikasi',
        ]);

        // Catat ke Audit Trail log_activities
        LogActivity::record(
            'ACC_VERIFIKASI_PERUSAHAAN',
            "Menyetujui (ACC) perusahaan '{$company->nama_perusahaan}' menjadi berstatus Terverifikasi.",
            $company
        );

        return $res;
    }

    /**
     * Mengupdate atribut perusahaan oleh admin (memperbaiki typo dsb.) dan memverifikasinya.
     *
     * @param  array<string, mixed>  $validatedData
     */
    public function updateAndVerify(Perusahaan $company, array $validatedData, bool $verifyNow = true): Perusahaan
    {
        $oldValues = $company->only(array_keys($validatedData));

        if ($verifyNow) {
            $validatedData['status_verifikasi'] = 'Terverifikasi';
        }

        $company->update($validatedData);

        // Catat ke Audit Trail log_activities
        LogActivity::record(
            'UPDATE_VERIFIKASI_PERUSAHAAN',
            "Memperbarui data dan memverifikasi perusahaan '{$company->nama_perusahaan}'.",
            $company,
            $oldValues,
            $company->only(array_keys($validatedData))
        );

        return $company->fresh();
    }

    /**
     * Menolak pengajuan perusahaan.
     */
    public function rejectCompany(Perusahaan $company, ?string $alasan = null): bool
    {
        $res = $company->update([
            'status_verifikasi' => 'Ditolak',
        ]);

        // Catat ke Audit Trail log_activities
        LogActivity::record(
            'REJECT_PERUSAHAAN',
            "Menolak pengajuan perusahaan '{$company->nama_perusahaan}'.".($alasan ? " Alasan: {$alasan}" : ''),
            $company
        );

        return $res;
    }

    /**
     * Membersihkan nama perusahaan dari tanda baca dan kata umum / bentuk badan usaha (Stop Words).
     * Contoh: "PT. Bank Central Asia, Tbk" -> "bank central asia"
     *
     * @param  string  $name  Nama mentah perusahaan
     * @return string Nama yang dinormalisasi huruf kecil, tanpa tanda baca, dan tanpa stop words
     */
    protected function cleanCompanyName(string $name): string
    {
        // [LANGKAH 1] trim() membuang spasi di pinggir, strtolower() mengubah semua huruf jadi kecil (lowercase)
        // Variabel $lower (string): Contoh: "pt. bank central asia, tbk"
        $lower = strtolower(trim($name));

        // [LANGKAH 2] preg_replace() mencari simbol titik, koma, strip, kurung, garis miring dan menggantinya dengan spasi tunggal
        // Variabel $clean (string): Contoh: "pt  bank central asia  tbk" (simbol hilang berganti spasi)
        $clean = preg_replace('/[.,\-_()\[\]\/\\\\]+/', ' ', $lower);

        // [LANGKAH 3] preg_split('/\s+/', ...) memotong (split) string berdasarkan spasi berurutan menjadi kumpulan kata (token)
        // Variabel $words (array): Contoh: ["pt", "bank", "central", "asia", "tbk"]
        $words = preg_split('/\s+/', (string) $clean, -1, PREG_SPLIT_NO_EMPTY) ?: [];

        // [LANGKAH 4] array_filter() menyaring dan membuang kata yang terdaftar di daftar $this->stopWords (pt, cv, tbk, dll.)
        // Variabel $filtered (array): Contoh: ["bank", "central", "asia"]
        $filtered = array_filter($words, fn ($w) => ! in_array($w, $this->stopWords, true));

        // [LANGKAH 5] implode(' ', $filtered) menggabungkan kembali array kata-kata inti menjadi 1 baris kalimat string dipisahkan spasi
        // Output (string): Contoh: "bank central asia"
        return trim(implode(' ', $filtered));
    }

    /**
     * Menghasilkan array token kata bersih setelah proses pembersihan stop words.
     * Contoh: "Bank Central Asia" -> ["bank", "central", "asia"]
     *
     * @param  string  $name  Nama mentah perusahaan
     * @return array<int, string> Kumpulan kata-kata penyusun nama
     */
    protected function getTokens(string $name): array
    {
        // [LANGKAH 1] Ambil dulu string bersih tanpa PT/CV dari cleanCompanyName()
        $clean = $this->cleanCompanyName($name);

        // [LANGKAH 2] Jika string kosong, langsung kembalikan array kosong []
        if ($clean === '') {
            return [];
        }

        // [LANGKAH 3] INILAH TEMPAT DIA MEMOTONG-MOTONG MENJADI TOKEN KATA!
        // preg_split('/\s+/', ...) membelah string berdasarkan spasi menjadi elemen array.
        // Output (array): Contoh "bank central asia" dipotong menjadi -> ["bank", "central", "asia"]
        return preg_split('/\s+/', $clean, -1, PREG_SPLIT_NO_EMPTY) ?: [];
    }

    /**
     * Menghasilkan akronim/singkatan otomatis dari huruf depan setiap kata penting.
     * Berguna mendeteksi kecocokan seperti "BCA" dengan "Bank Central Asia".
     *
     * @param  string  $name  Nama perusahaan
     * @return string Akronim huruf kecil (contoh: "bca"), atau string kosong jika hanya 1 kata
     */
    protected function makeAcronym(string $name): string
    {
        // [LANGKAH 1] Ambil array kumpulan kata token (misal: ["bank", "central", "asia"])
        $tokens = $this->getTokens($name);

        // [LANGKAH 2] Jika hanya ada 1 kata (misal "Google"), tidak perlu dibuat akronim (kembalikan string kosong)
        if (count($tokens) <= 1) {
            return '';
        }

        // [LANGKAH 3] Inisialisasi string kosong untuk menampung huruf-huruf depan
        $acronym = '';

        // [LANGKAH 4] Looping setiap kata token, lalu ambil indeks karakter ke-0 ($token[0])
        // Misal kata "bank" -> ambil 'b', kata "central" -> ambil 'c', kata "asia" -> ambil 'a'
        foreach ($tokens as $token) {
            $acronym .= $token[0] ?? '';
        }

        // [LANGKAH 5] Kembalikan akronim dalam format huruf kecil (lowercase). Contoh output: "bca"
        return strtolower($acronym);
    }
}
