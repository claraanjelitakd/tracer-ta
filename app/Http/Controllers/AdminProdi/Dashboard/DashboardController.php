<?php

namespace App\Http\Controllers\AdminProdi\Dashboard;

use App\Http\Controllers\Controller;
use App\Models\Perusahaan;
use App\Models\ProdiQuestion;
use App\Models\ProdiQuestionSection;
use App\Models\ProdiResponse;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Inertia\Inertia;
use Inertia\Response;

/**
 * DashboardController
 *
 * Fungsi: Menampilkan halaman utama untuk Admin Program Studi.
 * Menyajikan statistik alumni, status pengisian kuesioner, dan instrumen pertanyaan khusus prodi
 * berkecepatan tinggi dengan memanfaatkan Database View `v_alumni_audit_rekap`.
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

        // 1. Data ringkasan alumni berbasis Database View (v_alumni_audit_rekap)
        $auditQuery = $prodiId
            ? DB::table('v_alumni_audit_rekap')->where('prodi_id', $prodiId)
            : null;

        $totalAlumni = $auditQuery ? (clone $auditQuery)->count() : 0;
        $totalUnivSelesai = $auditQuery ? (clone $auditQuery)->where('is_complete_univ', 1)->count() : 0;
        $totalLinkedIn = $auditQuery
            ? (clone $auditQuery)->where(function ($q) {
                $q->whereNotNull('linkedin_url')->where('linkedin_url', '!=', '')
                    ->orWhereNotNull('linkedin_username')->where('linkedin_username', '!=', '');
            })->count()
            : 0;

        // 2. Hitung total section dan pertanyaan khusus prodi ini
        $totalSections = $prodiId ? ProdiQuestionSection::where('prodi_id', $prodiId)->count() : 0;
        $totalPertanyaan = $prodiId ? ProdiQuestion::where('prodi_id', $prodiId)->count() : 0;

        $prodiQuestionIds = $prodiId ? ProdiQuestion::where('prodi_id', $prodiId)->where('type', '!=', 'header')->pluck('id') : collect();

        // 3. Hitung alumni yang sudah merespon kuesioner prodi
        $totalRespondenProdi = 0;
        if ($prodiQuestionIds->isNotEmpty()) {
            $totalRespondenProdi = ProdiResponse::whereIn('prodi_question_id', $prodiQuestionIds)
                ->distinct('biodata_id')
                ->count('biodata_id');
        }

        $persentasePartisipasi = $totalAlumni > 0 ? round(($totalRespondenProdi / $totalAlumni) * 100, 1) : 0;
        $persentaseUniv = $totalAlumni > 0 ? round(($totalUnivSelesai / $totalAlumni) * 100, 1) : 0;

        $totalPendingPerusahaan = $prodiId
            ? Perusahaan::where('created_by_prodi_id', $prodiId)->where('status_verifikasi', 'Menunggu Verifikasi')->count()
            : 0;

        // 4. Daftar Sections beserta jumlah pertanyaan
        $sectionsList = $prodiId
            ? ProdiQuestionSection::where('prodi_id', $prodiId)
                ->withCount('questions')
                ->orderBy('order', 'asc')
                ->get()
                ->map(fn ($s) => [
                    'id' => $s->id,
                    'title' => $s->title,
                    'order' => $s->order,
                    'questions_count' => $s->questions_count,
                ])
            : [];

        // 5. Ringkasan 8 alumni terbaru untuk tabel interaktif di dashboard via v_alumni_audit_rekap
        $recentAlumnis = $auditQuery
            ? (clone $auditQuery)
                ->orderByDesc('biodata_id')
                ->take(8)
                ->get()
                ->map(function ($row) {
                    return [
                        'id' => $row->biodata_id,
                        'nim' => $row->nim ?? '-',
                        'nama' => $row->nama ?? '-',
                        'tahun_lulus' => $row->tahun_lulus ?? $row->tahun_akademik_lulus ?? '-',
                        'is_univ_complete' => (bool) $row->is_complete_univ,
                        'is_prodi_complete' => (bool) $row->is_complete_prodi,
                        'has_linkedin' => ! empty($row->linkedin_url) || ! empty($row->linkedin_username),
                    ];
                })
            : collect([]);

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
                'totalPendingPerusahaan' => $totalPendingPerusahaan,
                'totalLinkedIn' => $totalLinkedIn,
            ],
            'sectionsList' => $sectionsList,
            'recentAlumnis' => $recentAlumnis,
        ]);
    }
}
