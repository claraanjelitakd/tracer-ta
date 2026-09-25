<?php

namespace App\Services\Perusahaan;

use App\Models\Biodata;
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
                    $q->select('id', 'user_id', 'nim', 'nama', 'prodi_id', 'tahun_lulus', 'posisi_jabatan', 'kategori_pekerjaan', 'perusahaan_id')
                        ->with('prodi:id,nama_prodi');
                },
            ])
            ->where('status_verifikasi', 'Menunggu Verifikasi');

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
                    ->orWhereHas('biodata', fn ($b) => $b->where('nama', 'like', "%{$search}%")->orWhere('nim', 'like', "%{$search}%"))
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
                    $q->select('id', 'user_id', 'nim', 'nama', 'prodi_id', 'tahun_lulus', 'posisi_jabatan', 'kategori_pekerjaan', 'perusahaan_id')
                        ->with('prodi:id,nama_prodi');
                },
            ])
            ->where('status_verifikasi', 'Menunggu Verifikasi');

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
                    ->orWhereHas('biodata', fn ($b) => $b->where('nama', 'like', "%{$search}%")->orWhere('nim', 'like', "%{$search}%"))
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
     * Menemukan daftar rekomendasi perusahaan terverifikasi yang mirip berdasarkan nama,
     * akronim/singkatan, kecocokan kata, serta lokasi (Provinsi & Kabupaten/Kota).
     *
     * @return array<int, array<string, mixed>>
     */
    public function getSimilarRecommendations(Perusahaan $pendingCompany, int $limit = 6): array
    {
        $rawPendingName = trim((string) $pendingCompany->nama_perusahaan);
        if ($rawPendingName === '') {
            return [];
        }

        $cleanPending = $this->cleanCompanyName($rawPendingName);
        $pendingTokens = $this->getTokens($rawPendingName);
        $pendingAcronym = $this->makeAcronym($rawPendingName);

        // Ambil semua master data perusahaan yang berstatus Terverifikasi
        $verifiedCompanies = Perusahaan::query()
            ->with(['propinsi:id,nama_provinsi', 'kabupaten:id,nama_kabupaten'])
            ->where('status_verifikasi', 'Terverifikasi')
            ->where('id', '!=', $pendingCompany->id)
            ->get();

        $scored = [];

        foreach ($verifiedCompanies as $verified) {
            $rawVerifiedName = trim((string) $verified->nama_perusahaan);
            $cleanVerified = $this->cleanCompanyName($rawVerifiedName);
            $verifiedTokens = $this->getTokens($rawVerifiedName);
            $verifiedAcronym = $this->makeAcronym($rawVerifiedName);

            $nameScore = 0;
            $reasons = [];

            // 1. Exact Match pada nama yang sudah dibersihkan
            if ($cleanPending !== '' && $cleanPending === $cleanVerified) {
                $nameScore = 95;
                $reasons[] = 'Nama Identik (Tanpa PT/CV)';
            }
            // 2. Cek Kesamaan Akronim / Singkatan (contoh: BCA vs Bank Central Asia)
            elseif (
                (! empty($verifiedAcronym) && strtolower($cleanPending) === strtolower($verifiedAcronym)) ||
                (! empty($pendingAcronym) && strtolower($cleanVerified) === strtolower($pendingAcronym)) ||
                (in_array(strtolower($cleanPending), $verifiedTokens, true)) ||
                (in_array(strtolower($cleanVerified), $pendingTokens, true))
            ) {
                $nameScore = 88;
                $reasons[] = 'Akronim / Singkatan Cocok';
            }
            // 3. Substring / Containment Match
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
            // 4. Overlap Kata (Token Intersect)
            else {
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

                // 5. String Similarity (Levenshtein / similar_text)
                similar_text($cleanPending, $cleanVerified, $percent);
                if ($percent >= 60 && (int) $percent > $nameScore) {
                    $nameScore = (int) $percent;
                    $reasons[] = 'Struktur Huruf Mirip ('.round($percent).'%)';
                }
            }

            // Cek Kesamaan Lokasi Wilayah (Kota/Kabupaten & Provinsi)
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

            // Bonus bobot jika lokasi sama
            $totalScore = $nameScore;
            if ($isSameCity && $nameScore >= 35) {
                $totalScore += 20;
            } elseif ($isSameProvince && $nameScore >= 35) {
                $totalScore += 10;
            }

            $totalScore = min(100, $totalScore);

            // Ambang batas (threshold) untuk dijadikan rekomendasi
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

        // Urutkan berdasarkan similarity_score tertinggi
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

            // Alihkan relasi biodata alumni ke perusahaan terverifikasi
            Biodata::where('perusahaan_id', $pendingCompany->id)->update([
                'perusahaan_id' => $verifiedCompany->id,
            ]);

            // Hapus record duplicate / pending yang sudah digantikan
            $pendingCompany->delete();

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
        return $company->update([
            'status_verifikasi' => 'Terverifikasi',
        ]);
    }

    /**
     * Mengupdate atribut perusahaan oleh admin (memperbaiki typo dsb.) dan memverifikasinya.
     *
     * @param  array<string, mixed>  $validatedData
     */
    public function updateAndVerify(Perusahaan $company, array $validatedData, bool $verifyNow = true): Perusahaan
    {
        if ($verifyNow) {
            $validatedData['status_verifikasi'] = 'Terverifikasi';
        }

        $company->update($validatedData);

        return $company->fresh();
    }

    /**
     * Menolak pengajuan perusahaan.
     */
    public function rejectCompany(Perusahaan $company, ?string $alasan = null): bool
    {
        return $company->update([
            'status_verifikasi' => 'Ditolak',
        ]);
    }

    /**
     * Membersihkan nama perusahaan dari kata-kata umum, singkatan PT/CV, tanda baca, dsb.
     */
    protected function cleanCompanyName(string $name): string
    {
        $lower = strtolower(trim($name));

        // Hapus tanda baca titik, koma, strip, kurung
        $clean = preg_replace('/[.,\-_()\[\]\/\\\\]+/', ' ', $lower);
        $words = preg_split('/\s+/', (string) $clean, -1, PREG_SPLIT_NO_EMPTY) ?: [];

        // Hapus stop words umum
        $filtered = array_filter($words, fn ($w) => ! in_array($w, $this->stopWords, true));

        return trim(implode(' ', $filtered));
    }

    /**
     * Menghasilkan array token kata bersih.
     *
     * @return array<int, string>
     */
    protected function getTokens(string $name): array
    {
        $clean = $this->cleanCompanyName($name);
        if ($clean === '') {
            return [];
        }

        return preg_split('/\s+/', $clean, -1, PREG_SPLIT_NO_EMPTY) ?: [];
    }

    /**
     * Menghasilkan akronim/singkatan dari huruf depan setiap kata penting.
     */
    protected function makeAcronym(string $name): string
    {
        $tokens = $this->getTokens($name);
        if (count($tokens) <= 1) {
            return '';
        }

        $acronym = '';
        foreach ($tokens as $token) {
            $acronym .= $token[0] ?? '';
        }

        return strtolower($acronym);
    }
}
