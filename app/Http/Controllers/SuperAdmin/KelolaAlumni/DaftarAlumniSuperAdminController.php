<?php

namespace App\Http\Controllers\SuperAdmin\KelolaAlumni;

use App\Http\Controllers\Controller;
use App\Models\Prodi;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Inertia\Inertia;
use Inertia\Response;

/**
 * DaftarAlumniSuperAdminController
 *
 * Fungsi:
 * Menampilkan daftar seluruh alumni terpadu untuk Super Admin lengkap dengan filter Prodi,
 * Tahun Kelulusan, Semester Lulus, Pencarian Nama/NIM, serta audit status kelengkapan data
 * menggunakan Database View (v_alumni_audit_rekap) berkecepatan tinggi tanpa N+1 query.
 */
class DaftarAlumniSuperAdminController extends Controller
{
    /**
     * Tampilkan Halaman Index Daftar Alumni
     */
    public function index(Request $request): Response
    {
        $pencarian = $request->input('search');
        $tahunTerpilih = $request->input('tahun');
        $semesterTerpilih = $request->input('semester');
        $statusTerpilih = $request->input('status');
        $prodiIdTerpilih = $request->input('prodi_id');
        $targetTerpilih = $request->input('target');

        // 1. Ambil list tahun kelulusan unik dari view untuk dropdown filter
        $daftarTahun = DB::table('v_alumni_audit_rekap')
            ->whereNotNull('tahun_lulus')
            ->orWhereNotNull('tahun_akademik_lulus')
            ->pluck('tahun_lulus')
            ->filter()
            ->map(function ($item) {
                if (preg_match('/(\d{4})/', (string) $item, $matches)) {
                    return $matches[1];
                }

                return trim($item);
            })->unique()->sortDesc()->values()->all();

        // 1b. Ambil daftar target periode kelulusan unik (Semester & Tahun Lulus)
        $daftarTarget = DB::table('v_alumni_audit_rekap')
            ->whereNotNull('tahun_akademik_lulus')
            ->where('tahun_akademik_lulus', '!=', '')
            ->pluck('tahun_akademik_lulus')
            ->unique()
            ->sortDesc()
            ->values()
            ->all();

        // 2. Kueri cepat berbasis Database View (v_alumni_audit_rekap)
        $query = DB::table('v_alumni_audit_rekap');

        // Filter Program Studi
        if ($prodiIdTerpilih && $prodiIdTerpilih !== 'all') {
            $query->where('prodi_id', $prodiIdTerpilih);
        }

        // Filter Target Kelulusan (Semester + Tahun)
        if ($targetTerpilih && $targetTerpilih !== 'all') {
            $query->where('tahun_akademik_lulus', $targetTerpilih);
        }

        // Filter Pencarian (Nama Lengkap atau NIM)
        if ($pencarian) {
            $query->where(function ($w) use ($pencarian) {
                $w->where('nim', 'like', "%{$pencarian}%")
                    ->orWhere('nama', 'like', "%{$pencarian}%")
                    ->orWhere('email', 'like', "%{$pencarian}%");
            });
        }

        // Filter Tahun Kelulusan
        if ($tahunTerpilih && $tahunTerpilih !== 'all') {
            $query->where(function ($q) use ($tahunTerpilih) {
                $q->where('tahun_akademik_lulus', 'like', "%{$tahunTerpilih}%")
                    ->orWhere('tahun_lulus', 'like', "%{$tahunTerpilih}%");
            });
        }

        // Filter Semester Kelulusan (Gasal / Genap)
        if ($semesterTerpilih && $semesterTerpilih !== 'all') {
            $query->where('tahun_akademik_lulus', 'like', "%{$semesterTerpilih}%");
        }

        // Filter Status Selesai vs Belum Selesai
        if ($statusTerpilih === 'selesai') {
            $query->where('is_complete_total', 1);
        } elseif ($statusTerpilih === 'belum_selesai') {
            $query->where('is_complete_total', 0);
        }

        $semuaAlumni = $query->orderBy('nim', 'asc')->get();

        // 3. Mapping data reaktif untuk Frontend
        $alumniList = [];
        $totalSelesai = 0;
        $totalBelumSelesai = 0;

        foreach ($semuaAlumni as $item) {
            $isComplete = (bool) $item->is_complete_total;
            if ($isComplete) {
                $totalSelesai++;
            } else {
                $totalBelumSelesai++;
            }

            // Parsing semester kelulusan
            $rawSemesterLulus = $item->tahun_akademik_lulus ?? '-';
            $semesterLabel = 'Gasal';
            if (stripos($rawSemesterLulus, 'genap') !== false) {
                $semesterLabel = 'Genap';
            } elseif (stripos($rawSemesterLulus, 'gasal') !== false) {
                $semesterLabel = 'Gasal';
            }

            $alumniList[] = [
                'id' => $item->biodata_id,
                'biodata_id' => $item->biodata_id,
                'nim' => $item->nim,
                'nama' => $item->nama ?? 'Mahasiswa UKDW',
                'prodi' => $item->nama_prodi ?? '-',
                'prodi_kode' => $item->kode_prodi ?? '',
                'prodi_id' => $item->prodi_id,
                'fakultas' => $item->nama_fakultas ?? '-',
                'fakultas_id' => $item->fakultas_id,
                'tahun_akademik_lulus' => $rawSemesterLulus,
                'semester' => $semesterLabel,
                'tahun_lulus' => $item->tahun_lulus ?? '-',
                'ipk' => $item->ipk ?? '-',
                'status_yudisium' => $item->status_yudisium ?? 'Lulus',
                'perusahaan' => $item->nama_perusahaan ?? '-',
                'posisi_jabatan' => $item->posisi_jabatan ?? '-',
                'kelengkapan' => [
                    'is_complete' => $isComplete,
                    'status' => $item->status_tracer_label,
                    'percentage' => (int) $item->univ_percentage,
                    'profile' => [
                        'is_complete' => (bool) $item->is_profile_complete,
                        'percentage' => $item->is_profile_complete ? 100 : 50,
                    ],
                    'questionnaire' => [
                        'is_complete' => (bool) $item->is_complete_univ,
                        'percentage' => (int) $item->univ_percentage,
                        'answered_count' => (int) $item->answered_mandatory_univ,
                        'total_mandatory' => (int) $item->total_mandatory_univ,
                    ],
                    'prodi' => [
                        'is_complete' => (bool) $item->is_complete_prodi,
                        'percentage' => (int) $item->prodi_percentage,
                        'answered_count' => (int) $item->answered_prodi_questions,
                        'total_questions' => (int) $item->total_prodi_questions,
                    ],
                ],
            ];
        }

        $totalFiltered = count($alumniList);
        $rasioSelesai = $totalFiltered > 0 ? (int) round(($totalSelesai / $totalFiltered) * 100) : 0;

        // 4. Master Program Studi
        $daftarProdi = Prodi::orderBy('kode_prodi', 'asc')->get();

        return Inertia::render('SuperAdmin/Alumni/Index', [
            'biodatas' => $alumniList,
            'alumnis' => $alumniList,
            'daftarTahun' => $daftarTahun,
            'daftarTarget' => $daftarTarget,
            'prodis' => $daftarProdi,
            'filters' => [
                'search' => $pencarian ?? '',
                'tahun' => $tahunTerpilih,
                'semester' => $semesterTerpilih,
                'target' => $targetTerpilih ?? 'all',
                'status' => $statusTerpilih,
                'prodi_id' => $prodiIdTerpilih,
            ],
            'stats' => [
                'total_alumni' => $totalFiltered,
                'total_selesai' => $totalSelesai,
                'total_belum_selesai' => $totalBelumSelesai,
                'persentase_selesai' => $rasioSelesai,
            ],
        ]);
    }
}
