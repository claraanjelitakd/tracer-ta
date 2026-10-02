<?php

namespace App\Http\Controllers\SuperAdmin\LinkedIn;

use App\Http\Controllers\Controller;
use App\Models\Biodata;
use App\Models\DataAkademik;
use App\Services\LinkedIn\LinkedInProfileService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

/**
 * Class LinkedInSyncController
 *
 * Mengelola fitur Sinkronisasi Profil LinkedIn untuk Super Admin:
 * - Menampilkan direktori alumni berdasarkan filter Tahun Kelulusan resmi.
 * - Menyediakan sinkronisasi individual berbasis Mock LinkedIn Provider.
 * - Menyediakan sinkronisasi massal (Bulk Sync) per angkatan kelulusan.
 * - Menghitung metrik analitik status sinkronisasi profil secara terpadu.
 */
class LinkedInSyncController extends Controller
{
    /**
     * Menampilkan halaman utama Sinkronisasi LinkedIn Super Admin.
     */
    public function index(Request $request): Response
    {
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
        $queryAlumni = Biodata::with(['perusahaan', 'dataAkademik', 'latestLinkedinSyncLog'])
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

        $alumniList = $queryAlumni->map(function (Biodata $b) use (&$denganLinkedin, &$berhasilSinkron, &$gagalSinkron, &$dilewati) {
            $hasUsername = ! empty(trim($b->linkedin_username ?? ''));
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
            ];
        })->values()->all();

        return Inertia::render('SuperAdmin/LinkedIn/Index', [
            'daftarTahun' => $daftarTahun,
            'tahunTerpilih' => $tahunTerpilih,
            'alumnis' => $alumniList,
            'stats' => [
                'total_alumni' => $totalAlumni,
                'dengan_linkedin' => $denganLinkedin,
                'berhasil_sinkron' => $berhasilSinkron,
                'gagal_sinkron' => $gagalSinkron,
                'dilewati' => $dilewati,
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
     * Memproses sinkronisasi massal (Bulk Sync) untuk alumni pada tahun tertentu.
     * Mengembalikan ringkasan status eksekusi.
     */
    public function syncBatch(Request $request, LinkedInProfileService $service): JsonResponse
    {
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
