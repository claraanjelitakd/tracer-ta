<?php

namespace App\Http\Controllers\AdminBiroTiga\KelolaAlumni;

use App\Http\Controllers\Controller;
use App\Models\Biodata;
use App\Models\Kabupaten;
use App\Models\Kuesioner;
use App\Models\Perusahaan;
use App\Models\ProdiQuestionSection;
use App\Models\ProdiResponse;
use App\Models\Propinsi;
use App\Models\Tracer;
use App\Services\Kuesioner\KelengkapanTracerService;
use App\Services\Kuesioner\KuesionerSyncService;
use Inertia\Inertia;
use Inertia\Response;
use Symfony\Component\HttpFoundation\StreamedResponse;

/**
 * DetailAlumniController (Biro 3)
 *
 * Fungsi:
 * Menampilkan profil rinci alumni, rekam jejak akademik, serta audit jawaban kuesioner tracer study
 * (Universitas dan Program Studi) secara komprehensif, dilengkapi fitur sinkronisasi LinkedIn.
 */
class DetailAlumniController extends Controller
{
    /**
     * Tampilkan Detail Profil & Kuesioner Alumni
     *
     * @param  int|string  $id
     */
    public function tampilkanDetailAlumni($id): Response
    {
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
        ])->findOrFail($id);

        // Pastikan respon profil tersinkron ke tabel responses
        KuesionerSyncService::syncProfileResponses($alumni);

        // Ambil evaluasi kelengkapan terpadu
        $evaluasi = KelengkapanTracerService::evaluasiKelengkapanTotal($alumni);

        // Ambil seluruh jawaban kuesioner alumni
        $savedResponses = Tracer::where('biodata_id', $alumni->id)
            ->get()
            ->keyBy('question_id');

        // Ambil seluruh section dan pertanyaan dari kuesioner aktif
        $kuesioner = Kuesioner::where('is_active', true)
            ->with(['sections' => function ($secQuery) {
                $secQuery->orderBy('order', 'asc')
                    ->with(['subpertanyaans' => function ($qQuery) {
                        $qQuery->orderBy('order', 'asc')
                            ->with('detils');
                    }]);
            }])
            ->first();

        // Susun struktur data section & jawaban terformat untuk Frontend
        $sectionsWithAnswers = [];

        if ($kuesioner) {
            foreach ($kuesioner->sections as $section) {
                $subpertanyaansList = [];

                foreach ($section->subpertanyaans as $subpertanyaan) {
                    $resp = $savedResponses->get($subpertanyaan->id);
                    $isMandatory = KelengkapanTracerService::isMandatoryQuestion($subpertanyaan->kode_pertanyaan);

                    $hasAnswer = false;
                    $displayAnswer = null;

                    if ($resp) {
                        if (! empty($resp->answer_text) && trim((string) $resp->answer_text) !== '') {
                            $hasAnswer = true;
                            $displayAnswer = (string) $resp->answer_text;
                        } elseif (is_array($resp->answer_json) && count($resp->answer_json) > 0) {
                            $displayAnswer = implode(', ', $resp->answer_json);
                            $hasAnswer = true;
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

                $sectionsWithAnswers[] = [
                    'id' => $section->id,
                    'section' => $section->section,
                    'title' => $section->title ?: $section->section,
                    'order' => $section->order,
                    'subpertanyaans' => $subpertanyaansList,
                    'unanswered_mandatory_count' => $unansweredCount,
                ];
            }
        }

        // Susun Data Form Profil Lengkap
        $dataAkademik = $alumni->dataAkademik;
        $orangTua = $alumni->orangTua ?? $dataAkademik?->orangTua;
        $yudisium = $alumni->yudisium ?? $dataAkademik?->yudisium;
        $atasan = $alumni->atasan;

        $formData = [
            'nim' => $alumni->nim ?? '',
            'nama' => $alumni->nama ?? $dataAkademik?->nama ?? '',
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
            'email' => $alumni->email ?? $dataAkademik?->email_students ?? '',
            'email_students' => $dataAkademik?->email_students ?? '',

            'angkatan_masuk' => $dataAkademik?->angkatan_masuk ?? '',
            'status_mahasiswa' => $dataAkademik?->status_mahasiswa ?? 'AR',
            'tahun_akademik_lulus' => $yudisium?->tahun_akademik_lulus ?? $dataAkademik?->tahun_akademik_lulus ?? '',
            'tahun_lulus' => $alumni->tahun_lulus ?? $yudisium?->tahun_lulus ?? $dataAkademik?->tahun_lulus ?? '',
            'ipk' => $dataAkademik?->ip_kumulatif ?? '',
            'ip_kumulatif' => $dataAkademik?->ip_kumulatif ?? '',
            'total_sks' => $dataAkademik?->total_sks ?? '',
            'total_angka_kualitas' => $dataAkademik?->total_angka_kualitas ?? '',

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

            'nama_orang_tua' => $orangTua?->nama_orang_tua ?? '',
            'pekerjaan_orang_tua' => $orangTua?->pekerjaan ?? '',
            'alamat_orang_tua' => $orangTua?->alamat ?? '',
            'kota_orang_tua' => $orangTua?->kota ?? '',
            'kabupaten_id_orang_tua' => $orangTua?->kabupaten_id ?? '',
            'propinsi_id_orang_tua' => $orangTua?->propinsi_id ?? '',
            'provinsi_id_orang_tua' => $orangTua?->propinsi_id ?? '',
            'kode_pos_orang_tua' => $orangTua?->kode_pos ?? '',
            'nomor_telepon_orang_tua' => $orangTua?->nomor_telepon ?? '',

            'instagram_url' => $alumni->instagram_url ?? '',
            'facebook_url' => $alumni->facebook_url ?? '',
            'linkedin_url' => $alumni->linkedin_url ?? '',
            'linkedin_username' => $alumni->linkedin_username ?? '',
            'expert' => $alumni->expert ?? '',
            'minat' => $alumni->minat ?? '',
            'posisi_jabatan' => $alumni->posisi_jabatan ?? '',
            'jenis_pekerjaan' => $alumni->jenis_pekerjaan ?? '',
            'zipcode' => $alumni->zipcode ?? '',

            'nama_perusahaan' => $alumni->perusahaan?->nama_perusahaan ?? '',
            'perusahaan_alamat' => $alumni->perusahaan?->alamat ?? '',
            'perusahaan_skala' => $alumni->perusahaan?->skala ?? '',
            'perusahaan_propinsi_id' => $alumni->perusahaan?->propinsi_id ?? '',
            'perusahaan_kabupaten_id' => $alumni->perusahaan?->kabupaten_id ?? '',
            'perusahaan_status_verifikasi' => $alumni->perusahaan?->status_verifikasi ?? '',

            'nama_atasan' => $atasan?->nama ?? '',
            'email_atasan' => $atasan?->email ?? '',
            'telepon_atasan' => $atasan?->telepon ?? '',
        ];

        // Kuesioner Khusus Program Studi
        $prodiSectionsWithAnswers = [];
        $prodiEvaluasi = [
            'is_complete' => false,
            'percentage' => 0,
            'answered_count' => 0,
            'total_questions' => 0,
        ];

        if ($alumni->prodi_id) {
            $prodiSections = ProdiQuestionSection::where('prodi_id', $alumni->prodi_id)
                ->with(['questions' => function ($qQuery) {
                    $qQuery->orderBy('order', 'asc')->with('options');
                }])
                ->orderBy('order', 'asc')
                ->get();

            $savedProdiResponses = ProdiResponse::where('biodata_id', $alumni->id)
                ->get()
                ->keyBy('prodi_question_id');

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
        }

        $propinsis = Propinsi::orderBy('nama_provinsi', 'asc')->get();
        $kabupatens = Kabupaten::orderBy('nama_kabupaten', 'asc')->get();
        $perusahaans = Perusahaan::select('id', 'nama_perusahaan', 'propinsi_id', 'kabupaten_id', 'alamat', 'kode_pos', 'skala', 'status_verifikasi')->get();

        return Inertia::render('AdminBiroTiga/AlumniShow', [
            'biodata' => $alumni,
            'alumni' => $alumni,
            'evaluasi' => $evaluasi,
            'sections' => $sectionsWithAnswers,
            'prodiSections' => $prodiSectionsWithAnswers,
            'prodiEvaluasi' => $prodiEvaluasi,
            'formData' => $formData,
            'propinsis' => $propinsis,
            'provinces' => $propinsis,
            'kabupatens' => $kabupatens,
            'perusahaans' => $perusahaans,
            'companies' => $perusahaans,
        ]);
    }

    /**
     * Download Excel / CSV Semua Butir Pertanyaan & Jawaban per Alumni
     *
     * @param  int|string  $id
     */
    public function exportExcel($id): StreamedResponse
    {
        $alumni = Biodata::with([
            'dataAkademik.yudisium',
            'dataAkademik.orangTua',
            'yudisium',
            'orangTua',
            'perusahaan.propinsi',
            'perusahaan.kabupaten',
            'atasan',
            'user',
            'prodi.fakultas',
        ])->findOrFail($id);

        KuesionerSyncService::syncProfileResponses($alumni);

        $savedResponses = Tracer::where('biodata_id', $alumni->id)
            ->get()
            ->keyBy('question_id');

        $kuesioner = Kuesioner::where('is_active', true)
            ->with(['sections' => function ($secQuery) {
                $secQuery->orderBy('order', 'asc')
                    ->with(['subpertanyaans' => function ($qQuery) {
                        $qQuery->orderBy('order', 'asc')
                            ->with('detils');
                    }]);
            }])
            ->first();

        $savedProdiResponses = ProdiResponse::where('biodata_id', $alumni->id)
            ->get()
            ->keyBy('prodi_question_id');

        $prodiSections = $alumni->prodi_id
            ? ProdiQuestionSection::where('prodi_id', $alumni->prodi_id)
                ->with(['questions' => function ($qQuery) {
                    $qQuery->orderBy('order', 'asc')->with('options');
                }])
                ->orderBy('order', 'asc')
                ->get()
            : collect([]);

        $nim = preg_replace('/[^a-zA-Z0-9_-]/', '_', (string) $alumni->nim);
        $nama = preg_replace('/[^a-zA-Z0-9_-]/', '_', (string) ($alumni->nama ?? 'Alumni'));
        $filename = "Tracer_Study_{$nim}_{$nama}.csv";

        return response()->streamDownload(function () use ($alumni, $kuesioner, $savedResponses, $prodiSections, $savedProdiResponses) {
            $handle = fopen('php://output', 'w');
            fwrite($handle, "\xEF\xBB\xBF");

            fputcsv($handle, ['LAPORAN LENGKAP KUESIONER TRACER STUDY ALUMNI']);
            fputcsv($handle, ['Universitas Kristen Duta Wacana (UKDW) - Biro 3']);
            fputcsv($handle, ['Diunduh pada', date('d F Y, H:i').' WIB']);
            fputcsv($handle, []);

            $prodiNama = $alumni->prodi?->nama_prodi ?? ($alumni->dataAkademik?->program_studi ?? '-');
            $fakultasNama = $alumni->prodi?->fakultas?->nama_fakultas ?? ($alumni->dataAkademik?->fakultas ?? '-');
            $tahunLulus = $alumni->tahun_lulus ?? ($alumni->yudisium?->tahun_lulus ?? ($alumni->dataAkademik?->tahun_lulus ?? '-'));

            fputcsv($handle, ['IDENTITAS ALUMNI']);
            fputcsv($handle, ['NIM', $alumni->nim, 'Program Studi', $prodiNama]);
            fputcsv($handle, ['Nama Lengkap', $alumni->nama, 'Fakultas', $fakultasNama]);
            fputcsv($handle, ['Email', $alumni->email_pribadi ?: ($alumni->email ?: '-'), 'Tahun Lulus', $tahunLulus]);
            fputcsv($handle, ['Nomor Telepon', $alumni->nomor_telepon ?: '-', 'Perusahaan Saat Ini', $alumni->perusahaan?->nama_perusahaan ?: '-']);
            fputcsv($handle, []);

            fputcsv($handle, ['1. KUESIONER TRACER STUDY UNIVERSITAS']);
            fputcsv($handle, ['No', 'Bagian / Seksi', 'Kode Pertanyaan', 'Pertanyaan / Instrumen', 'Tipe Input', 'Sifat', 'Status', 'Respon / Jawaban Alumni']);

            $no = 1;
            if ($kuesioner) {
                foreach ($kuesioner->sections as $section) {
                    $sectionTitle = $section->title ?: ($section->section ?: 'Bagian Kuesioner');
                    fputcsv($handle, ['--', "Seksi {$section->order}: {$sectionTitle}", '', '', '', '', '', '']);

                    foreach ($section->subpertanyaans as $sub) {
                        $resp = $savedResponses->get($sub->id);
                        $isMandatory = KelengkapanTracerService::isMandatoryQuestion($sub->kode_pertanyaan);
                        $isHeader = in_array($sub->type, ['header', 'section_header']);

                        $answerText = $isHeader ? '(Header Bagian)' : '-';
                        $statusText = $isHeader ? 'Header' : 'Belum Dijawab';

                        if ($resp && ! $isHeader) {
                            if (! empty($resp->answer_text) && trim((string) $resp->answer_text) !== '') {
                                $answerText = (string) $resp->answer_text;
                                $statusText = 'Terjawab';
                            } elseif (is_array($resp->answer_json) && count($resp->answer_json) > 0) {
                                $jsonFormatted = [];
                                foreach ($resp->answer_json as $k => $v) {
                                    $jsonFormatted[] = is_numeric($k) ? (string) $v : "{$k}: {$v}";
                                }
                                $answerText = implode(', ', $jsonFormatted);
                                $statusText = 'Terjawab';
                            }
                        }

                        fputcsv($handle, [
                            $no,
                            $sectionTitle,
                            $sub->kode_pertanyaan,
                            $sub->subpertanyaan,
                            $sub->type ?: 'text',
                            $isHeader ? '-' : ($isMandatory ? 'Wajib' : 'Opsional'),
                            $statusText,
                            $answerText,
                        ]);

                        $no++;
                    }
                }
            }

            if ($prodiSections->isNotEmpty()) {
                fputcsv($handle, []);
                fputcsv($handle, ['2. KUESIONER KHUSUS PROGRAM STUDI: '.$prodiNama]);
                fputcsv($handle, ['No', 'Bagian / Seksi', 'Kode Pertanyaan', 'Pertanyaan / Instrumen', 'Tipe Input', 'Sifat', 'Status', 'Respon / Jawaban Alumni']);

                $noProdi = 1;
                foreach ($prodiSections as $pSection) {
                    $pSectionTitle = $pSection->title ?: 'Section Prodi';
                    fputcsv($handle, ['--', $pSectionTitle, '', '', '', '', '', '']);

                    foreach ($pSection->questions as $pQuestion) {
                        $pResp = $savedProdiResponses->get($pQuestion->id);
                        $isHeader = in_array($pQuestion->type, ['header', 'section_header']);
                        $pMandatory = (bool) $pQuestion->is_required;

                        $answerText = $isHeader ? '(Header Bagian)' : '-';
                        $statusText = $isHeader ? 'Header' : 'Belum Dijawab';

                        if ($pResp && ! $isHeader) {
                            if (! empty($pResp->answer_text) && trim((string) $pResp->answer_text) !== '') {
                                $answerText = (string) $pResp->answer_text;
                                $statusText = 'Terjawab';
                            } elseif (is_array($pResp->answer_json) && count($pResp->answer_json) > 0) {
                                $answerText = implode(', ', $pResp->answer_json);
                                $statusText = 'Terjawab';
                            }
                        }

                        fputcsv($handle, [
                            $noProdi,
                            $pSectionTitle,
                            $pQuestion->code ?: '-',
                            $pQuestion->question_text,
                            $pQuestion->type ?: 'text',
                            $isHeader ? '-' : ($pMandatory ? 'Wajib' : 'Opsional'),
                            $statusText,
                            $answerText,
                        ]);

                        $noProdi++;
                    }
                }
            }

            fclose($handle);
        }, $filename, [
            'Content-Type' => 'text/csv; charset=UTF-8',
            'Content-Disposition' => "attachment; filename=\"{$filename}\"",
            'Pragma' => 'no-cache',
            'Expires' => '0',
        ]);
    }
}
