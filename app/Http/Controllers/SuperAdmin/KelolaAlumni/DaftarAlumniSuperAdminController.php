<?php

namespace App\Http\Controllers\SuperAdmin\KelolaAlumni;

use App\Http\Controllers\Controller;
use App\Models\Biodata;
use App\Models\EvaluasiAtasan;
use App\Models\Prodi;
use App\Services\Export\AlumniTracerExcelExporter;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Inertia\Inertia;
use Inertia\Response;

/**
 * DaftarAlumniSuperAdminController
 *
 * Fungsi:
 * Menampilkan daftar seluruh alumni terpadu untuk Super Admin lengkap dengan filter Prodi,
 * Tahun Kelulusan (default tahun terbaru), Semester Lulus, Pencarian Nama/NIM, serta audit status
 * kelengkapan data & evaluasi atasan, serta fitur ekspor ZIP per Prodi untuk tahun yang difilter.
 */
class DaftarAlumniSuperAdminController extends Controller
{
    /**
     * Tampilkan Halaman Index Daftar Alumni
     */
    public function index(Request $request): Response
    {
        $pencarian = $request->input('search');
        $semesterTerpilih = $request->input('semester');
        $statusTerpilih = $request->input('status');
        $prodiIdTerpilih = $request->input('prodi_id');
        $targetTerpilih = $request->input('target');

        // 1. Ambil list tahun kelulusan unik langsung dari data_akademik (ringan, instan, tanpa view overhead)
        $daftarTahun = DB::table('data_akademik')
            ->whereNotNull('tahun_lulus')
            ->where('tahun_lulus', '!=', '')
            ->distinct()
            ->pluck('tahun_lulus')
            ->filter()
            ->map(function ($item) {
                if (preg_match('/(\d{4})/', (string) $item, $matches)) {
                    return $matches[1];
                }

                return trim($item);
            })->unique()->sortDesc()->values()->all();

        if (empty($daftarTahun)) {
            $daftarTahun = [(string) date('Y')];
        }

        // Sesuai kebutuhan pengguna: Tahun Kelulusan default ke tahun terbaru saja (tidak perlu 'all')
        $tahunTerbaru = $daftarTahun[0] ?? (string) date('Y');
        $tahunTerpilih = $request->input('tahun', $tahunTerbaru);
        if ($tahunTerpilih === 'all' || empty($tahunTerpilih)) {
            $tahunTerpilih = $tahunTerbaru;
        }

        // 1b. Ambil daftar target periode kelulusan unik langsung dari data_akademik
        $daftarTarget = DB::table('data_akademik')
            ->whereNotNull('tahun_akademik_lulus')
            ->where('tahun_akademik_lulus', '!=', '')
            ->distinct()
            ->pluck('tahun_akademik_lulus')
            ->filter()
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

        // Filter Tahun Kelulusan (Mendukung tahun 4-digit maupun format akademik seperti '2024/2025')
        if ($tahunTerpilih && $tahunTerpilih !== 'all') {
            $tahunNormalized = preg_match('/(\d{4})/', (string) $tahunTerpilih, $m) ? $m[1] : $tahunTerpilih;
            $query->where(function ($q) use ($tahunTerpilih, $tahunNormalized) {
                $q->where('tahun_lulus', $tahunNormalized)
                    ->orWhere('tahun_lulus', $tahunTerpilih)
                    ->orWhere('tahun_akademik_lulus', 'like', "%{$tahunNormalized}%");
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

        // Ambil hanya kolom yang dibutuhkan untuk tampilan tabel & ekspor
        $semuaAlumni = $query->select([
            'biodata_id', 'nim', 'nama', 'nama_prodi', 'kode_prodi', 'prodi_id',
            'nama_fakultas', 'fakultas_id', 'tahun_akademik_lulus', 'tahun_lulus',
            'ipk', 'status_yudisium', 'nama_perusahaan', 'posisi_jabatan',
            'is_complete_total', 'status_tracer_label', 'univ_percentage',
            'is_profile_complete', 'is_complete_univ', 'answered_mandatory_univ',
            'total_mandatory_univ', 'is_complete_prodi', 'prodi_percentage',
            'answered_prodi_questions', 'total_prodi_questions',
        ])->orderBy('nim', 'asc')->get();

        // Ambil status Evaluasi Atasan untuk alumni yang terfilter
        $biodataIds = $semuaAlumni->pluck('biodata_id')->filter()->all();
        $evaluasiMap = EvaluasiAtasan::whereIn('biodata_id', $biodataIds)
            ->select(['id', 'biodata_id', 'is_submitted', 'submitted_at', 'token'])
            ->get()
            ->keyBy('biodata_id');

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

            $ev = $evaluasiMap->get($item->biodata_id);
            $evaluasiStatus = 'none';
            if ($ev) {
                $evaluasiStatus = $ev->is_submitted ? 'submitted' : 'pending';
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
                'evaluasi_atasan' => [
                    'has_evaluasi' => (bool) $ev,
                    'is_submitted' => (bool) ($ev?->is_submitted),
                    'status' => $evaluasiStatus,
                    'status_label' => $ev ? ($ev->is_submitted ? 'Sudah Diisi' : 'Menunggu Respon') : 'Belum Ada Atasan',
                    'submitted_at' => $ev?->submitted_at ? $ev->submitted_at->format('d/m/Y H:i') : null,
                    'token' => $ev?->token,
                ],
            ];
        }

        $totalFiltered = count($alumniList);
        $rasioSelesai = $totalFiltered > 0 ? (int) round(($totalSelesai / $totalFiltered) * 100) : 0;

        // 4. Master Program Studi
        $daftarProdi = Prodi::orderBy('kode_prodi', 'asc')->get(['id', 'kode_prodi', 'nama_prodi']);

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

    /**
     * Download Berkas ZIP berisi Excel (.xls) per Lulusan untuk Prodi dan Tahun Tertentu
     */
    public function exportZip(Request $request)
    {
        $prodiId = $request->input('prodi_id');
        $tahun = $request->input('tahun');

        if (! $prodiId || $prodiId === 'all') {
            return back()->with('error', 'Silakan pilih Program Studi terlebih dahulu untuk mengunduh arsip ZIP.');
        }

        $prodi = Prodi::findOrFail($prodiId);

        // Ambil ID biodata yang cocok melalui view database v_alumni_audit_rekap
        $auditQuery = DB::table('v_alumni_audit_rekap')
            ->where('prodi_id', $prodiId);

        if ($tahun && $tahun !== 'all') {
            $auditQuery->where('tahun_lulus', $tahun);
        }

        $biodataIds = $auditQuery->pluck('biodata_id')->filter()->unique()->values()->all();

        if (empty($biodataIds)) {
            $fallbackQuery = Biodata::where('prodi_id', $prodiId);
            if ($tahun && $tahun !== 'all') {
                $fallbackQuery->whereHas('dataAkademik', function ($da) use ($tahun) {
                    $da->where('tahun_lulus', $tahun);
                });
            }

            $alumnis = $fallbackQuery->with([
                'dataAkademik.yudisium',
                'dataAkademik.orangTua',
                'dataAkademik.propinsi',
                'dataAkademik.kabupaten',
                'yudisium',
                'orangTua',
                'propinsi',
                'kabupaten',
                'perusahaan.propinsi',
                'perusahaan.kabupaten',
                'atasan',
                'user',
                'prodi.fakultas',
                'evaluasiAtasan.atasan',
                'evaluasiAtasan.perusahaan',
                'evaluasiAtasan.respons.pertanyaan',
            ])->get();
        } else {
            $alumnis = Biodata::whereIn('id', $biodataIds)
                ->with([
                    'dataAkademik.yudisium',
                    'dataAkademik.orangTua',
                    'dataAkademik.propinsi',
                    'dataAkademik.kabupaten',
                    'yudisium',
                    'orangTua',
                    'propinsi',
                    'kabupaten',
                    'perusahaan.propinsi',
                    'perusahaan.kabupaten',
                    'atasan',
                    'user',
                    'prodi.fakultas',
                    'evaluasiAtasan.atasan',
                    'evaluasiAtasan.perusahaan',
                    'evaluasiAtasan.respons.pertanyaan',
                ])
                ->get();
        }

        if ($alumnis->isEmpty()) {
            return back()->with('error', 'Tidak ditemukan data alumni untuk Program Studi '.$prodi->nama_prodi.' pada tahun '.$tahun.'.');
        }

        $cleanProdiName = preg_replace('/[^a-zA-Z0-9_-]/', '_', $prodi->nama_prodi);
        $cleanTahun = $tahun ?: 'Terbaru';
        $zipFileName = "Tracer_Study_{$cleanProdiName}_{$cleanTahun}.zip";

        $zipPath = tempnam(sys_get_temp_dir(), 'tracer_zip_');
        $zip = new \ZipArchive;

        if ($zip->open($zipPath, \ZipArchive::CREATE | \ZipArchive::OVERWRITE) !== true) {
            return back()->with('error', 'Gagal memproses arsip ZIP di server.');
        }

        foreach ($alumnis as $alumni) {
            $excelContent = AlumniTracerExcelExporter::generateExcelContent($alumni);
            $nim = preg_replace('/[^a-zA-Z0-9_-]/', '_', (string) $alumni->nim);
            $nama = preg_replace('/[^a-zA-Z0-9_-]/', '_', (string) ($alumni->nama ?? 'Alumni'));
            $excelFileName = "Tracer_Study_{$nim}_{$nama}.xls";

            $zip->addFromString($excelFileName, $excelContent);
        }

        $zip->close();

        return response()->download($zipPath, $zipFileName, [
            'Content-Type' => 'application/zip',
            'Cache-Control' => 'no-cache, no-store, must-revalidate',
        ])->deleteFileAfterSend(true);
    }
}
