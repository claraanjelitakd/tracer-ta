<?php

namespace App\Http\Controllers\AdminProdi\KelolaAlumni;

use App\Http\Controllers\Controller;
use App\Models\Biodata;
use App\Models\DataAkademik;
use App\Models\Kuesioner;
use App\Models\ProdiQuestion;
use App\Models\ProdiQuestionSection;
use App\Models\ProdiResponse;
use App\Models\Tracer;
use App\Services\Kuesioner\KelengkapanTracerService;
use App\Services\Kuesioner\KuesionerSyncService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Inertia\Inertia;
use Inertia\Response;

/**
 * Controller untuk menampilkan Direktori Alumni dan Detail Hasil Kuesioner
 * khusus Program Studi yang sedang login.
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
        $tahunTerpilih = $request->input('tahun', 'all');
        $semesterTerpilih = $request->input('semester', 'all');
        $statusUnivTerpilih = $request->input('status_univ', 'all');
        $statusProdiTerpilih = $request->input('status_prodi', 'all');

        // 1. Ambil daftar tahun akademik lulus unik untuk prodi ini
        $rawTahunAkademik = DataAkademik::whereHas('biodata', function ($q) use ($prodiId) {
            $q->where('prodi_id', $prodiId);
        })
            ->whereNotNull('tahun_akademik_lulus')
            ->distinct()
            ->pluck('tahun_akademik_lulus')
            ->values();

        $daftarTahun = $rawTahunAkademik->map(function ($item) {
            if (preg_match('/(\d{4}\/\d{4}|\d{4})/', $item, $matches)) {
                return $matches[1];
            }

            return trim($item);
        })->unique()->sortDesc()->values()->all();

        // 2. Kueri data alumni khusus prodi ini
        $query = Biodata::where('prodi_id', $prodiId)
            ->with([
                'dataAkademik.yudisium',
                'yudisium',
                'user',
            ]);

        // Filter Pencarian (Nama Lengkap atau NIM)
        if ($pencarian) {
            $query->where(function ($w) use ($pencarian) {
                $w->where('nim', 'like', "%{$pencarian}%")
                    ->orWhere('nama', 'like', "%{$pencarian}%")
                    ->orWhereHas('dataAkademik', function ($q) use ($pencarian) {
                        $q->where('nama', 'like', "%{$pencarian}%")
                            ->orWhere('nim', 'like', "%{$pencarian}%");
                    })
                    ->orWhereHas('user', function ($q) use ($pencarian) {
                        $q->where('name', 'like', "%{$pencarian}%")
                            ->orWhere('username', 'like', "%{$pencarian}%");
                    });
            });
        }

        // Filter Tahun Kelulusan
        if ($tahunTerpilih && $tahunTerpilih !== 'all') {
            $query->where(function ($q) use ($tahunTerpilih) {
                $q->where('tahun_lulus', 'like', "%{$tahunTerpilih}%")
                    ->orWhereHas('dataAkademik', function ($qa) use ($tahunTerpilih) {
                        $qa->where('tahun_akademik_lulus', 'like', "%{$tahunTerpilih}%")
                            ->orWhere('tahun_lulus', 'like', "%{$tahunTerpilih}%");
                    });
            });
        }

        // Filter Semester Kelulusan (Gasal / Genap)
        if ($semesterTerpilih && $semesterTerpilih !== 'all') {
            $query->whereHas('dataAkademik', function ($q) use ($semesterTerpilih) {
                $q->where('tahun_akademik_lulus', 'like', "%{$semesterTerpilih}%");
            });
        }

        $allAlumni = $query->get();

        // Total pertanyaan prodi ini
        $totalProdiQuestions = ProdiQuestion::where('prodi_id', $prodiId)->count();
        $prodiQuestionIds = ProdiQuestion::where('prodi_id', $prodiId)->pluck('id');

        // 3. Evaluasi status kuesioner masing-masing alumni
        $totalUnivSelesai = 0;
        $totalProdiSelesai = 0;

        $daftarAlumniFormat = $allAlumni->map(function ($alumni) use (&$totalUnivSelesai, &$totalProdiSelesai, $totalProdiQuestions, $prodiQuestionIds) {
            // Evaluasi Kuesioner Universitas
            $eval = KelengkapanTracerService::evaluasiKelengkapanTotal($alumni);
            $isUnivComplete = $eval['questionnaire']['is_complete'] ?? false;
            $univPercentage = $eval['questionnaire']['percentage'] ?? 0;

            if ($isUnivComplete) {
                $totalUnivSelesai++;
            }

            // Evaluasi Kuesioner Prodi
            $prodiAnswersCount = 0;
            if ($totalProdiQuestions > 0) {
                $prodiAnswersCount = ProdiResponse::where('biodata_id', $alumni->id)
                    ->whereIn('prodi_question_id', $prodiQuestionIds)
                    ->count();
            }
            $isProdiComplete = ($totalProdiQuestions > 0 && $prodiAnswersCount >= $totalProdiQuestions);

            if ($isProdiComplete) {
                $totalProdiSelesai++;
            }

            $akademik = $alumni->dataAkademik;

            return [
                'id' => $alumni->id,
                'nim' => $alumni->nim,
                'nama' => $alumni->nama ?? $akademik?->nama ?? $alumni->user?->name ?? 'Belum ada nama',
                'email' => $alumni->email_pribadi ?? $alumni->email ?? $akademik?->email_pribadi ?? $alumni->user?->email ?? '-',
                'nomor_telepon' => $alumni->nomor_telepon ?? $akademik?->nomor_telepon ?? '-',
                'tahun_lulus' => $alumni->tahun_lulus ?? $alumni->yudisium?->tahun_lulus ?? $akademik?->tahun_akademik_lulus ?? $akademik?->tahun_lulus ?? '-',
                'ipk' => $akademik?->ip_kumulatif ?? '-',
                'is_univ_complete' => $isUnivComplete,
                'univ_percentage' => $univPercentage,
                'is_prodi_complete' => $isProdiComplete,
                'prodi_answers_count' => $prodiAnswersCount,
                'total_prodi_questions' => $totalProdiQuestions,
            ];
        });

        // Filter status lanjutan di koleksi
        if ($statusUnivTerpilih !== 'all') {
            $isTarget = ($statusUnivTerpilih === 'selesai');
            $daftarAlumniFormat = $daftarAlumniFormat->filter(fn ($item) => $item['is_univ_complete'] === $isTarget)->values();
        }

        if ($statusProdiTerpilih !== 'all') {
            $isTarget = ($statusProdiTerpilih === 'selesai');
            $daftarAlumniFormat = $daftarAlumniFormat->filter(fn ($item) => $item['is_prodi_complete'] === $isTarget)->values();
        }

        $totalFiltered = $daftarAlumniFormat->count();
        $totalAlumniProdi = $allAlumni->count();

        return Inertia::render('AdminProdi/Alumni/Index', [
            'user' => $user,
            'prodi' => $prodi,
            'alumnis' => $daftarAlumniFormat,
            'daftarTahun' => $daftarTahun,
            'filters' => [
                'search' => $pencarian,
                'tahun' => $tahunTerpilih,
                'semester' => $semesterTerpilih,
                'status_univ' => $statusUnivTerpilih,
                'status_prodi' => $statusProdiTerpilih,
            ],
            'stats' => [
                'total_alumni' => $totalAlumniProdi,
                'total_filtered' => $totalFiltered,
                'univ_selesai' => $totalUnivSelesai,
                'univ_belum' => max(0, $totalAlumniProdi - $totalUnivSelesai),
                'prodi_selesai' => $totalProdiSelesai,
                'prodi_belum' => max(0, $totalAlumniProdi - $totalProdiSelesai),
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

        // Pastikan respon profil tersinkron ke tabel tracer
        KuesionerSyncService::syncProfileResponses($alumni);

        // Ambil evaluasi kelengkapan terpadu
        $evaluasi = KelengkapanTracerService::evaluasiKelengkapanTotal($alumni);

        // 1. Kuesioner Universitas (Tracer Study)
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
                        } elseif (is_array($resp->answer_json) && count($resp->answer_json) > 0) {
                            $hasAnswer = true;
                            $displayAnswer = implode(', ', $resp->answer_json);
                        }
                    }

                    $subpertanyaansList[] = [
                        'id' => $subpertanyaan->id,
                        'kode_pertanyaan' => $subpertanyaan->kode_pertanyaan,
                        'subpertanyaan' => $subpertanyaan->subpertanyaan,
                        'type' => $subpertanyaan->type,
                        'kelompok' => $subpertanyaan->kelompok,
                        'is_mandatory' => $isMandatory,
                        'is_answered' => $hasAnswer,
                        'answer' => $displayAnswer,
                        'answer_json' => $resp?->answer_json,
                        'detils' => $subpertanyaan->detils,
                    ];
                }

                $unansweredCount = count(array_filter($subpertanyaansList, function ($item) {
                    return $item['is_mandatory'] && ! $item['is_answered'];
                }));

                $univSectionsWithAnswers[] = [
                    'id' => $section->id,
                    'section' => $section->section,
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
                $totalProdiQuestions++;
                $pResp = $savedProdiResponses->get($pQuestion->id);

                $hasAnswer = false;
                $displayAnswer = null;

                if ($pResp) {
                    if (! empty($pResp->answer_text) && trim((string) $pResp->answer_text) !== '') {
                        $hasAnswer = true;
                        $displayAnswer = (string) $pResp->answer_text;
                    } elseif (is_array($pResp->answer_json) && count($pResp->answer_json) > 0) {
                        $hasAnswer = true;
                        $displayAnswer = implode(', ', $pResp->answer_json);
                    }
                }

                if ($hasAnswer) {
                    $totalProdiAnswered++;
                }

                $pQuestionsList[] = [
                    'id' => $pQuestion->id,
                    'code' => $pQuestion->code,
                    'question_text' => $pQuestion->question_text,
                    'type' => $pQuestion->type,
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
                return $item['is_mandatory'] && ! $item['is_answered'];
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
        $dataAkademik = $alumni->dataAkademik;
        $orangTua = $alumni->orangTua ?? $dataAkademik?->orangTua;
        $yudisium = $alumni->yudisium ?? $dataAkademik?->yudisium;
        $atasan = $alumni->atasan;

        $formData = [
            // Identitas Pribadi
            'nim' => $alumni->nim ?? '',
            'nama' => $alumni->nama ?? $dataAkademik?->nama ?? $alumni->user?->name ?? '',
            'tempat_lahir' => $dataAkademik?->tempat_lahir ?? '',
            'tanggal_lahir' => $dataAkademik?->tanggal_lahir ?? '',
            'agama' => $alumni->agama ?? $dataAkademik?->agama ?? '',
            'jenis_kelamin' => $dataAkademik?->jenis_kelamin ?? '',
            'golongan_darah' => $dataAkademik?->golongan_darah ?? '',
            'warga_negara' => $dataAkademik?->warga_negara ?? 'WNI',
            'nik' => $alumni->nik ?? $dataAkademik?->nik ?? '',
            'no_kk' => $alumni->no_kk ?? $dataAkademik?->no_kk ?? '',
            'nisn' => $dataAkademik?->nisn ?? '',
            'no_bpjs' => $alumni->no_bpjs ?? $dataAkademik?->no_bpjs ?? '',
            'npwp' => $alumni->npwp ?? '',

            // Kontak & Alamat Pribadi
            'alamat_saat_ini' => $alumni->alamat ?? $dataAkademik?->alamat_saat_ini ?? '',
            'alamat' => $alumni->alamat ?? $dataAkademik?->alamat_saat_ini ?? '',
            'kelurahan' => $alumni->kelurahan ?? $dataAkademik?->kelurahan ?? '',
            'kecamatan' => $alumni->kecamatan ?? $dataAkademik?->kecamatan ?? '',
            'kabupaten_id' => $alumni->kabupaten_id ?? $dataAkademik?->kabupaten_id ?? '',
            'propinsi_id' => $alumni->propinsi_id ?? $dataAkademik?->propinsi_id ?? '',
            'provinsi_id' => $alumni->propinsi_id ?? $dataAkademik?->propinsi_id ?? '',
            'kode_pos' => $alumni->kode_pos ?? $dataAkademik?->kode_pos ?? '',
            'nomor_telepon' => $alumni->nomor_telepon ?? $dataAkademik?->nomor_telepon ?? '',
            'email_pribadi' => $alumni->email_pribadi ?? $alumni->email ?? $dataAkademik?->email_pribadi ?? '',
            'email_students' => $dataAkademik?->email_students ?? '',

            // Data Akademik Utama
            'angkatan_masuk' => $dataAkademik?->angkatan_masuk ?? '',
            'status_mahasiswa' => $dataAkademik?->status_mahasiswa ?? 'AR',
            'tahun_akademik_lulus' => $yudisium?->tahun_akademik_lulus ?? $dataAkademik?->tahun_akademik_lulus ?? '',
            'tahun_lulus' => $alumni->tahun_lulus ?? $yudisium?->tahun_lulus ?? $dataAkademik?->tahun_lulus ?? '',
            'ipk' => $dataAkademik?->ip_kumulatif ?? '',
            'ip_kumulatif' => $dataAkademik?->ip_kumulatif ?? '',
            'total_sks' => $dataAkademik?->total_sks ?? '',
            'total_angka_kualitas' => $dataAkademik?->total_angka_kualitas ?? '',

            // Yudisium
            'judul_ta' => $yudisium?->judul_ta ?? '',
            'judul_ta_inggris' => $yudisium?->judul_ta_inggris ?? '',
            'dosen_pembimbing_1' => $yudisium?->dosen_pembimbing_1 ?? '',
            'dosen_pembimbing_2' => $yudisium?->dosen_pembimbing_2 ?? '',
            'dosen_penguji_1' => $yudisium?->dosen_penguji_1 ?? '',
            'dosen_penguji_2' => $yudisium?->dosen_penguji_2 ?? '',
            'url_publikasi' => $yudisium?->url_publikasi ?? '',
            'jenis_publikasi' => $yudisium?->jenis_publikasi ?? '',
            'status_publikasi' => $yudisium?->status_publikasi ?? '',
            'keterangan_hasil_yudisium' => $yudisium?->keterangan_hasil_yudisium ?? '',
            'proses_yudisium' => $yudisium?->proses_yudisium ?? '',

            // Data Orang Tua
            'nama_orang_tua' => $orangTua?->nama_orang_tua ?? '',
            'pekerjaan_orang_tua' => $orangTua?->pekerjaan ?? '',
            'alamat_orang_tua' => $orangTua?->alamat ?? '',
            'kota_orang_tua' => $orangTua?->kota ?? '',
            'kabupaten_id_orang_tua' => $orangTua?->kabupaten_id ?? '',
            'propinsi_id_orang_tua' => $orangTua?->propinsi_id ?? '',
            'provinsi_id_orang_tua' => $orangTua?->propinsi_id ?? '',
            'kode_pos_orang_tua' => $orangTua?->kode_pos ?? '',
            'nomor_telepon_orang_tua' => $orangTua?->nomor_telepon ?? '',

            // Karier / Profil Profesional
            'instagram_url' => $alumni->instagram_url ?? '',
            'facebook_url' => $alumni->facebook_url ?? '',
            'linkedin_url' => $alumni->linkedin_url ?? '',
            'linkedin_username' => $alumni->linkedin_username ?? '',
            'expert' => $alumni->expert ?? '',
            'minat' => $alumni->minat ?? '',
            'posisi_jabatan' => $alumni->posisi_jabatan ?? '',
            'jenis_pekerjaan' => $alumni->jenis_pekerjaan ?? '',
            'zipcode' => $alumni->zipcode ?? '',

            // Data Perusahaan
            'nama_perusahaan' => $alumni->perusahaan?->nama_perusahaan ?? '',
            'perusahaan_alamat' => $alumni->perusahaan?->alamat ?? '',
            'perusahaan_skala' => $alumni->perusahaan?->skala ?? '',
            'perusahaan_status_verifikasi' => $alumni->perusahaan?->status_verifikasi ?? '',

            // Data Atasan
            'nama_atasan' => $atasan?->nama ?? '',
            'email_atasan' => $atasan?->email ?? '',
            'telepon_atasan' => $atasan?->telepon ?? '',
        ];

        return Inertia::render('AdminProdi/Alumni/Show', [
            'user' => $user,
            'prodi' => $prodi,
            'biodata' => $alumni,
            'alumni' => $alumni,
            'evaluasi' => $evaluasi,
            'univSections' => $univSectionsWithAnswers,
            'prodiSections' => $prodiSectionsWithAnswers,
            'prodiEvaluasi' => $prodiEvaluasi,
            'formData' => $formData,
        ]);
    }
}
