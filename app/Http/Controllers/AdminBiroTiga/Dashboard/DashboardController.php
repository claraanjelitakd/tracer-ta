<?php

namespace App\Http\Controllers\AdminBiroTiga\Dashboard;

use App\Http\Controllers\Controller;
use App\Models\Perusahaan;
use App\Models\Prodi;
use App\Models\RefSubpertanyaan2021;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Inertia\Inertia;

/**
 * DashboardController (Biro 3)
 *
 * Fungsi: Menyajikan dashboard eksekutif resmi untuk Administrator Biro 3 (Biro Kemahasiswaan, Alumni, dan Pengembangan Karir).
 * Tujuan: Memberikan ringkasan indikator kinerja utama (KPI) pelacakan alumni, tingkat partisipasi pengisian kuesioner,
 *         serta pintu gerbang navigasi utama ke pengelolaan data alumni dan instrumen pertanyaan.
 */
class DashboardController extends Controller
{
    /**
     * Menampilkan Halaman Dashboard Utama Biro 3
     *
     * Hubungan dengan Frontend:
     * Method ini adalah pasangan dari file: resources/js/Pages/AdminBiroTiga/Dashboard.vue
     * Data di dalam Inertia::render(...) akan diterima sebagai props oleh komponen Vue tersebut.
     */
    public function tampilkanDashboard(Request $request)
    {
        // $user: Menyimpan data akun Admin Biro 3 yang sedang aktif login (id, name, email, role)
        $user = $request->user();

        // -----------------------------------------------------------------
        // 1. Perhitungan Metrik KPI Utama berbasis View (v_alumni_audit_rekap)
        // -----------------------------------------------------------------

        // $totalAlumni: Menghitung jumlah seluruh baris data alumni dari database view v_alumni_audit_rekap
        $totalAlumni = DB::table('v_alumni_audit_rekap')->count();

        // $totalResponden: Menghitung alumni yang sudah merespon pertanyaan kuesioner wajib universitas
        $totalResponden = DB::table('v_alumni_audit_rekap')->where('answered_mandatory_univ', '>', 0)->count();

        // $persentaseRespon: Menghitung rasio responden dibandingkan total alumni dalam bentuk persen (misal: 65.5%)
        $persentaseRespon = $totalAlumni > 0 ? round(($totalResponden / $totalAlumni) * 100, 1) : 0;

        // $totalPertanyaan: Menghitung jumlah seluruh butir instrumen pertanyaan kuesioner di tabel 'ref_subpertanyaan2021'
        $totalPertanyaan = RefSubpertanyaan2021::count();

        // $totalProdi: Menghitung jumlah program studi yang terdaftar di tabel 'prodis'
        $totalProdi = Prodi::count();

        // $alumniLinkedIn: Menghitung jumlah alumni yang profilnya sudah terhubung dengan LinkedIn
        $alumniLinkedIn = DB::table('v_alumni_audit_rekap')->where(function ($q) {
            $q->whereNotNull('linkedin_url')
                ->where('linkedin_url', '!=', '')
                ->orWhereNotNull('linkedin_username')
                ->where('linkedin_username', '!=', '');
        })->count();

        // -----------------------------------------------------------------
        // 2. Ringkasan Statistik Partisipasi per Program Studi (Single Query)
        // -----------------------------------------------------------------

        $statsPerProdi = DB::table('v_alumni_audit_rekap')
            ->select(
                'prodi_id',
                DB::raw('COUNT(*) as total_alumni'),
                DB::raw('SUM(CASE WHEN answered_mandatory_univ > 0 THEN 1 ELSE 0 END) as total_responden')
            )
            ->whereNotNull('prodi_id')
            ->groupBy('prodi_id')
            ->get()
            ->keyBy('prodi_id');

        $prodiSummaries = Prodi::orderBy('kode_prodi', 'asc')
            ->get()
            ->map(function ($prodi) use ($statsPerProdi) {
                $stat = $statsPerProdi->get($prodi->id);
                $totalBiodata = (int) ($stat->total_alumni ?? 0);
                $respondenCount = (int) ($stat->total_responden ?? 0);
                $rate = $totalBiodata > 0
                    ? round(($respondenCount / $totalBiodata) * 100, 1)
                    : 0;

                return [
                    'id' => $prodi->id,
                    'kode_prodi' => $prodi->kode_prodi,
                    'nama_prodi' => $prodi->nama_prodi,
                    'total_alumni' => $totalBiodata,
                    'total_responden' => $respondenCount,
                    'response_rate' => $rate,
                ];
            });

        // -----------------------------------------------------------------
        // 3. Data Alumni Terbaru untuk Pratinjau Cepat via v_alumni_audit_rekap
        // -----------------------------------------------------------------

        $totalPendingPerusahaan = Perusahaan::where('status_verifikasi', 'Menunggu Verifikasi')->count();

        $recentAlumni = DB::table('v_alumni_audit_rekap')
            ->orderByDesc('biodata_id')
            ->take(8)
            ->get()
            ->map(function ($alumni) {
                return [
                    'id' => $alumni->biodata_id,
                    'nim' => $alumni->nim ?? '-',
                    'nama' => $alumni->nama ?? 'Belum terisi',
                    'prodi' => $alumni->nama_prodi ?? '-',
                    'tahun_lulus' => $alumni->tahun_lulus ?? $alumni->tahun_akademik_lulus ?? '-',
                    'has_linkedin' => ! empty($alumni->linkedin_url) || ! empty($alumni->linkedin_username),
                    'has_responded' => ((int) $alumni->answered_mandatory_univ) > 0,
                ];
            });

        // -----------------------------------------------------------------
        // 4. Melempar Data ke Halaman Vue (Frontend)
        // -----------------------------------------------------------------
        // Membuka file: resources/js/Pages/AdminBiroTiga/Dashboard.vue
        return Inertia::render('AdminBiroTiga/Dashboard', [
            'user' => $user,           // Props: Informasi admin yang sedang login
            'stats' => [                // Props: Kumpulan metrik KPI untuk kartu-kartu statistik atas
                'total_alumni' => $totalAlumni,
                'total_responden' => $totalResponden,
                'persentase_respon' => $persentaseRespon,
                'total_pertanyaan' => $totalPertanyaan,
                'total_prodi' => $totalProdi,
                'alumni_linkedin' => $alumniLinkedIn,
                'total_pending_perusahaan' => $totalPendingPerusahaan,
            ],
            'prodiSummaries' => $prodiSummaries, // Props: Data untuk tabel statistik partisipasi per prodi
            'recentAlumni' => $recentAlumni,   // Props: Data untuk tabel alumni terbaru
        ]);
    }
}
