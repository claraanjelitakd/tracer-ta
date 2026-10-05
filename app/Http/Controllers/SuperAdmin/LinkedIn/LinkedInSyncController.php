<?php

namespace App\Http\Controllers\SuperAdmin\LinkedIn;

use App\Http\Controllers\Controller;
use App\Models\Biodata;
use App\Models\DataAkademik;
use App\Models\LinkedinSyncResult;
use App\Models\LogActivity;
use App\Services\LinkedIn\Exceptions\LinkedInProfileNotFoundException;
use App\Services\LinkedIn\Exceptions\LinkedInSyncException;
use App\Services\LinkedIn\LinkedInProfileService;
use App\Services\LinkedIn\Mappers\LinkedInProfileMapper;
use App\Services\LinkedIn\Providers\ApifyLinkedInProvider;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Inertia\Inertia;
use Inertia\Response;

/**
 * Class LinkedInSyncController
 *
 * Mengelola fitur Sinkronisasi Profil LinkedIn untuk Super Admin:
 * - Menampilkan direktori alumni berdasarkan filter Tahun Kelulusan resmi.
 * - Mendukung 2 provider resmi yang dapat dikonfigurasi:
 *   1. Official LinkedIn Provider (Driver: Mock atau API server-to-server)
 *   2. Apify LinkedIn Scraper Provider (Actor: data_forge_org~linkedin-scraper) dengan flow staging & review
 * - Menyediakan sinkronisasi individual per-alumni.
 * - Menyediakan alur Review Staging (Approve / Reject) untuk provider Apify.
 * - Menyediakan histori lengkap setiap sinkronisasi tanpa menimpa data sebelumnya.
 */
class LinkedInSyncController extends Controller
{
    /**
     * Menampilkan halaman utama Sinkronisasi LinkedIn Super Admin.
     */
    public function index(Request $request): Response
    {
        $provider = config('services.linkedin.provider', 'official');

        // 1. Ambil daftar tahun kelulusan unik dari master data akademik / yudisium
        $daftarTahun = DataAkademik::whereNotNull('tahun_lulus')
            ->where('tahun_lulus', '>', 1990)
            ->distinct()
            ->orderByDesc('tahun_lulus')
            ->pluck('tahun_lulus')
            ->map(fn ($y) => (string) $y)
            ->values()
            ->all();

        if (empty($daftarTahun)) {
            $daftarTahun = [(string) date('Y')];
        }

        // 2. Tahun terpilih dari request (default: tahun kelulusan terbaru)
        $tahunTerpilih = (string) $request->input('tahun', $daftarTahun[0]);

        // 3. Query alumni yang lulus pada tahun tersebut
        $queryAlumni = Biodata::with(['perusahaan', 'dataAkademik', 'latestLinkedinSyncLog', 'latestLinkedinSyncResult.reviewer'])
            ->where(function ($q) use ($tahunTerpilih) {
                $q->whereHas('dataAkademik', fn ($sub) => $sub->where('tahun_lulus', $tahunTerpilih))
                    ->orWhereHas('yudisium.dataAkademik', fn ($sub) => $sub->where('tahun_lulus', $tahunTerpilih));
            })
            ->orderBy('nim', 'asc')
            ->get();

        // 4. Kalkulasi metrik analitik (Summary KPI)
        $totalAlumni = $queryAlumni->count();
        $denganLinkedin = 0;
        $berhasilSinkron = 0;
        $gagalSinkron = 0;
        $dilewati = 0;
        $pendingReview = 0;

        $alumniList = $queryAlumni->map(function (Biodata $b) use (&$denganLinkedin, &$berhasilSinkron, &$gagalSinkron, &$dilewati, &$pendingReview, $provider) {
            $hasUrl = ! empty(trim($b->linkedin_url ?? ''));
            $hasUsername = ! empty(trim($b->linkedin_username ?? ''));

            if ($provider === 'apify') {
                if ($hasUrl) {
                    $denganLinkedin++;
                } else {
                    $dilewati++;
                }

                $latestResult = $b->latestLinkedinSyncResult;
                $statusSync = 'Belum Disinkronkan';
                $statusColor = 'slate';

                if ($latestResult) {
                    if ($latestResult->status === 'pending') {
                        $statusSync = 'Pending Review';
                        $statusColor = 'amber';
                        $pendingReview++;
                    } elseif ($latestResult->status === 'approved') {
                        $statusSync = 'Approved';
                        $statusColor = 'emerald';
                        $berhasilSinkron++;
                    } elseif ($latestResult->status === 'rejected') {
                        $statusSync = 'Rejected';
                        $statusColor = 'rose';
                        $gagalSinkron++;
                    }
                } elseif (! $hasUrl) {
                    $statusSync = 'Belum Ada URL LinkedIn';
                    $statusColor = 'slate';
                }

                $terakhirSync = $latestResult ? $latestResult->scraped_at?->format('d/m/Y H:i') : '-';
                $terakhirSyncHuman = $latestResult ? $latestResult->scraped_at?->diffForHumans() : '-';

                return [
                    'id' => $b->id,
                    'nim' => $b->nim,
                    'nama' => $b->nama ?? 'Nama Alumni Tidak Ditemukan',
                    'tahun_lulus' => $b->tahun_lulus ?? '-',
                    'linkedin_username' => $b->linkedin_username ?: null,
                    'linkedin_url' => $b->linkedin_url ?: null,
                    'perusahaan' => $b->perusahaan?->nama_perusahaan ?? '-',
                    'posisi' => $b->posisi_jabatan ?? '-',
                    'status_sync' => $statusSync,
                    'status_color' => $statusColor,
                    'terakhir_sync' => $terakhirSync ?: '-',
                    'terakhir_sync_human' => $terakhirSyncHuman ?: '-',
                    'latest_sync_result' => $latestResult ? [
                        'id' => $latestResult->id,
                        'status' => $latestResult->status,
                        'scraped_at' => $latestResult->scraped_at?->format('d/m/Y H:i'),
                        'reviewed_at' => $latestResult->reviewed_at?->format('d/m/Y H:i'),
                        'reviewer_name' => $latestResult->reviewer?->name,
                    ] : null,
                ];
            }

            // Official Provider Flow
            if ($hasUsername) {
                $denganLinkedin++;
            } else {
                $dilewati++;
            }

            $lastLog = $b->latestLinkedinSyncLog;
            $statusSync = 'Belum Disinkronkan';
            $statusColor = 'slate';

            if ($lastLog) {
                $newValues = $lastLog->new_values ?? [];
                $logStatus = $newValues['status'] ?? null;

                if ($logStatus === 'Berhasil') {
                    $statusSync = 'Berhasil';
                    $statusColor = 'emerald';
                    $berhasilSinkron++;
                } elseif ($logStatus === 'Gagal') {
                    $statusSync = 'Gagal';
                    $statusColor = 'rose';
                    $gagalSinkron++;
                }
            } elseif (! $hasUsername) {
                $statusSync = 'Belum Ada Username';
                $statusColor = 'amber';
            }

            return [
                'id' => $b->id,
                'nim' => $b->nim,
                'nama' => $b->nama ?? 'Nama Alumni Tidak Ditemukan',
                'tahun_lulus' => $b->tahun_lulus ?? '-',
                'linkedin_username' => $b->linkedin_username ?: null,
                'linkedin_url' => $b->linkedin_url ?: null,
                'perusahaan' => $b->perusahaan?->nama_perusahaan ?? '-',
                'posisi' => $b->posisi_jabatan ?? '-',
                'status_sync' => $statusSync,
                'status_color' => $statusColor,
                'terakhir_sync' => $lastLog ? $lastLog->created_at->format('d/m/Y H:i') : '-',
                'terakhir_sync_human' => $lastLog ? $lastLog->created_at->diffForHumans() : '-',
                'latest_sync_result' => null,
            ];
        })->values()->all();

        return Inertia::render('SuperAdmin/LinkedIn/Index', [
            'daftarTahun' => $daftarTahun,
            'tahunTerpilih' => $tahunTerpilih,
            'provider' => $provider,
            'alumnis' => $alumniList,
            'stats' => [
                'total_alumni' => $totalAlumni,
                'dengan_linkedin' => $denganLinkedin,
                'berhasil_sinkron' => $berhasilSinkron,
                'gagal_sinkron' => $gagalSinkron,
                'dilewati' => $dilewati,
                'pending_review' => $pendingReview,
            ],
        ]);
    }

    /**
     * Memproses sinkronisasi profil LinkedIn untuk satu alumni.
     * Mengembalikan respons JSON agar tidak memicu reload halaman penuh.
     */
    public function syncSingle(Request $request, int $id, LinkedInProfileService $service): JsonResponse
    {
        $biodata = Biodata::with(['perusahaan', 'dataAkademik'])->findOrFail($id);
        $provider = config('services.linkedin.provider', 'official');

        // =====================================================================
        // FLOW APIFY: STAGING PER-ALUMNI (STATUS PENDING)
        // =====================================================================
        if ($provider === 'apify') {
            $url = trim($biodata->linkedin_url ?? '');

            // 1. Validasi URL tidak boleh kosong
            if (empty($url)) {
                return response()->json([
                    'success' => false,
                    'status' => 'EMPTY_URL',
                    'message' => 'URL LinkedIn alumni belum diisi. Provider Apify hanya menerima input dari field linkedin_url.',
                ], 422);
            }

            // 2. Validasi format URL LinkedIn
            if (! ApifyLinkedInProvider::isValidLinkedInUrl($url)) {
                return response()->json([
                    'success' => false,
                    'status' => 'INVALID_URL',
                    'message' => "Format URL LinkedIn tidak valid: '{$url}'. Harap perbaiki URL profil LinkedIn alumni.",
                ], 422);
            }

            try {
                /** @var ApifyLinkedInProvider $apify */
                $apify = app(ApifyLinkedInProvider::class);

                // Jalankan request ke Apify Actor (tepat 1 alumni -> 1 request)
                $scrapedData = $apify->scrapeProfile($url);

                // Simpan hasil ke staging linkedin_sync_results (STATUS PENDING)
                // DATA UTAMA ALUMNI TIDAK BERUBAH SAMA SEKALI PADA TAHAP INI
                $syncResult = LinkedinSyncResult::create([
                    'biodata_id' => $biodata->id,
                    'linkedin_url' => $url,
                    'linkedin_username' => $biodata->linkedin_username,
                    'scraped_data' => $scrapedData,
                    'status' => 'pending',
                    'scraped_at' => now(),
                ]);

                // Catat log audit aktivitas
                LogActivity::record(
                    action: 'linkedin_sync_apify',
                    description: "Pengambilan data profil LinkedIn via Apify berhasil untuk alumni {$biodata->nama} ({$biodata->nim}). Menunggu tinjauan Super Admin.",
                    subject: $biodata,
                    oldValues: null,
                    newValues: [
                        'sync_result_id' => $syncResult->id,
                        'status' => 'pending',
                        'provider' => 'apify',
                    ],
                );

                return response()->json([
                    'success' => true,
                    'status' => 'PENDING',
                    'message' => 'Sinkronisasi profil via Apify berhasil. Data disimpan dalam status Pending Review.',
                    'sync_result_id' => $syncResult->id,
                    'sync_result' => [
                        'id' => $syncResult->id,
                        'status' => 'pending',
                        'scraped_at' => $syncResult->scraped_at?->format('d/m/Y H:i'),
                        'parsed' => $syncResult->getPreviewData(),
                    ],
                    'alumni' => [
                        'id' => $biodata->id,
                        'nim' => $biodata->nim,
                        'nama' => $biodata->nama,
                        'status_sync' => 'Pending Review',
                        'status_color' => 'amber',
                        'terakhir_sync' => $syncResult->scraped_at?->format('d/m/Y H:i'),
                        'terakhir_sync_human' => 'Baru saja',
                        'latest_sync_result' => [
                            'id' => $syncResult->id,
                            'status' => 'pending',
                            'scraped_at' => $syncResult->scraped_at?->format('d/m/Y H:i'),
                        ],
                    ],
                ]);

            } catch (LinkedInProfileNotFoundException $e) {
                return response()->json([
                    'success' => false,
                    'status' => 'NOT_FOUND',
                    'message' => 'Profil LinkedIn tidak ditemukan atau bersifat privat pada dataset Apify.',
                ], 404);
            } catch (LinkedInSyncException $e) {
                return response()->json([
                    'success' => false,
                    'status' => 'ERROR',
                    'message' => $e->getMessage(),
                ], 422);
            } catch (\Throwable $e) {
                return response()->json([
                    'success' => false,
                    'status' => 'ERROR',
                    'message' => 'Terjadi kesalahan sistem saat menghubungi server Apify.',
                ], 500);
            }
        }

        // =====================================================================
        // FLOW OFFICIAL LINKEDIN PROVIDER (EXISTING)
        // =====================================================================
        $result = $service->syncBiodata($biodata);

        // Ambil data mutakhir setelah sinkronisasi
        $fresh = $biodata->fresh(['perusahaan', 'latestLinkedinSyncLog', 'dataAkademik']);
        $lastLog = $fresh->latestLinkedinSyncLog;

        $statusSync = match ($result['status']) {
            'SUCCESS' => 'Berhasil',
            'SKIPPED' => 'Belum Ada Username',
            default => 'Gagal',
        };

        $statusColor = match ($result['status']) {
            'SUCCESS' => 'emerald',
            'SKIPPED' => 'amber',
            default => 'rose',
        };

        return response()->json([
            'success' => in_array($result['status'], ['SUCCESS', 'SKIPPED']),
            'status' => $result['status'],
            'message' => $result['message'],
            'summary' => $result['summary'],
            'alumni' => [
                'id' => $fresh->id,
                'nim' => $fresh->nim,
                'nama' => $fresh->nama,
                'tahun_lulus' => $fresh->tahun_lulus,
                'linkedin_username' => $fresh->linkedin_username,
                'linkedin_url' => $fresh->linkedin_url,
                'perusahaan' => $fresh->perusahaan?->nama_perusahaan ?? '-',
                'posisi' => $fresh->posisi_jabatan ?? '-',
                'status_sync' => $statusSync,
                'status_color' => $statusColor,
                'terakhir_sync' => $lastLog ? $lastLog->created_at->format('d/m/Y H:i') : now()->format('d/m/Y H:i'),
                'terakhir_sync_human' => 'Baru saja',
            ],
        ]);
    }

    /**
     * Menampilkan detail hasil sinkronisasi staging untuk peninjauan (Review Modal).
     */
    public function showResult(int $id): JsonResponse
    {
        $result = LinkedinSyncResult::with(['biodata.dataAkademik', 'biodata.perusahaan', 'reviewer'])
            ->findOrFail($id);

        return response()->json([
            'success' => true,
            'result' => [
                'id' => $result->id,
                'biodata_id' => $result->biodata_id,
                'alumni_nama' => $result->biodata->nama,
                'alumni_nim' => $result->biodata->nim,
                'linkedin_url' => $result->linkedin_url,
                'status' => $result->status,
                'scraped_at' => $result->scraped_at?->format('d/m/Y H:i'),
                'reviewed_at' => $result->reviewed_at?->format('d/m/Y H:i'),
                'reviewer_name' => $result->reviewer?->name,
                'preview' => $result->getPreviewData(),
                'raw' => $result->scraped_data,
                'current_data' => [
                    'posisi_jabatan' => $result->biodata->posisi_jabatan,
                    'nama_perusahaan' => $result->biodata->perusahaan?->nama_perusahaan,
                    'expert' => $result->biodata->expert,
                ],
            ],
        ]);
    }

    /**
     * Menyetujui (Approve) hasil sinkronisasi Apify dan menerapkan data ke data utama.
     */
    public function approveResult(Request $request, int $id, LinkedInProfileMapper $mapper): JsonResponse
    {
        return DB::transaction(function () use ($id, $mapper, $request) {
            $result = LinkedinSyncResult::with(['biodata.perusahaan'])->lockForUpdate()->findOrFail($id);

            // Validasi status wajib 'pending'
            if ($result->status !== 'pending') {
                return response()->json([
                    'success' => false,
                    'message' => "Hasil sinkronisasi ini sudah berstatus '{$result->status}' dan tidak dapat disetujui kembali.",
                ], 422);
            }

            $preview = $result->getPreviewData();
            $biodata = $result->biodata;
            $updates = [];
            $changesSummary = [];

            // Input kustom dari Super Admin (opsional jika diedit/disesuaikan di modal)
            $customJob = $request->filled('posisi_jabatan') ? trim($request->input('posisi_jabatan')) : null;
            $customWiraswasta = $request->filled('posisi_wiraswasta') ? trim($request->input('posisi_wiraswasta')) : null;
            $customCompany = $request->filled('nama_perusahaan') ? trim($request->input('nama_perusahaan')) : null;
            $tipePekerjaan = $request->input('tipe_pekerjaan', 'pekerja'); // 'pekerja' atau 'wirausaha'

            // 1. Pemetaan Posisi Jabatan / Posisi Wiraswasta
            if ($tipePekerjaan === 'wirausaha') {
                $targetWiraswasta = $customWiraswasta ?: $customJob ?: $preview['current_job'];
                if (! empty($targetWiraswasta)) {
                    $updates['posisi_wiraswasta'] = $targetWiraswasta;
                    $updates['kategori_pekerjaan'] = 'Wiraswasta';
                    $changesSummary[] = "Kategori diset Wiraswasta dengan posisi '{$targetWiraswasta}'";
                }
            } else {
                $targetJob = $customJob ?: $preview['current_job'];
                if (! empty($targetJob)) {
                    $oldJob = $biodata->posisi_jabatan;
                    $updates['posisi_jabatan'] = $targetJob;
                    $updates['kategori_pekerjaan'] = 'Pekerja';
                    $changesSummary[] = "Jabatan diperbarui dari '".($oldJob ?: '-')."' menjadi '{$targetJob}'";
                }
            }

            // 2. Pemetaan Perusahaan (Otomatis ditambahkan ke tabel master perusahaan dengan status Menunggu Verifikasi)
            $perusahaan = null;
            $targetCompany = $customCompany ?: $preview['current_company'];
            if (! empty($targetCompany)) {
                $companyName = trim($targetCompany);
                $locationName = $preview['current_company_location'] ?? $preview['location'];
                $companyUrl = $preview['current_company_url'] ?? null;

                $perusahaan = $mapper->findOrCreateCompany($companyName, $locationName);

                // Pastikan status perusahaan SELALU 'Menunggu Verifikasi' sesuai kebijakan approval ulang
                $perusahaanUpdates = [];
                if ($perusahaan->status_verifikasi !== 'Menunggu Verifikasi') {
                    $perusahaanUpdates['status_verifikasi'] = 'Menunggu Verifikasi';
                }
                if (empty($perusahaan->homepage) && ! empty($companyUrl)) {
                    $perusahaanUpdates['homepage'] = $companyUrl;
                }
                if (! empty($perusahaanUpdates)) {
                    $perusahaan->update($perusahaanUpdates);
                }

                if ($biodata->perusahaan_id !== $perusahaan->id) {
                    $oldCompany = $biodata->perusahaan?->nama_perusahaan ?? '-';
                    $updates['perusahaan_id'] = $perusahaan->id;
                    $changesSummary[] = "Perusahaan diperbarui dari '{$oldCompany}' menjadi '{$perusahaan->nama_perusahaan}' (Status: Menunggu Verifikasi)";
                }
            }

            // 3. Pemetaan Foto Profil (Diunduh ke public/uploads/profile)
            $syncFoto = $request->has('sync_foto') ? $request->boolean('sync_foto') : true;
            if ($syncFoto && ! empty($preview['profile_picture'])) {
                try {
                    $photoUrl = $preview['profile_picture'];
                    $imageResponse = Http::timeout(10)->get($photoUrl);

                    if ($imageResponse->successful() && ! empty($imageResponse->body())) {
                        $dir = public_path('uploads/profile');
                        if (! file_exists($dir)) {
                            mkdir($dir, 0755, true);
                        }
                        $safeNim = preg_replace('/[^a-zA-Z0-9_-]/', '', $biodata->nim ?: 'alumni');
                        $filename = "profile_{$safeNim}_".time().'.jpg';
                        file_put_contents("{$dir}/{$filename}", $imageResponse->body());
                        $updates['foto'] = "/uploads/profile/{$filename}";
                        $changesSummary[] = "Foto profil diunduh dan disimpan ke /uploads/profile/{$filename}";
                    } else {
                        $updates['foto'] = $photoUrl;
                        $changesSummary[] = 'Foto profil ditautkan dari URL LinkedIn';
                    }
                } catch (\Throwable $e) {
                    $updates['foto'] = $preview['profile_picture'];
                    $changesSummary[] = 'Foto profil ditautkan dari URL LinkedIn';
                }
            }

            // 4. Pemetaan Username LinkedIn (jika di biodata masih kosong)
            if (empty($biodata->linkedin_username) && ! empty($preview['handle'])) {
                $updates['linkedin_username'] = $preview['handle'];
                $changesSummary[] = "Username LinkedIn disimpan: {$preview['handle']}";
            }

            // 5. Pemetaan Pendidikan Lanjutan (hanya jika tersedia dan di biodata masih kosong)
            if (! empty($preview['education'][0])) {
                $edu = $preview['education'][0];
                if (empty($biodata->pendidikan_tingkat) && ! empty($edu['degree'])) {
                    $updates['pendidikan_tingkat'] = $edu['degree'];
                    $changesSummary[] = "Pendidikan tingkat disimpan: {$edu['degree']}";
                }
                if (empty($biodata->perguruan_tinggi) && ! empty($edu['school'])) {
                    $updates['perguruan_tinggi'] = $edu['school'];
                    $changesSummary[] = "Perguruan tinggi studi lanjut: {$edu['school']}";
                }
                if (empty($biodata->pendidikan_prodi) && ! empty($edu['field_of_study'])) {
                    $updates['pendidikan_prodi'] = $edu['field_of_study'];
                    $changesSummary[] = "Program studi studi lanjut: {$edu['field_of_study']}";
                }
            }

            // 6. Pemetaan Keahlian (Skills) jika ada dan field expert di biodata masih kosong
            if (! empty($preview['skills']) && empty($biodata->expert)) {
                $skillsStr = implode(', ', array_slice($preview['skills'], 0, 10));
                $updates['expert'] = $skillsStr;
                $changesSummary[] = "Keahlian diisi dari profil LinkedIn: {$skillsStr}";
            }

            // 7. Perbarui data utama biodata jika ada perubahan
            if (! empty($updates)) {
                $biodata->update($updates);
            }

            // 5. Ubah status staging menjadi 'approved'
            $result->update([
                'status' => 'approved',
                'reviewed_by' => Auth::id(),
                'reviewed_at' => now(),
            ]);

            // 6. Catat audit log aktivitas
            LogActivity::record(
                action: 'linkedin_sync_approved',
                description: "Super Admin menyetujui hasil sinkronisasi LinkedIn Apify untuk alumni {$biodata->nama} ({$biodata->nim}).",
                subject: $biodata,
                oldValues: null,
                newValues: [
                    'sync_result_id' => $result->id,
                    'status' => 'approved',
                    'changes' => $changesSummary,
                ]
            );

            $fresh = $biodata->fresh(['perusahaan']);

            return response()->json([
                'success' => true,
                'message' => "Data profil LinkedIn untuk {$biodata->nama} berhasil disetujui dan diterapkan.",
                'changes_summary' => $changesSummary,
                'alumni' => [
                    'id' => $fresh->id,
                    'perusahaan' => $fresh->perusahaan?->nama_perusahaan ?? '-',
                    'posisi' => $fresh->posisi_jabatan ?? '-',
                    'status_sync' => 'Approved',
                    'status_color' => 'emerald',
                    'latest_sync_result' => [
                        'id' => $result->id,
                        'status' => 'approved',
                        'reviewed_at' => $result->reviewed_at?->format('d/m/Y H:i'),
                    ],
                ],
            ]);
        });
    }

    /**
     * Menolak (Reject) hasil sinkronisasi Apify tanpa mengubah data utama alumni.
     */
    public function rejectResult(Request $request, int $id): JsonResponse
    {
        return DB::transaction(function () use ($id) {
            $result = LinkedinSyncResult::with('biodata')->lockForUpdate()->findOrFail($id);

            // Validasi status wajib 'pending'
            if ($result->status !== 'pending') {
                return response()->json([
                    'success' => false,
                    'message' => "Hasil sinkronisasi ini sudah berstatus '{$result->status}' dan tidak dapat ditolak.",
                ], 422);
            }

            // Data utama TIDAK berubah sama sekali
            $result->update([
                'status' => 'rejected',
                'reviewed_by' => Auth::id(),
                'reviewed_at' => now(),
            ]);

            // Catat log audit aktivitas
            LogActivity::record(
                action: 'linkedin_sync_rejected',
                description: "Super Admin menolak hasil sinkronisasi LinkedIn Apify untuk alumni {$result->biodata->nama} ({$result->biodata->nim}). Data utama tidak diubah.",
                subject: $result->biodata,
                oldValues: null,
                newValues: [
                    'sync_result_id' => $result->id,
                    'status' => 'rejected',
                ]
            );

            return response()->json([
                'success' => true,
                'message' => "Hasil sinkronisasi LinkedIn untuk {$result->biodata->nama} telah ditolak. Data utama tidak diubah.",
                'alumni' => [
                    'id' => $result->biodata->id,
                    'status_sync' => 'Rejected',
                    'status_color' => 'rose',
                    'latest_sync_result' => [
                        'id' => $result->id,
                        'status' => 'rejected',
                        'reviewed_at' => $result->reviewed_at?->format('d/m/Y H:i'),
                    ],
                ],
            ]);
        });
    }

    /**
     * Mengambil seluruh histori hasil sinkronisasi LinkedIn untuk seorang alumni.
     */
    public function history(int $id): JsonResponse
    {
        $biodata = Biodata::findOrFail($id);

        $results = LinkedinSyncResult::where('biodata_id', $id)
            ->with('reviewer')
            ->orderByDesc('id')
            ->get()
            ->map(function ($r) {
                return [
                    'id' => $r->id,
                    'status' => $r->status,
                    'linkedin_url' => $r->linkedin_url,
                    'scraped_at' => $r->scraped_at?->format('d/m/Y H:i'),
                    'reviewed_at' => $r->reviewed_at?->format('d/m/Y H:i'),
                    'reviewer_name' => $r->reviewer?->name ?? '-',
                    'preview' => $r->getPreviewData(),
                ];
            });

        return response()->json([
            'success' => true,
            'alumni' => [
                'id' => $biodata->id,
                'nim' => $biodata->nim,
                'nama' => $biodata->nama,
            ],
            'history' => $results,
        ]);
    }

    /**
     * Memproses sinkronisasi massal (Bulk Sync) untuk alumni pada tahun tertentu.
     * Mengembalikan ringkasan status eksekusi.
     */
    public function syncBatch(Request $request, LinkedInProfileService $service): JsonResponse
    {
        $provider = config('services.linkedin.provider', 'official');

        // Untuk provider Apify, bulk scraping dinonaktifkan demi Cost Safety
        if ($provider === 'apify') {
            return response()->json([
                'success' => false,
                'message' => 'Bulk sync dinonaktifkan untuk provider Apify demi keamanan biaya (Cost Safety). Silakan lakukan sinkronisasi per alumni.',
            ], 400);
        }

        $tahun = (string) $request->input('tahun');

        // Cari alumni pada tahun terpilih
        $alumniQuery = Biodata::where(function ($q) use ($tahun) {
            $q->whereHas('dataAkademik', fn ($sub) => $sub->where('tahun_lulus', $tahun))
                ->orWhereHas('yudisium.dataAkademik', fn ($sub) => $sub->where('tahun_lulus', $tahun));
        });

        $biodataIds = $alumniQuery->pluck('id')->all();

        if (empty($biodataIds)) {
            return response()->json([
                'success' => false,
                'message' => "Tidak ada alumni yang terdaftar pada tahun kelulusan {$tahun}.",
                'summary' => [
                    'total' => 0,
                    'berhasil' => 0,
                    'gagal' => 0,
                    'dilewati' => 0,
                ],
                'results' => [],
            ], 422);
        }

        $batchResult = $service->syncBatch($biodataIds);

        return response()->json([
            'success' => true,
            'message' => "Sinkronisasi massal tahun {$tahun} selesai.",
            'summary' => [
                'total' => $batchResult['total'],
                'berhasil' => $batchResult['berhasil'],
                'gagal' => $batchResult['gagal'],
                'dilewati' => $batchResult['dilewati'],
            ],
            'results' => $batchResult['results'],
        ]);
    }
}
