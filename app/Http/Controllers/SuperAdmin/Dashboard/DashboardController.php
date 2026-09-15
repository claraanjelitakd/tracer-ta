<?php

namespace App\Http\Controllers\SuperAdmin\Dashboard;

use App\Http\Controllers\Controller;
use App\Models\Biodata;
use App\Models\KelompokPertanyaan;
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

        // 2. Mengembalikan view dasbor Superadmin
        return Inertia::render('SuperAdmin/Dashboard', [
            'user' => $user,
            'stats' => [
                'total_pertanyaan' => $totalPertanyaan,
                'total_sections' => $totalSections,
                'total_alumni' => $totalAlumni,
                'total_responden' => $totalResponden,
                'total_prodi' => $totalProdi,
            ],
        ]);
    }
}
