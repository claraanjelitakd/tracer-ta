<?php

namespace App\Http\Controllers\AdminProdi\Dashboard;

use App\Http\Controllers\Controller;
use App\Models\Biodata;
use App\Models\ProdiQuestion;
use App\Models\ProdiQuestionSection;
use App\Models\ProdiResponse;
use App\Services\Kuesioner\KelengkapanTracerService;
use Illuminate\Support\Facades\Auth;
use Inertia\Inertia;
use Inertia\Response;

/**
 * DashboardController
 *
 * Fungsi: Menampilkan halaman utama untuk Admin Program Studi.
 * Menyajikan statistik alumni, status pengisian kuesioner, dan instrumen pertanyaan khusus prodi.
 */
class DashboardController extends Controller
{
    /**
     * Tampilkan Dashboard Admin Prodi
     */
    public function tampilkanDashboard(): Response
    {
        $user = Auth::user()->load('prodi');
        $prodi = $user->prodi;
        $prodiId = $user->prodi_id;

        // Hitung total alumni di program studi ini
        $totalAlumni = $prodiId ? Biodata::where('prodi_id', $prodiId)->count() : 0;

        // Hitung total section dan pertanyaan khusus prodi ini
        $totalSections = $prodiId ? ProdiQuestionSection::where('prodi_id', $prodiId)->count() : 0;
        $totalPertanyaan = $prodiId ? ProdiQuestion::where('prodi_id', $prodiId)->count() : 0;

        $prodiQuestionIds = $prodiId ? ProdiQuestion::where('prodi_id', $prodiId)->pluck('id') : collect();

        // Hitung alumni yang sudah merespon kuesioner prodi
        $totalRespondenProdi = 0;
        if ($prodiQuestionIds->isNotEmpty()) {
            $totalRespondenProdi = ProdiResponse::whereIn('prodi_question_id', $prodiQuestionIds)
                ->distinct('biodata_id')
                ->count('biodata_id');
        }

        // Hitung alumni prodi yang sudah menyelesaikan kuesioner universitas
        $alumnis = $prodiId ? Biodata::where('prodi_id', $prodiId)->with(['user', 'dataAkademik', 'yudisium'])->get() : collect();
        $totalUnivSelesai = 0;
        foreach ($alumnis as $a) {
            $eval = KelengkapanTracerService::evaluasiKelengkapanTotal($a);
            if ($eval['questionnaire']['is_complete'] ?? false) {
                $totalUnivSelesai++;
            }
        }

        $persentasePartisipasi = $totalAlumni > 0 ? round(($totalRespondenProdi / $totalAlumni) * 100, 1) : 0;
        $persentaseUniv = $totalAlumni > 0 ? round(($totalUnivSelesai / $totalAlumni) * 100, 1) : 0;

        // Ringkasan 5 alumni terbaru untuk tabel cepat di dashboard
        $recentAlumnis = $alumnis->take(5)->map(function ($alumni) use ($prodiQuestionIds, $totalPertanyaan) {
            $eval = KelengkapanTracerService::evaluasiKelengkapanTotal($alumni);
            $prodiAnswers = ProdiResponse::where('biodata_id', $alumni->id)
                ->whereIn('prodi_question_id', $prodiQuestionIds)
                ->count();

            return [
                'id' => $alumni->id,
                'nim' => $alumni->nim,
                'nama' => $alumni->nama ?? $alumni->dataAkademik?->nama ?? $alumni->user?->name ?? '-',
                'tahun_lulus' => $alumni->tahun_lulus ?? $alumni->yudisium?->tahun_lulus ?? $alumni->dataAkademik?->tahun_akademik_lulus ?? '-',
                'is_univ_complete' => $eval['questionnaire']['is_complete'] ?? false,
                'is_prodi_complete' => ($totalPertanyaan > 0 && $prodiAnswers >= $totalPertanyaan),
            ];
        });

        return Inertia::render('AdminProdi/Dashboard', [
            'user' => $user,
            'prodi' => $prodi,
            'stats' => [
                'totalAlumni' => $totalAlumni,
                'totalSections' => $totalSections,
                'totalPertanyaan' => $totalPertanyaan,
                'totalRespondenProdi' => $totalRespondenProdi,
                'persentasePartisipasi' => $persentasePartisipasi,
                'totalUnivSelesai' => $totalUnivSelesai,
                'persentaseUniv' => $persentaseUniv,
            ],
            'recentAlumnis' => $recentAlumnis,
        ]);
    }
}
