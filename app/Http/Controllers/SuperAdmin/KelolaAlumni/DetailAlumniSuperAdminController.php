<?php

namespace App\Http\Controllers\SuperAdmin\KelolaAlumni;

use App\Http\Controllers\Controller;
use App\Models\Atasan;
use App\Models\Biodata;
use App\Models\EvaluasiAtasan;
use App\Models\Kabupaten;
use App\Models\Kuesioner;
use App\Models\LinkedinSyncResult;
use App\Models\PertanyaanEvaluasiAtasan;
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
use Inertia\Inertia;
use Inertia\Response;
use Symfony\Component\HttpFoundation\StreamedResponse;

/**
 * DetailAlumniSuperAdminController
 *
 * Fungsi:
 * Menampilkan halaman detail mandiri untuk profil mahasiswa/alumni sekaligus audit jawaban
 * kuesioner tracer study bagi Super Admin.
 *
 * Fitur:
 * 1. Rekap profil lengkap (Data Pribadi, Data Akademik, Yudisium, Orang Tua, Perusahaan, Atasan).
 * 2. Form lengkap pengeditan profil oleh Super Admin untuk membantu alumni memperbarui datanya.
 * 3. Tabulasi jawaban kuesioner tracer study per section dengan hitungan belum dijawab.
 * 4. Indikator butir pertanyaan Wajib vs Opsional secara visual.
 * 5. Tab 4: Trace pemetaan hasil scraping LinkedIn (Apify mentah vs target database vs nilai aktual) dan histori staging.
 */
class DetailAlumniSuperAdminController extends Controller
{
    /**
     * Tampilkan Halaman Detail Profil & Kuesioner Alumni
     *
     * @param  int|string  $id
     * @return Response
     */
    public function show($id)
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
            'latestLinkedinSyncResult.reviewer',
        ])->findOrFail($id);

        // Pastikan respon profil tersinkron ke tabel responses
        KuesionerSyncService::syncProfileResponses($alumni);

        // Ambil evaluasi kelengkapan terpadu
        $evaluasi = KelengkapanTracerService::evaluasiKelengkapanTotal($alumni);

        // Ambil seluruh jawaban kuesioner alumni
        $savedResponses = Tracer::where('biodata_id', $alumni->id)
            ->get()
            ->keyBy('question_id');

        // Ambil seluruh section dan pertanyaan dari kuesioner aktif (Seksi 1 Identitas dikelola di Tab 1 Detail Profile)
        $kuesioner = Kuesioner::where('is_active', true)
            ->with(['sections' => function ($secQuery) {
                $secQuery->where(function ($sq) {
                    $sq->whereNull('kode_kelompok')
                        ->orWhere('kode_kelompok', '!=', '1');
                })
                    ->orderBy('order', 'asc')
                    ->with(['subpertanyaans' => function ($qQuery) {
                        $qQuery->where(function ($w) {
                            $w->whereNull('tampil_di')
                                ->orWhere('tampil_di', '!=', 'profil');
                        })->where(function ($w) {
                            $w->whereNull('kelompok')
                                ->orWhere('kelompok', '!=', 'BIO');
                        })
                            ->orderBy('order', 'asc')
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

                // Hitung berapa pertanyaan wajib dan opsional di section ini yang belum dijawab
                $unansweredMandatoryCount = count(array_filter($subpertanyaansList, function ($item) {
                    return $item['is_mandatory'] && ! $item['is_answered'] && ! $item['is_header'];
                }));

                $unansweredOptionalCount = count(array_filter($subpertanyaansList, function ($item) {
                    return ! $item['is_mandatory'] && ! $item['is_answered'] && ! $item['is_header'];
                }));

                $answeredCount = count(array_filter($subpertanyaansList, function ($item) {
                    return $item['is_answered'] && ! $item['is_header'];
                }));

                $totalQuestionsCount = count(array_filter($subpertanyaansList, function ($item) {
                    return ! $item['is_header'];
                }));

                $sectionsWithAnswers[] = [
                    'id' => $section->id,
                    'section' => $section->section,
                    'title' => $section->title ?: $section->section,
                    'order' => $section->order,
                    'subpertanyaans' => $subpertanyaansList,
                    'unanswered_mandatory_count' => $unansweredMandatoryCount,
                    'unanswered_optional_count' => $unansweredOptionalCount,
                    'answered_count' => $answeredCount,
                    'total_questions_count' => $totalQuestionsCount,
                ];
            }
        }

        // Susun Data Form Profil Lengkap menggunakan AdminAlumniProfileService
        $formData = AdminAlumniProfileService::buildFormData($alumni);

        // 7. Muat Kuesioner Khusus Program Studi jika alumni terafiliasi prodi
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
        $negaras = RefNegara::orderBy('nama_negara', 'asc')->get();
        $refOptions = AdminAlumniProfileService::getRefOptions();
        $perusahaans = Perusahaan::select('id', 'nama_perusahaan', 'jenis_lokasi', 'negara', 'propinsi_id', 'kabupaten_id', 'alamat', 'kode_pos', 'skala', 'jenis_perusahaan', 'jenis_perusahaan_lainnya', 'status_verifikasi')->get();

        $latestSync = $alumni->latestLinkedinSyncResult;
        $linkedinTraceMapping = $this->buildLinkedinTraceMapping($alumni, $latestSync);
        $linkedinHistory = LinkedinSyncResult::where('biodata_id', $alumni->id)
            ->with('reviewer')
            ->orderByDesc('id')
            ->take(10)
            ->get();

        // Ambil Data Evaluasi Atasan (Pengguna Lulusan)
        $evaluasiAtasan = EvaluasiAtasan::where('biodata_id', $alumni->id)
            ->with(['atasan', 'perusahaan', 'respons.pertanyaan'])
            ->latest('updated_at')
            ->first();

        $pertanyaanEvaluasiAtasan = PertanyaanEvaluasiAtasan::where('is_active', true)
            ->orderBy('order', 'asc')
            ->get();

        return Inertia::render('SuperAdmin/Alumni/Show', [
            'biodata' => $alumni,
            'alumni' => $alumni,
            'evaluasi' => $evaluasi,
            'evaluasiAtasan' => $evaluasiAtasan,
            'pertanyaanEvaluasiAtasan' => $pertanyaanEvaluasiAtasan,
            'sections' => $sectionsWithAnswers,
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
            'linkedinSyncResult' => $latestSync,
            'linkedinTraceMapping' => $linkedinTraceMapping,
            'linkedinHistory' => $linkedinHistory,
        ]);
    }

    /**
     * Membangun Baris Pemetaan Audit Trace Hasil Scraping LinkedIn ke Basis Data
     */
    public function buildLinkedinTraceMapping(Biodata $alumni, ?LinkedinSyncResult $syncResult): array
    {
        if (! $syncResult) {
            return [];
        }

        $item = $syncResult->getProfileItem();
        if (empty($item)) {
            return [];
        }

        $rows = [];
        $handledKeys = [];

        // 1. Foto Profil
        $scrapedFoto = $item['li_profile_image_url'] ?? ($item['profile_picture'] ?? ($item['profilePicture'] ?? ($item['picture_url'] ?? null)));
        $dbFoto = $alumni->foto;
        $rows[] = [
            'key' => 'li_profile_image_url',
            'label' => 'Foto Profil Alumni',
            'scraped_value' => $scrapedFoto ?: '(Tidak ditemukan)',
            'target_table' => 'biodata',
            'target_column' => 'foto',
            'db_value' => $dbFoto ?: '(Belum tersimpan di biodata)',
            'status' => ! empty($dbFoto) ? 'Tersinkron' : 'Belum Disinkron',
            'badge' => ! empty($dbFoto) ? 'emerald' : 'amber',
            'note' => 'Disimpan permanen ke direktori /uploads/profile/',
        ];
        $handledKeys = array_merge($handledKeys, ['li_profile_image_url', 'profile_picture', 'profilePicture', 'picture_url']);

        // 2. Posisi Jabatan / Job Title
        $scrapedJob = $item['job_title'] ?? ($item['jobTitle'] ?? ($item['experiences'][0]['title'] ?? ($item['headline'] ?? null)));
        $dbJob = $alumni->posisi_jabatan;
        $rows[] = [
            'key' => 'job_title',
            'label' => 'Posisi Jabatan Struktural (F2G)',
            'scraped_value' => $scrapedJob ?: '(Tidak ditemukan)',
            'target_table' => 'biodata',
            'target_column' => 'posisi_jabatan',
            'db_value' => $dbJob ?: '(Belum terisi)',
            'status' => ! empty($dbJob) ? 'Tersinkron' : 'Belum Disinkron',
            'badge' => ! empty($dbJob) ? 'emerald' : 'amber',
            'note' => 'Jabatan profesional hasil ekstraksi LinkedIn',
        ];
        $handledKeys = array_merge($handledKeys, ['job_title', 'jobTitle']);

        // 3. Status Pekerjaan (Aktivitas Utama)
        $dbKategori = $alumni->kategori_pekerjaan;
        $derivedStatus = (! empty($scrapedJob) || ! empty($item['company_name'])) ? 'Pekerja' : 'Belum Bekerja';
        $rows[] = [
            'key' => 'status_pekerjaan',
            'label' => 'Status Pekerjaan / Aktivitas Utama',
            'scraped_value' => $derivedStatus.' / Karyawan',
            'target_table' => 'biodata',
            'target_column' => 'kategori_pekerjaan',
            'db_value' => $dbKategori ?: '(Belum terisi)',
            'status' => ($dbKategori === 'Pekerja' || $dbKategori === 'Wiraswasta') ? 'Tersinkron' : 'Belum Disinkron',
            'badge' => ($dbKategori === 'Pekerja' || $dbKategori === 'Wiraswasta') ? 'emerald' : 'amber',
            'note' => "Pilihan: 'Pekerja' atau 'Wiraswasta'",
        ];

        // 4. Nama Perusahaan / Tempat Bekerja
        $scrapedCompany = $item['company_name'] ?? ($item['companyName'] ?? ($item['experiences'][0]['company_name'] ?? null));
        $dbCompany = $alumni->perusahaan?->nama_perusahaan;
        $rows[] = [
            'key' => 'company_name',
            'label' => 'Nama Perusahaan / Tempat Bekerja',
            'scraped_value' => $scrapedCompany ?: '(Tidak ditemukan)',
            'target_table' => 'perusahaan (relasi biodata.perusahaan_id)',
            'target_column' => 'nama_perusahaan',
            'db_value' => $dbCompany ?: '(Belum terhubung)',
            'status' => ! empty($dbCompany) ? 'Tersinkron' : 'Belum Disinkron',
            'badge' => ! empty($dbCompany) ? 'emerald' : 'amber',
            'note' => 'Terkoneksi ke tabel master perusahaan',
        ];
        $handledKeys = array_merge($handledKeys, ['company_name', 'companyName']);

        // 5. Status Verifikasi Perusahaan
        $dbStatusVerif = $alumni->perusahaan?->status_verifikasi;
        $rows[] = [
            'key' => 'perusahaan.status_verifikasi',
            'label' => 'Status Verifikasi Perusahaan',
            'scraped_value' => 'Menunggu Verifikasi (Default Approval)',
            'target_table' => 'perusahaan',
            'target_column' => 'status_verifikasi',
            'db_value' => $dbStatusVerif ?: '(Belum terdaftar)',
            'status' => $dbStatusVerif === 'Terverifikasi' ? 'Terverifikasi' : 'Menunggu Verifikasi',
            'badge' => $dbStatusVerif === 'Terverifikasi' ? 'emerald' : 'amber',
            'note' => 'Diverifikasi langsung oleh Super Admin kampus',
        ];

        // 6. URL Profil LinkedIn
        $scrapedUrl = $item['li_profile_url'] ?? ($item['linkedin_url'] ?? ($item['url'] ?? ($item['input'] ?? $syncResult->linkedin_url)));
        $dbUrl = $alumni->linkedin_url;
        $rows[] = [
            'key' => 'li_profile_url',
            'label' => 'URL Profil LinkedIn',
            'scraped_value' => $scrapedUrl ?: '(Tidak ditemukan)',
            'target_table' => 'biodata',
            'target_column' => 'linkedin_url',
            'db_value' => $dbUrl ?: '(Belum terisi)',
            'status' => ! empty($dbUrl) ? 'Tersinkron' : 'Belum Disinkron',
            'badge' => ! empty($dbUrl) ? 'emerald' : 'amber',
            'note' => 'Tautan profil resmi alumni di LinkedIn',
        ];
        $handledKeys = array_merge($handledKeys, ['li_profile_url', 'linkedin_url', 'url', 'input', 'source_url']);

        // 7. Username LinkedIn
        $scrapedHandle = $item['li_profile_handle'] ?? ($item['handle'] ?? ($item['publicIdentifier'] ?? $syncResult->linkedin_username));
        $dbHandle = $alumni->linkedin_username;
        $rows[] = [
            'key' => 'li_profile_handle',
            'label' => 'Username LinkedIn',
            'scraped_value' => $scrapedHandle ?: '(Tidak ditemukan)',
            'target_table' => 'biodata',
            'target_column' => 'linkedin_username',
            'db_value' => $dbHandle ?: '(Belum terisi)',
            'status' => ! empty($dbHandle) ? 'Tersinkron' : 'Belum Disinkron',
            'badge' => ! empty($dbHandle) ? 'emerald' : 'amber',
            'note' => 'Identifier publik unik profil LinkedIn',
        ];
        $handledKeys = array_merge($handledKeys, ['li_profile_handle', 'handle', 'publicIdentifier']);

        // 8. Jenjang Pendidikan Lanjut
        $scrapedDegree = $item['education'][0]['degree_name'] ?? ($item['education'][0]['degree'] ?? null);
        $dbDegree = $alumni->pendidikan_tingkat;
        $rows[] = [
            'key' => 'education[0].degree_name',
            'label' => 'Jenjang Studi Lanjut',
            'scraped_value' => $scrapedDegree ?: '(Tidak tercantum)',
            'target_table' => 'biodata',
            'target_column' => 'pendidikan_tingkat',
            'db_value' => $dbDegree ?: '(Belum terisi)',
            'status' => ! empty($dbDegree) ? 'Tersinkron' : 'Opsional / Belum Terisi',
            'badge' => ! empty($dbDegree) ? 'emerald' : 'slate',
            'note' => 'Jenjang studi lanjutan alumni (S1/S2/S3/Spesialis)',
        ];

        // 9. Perguruan Tinggi Studi Lanjut
        $scrapedSchool = $item['education'][0]['school_name'] ?? ($item['education'][0]['school'] ?? ($item['school_name'] ?? null));
        $dbSchool = $alumni->perguruan_tinggi;
        $rows[] = [
            'key' => 'education[0].school_name',
            'label' => 'Perguruan Tinggi Studi Lanjut',
            'scraped_value' => $scrapedSchool ?: '(Tidak tercantum)',
            'target_table' => 'biodata',
            'target_column' => 'perguruan_tinggi',
            'db_value' => $dbSchool ?: '(Belum terisi)',
            'status' => ! empty($dbSchool) ? 'Tersinkron' : 'Opsional / Belum Terisi',
            'badge' => ! empty($dbSchool) ? 'emerald' : 'slate',
            'note' => 'Nama universitas / institut tempat studi lanjut',
        ];

        // 10. Program Studi Studi Lanjut
        $scrapedProdi = $item['education'][0]['field_of_study'] ?? ($item['education'][0]['field'] ?? null);
        $dbProdi = $alumni->pendidikan_prodi;
        $rows[] = [
            'key' => 'education[0].field_of_study',
            'label' => 'Program Studi Studi Lanjut',
            'scraped_value' => $scrapedProdi ?: '(Tidak tercantum)',
            'target_table' => 'biodata',
            'target_column' => 'pendidikan_prodi',
            'db_value' => $dbProdi ?: '(Belum terisi)',
            'status' => ! empty($dbProdi) ? 'Tersinkron' : 'Opsional / Belum Terisi',
            'badge' => ! empty($dbProdi) ? 'emerald' : 'slate',
            'note' => 'Nama jurusan / program studi lanjut',
        ];
        $handledKeys = array_merge($handledKeys, ['education', 'school_name', 'li_school_url']);

        // 11. Lokasi / Domisili
        $scrapedLocation = $item['location'] ?? ($item['locationName'] ?? ($item['geoCountryName'] ?? null));
        $dbLocation = $alumni->perusahaan?->alamat ?: $alumni->alamat;
        $rows[] = [
            'key' => 'location',
            'label' => 'Lokasi / Wilayah Pekerjaan',
            'scraped_value' => $scrapedLocation ?: '(Tidak tercantum)',
            'target_table' => 'perusahaan / biodata',
            'target_column' => 'alamat',
            'db_value' => $dbLocation ?: '(Belum terisi)',
            'status' => ! empty($dbLocation) ? 'Tersinkron' : 'Belum Disinkron',
            'badge' => ! empty($dbLocation) ? 'emerald' : 'amber',
            'note' => 'Dipetakan ke alamat perusahaan atau domisili',
        ];
        $handledKeys = array_merge($handledKeys, ['location', 'locationName', 'geoCountryName', 'li_profile_country']);

        // 12. Nama Lengkap
        $scrapedName = $item['full_name'] ?? ($item['fullName'] ?? (($item['first_name'] ?? '').' '.($item['last_name'] ?? '')));
        $dbName = $alumni->dataAkademik?->nama ?? $alumni->nama;
        $rows[] = [
            'key' => 'full_name',
            'label' => 'Nama Lengkap Mahasiswa / Alumni',
            'scraped_value' => trim((string) $scrapedName) ?: '(Tidak tercantum)',
            'target_table' => 'data_akademik (Master)',
            'target_column' => 'nama',
            'db_value' => $dbName ?: '(Kosong)',
            'status' => 'Terkunci Data Akademik',
            'badge' => 'slate',
            'note' => 'Data nama resmi dikunci pangkalan data kampus',
        ];
        $handledKeys = array_merge($handledKeys, ['full_name', 'fullName', 'first_name', 'last_name']);

        // 13. Headline
        if (isset($item['headline'])) {
            $rows[] = [
                'key' => 'headline',
                'label' => 'Headline LinkedIn',
                'scraped_value' => (string) $item['headline'],
                'target_table' => 'linkedin_sync_results',
                'target_column' => 'scraped_data->headline',
                'db_value' => (string) $item['headline'],
                'status' => 'Tersimpan di Histori',
                'badge' => 'emerald',
                'note' => 'Teks bio / headline profil LinkedIn',
            ];
            $handledKeys[] = 'headline';
        }

        // 14. Keahlian (Skills)
        $rawSkills = $item['skills'] ?? [];
        $skillsList = [];
        if (is_array($rawSkills)) {
            foreach ($rawSkills as $skill) {
                if (is_string($skill)) {
                    $skillsList[] = $skill;
                } elseif (is_array($skill) && ! empty($skill['name'])) {
                    $skillsList[] = $skill['name'];
                }
            }
        }
        $scrapedSkills = ! empty($skillsList) ? implode(', ', array_slice($skillsList, 0, 10)) : null;
        $dbSkills = $alumni->skills ?? $alumni->expert;
        $rows[] = [
            'key' => 'skills',
            'label' => 'Keahlian Alumni (Skills)',
            'scraped_value' => $scrapedSkills ?: '(Tidak dicantumkan publik)',
            'target_table' => 'biodata',
            'target_column' => 'skills',
            'db_value' => $dbSkills ?: '(Belum terisi)',
            'status' => ! empty($dbSkills) ? 'Tersinkron' : 'Belum Disinkron',
            'badge' => ! empty($dbSkills) ? 'emerald' : 'amber',
            'note' => 'Daftar keahlian profesional LinkedIn alumni',
        ];
        $handledKeys = array_merge($handledKeys, ['skills']);

        // 15. Experiences (Riwayat Pengalaman Kerja)
        $rawExp = $item['experiences'] ?? ($item['experience'] ?? []);
        if (is_array($rawExp) && ! empty($rawExp)) {
            $countExp = count($rawExp);
            $firstExpTitle = $rawExp[0]['title'] ?? ($rawExp[0]['position'] ?? '-');
            $firstExpComp = $rawExp[0]['company_name'] ?? ($rawExp[0]['company'] ?? '-');
            $dbExp = $alumni->experience ?? $alumni->minat;
            $rows[] = [
                'key' => 'experiences',
                'label' => "Riwayat Pengalaman Kerja ({$countExp} entri)",
                'scraped_value' => "Posisi Terkini: {$firstExpTitle} di {$firstExpComp} (+".($countExp - 1).' lainnya)',
                'target_table' => 'biodata',
                'target_column' => 'experience',
                'db_value' => $dbExp ?: "{$alumni->posisi_jabatan} di ".($alumni->perusahaan?->nama_perusahaan ?? '-'),
                'status' => ! empty($dbExp) ? 'Tersinkron' : 'Belum Disinkron',
                'badge' => ! empty($dbExp) ? 'emerald' : 'amber',
                'note' => 'Riwayat posisi dan tempat kerja hasil sinkronisasi LinkedIn',
            ];
            $handledKeys = array_merge($handledKeys, ['experiences', 'experience', 'positions']);
        }

        // 15. Tambahkan sisa atribut scraping yang belum dimapping (metadata Apify)
        foreach ($item as $rawKey => $rawVal) {
            if (in_array($rawKey, $handledKeys)) {
                continue;
            }

            $displayVal = is_array($rawVal) ? json_encode($rawVal, JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE) : (string) $rawVal;
            if (strlen($displayVal) > 120) {
                $displayVal = substr($displayVal, 0, 117).'...';
            }

            $rows[] = [
                'key' => $rawKey,
                'label' => 'Metadata: '.str_replace(['_', '.'], ' ', ucwords($rawKey, '_')),
                'scraped_value' => $displayVal,
                'target_table' => 'linkedin_sync_results',
                'target_column' => "scraped_data->{$rawKey}",
                'db_value' => $displayVal,
                'status' => 'Metadata Scraping',
                'badge' => 'slate',
                'note' => 'Metadata mentah dari engine Apify scraper',
            ];
        }

        return $rows;
    }

    /**
     * Memperbarui Data Profil Alumni oleh Super Admin
     *
     * @param  int|string  $id
     * @return RedirectResponse
     */
    public function updateProfile(Request $request, $id)
    {
        $biodata = Biodata::findOrFail($id);
        AdminAlumniProfileService::updateProfile($biodata, $request->all());

        return redirect()->back()->with('success', 'Data profil mahasiswa berhasil diperbarui oleh Super Admin.');
    }

    /**
     * Download Excel / CSV Semua Butir Pertanyaan & Jawaban per Alumni
     *
     * @param  int|string  $id
     * @return StreamedResponse
     */
    /**
     * Download Excel (.xls) Semua Butir Pertanyaan & Jawaban per Alumni
     *
     * @param  int|string  $id
     * @return StreamedResponse
     */
    public function exportExcel($id)
    {
        return AlumniTracerExcelExporter::download($id);
    }
}
