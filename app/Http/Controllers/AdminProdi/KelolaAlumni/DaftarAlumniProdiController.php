<?php

namespace App\Http\Controllers\AdminProdi\KelolaAlumni;

use App\Http\Controllers\Controller;
use App\Models\Biodata;
use App\Models\Kabupaten;
use App\Models\Kuesioner;
use App\Models\Perusahaan;
use App\Models\ProdiQuestionSection;
use App\Models\ProdiResponse;
use App\Models\Propinsi;
use App\Models\RefNegara;
use App\Models\Tracer;
use App\Services\Alumni\AdminAlumniProfileService;
use App\Services\Export\AlumniTracerExcelExporter;
use App\Services\Kuesioner\KelengkapanTracerService;
use App\Services\Kuesioner\KuesionerSyncService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Inertia\Inertia;
use Inertia\Response;
use Symfony\Component\HttpFoundation\StreamedResponse;

/**
 * DaftarAlumniProdiController
 *
 * Fungsi:
 * Menampilkan direktori mahasiswa & alumni khusus untuk Program Studi yang login,
 * menggunakan Database View (v_alumni_audit_rekap) dengan kecepatan tinggi tanpa N+1 query.
 */
class DaftarAlumniProdiController extends Controller
{
    /**
     * Tampilkan Direktori Mahasiswa & Alumni Khusus Prodi
     */
    public function index(Request $request): Response
    {
        $user = Auth::user()->load('prodi');
        $prodi = $user->prodi;
        $prodiId = $user->prodi_id;

        if (! $prodiId) {
            abort(403, 'Akun Anda belum dikaitkan dengan Program Studi.');
        }

        $pencarian = $request->input('search');
        $tahunTerpilih = $request->input('tahun');
        $semesterTerpilih = $request->input('semester');
        $statusTerpilih = $request->input('status');

        // 1. Ambil daftar tahun kelulusan unik dari view untuk prodi ini
        $daftarTahun = DB::table('v_alumni_audit_rekap')
            ->where('prodi_id', $prodiId)
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

        // 2. Kueri cepat berbasis Database View (v_alumni_audit_rekap)
        $query = DB::table('v_alumni_audit_rekap')
            ->where('prodi_id', $prodiId);

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

        return Inertia::render('AdminProdi/Alumni/Index', [
            'user' => $user,
            'prodi' => $prodi,
            'alumnis' => $alumniList,
            'daftarTahun' => $daftarTahun,
            'filters' => [
                'search' => $pencarian ?? '',
                'tahun' => $tahunTerpilih,
                'semester' => $semesterTerpilih,
                'status' => $statusTerpilih,
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
     * Tampilkan Halaman Detail Profil, Kuesioner Universitas & Kuesioner Prodi Alumni
     */
    public function show(int $id): Response
    {
        $user = Auth::user()->load('prodi');
        $prodi = $user->prodi;
        $prodiId = $user->prodi_id;

        if (! $prodiId) {
            abort(403, 'Akun Anda belum dikaitkan dengan Program Studi.');
        }

        $alumni = Biodata::with([
            'dataAkademik.yudisium',
            'dataAkademik.orangTua',
            'yudisium',
            'orangTua',
            'perusahaan.propinsi',
            'perusahaan.kabupaten',
            'atasan',
            'user',
            'prodi',
        ])
            ->where('prodi_id', $prodiId)
            ->findOrFail($id);

        KuesionerSyncService::syncProfileResponses($alumni);

        $evaluasi = KelengkapanTracerService::evaluasiKelengkapanTotal($alumni);

        // 1. Kuesioner Universitas
        $savedUnivResponses = Tracer::where('biodata_id', $alumni->id)
            ->get()
            ->keyBy('question_id');

        $kuesionerUniv = Kuesioner::where('is_active', true)
            ->with(['sections' => function ($secQuery) {
                $secQuery->orderBy('order', 'asc')
                    ->with(['subpertanyaans' => function ($qQuery) {
                        $qQuery->orderBy('order', 'asc')
                            ->with('detils');
                    }]);
            }])
            ->first();

        $univSectionsWithAnswers = [];
        if ($kuesionerUniv) {
            foreach ($kuesionerUniv->sections as $section) {
                $subpertanyaansList = [];

                foreach ($section->subpertanyaans as $subpertanyaan) {
                    $resp = $savedUnivResponses->get($subpertanyaan->id);
                    $isMandatory = KelengkapanTracerService::isMandatoryQuestion($subpertanyaan->kode_pertanyaan);

                    $hasAnswer = false;
                    $displayAnswer = null;

                    if ($resp) {
                        if (! empty($resp->answer_text) && trim((string) $resp->answer_text) !== '') {
                            $hasAnswer = true;
                            $displayAnswer = (string) $resp->answer_text;
                        } elseif (! empty($resp->answer) && trim((string) $resp->answer) !== '') {
                            $hasAnswer = true;
                            $displayAnswer = (string) $resp->answer;
                        } elseif (is_array($resp->answer_json) && count($resp->answer_json) > 0) {
                            $hasAnswer = true;
                            $displayAnswer = implode(', ', $resp->answer_json);
                        }
                    }

                    $isHeader = in_array($subpertanyaan->type, ['header', 'section_header']);

                    $subpertanyaansList[] = [
                        'id' => $subpertanyaan->id,
                        'kode_pertanyaan' => $subpertanyaan->kode_pertanyaan,
                        'subpertanyaan' => $subpertanyaan->subpertanyaan,
                        'type' => $subpertanyaan->type,
                        'kelompok' => $subpertanyaan->kelompok,
                        'is_mandatory' => $isMandatory,
                        'is_header' => $isHeader,
                        'is_answered' => $hasAnswer,
                        'answer' => $displayAnswer,
                        'answer_json' => $resp?->answer_json,
                        'detils' => $subpertanyaan->detils,
                    ];
                }

                $unansweredCount = count(array_filter($subpertanyaansList, function ($item) {
                    return $item['is_mandatory'] && ! $item['is_answered'] && ! $item['is_header'];
                }));

                $univSectionsWithAnswers[] = [
                    'id' => $section->id,
                    'section' => $section->section,
                    'title' => $section->title ?: $section->section,
                    'order' => $section->order,
                    'subpertanyaans' => $subpertanyaansList,
                    'unanswered_mandatory_count' => $unansweredCount,
                ];
            }
        }

        // 2. Kuesioner Khusus Program Studi
        $prodiSections = ProdiQuestionSection::where('prodi_id', $prodiId)
            ->with(['questions' => function ($qQuery) {
                $qQuery->orderBy('order', 'asc')->with('options');
            }])
            ->orderBy('order', 'asc')
            ->get();

        $savedProdiResponses = ProdiResponse::where('biodata_id', $alumni->id)
            ->get()
            ->keyBy('prodi_question_id');

        $prodiSectionsWithAnswers = [];
        $totalProdiQuestions = 0;
        $totalProdiAnswered = 0;

        foreach ($prodiSections as $pSection) {
            $pQuestionsList = [];

            foreach ($pSection->questions as $pQuestion) {
                $isHeader = in_array($pQuestion->type, ['header', 'section_header']);
                $pResp = $savedProdiResponses->get($pQuestion->id);

                $hasAnswer = false;
                $displayAnswer = null;

                if ($pResp && ! $isHeader) {
                    if (! empty($pResp->answer_text) && trim((string) $pResp->answer_text) !== '') {
                        $hasAnswer = true;
                        $displayAnswer = (string) $pResp->answer_text;
                    } elseif (is_array($pResp->answer_json) && count($pResp->answer_json) > 0) {
                        $hasAnswer = true;
                        $displayAnswer = implode(', ', $pResp->answer_json);
                    }
                }

                if (! $isHeader) {
                    $totalProdiQuestions++;
                    if ($hasAnswer) {
                        $totalProdiAnswered++;
                    }
                }

                $pQuestionsList[] = [
                    'id' => $pQuestion->id,
                    'code' => $pQuestion->code,
                    'question_text' => $pQuestion->question_text,
                    'type' => $pQuestion->type,
                    'is_header' => $isHeader,
                    'is_mandatory' => (bool) $pQuestion->is_required,
                    'is_answered' => $hasAnswer,
                    'answer_text' => $displayAnswer,
                    'options' => $pQuestion->options->map(function ($opt) {
                        return [
                            'id' => $opt->id,
                            'text' => $opt->option_text,
                        ];
                    }),
                ];
            }

            $unansweredProdiCount = count(array_filter($pQuestionsList, function ($item) {
                return $item['is_mandatory'] && ! $item['is_answered'] && ! $item['is_header'];
            }));

            $prodiSectionsWithAnswers[] = [
                'id' => $pSection->id,
                'title' => $pSection->title,
                'description' => $pSection->description,
                'order' => $pSection->order,
                'questions' => $pQuestionsList,
                'unanswered_mandatory_count' => $unansweredProdiCount,
            ];
        }

        $prodiPercentage = $totalProdiQuestions > 0
            ? round(($totalProdiAnswered / $totalProdiQuestions) * 100)
            : 0;

        $prodiEvaluasi = [
            'is_complete' => ($totalProdiQuestions > 0 && $totalProdiAnswered >= $totalProdiQuestions),
            'percentage' => $prodiPercentage,
            'answered_count' => $totalProdiAnswered,
            'total_questions' => $totalProdiQuestions,
        ];

        // 3. Data Form Profil Lengkap
        $formData = AdminAlumniProfileService::buildFormData($alumni);

        $propinsis = Propinsi::orderBy('nama_provinsi', 'asc')->get();
        $kabupatens = Kabupaten::orderBy('nama_kabupaten', 'asc')->get();
        $negaras = RefNegara::orderBy('nama_negara', 'asc')->get();
        $refOptions = AdminAlumniProfileService::getRefOptions();
        $perusahaans = Perusahaan::select('id', 'nama_perusahaan', 'jenis_lokasi', 'negara', 'propinsi_id', 'kabupaten_id', 'alamat', 'kode_pos', 'skala', 'jenis_perusahaan', 'jenis_perusahaan_lainnya', 'status_verifikasi')->get();

        return Inertia::render('AdminProdi/Alumni/Show', [
            'user' => $user,
            'prodi' => $prodi,
            'biodata' => $alumni,
            'alumni' => $alumni,
            'evaluasi' => $evaluasi,
            'sections' => $univSectionsWithAnswers,
            'univSections' => $univSectionsWithAnswers,
            'prodiSections' => $prodiSectionsWithAnswers,
            'prodiEvaluasi' => $prodiEvaluasi,
            'formData' => $formData,
            'propinsis' => $propinsis,
            'provinces' => $propinsis,
            'kabupatens' => $kabupatens,
            'negaras' => $negaras,
            'refOptions' => $refOptions,
            'perusahaans' => $perusahaans,
            'companies' => $perusahaans,
        ]);
    }

    /**
     * Update Profil Mahasiswa oleh Admin Prodi
     */
    public function updateProfile(Request $request, int $id): RedirectResponse
    {
        $user = Auth::user();
        $prodiId = $user->prodi_id;

        $biodata = Biodata::where('prodi_id', $prodiId)->findOrFail($id);

        AdminAlumniProfileService::updateProfile($biodata, $request->all());

        return redirect()->back()->with('success', 'Data profil mahasiswa berhasil diperbarui oleh Admin Program Studi.');
    }

    /**
     * Download Excel (.xls) Semua Butir Pertanyaan & Jawaban per Alumni untuk Prodi
     */
    public function exportExcel(int $id): StreamedResponse
    {
        $user = Auth::user();
        $prodiId = $user->prodi_id;

        // Pastikan alumni berada di prodi yang sama
        Biodata::where('prodi_id', $prodiId)->findOrFail($id);

        return AlumniTracerExcelExporter::download($id);
    }
}
