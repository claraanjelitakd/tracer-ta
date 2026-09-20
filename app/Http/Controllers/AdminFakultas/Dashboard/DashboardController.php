<?php

namespace App\Http\Controllers\AdminFakultas\Dashboard;

use App\Http\Controllers\Controller;
use App\Models\Prodi;
use App\Models\RefFakultas;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Inertia\Inertia;
use Inertia\Response;

/**
 * DashboardController (Admin Fakultas)
 *
 * Fungsi:
 * Menampilkan ringkasan metrik partisipasi tracer study, distribusi per prodi dalam fakultas,
 * dan ringkasan data alumni fakultas yang bersangkutan.
 */
class DashboardController extends Controller
{
    /**
     * Tampilkan Dashboard Utama Admin Fakultas
     */
    public function tampilkanDashboard(Request $request): Response
    {
        $user = Auth::user()->load('fakultas');
        $fakultasId = $user->fakultas_id;

        if (! $fakultasId) {
            // Jika belum di-set fakultas_id di user, cari dari relasi atau ambil fakultas default
            $fakultas = RefFakultas::first();
            $fakultasId = $fakultas?->id;
        } else {
            $fakultas = $user->fakultas ?: RefFakultas::find($fakultasId);
        }

        // Ambil daftar prodi dalam fakultas ini
        $prodisFakultas = Prodi::where('fakultas_id', $fakultasId)
            ->orderBy('kode_prodi', 'asc')
            ->get();

        $prodiIds = $prodisFakultas->pluck('id')->all();

        // Kueri dari Database View v_alumni_audit_rekap
        $alumniView = DB::table('v_alumni_audit_rekap')
            ->whereIn('prodi_id', $prodiIds)
            ->get();

        $totalAlumni = $alumniView->count();
        $totalSelesai = $alumniView->where('is_complete_total', 1)->count();
        $persentaseSelesai = $totalAlumni > 0 ? (int) round(($totalSelesai / $totalAlumni) * 100) : 0;

        // Hitung distribusi partisipasi per program studi dalam fakultas
        $prodiSummaries = $prodisFakultas->map(function ($p) use ($alumniView) {
            $alumniProdi = $alumniView->where('prodi_id', $p->id);
            $countProdi = $alumniProdi->count();
            $selesaiProdi = $alumniProdi->where('is_complete_total', 1)->count();
            $rate = $countProdi > 0 ? (int) round(($selesaiProdi / $countProdi) * 100) : 0;

            return [
                'id' => $p->id,
                'kode_prodi' => $p->kode_prodi,
                'nama_prodi' => $p->nama_prodi,
                'total_alumni' => $countProdi,
                'total_responden' => $selesaiProdi,
                'response_rate' => $rate,
            ];
        });

        // 5 data alumni terbaru dalam fakultas
        $recentAlumni = $alumniView->sortByDesc('biodata_id')->take(5)->map(function ($item) {
            return [
                'id' => $item->biodata_id,
                'nim' => $item->nim,
                'nama' => $item->nama ?? 'Mahasiswa',
                'prodi' => $item->nama_prodi ?? '-',
                'is_complete' => (bool) $item->is_complete_total,
                'tahun_lulus' => $item->tahun_lulus ?? '-',
            ];
        })->values()->all();

        return Inertia::render('AdminFakultas/Dashboard', [
            'user' => $user,
            'fakultas' => $fakultas,
            'prodis' => $prodisFakultas,
            'prodiSummaries' => $prodiSummaries,
            'recentAlumni' => $recentAlumni,
            'stats' => [
                'total_alumni' => $totalAlumni,
                'total_selesai' => $totalSelesai,
                'total_belum_selesai' => max(0, $totalAlumni - $totalSelesai),
                'persentase_selesai' => $persentaseSelesai,
                'total_prodi' => $prodisFakultas->count(),
            ],
        ]);
    }
}
