<?php

namespace App\Http\Controllers\SuperAdmin\KelolaAlumni;

use App\Http\Controllers\Controller;
use App\Models\Atasan;
use App\Models\Biodata;
use App\Models\DataOrangTua;
use App\Models\Kabupaten;
use App\Models\Kuesioner;
use App\Models\Perusahaan;
use App\Models\ProdiQuestionSection;
use App\Models\ProdiResponse;
use App\Models\Propinsi;
use App\Models\Tracer;
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

                // Hitung berapa pertanyaan wajib di section ini yang belum dijawab
                $unansweredCount = count(array_filter($subpertanyaansList, function ($item) {
                    return $item['is_mandatory'] && ! $item['is_answered'];
                }));

                $sectionsWithAnswers[] = [
                    'id' => $section->id,
                    'section' => $section->section,
                    'order' => $section->order,
                    'subpertanyaans' => $subpertanyaansList,
                    'unanswered_mandatory_count' => $unansweredCount,
                ];
            }
        }

        // Susun Data Form Profil Lengkap (sama persis dengan Profil Biodata)
        $dataAkademik = $alumni->dataAkademik;
        $orangTua = $alumni->orangTua ?? $dataAkademik?->orangTua;
        $yudisium = $alumni->yudisium ?? $dataAkademik?->yudisium;
        $atasan = $alumni->atasan;

        $formData = [
            // Identitas Pribadi
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
            'email' => $alumni->email ?? $dataAkademik?->email_students ?? '',
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
            'perusahaan_propinsi_id' => $alumni->perusahaan?->propinsi_id ?? '',
            'perusahaan_kabupaten_id' => $alumni->perusahaan?->kabupaten_id ?? '',
            'perusahaan_status_verifikasi' => $alumni->perusahaan?->status_verifikasi ?? '',

            // Data Atasan
            'nama_atasan' => $atasan?->nama ?? '',
            'email_atasan' => $atasan?->email ?? '',
            'telepon_atasan' => $atasan?->telepon ?? '',
        ];

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
        }

        $propinsis = Propinsi::orderBy('nama_provinsi', 'asc')->get();
        $kabupatens = Kabupaten::orderBy('nama_kabupaten', 'asc')->get();
        $perusahaans = Perusahaan::select('id', 'nama_perusahaan', 'propinsi_id', 'kabupaten_id', 'alamat', 'kode_pos', 'skala', 'status_verifikasi')->get();

        return Inertia::render('SuperAdmin/Alumni/Show', [
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
     * Memperbarui Data Profil Alumni oleh Super Admin
     *
     * @param  int|string  $id
     * @return RedirectResponse
     */
    public function updateProfile(Request $request, $id)
    {
        $biodata = Biodata::findOrFail($id);
        $data = $request->all();

        // 1. Perbarui Data Orang Tua
        $dataOrangTua = [
            'nama_orang_tua' => ! empty($data['nama_orang_tua']) ? $data['nama_orang_tua'] : null,
            'pekerjaan' => ! empty($data['pekerjaan_orang_tua']) ? $data['pekerjaan_orang_tua'] : null,
            'alamat' => ! empty($data['alamat_orang_tua']) ? $data['alamat_orang_tua'] : null,
            'kota' => ! empty($data['kota_orang_tua']) ? $data['kota_orang_tua'] : null,
            'kabupaten_id' => ! empty($data['kabupaten_id_orang_tua']) ? $data['kabupaten_id_orang_tua'] : null,
            'propinsi_id' => ! empty($data['propinsi_id_orang_tua']) ? $data['propinsi_id_orang_tua'] : (! empty($data['provinsi_id_orang_tua']) ? $data['provinsi_id_orang_tua'] : null),
            'kode_pos' => ! empty($data['kode_pos_orang_tua']) ? $data['kode_pos_orang_tua'] : null,
            'nomor_telepon' => ! empty($data['nomor_telepon_orang_tua']) ? $data['nomor_telepon_orang_tua'] : null,
        ];
        DataOrangTua::updateOrCreate(
            ['nim' => $biodata->nim],
            $dataOrangTua
        );

        // 2. Tangani Data Perusahaan
        $perusahaanId = $biodata->perusahaan_id;
        if (! empty($data['nama_perusahaan'])) {
            $perusahaan = Perusahaan::firstOrCreate(
                ['nama_perusahaan' => $data['nama_perusahaan']],
                [
                    'propinsi_id' => $data['perusahaan_propinsi_id'] ?? ($data['company_province_id'] ?? null),
                    'kabupaten_id' => $data['perusahaan_kabupaten_id'] ?? ($data['company_kabupaten_id'] ?? null),
                    'alamat' => $data['perusahaan_alamat'] ?? ($data['company_alamat'] ?? null),
                    'skala' => $data['perusahaan_skala'] ?? ($data['company_skala'] ?? null),
                    'status_verifikasi' => 'Terverifikasi',
                ]
            );

            // Perbarui detail alamat/wilayah perusahaan jika sudah ada
            $perusahaan->update([
                'propinsi_id' => ! empty($data['perusahaan_propinsi_id']) ? $data['perusahaan_propinsi_id'] : (! empty($data['company_province_id']) ? $data['company_province_id'] : null),
                'kabupaten_id' => ! empty($data['perusahaan_kabupaten_id']) ? $data['perusahaan_kabupaten_id'] : (! empty($data['company_kabupaten_id']) ? $data['company_kabupaten_id'] : null),
                'alamat' => ! empty($data['perusahaan_alamat']) ? $data['perusahaan_alamat'] : (! empty($data['company_alamat']) ? $data['company_alamat'] : null),
                'skala' => ! empty($data['perusahaan_skala']) ? $data['perusahaan_skala'] : (! empty($data['company_skala']) ? $data['company_skala'] : null),
            ]);

            $perusahaanId = $perusahaan->id;
        }

        // 3. Tangani Data Atasan
        $atasanId = $biodata->atasan_id;
        if (! empty($data['nama_atasan'])) {
            $atasan = ! empty($biodata->atasan_id) ? Atasan::find($biodata->atasan_id) : null;
            if ($atasan) {
                $atasan->update([
                    'nama' => $data['nama_atasan'],
                    'email' => ! empty($data['email_atasan']) ? $data['email_atasan'] : null,
                    'telepon' => ! empty($data['telepon_atasan']) ? $data['telepon_atasan'] : null,
                ]);
            } else {
                $atasan = Atasan::create([
                    'nama' => $data['nama_atasan'],
                    'email' => ! empty($data['email_atasan']) ? $data['email_atasan'] : null,
                    'telepon' => ! empty($data['telepon_atasan']) ? $data['telepon_atasan'] : null,
                ]);
            }
            $atasanId = $atasan->id;
        }

        // 4. Perbarui Model Biodata
        $biodata->update([
            'nama' => ! empty($data['nama']) ? $data['nama'] : $biodata->nama,
            'nomor_telepon' => ! empty($data['nomor_telepon']) ? $data['nomor_telepon'] : null,
            'email' => ! empty($data['email']) ? $data['email'] : null,
            'email_pribadi' => ! empty($data['email_pribadi']) ? $data['email_pribadi'] : null,
            'alamat' => ! empty($data['alamat_saat_ini']) ? $data['alamat_saat_ini'] : (! empty($data['alamat']) ? $data['alamat'] : null),
            'kelurahan' => ! empty($data['kelurahan']) ? $data['kelurahan'] : null,
            'kecamatan' => ! empty($data['kecamatan']) ? $data['kecamatan'] : null,
            'kabupaten_id' => ! empty($data['kabupaten_id']) ? $data['kabupaten_id'] : null,
            'propinsi_id' => ! empty($data['propinsi_id']) ? $data['propinsi_id'] : (! empty($data['provinsi_id']) ? $data['provinsi_id'] : null),
            'kode_pos' => ! empty($data['kode_pos']) ? $data['kode_pos'] : null,
            'agama' => ! empty($data['agama']) ? $data['agama'] : null,
            'nik' => ! empty($data['nik']) ? $data['nik'] : null,
            'no_kk' => ! empty($data['no_kk']) ? $data['no_kk'] : null,
            'no_bpjs' => ! empty($data['no_bpjs']) ? $data['no_bpjs'] : null,
            'npwp' => ! empty($data['npwp']) ? $data['npwp'] : null,
            'perusahaan_id' => $perusahaanId,
            'atasan_id' => $atasanId,
            'kategori_pekerjaan' => ! empty($data['kategori_pekerjaan']) ? $data['kategori_pekerjaan'] : null,
            'posisi_jabatan' => ! empty($data['posisi_jabatan']) ? $data['posisi_jabatan'] : null,
            'posisi_wiraswasta' => ! empty($data['posisi_wiraswasta']) ? $data['posisi_wiraswasta'] : null,
            'jenis_pekerjaan' => ! empty($data['jenis_pekerjaan']) ? $data['jenis_pekerjaan'] : null,
            'expert' => ! empty($data['expert']) ? $data['expert'] : null,
            'minat' => ! empty($data['minat']) ? $data['minat'] : null,
            'zipcode' => ! empty($data['zipcode']) ? $data['zipcode'] : null,
            'instagram_url' => ! empty($data['instagram_url']) ? $data['instagram_url'] : null,
            'facebook_url' => ! empty($data['facebook_url']) ? $data['facebook_url'] : null,
            'linkedin_url' => ! empty($data['linkedin_url']) ? $data['linkedin_url'] : null,
            'linkedin_username' => ! empty($data['linkedin_username']) ? $data['linkedin_username'] : null,
        ]);

        $biodata->refresh();

        // 5. Sinkronkan Respon Kuesioner (F1 s/d F2H)
        KuesionerSyncService::syncProfileResponses($biodata);

        return redirect()->back()->with('success', 'Data profil mahasiswa berhasil diperbarui oleh Super Admin.');
    }

    /**
     * Download Excel / CSV Semua Butir Pertanyaan & Jawaban per Alumni
     *
     * @param  int|string  $id
     * @return StreamedResponse
     */
    public function exportExcel($id)
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

        KuesionerSyncService::syncProfileResponses($alumni);

        // Ambil seluruh jawaban kuesioner umum
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

        // Kuesioner Prodi
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
        $filename = "Jawaban_Kuesioner_{$nim}_{$nama}.csv";

        return response()->streamDownload(function () use ($kuesioner, $savedResponses, $prodiSections, $savedProdiResponses) {
            $handle = fopen('php://output', 'w');

            // Write UTF-8 BOM agar Excel Windows membuka dengan rapi tanpa masalah encoding karakter
            fprintf($handle, chr(0xEF).chr(0xBB).chr(0xBF));

            // Header Kolom
            fputcsv($handle, [
                'No',
                'Kategori / Section',
                'Kode Pertanyaan',
                'Pertanyaan',
                'Tipe Pertanyaan',
                'Status Wajib',
                'Status Jawaban',
                'Jawaban Alumni',
            ]);

            $no = 1;

            // 1. Kuesioner Umum Tracer Study Universitas
            if ($kuesioner) {
                foreach ($kuesioner->sections as $section) {
                    $sectionName = $section->section ?: 'Bagian Kuesioner';
                    foreach ($section->subpertanyaans as $sub) {
                        $resp = $savedResponses->get($sub->id);
                        $isMandatory = KelengkapanTracerService::isMandatoryQuestion($sub->kode_pertanyaan);

                        $answerText = '';
                        $status = 'Belum Dijawab';

                        if ($resp) {
                            if (! empty($resp->answer_text) && trim((string) $resp->answer_text) !== '') {
                                $answerText = (string) $resp->answer_text;
                                $status = 'Terjawab';
                            } elseif (is_array($resp->answer_json) && count($resp->answer_json) > 0) {
                                $answerText = implode(', ', $resp->answer_json);
                                $status = 'Terjawab';
                            }
                        }

                        fputcsv($handle, [
                            $no++,
                            $sectionName,
                            $sub->kode_pertanyaan,
                            $sub->subpertanyaan,
                            $sub->type ?: 'text',
                            $isMandatory ? 'Wajib' : 'Opsional',
                            $status,
                            $answerText,
                        ]);
                    }
                }
            }

            // 2. Kuesioner Khusus Program Studi
            if ($prodiSections->isNotEmpty()) {
                foreach ($prodiSections as $pSection) {
                    $pSectionTitle = 'Kuesioner Prodi: '.($pSection->title ?: 'Section');
                    foreach ($pSection->questions as $pQuestion) {
                        $pResp = $savedProdiResponses->get($pQuestion->id);
                        $pMandatory = (bool) $pQuestion->is_required;

                        $answerText = '';
                        $status = 'Belum Dijawab';

                        if ($pResp) {
                            if (! empty($pResp->answer_text) && trim((string) $pResp->answer_text) !== '') {
                                $answerText = (string) $pResp->answer_text;
                                $status = 'Terjawab';
                            } elseif (is_array($pResp->answer_json) && count($pResp->answer_json) > 0) {
                                $answerText = implode(', ', $pResp->answer_json);
                                $status = 'Terjawab';
                            }
                        }

                        fputcsv($handle, [
                            $no++,
                            $pSectionTitle,
                            $pQuestion->code ?: '-',
                            $pQuestion->question_text,
                            $pQuestion->type ?: 'text',
                            $pMandatory ? 'Wajib' : 'Opsional',
                            $status,
                            $answerText,
                        ]);
                    }
                }
            }

            fclose($handle);
        }, $filename, [
            'Content-Type' => 'text/csv; charset=UTF-8',
            'Content-Disposition' => "attachment; filename=\"{$filename}\"",
        ]);
    }
}
