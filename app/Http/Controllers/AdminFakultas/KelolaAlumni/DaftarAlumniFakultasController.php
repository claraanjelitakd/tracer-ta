<?php

namespace App\Http\Controllers\AdminFakultas\KelolaAlumni;

use App\Http\Controllers\Controller;
use App\Models\Prodi;
use App\Models\RefFakultas;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Inertia\Inertia;
use Inertia\Response;

/**
 * DaftarAlumniFakultasController
 *
 * Fungsi:
 * Menampilkan direktori seluruh alumni dalam fakultas yang sedang login,
 * dilengkapi filter program studi yang ada di fakultas tersebut, filter tahun/semester kelulusan,
 * dan pencarian cepat menggunakan Database View (v_alumni_audit_rekap) dengan performa tinggi.
 */
class DaftarAlumniFakultasController extends Controller
{
    /**
     * Tampilkan Direktori Mahasiswa & Alumni Fakultas
     */
    public function index(Request $request): Response
    {
        $user = Auth::user()->load('fakultas');
        $fakultasId = $user->fakultas_id;

        if (! $fakultasId) {
            $fakultas = RefFakultas::first();
            $fakultasId = $fakultas?->id;
        } else {
            $fakultas = $user->fakultas ?: RefFakultas::find($fakultasId);
        }

        $pencarian = $request->input('search');
        $tahunTerpilih = $request->input('tahun');
        $semesterTerpilih = $request->input('semester');
        $statusTerpilih = $request->input('status');
        $prodiIdTerpilih = $request->input('prodi_id');

        // 1. Ambil daftar program studi yang ada di fakultas ini
        $daftarProdiFakultas = Prodi::where('fakultas_id', $fakultasId)
            ->orderBy('kode_prodi', 'asc')
            ->get();

        $prodiIdsFakultas = $daftarProdiFakultas->pluck('id')->all();

        // 2. Ambil list tahun kelulusan unik dari view untuk fakultas ini
        $daftarTahun = DB::table('v_alumni_audit_rekap')
            ->whereIn('prodi_id', $prodiIdsFakultas)
            ->where(function ($q) {
                $q->whereNotNull('tahun_lulus')
                    ->orWhereNotNull('tahun_akademik_lulus');
            })
            ->pluck('tahun_lulus')
            ->filter()
            ->map(function ($item) {
                if (preg_match('/(\d{4})/', (string) $item, $matches)) {
                    return $matches[1];
                }

                return trim($item);
            })->unique()->sortDesc()->values()->all();

        // 3. Kueri cepat dari Database View v_alumni_audit_rekap
        $query = DB::table('v_alumni_audit_rekap')
            ->whereIn('prodi_id', $prodiIdsFakultas);

        // Filter Program Studi (dalam fakultas ini)
        if ($prodiIdTerpilih && $prodiIdTerpilih !== 'all') {
            $query->where('prodi_id', $prodiIdTerpilih);
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

        // Filter Semester Kelulusan
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

        // 4. Mapping data untuk Frontend
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

        return Inertia::render('AdminFakultas/Alumni/Index', [
            'user' => $user,
            'fakultas' => $fakultas,
            'alumnis' => $alumniList,
            'daftarTahun' => $daftarTahun,
            'prodis' => $daftarProdiFakultas,
            'filters' => [
                'search' => $pencarian ?? '',
                'tahun' => $tahunTerpilih,
                'semester' => $semesterTerpilih,
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
