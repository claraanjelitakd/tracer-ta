<?php

namespace App\Http\Controllers\SuperAdmin\Dashboard;

use App\Http\Controllers\Controller;
use App\Models\Biodata;
use App\Models\KelompokPertanyaan;
use App\Models\LogActivity;
use App\Models\Perusahaan;
use App\Models\Prodi;
use App\Models\RefSubpertanyaan2021;
use App\Models\Tracer;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

/**
 * Class DashboardController (SuperAdmin)
 *
 * Fungsi:
 * Menampilkan halaman dasbor eksekutif utama untuk Superadmin.
 *
 * Tujuan:
 * Menyajikan gambaran umum ekosistem Tracer Study secara menyeluruh,
 * mencakup status instrumen kuesioner, total responden alumni,
 * serta akses cepat menuju modul pengelolaan kuesioner dan pengaturan sistem.
 */
class DashboardController extends Controller
{
    /**
     * Tampilkan Dashboard Superadmin beserta metrik ringkasan sistem.
     *
     * @return Response
     */
    public function tampilkanDashboard(Request $request)
    {
        $user = $request->user();

        // 1. Menghitung ringkasan statistik kuesioner dan partisipasi
        $totalPertanyaan = RefSubpertanyaan2021::count();
        $totalSections = KelompokPertanyaan::count();
        $totalAlumni = Biodata::count();
        $totalResponden = Tracer::distinct('biodata_id')->count('biodata_id');
        $totalProdi = Prodi::count();
        $totalPendingPerusahaan = Perusahaan::where('status_verifikasi', 'Menunggu Verifikasi')->count();
        $responseRate = $totalAlumni > 0 ? round(($totalResponden / $totalAlumni) * 100, 1) : 0;

        $totalLinkedIn = Biodata::where(function ($q) {
            $q->whereNotNull('linkedin_url')->where('linkedin_url', '!=', '')
                ->orWhereNotNull('linkedin_username')->where('linkedin_username', '!=', '');
        })->count();

        // 2. Ringkasan Partisipasi per Program Studi se-Universitas
        $prodiSummaries = Prodi::withCount('biodatas')
            ->orderBy('kode_prodi', 'asc')
            ->get()
            ->map(function ($p) {
                $respondenCount = Biodata::where('prodi_id', $p->id)
                    ->whereHas('tracers')
                    ->count();
                $totalBio = $p->biodatas_count ?? 0;
                $rate = $totalBio > 0 ? round(($respondenCount / $totalBio) * 100, 1) : 0;

                return [
                    'id' => $p->id,
                    'kode_prodi' => $p->kode_prodi,
                    'nama_prodi' => $p->nama_prodi,
                    'total_alumni' => $totalBio,
                    'total_responden' => $respondenCount,
                    'response_rate' => $rate,
                ];
            });

        // 3. Pratinjau 8 Alumni Terbaru Terdaftar
        $recentAlumni = Biodata::with(['prodi', 'user', 'dataAkademik'])
            ->latest()
            ->take(8)
            ->get()
            ->map(function ($alumni) {
                return [
                    'id' => $alumni->id,
                    'nim' => $alumni->nim ?? $alumni->user?->username ?? '-',
                    'nama' => $alumni->nama ?? $alumni->dataAkademik?->nama ?? $alumni->user?->name ?? 'Mahasiswa',
                    'prodi' => $alumni->prodi?->nama_prodi ?? '-',
                    'tahun_lulus' => $alumni->tahun_lulus ?? $alumni->dataAkademik?->tahun_akademik_lulus ?? '-',
                    'has_responded' => $alumni->tracers()->exists(),
                    'has_linkedin' => ! empty($alumni->linkedin_url) || ! empty($alumni->linkedin_username),
                ];
            });

        // 4. Pratinjau 6 Log Aktivitas Audit Terbaru
        $recentLogs = LogActivity::with('user')
            ->latest()
            ->take(6)
            ->get()
            ->map(function ($log) {
                return [
                    'id' => $log->id,
                    'user_name' => $log->user?->name ?? 'Sistem',
                    'action' => $log->action,
                    'description' => $log->description,
                    'ip_address' => $log->ip_address,
                    'created_at' => $log->created_at ? $log->created_at->diffForHumans() : '-',
                ];
            });

        // 5. Antrean Perusahaan Menunggu Verifikasi
        $pendingPerusahaanList = Perusahaan::with('createdProdi')
            ->where('status_verifikasi', 'Menunggu Verifikasi')
            ->latest()
            ->take(5)
            ->get()
            ->map(function ($c) {
                return [
                    'id' => $c->id,
                    'nama' => $c->nama_perusahaan,
                    'sektor' => $c->sektor_industri ?? $c->bentuk_lembaga ?? '-',
                    'kota' => $c->kota ?? $c->wilayah ?? '-',
                    'prodi' => $c->createdProdi?->nama_prodi ?? 'Umum',
                    'created_at' => $c->created_at ? $c->created_at->diffForHumans() : '-',
                ];
            });

        // 6. Mengembalikan view dasbor Superadmin
        return Inertia::render('SuperAdmin/Dashboard', [
            'user' => $user,
            'stats' => [
                'total_pertanyaan' => $totalPertanyaan,
                'total_sections' => $totalSections,
                'total_alumni' => $totalAlumni,
                'total_responden' => $totalResponden,
                'total_prodi' => $totalProdi,
                'total_pending_perusahaan' => $totalPendingPerusahaan,
                'response_rate' => $responseRate,
                'total_linkedin' => $totalLinkedIn,
            ],
            'prodiSummaries' => $prodiSummaries,
            'recentAlumni' => $recentAlumni,
            'recentLogs' => $recentLogs,
            'pendingPerusahaanList' => $pendingPerusahaanList,
        ]);
    }
}
