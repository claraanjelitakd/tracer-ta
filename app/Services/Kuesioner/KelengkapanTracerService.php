<?php

namespace App\Services\Kuesioner;

use App\Models\Biodata;
use App\Models\RefSubpertanyaan2021;
use App\Models\Tracer;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;

/**
 * KelengkapanTracerService
 *
 * Fungsi:
 * Mengaudit status kelengkapan data seorang alumni secara komprehensif, mencakup:
 * 1. Kelengkapan Profil (Data Pribadi, Data Akademik, Data Orang Tua, Data Perusahaan, Data Atasan, dan Posisi Jabatan).
 * 2. Kelengkapan Kuesioner Wajib (Ditentukan secara dinamis berdasarkan kolom `wajib = 1` di tabel `ref_subpertanyaan2021`).
 *
 * Pertanyaan dengan `wajib = 0` bersifat OPSIONAL dan tidak membatalkan kelengkapan status alumni.
 */
class KelengkapanTracerService
{
    /**
     * Kode pertanyaan yang dikelola di modul Profil (/alumni/profile),
     * sehingga dievaluasi oleh evaluasiProfil() dan bukan evaluasiKuesionerWajib().
     */
    public const PROFILE_MANAGED_CODES = [
        'F1', 'F2A', 'F2B', 'F2C', 'F2D',
        'BIO_TEMPAT_LAHIR', 'BIO_TANGGAL_LAHIR', 'BIO_JK', 'BIO_TGL_LULUS', 'BIO_JUDUL_TA', 'BIO_NIK', 'BIO_NPWP',
        'F5A1', 'F5A2', 'F510', 'F5B', 'F5C', 'F5D',
        'F2E', 'F2E1', 'F2E2', 'F2E3', 'F2F', 'F2G', 'F2H',
        'F11', 'F505',
    ];

    /**
     * Mengambil seluruh butir pertanyaan kuesioner yang berstatus WAJIB (wajib = 1) dari database.
     *
     * @return array<int, string>
     */
    public static function getMandatoryQuestionCodes(): array
    {
        $profileCodesUpper = array_map('strtoupper', self::PROFILE_MANAGED_CODES);

        return RefSubpertanyaan2021::where('wajib', true)
            ->where('type', '!=', 'header')
            ->whereNotIn(DB::raw('UPPER(kode_pertanyaan)'), $profileCodesUpper)
            ->pluck('kode_pertanyaan')
            ->all();
    }

    /**
     * Memeriksa apakah suatu kode pertanyaan berstatus Wajib (wajib = 1).
     *
     * @param  string  $code  Kode pertanyaan (misal: 'F1', 'F8', 'F17a1', dll.)
     */
    public static function isMandatoryQuestion(string $code): bool
    {
        $question = RefSubpertanyaan2021::where('kode_pertanyaan', $code)->first();

        return (bool) ($question?->wajib && $question?->type !== 'header');
    }

    /**
     * Evaluasi kelengkapan profil biodata alumni.
     *
     * @return array Shape: ['is_complete' => bool, 'percentage' => int, 'filled_count' => int, 'total_fields' => int, 'missing_fields' => array]
     */
    public static function evaluasiProfil(Biodata $biodata): array
    {
        $biodata->refresh();
        $biodata->load(['dataAkademik.orangTua', 'perusahaan.propinsi', 'perusahaan.kabupaten', 'atasan', 'user', 'prodi']);

        $dataAkademik = $biodata->dataAkademik;
        $orangTua = $dataAkademik?->orangTua ?? $biodata->orangTua;
        $perusahaan = $biodata->perusahaan;
        $atasan = $biodata->atasan;

        $fields = [
            // Data Pribadi & Kontak
            'Nama Lengkap' => $biodata->nama ?? $dataAkademik?->nama,
            'NIM' => $biodata->nim,
            'Tempat Lahir' => $dataAkademik?->tempat_lahir,
            'Tanggal Lahir' => $dataAkademik?->tanggal_lahir,
            'Agama' => $biodata->agama ?? $dataAkademik?->agama,
            'Jenis Kelamin' => $dataAkademik?->jenis_kelamin,
            'Nomor Telepon/HP' => $biodata->nomor_telepon ?? $dataAkademik?->nomor_telepon,
            'Email Pribadi' => $biodata->email_pribadi ?? $dataAkademik?->email_pribadi,
            'Alamat Domisili Saat Ini' => $biodata->alamat ?? $dataAkademik?->alamat_saat_ini,
            'NIK KTP' => $biodata->nik ?? $dataAkademik?->nik,
            'NPWP' => $biodata->npwp,

            // Data Akademik
            'IPK Kelulusan' => $dataAkademik?->ip_kumulatif,
            'Tahun Kelulusan' => $biodata->tahun_lulus ?? ($dataAkademik?->tahun_akademik_lulus ?? $dataAkademik?->tahun_lulus),

            // Data Orang Tua
            'Nama Orang Tua' => $orangTua?->nama_orang_tua,
            'Pekerjaan Orang Tua' => $orangTua?->pekerjaan,
            'Alamat Orang Tua' => $orangTua?->alamat,
            'Nomor Telepon Orang Tua' => $orangTua?->nomor_telepon,

            // Data Perusahaan & Atasan
            'Nama Perusahaan' => $perusahaan?->nama_perusahaan,
            'Alamat Perusahaan' => $perusahaan?->alamat,
            'Skala Perusahaan' => $perusahaan?->skala,
            'Provinsi Perusahaan' => $perusahaan?->propinsi_id,
            'Kabupaten Perusahaan' => $perusahaan?->kabupaten_id,
            'Nama Atasan' => $atasan?->nama,
            'Email Atasan' => $atasan?->email,
            'Nomor Telepon Atasan' => $atasan?->telepon,
            'Posisi Jabatan' => $biodata->posisi_jabatan,

            // Data Media Sosial & Profesional Alumni (Tabel biodata)
            'Bidang Keahlian (Expertise)' => $biodata->expert,
            'Minat & Ketertarikan' => $biodata->minat,
            'LinkedIn Profil URL' => $biodata->linkedin_url,
            'LinkedIn Username' => $biodata->linkedin_username,
            'Instagram Profil URL' => $biodata->instagram_url,
            'Facebook Profil URL' => $biodata->facebook_url,
            'Kode Pos Perusahaan (Zipcode)' => $biodata->zipcode,
        ];

        $isTeologi = ($biodata->prodi?->kode_prodi === '31' || substr((string) $biodata->nim, 0, 2) === '31');
        if ($isTeologi) {
            $fields['Jenis Pekerjaan (Gerejawi)'] = $biodata->jenis_pekerjaan;
        }

        $missing = [];
        foreach ($fields as $label => $val) {
            if ($val === null || trim((string) $val) === '') {
                $missing[] = $label;
            }
        }

        $totalFields = count($fields);
        $filledFields = $totalFields - count($missing);
        $percentage = (int) round(($filledFields / $totalFields) * 100);

        return [
            'is_complete' => empty($missing),
            'percentage' => $percentage,
            'filled_count' => $filledFields,
            'total_fields' => $totalFields,
            'missing_fields' => $missing,
        ];
    }

    /**
     * Menghitung daftar kode atau ID pertanyaan yang dilewati/disembunyikan oleh logika percabangan (jump_to / IF).
     *
     * @param  Collection  $allQuestions
     * @param  Collection  $responses
     * @return array<string|int, bool>
     */
    public static function getSkippedQuestionCodes($allQuestions, $responses): array
    {
        $skipped = [];
        $questionsList = $allQuestions->values();
        $total = $questionsList->count();

        if ($total === 0) {
            return $skipped;
        }

        // Map kode pertanyaan (uppercase) ke indeks urutan
        $codeToIndex = [];
        $codeToQuestion = [];
        foreach ($questionsList as $idx => $q) {
            $code = strtoupper(trim((string) $q->kode_pertanyaan));
            if ($code !== '') {
                $codeToIndex[$code] = $idx;
                $codeToQuestion[$code] = $q;
            }
        }

        for ($i = 0; $i < $total; $i++) {
            $q = $questionsList[$i];
            $code = strtoupper(trim((string) $q->kode_pertanyaan));

            // Jika pertanyaan ini sudah dilewati oleh percabangan sebelumnya
            if (isset($skipped[$code]) || isset($skipped[$q->id])) {
                continue;
            }

            $resp = $responses->get($code) ?? $responses->get((string) $q->id);
            if (! $resp) {
                continue;
            }

            $rawAnswer = $resp->answer ?? $resp->answer_text;
            $jsonAnswer = $resp->answer_json;

            // Aturan Berbasis Status Pekerjaan F8 (Standar Tracer Study Kemendikbud Dikti)
            if ($code === 'F8') {
                $ansStr = strtolower(trim((string) $rawAnswer));

                // Status 4: Melanjutkan Pendidikan -> lewati pertanyaan pekerjaan/mencari kerja
                if (str_contains($ansStr, 'melanjutkan pendidikan') || $ansStr === '4') {
                    $workCodes = ['F3', 'F4', 'F504', 'F502', 'F505', 'F506', 'F6', 'F7', 'F7A', 'F14', 'F15', 'F16'];
                    foreach ($workCodes as $wc) {
                        if (isset($codeToQuestion[$wc])) {
                            $skipped[$wc] = true;
                            $skipped[$codeToQuestion[$wc]->id] = true;
                        }
                    }
                }
                // Status 2: Belum Memungkinkan Bekerja -> lewati studi lanjut dan pertanyaan pekerjaan
                elseif (str_contains($ansStr, 'belum memungkinkan') || $ansStr === '2') {
                    $skipCodes = ['F18', 'F18A', 'F18B', 'F18C', 'F18D', 'F3', 'F4', 'F504', 'F502', 'F505', 'F506', 'F6', 'F7', 'F7A', 'F14', 'F15', 'F16'];
                    foreach ($skipCodes as $sc) {
                        if (isset($codeToQuestion[$sc])) {
                            $skipped[$sc] = true;
                            $skipped[$codeToQuestion[$sc]->id] = true;
                        }
                    }
                }
                // Status 5: Tidak Kerja tetapi sedang mencari kerja -> lewati studi lanjut & pertanyaan sedang bekerja
                elseif (str_contains($ansStr, 'sedang mencari kerja') || $ansStr === '5') {
                    $skipCodes = ['F18', 'F18A', 'F18B', 'F18C', 'F18D', 'F504', 'F502', 'F505', 'F506', 'F14', 'F15', 'F16'];
                    foreach ($skipCodes as $sc) {
                        if (isset($codeToQuestion[$sc])) {
                            $skipped[$sc] = true;
                            $skipped[$codeToQuestion[$sc]->id] = true;
                        }
                    }
                }
                // Status 1 & 3: Bekerja / Wiraswasta -> lewati studi lanjut (F18)
                else {
                    foreach (['F18', 'F18A', 'F18B', 'F18C', 'F18D'] as $sc) {
                        if (isset($codeToQuestion[$sc])) {
                            $skipped[$sc] = true;
                            $skipped[$codeToQuestion[$sc]->id] = true;
                        }
                    }
                }
            }

            // Aturan Khusus F504:
            // Jika Ya -> sembunyikan F506 (pencarian > 6 bulan)
            // Jika Tidak -> sembunyikan F502 & F505 (pencarian <= 6 bulan & gaji)
            if ($code === 'F504') {
                $ansStr = strtolower(trim((string) $rawAnswer));
                if ($ansStr === 'ya' || $ansStr === '1' || str_starts_with($ansStr, 'ya')) {
                    if (isset($codeToQuestion['F506'])) {
                        $skipped['F506'] = true;
                        $skipped[$codeToQuestion['F506']->id] = true;
                    }
                } elseif ($ansStr === 'tidak' || $ansStr === '2' || str_starts_with($ansStr, 'tidak')) {
                    foreach (['F502', 'F505'] as $wc) {
                        if (isset($codeToQuestion[$wc])) {
                            $skipped[$wc] = true;
                            $skipped[$codeToQuestion[$wc]->id] = true;
                        }
                    }
                }
            }

            $options = $q->detils ?? collect();
            if ($options->isEmpty()) {
                continue;
            }

            $selectedOption = null;

            if (! empty($rawAnswer)) {
                $ansStr = strtolower(trim((string) $rawAnswer));
                $selectedOption = $options->first(function ($opt) use ($ansStr) {
                    $optText = strtolower(trim((string) $opt->option_text));
                    $optCode = strtolower(trim((string) ($opt->kode_opsi ?? '')));

                    return $optText === $ansStr || $optCode === $ansStr || ($ansStr !== '' && str_starts_with($ansStr, $optText));
                });
            } elseif (is_array($jsonAnswer)) {
                $sel = $jsonAnswer['selected'] ?? ($jsonAnswer['pilihan'] ?? null);
                if ($sel) {
                    $selStr = strtolower(trim((string) $sel));
                    $selectedOption = $options->first(function ($opt) use ($selStr) {
                        $optText = strtolower(trim((string) $opt->option_text));
                        $optCode = strtolower(trim((string) ($opt->kode_opsi ?? '')));

                        return $optText === $selStr || $optCode === $selStr;
                    });
                }
            }

            // Jika opsi yang dipilih memiliki instruksi jump_to
            if ($selectedOption && ! empty($selectedOption->jump_to)) {
                $targetCode = strtoupper(trim((string) $selectedOption->jump_to));
                $targetIdx = $codeToIndex[$targetCode] ?? null;

                if ($targetIdx !== null && $targetIdx > $i) {
                    for ($j = $i + 1; $j < $targetIdx; $j++) {
                        $skippedQ = $questionsList[$j];
                        $skipped[strtoupper(trim((string) $skippedQ->kode_pertanyaan))] = true;
                        $skipped[$skippedQ->id] = true;
                    }
                }
            }
        }

        return $skipped;
    }

    /**
     * Evaluasi kelengkapan kuesioner tracer study wajib bagi alumni.
     * Murni didasarkan pada butir pertanyaan dengan status wajib = 1 di tabel ref_subpertanyaan2021
     * yang berlaku pada jalur alur pengisian alumni (memperhitungkan percabangan / IF jump_to).
     *
     * @return array Shape: ['is_complete' => bool, 'percentage' => int, 'answered_count' => int, 'total_mandatory' => int, 'missing_questions' => array]
     */
    public static function evaluasiKuesionerWajib(Biodata $biodata): array
    {
        // Pastikan respon profil tersinkron ke tabel tracer
        KuesionerSyncService::syncProfileResponses($biodata);

        // Ambil ID dan respon pertanyaan alumni
        $responses = Tracer::where('biodata_id', $biodata->id)
            ->with('subpertanyaan')
            ->get()
            ->keyBy(function ($item) {
                return strtoupper(trim((string) ($item->subpertanyaan?->kode_pertanyaan ?? $item->kode_pertanyaan)));
            });

        // Ambil seluruh butir pertanyaan terurut
        $allQuestions = RefSubpertanyaan2021::with('detils')
            ->orderBy('order', 'asc')
            ->get();

        $skippedCodes = self::getSkippedQuestionCodes($allQuestions, $responses);

        $profileCodesUpper = array_map('strtoupper', self::PROFILE_MANAGED_CODES);

        // Ambil seluruh butir pertanyaan wajib (wajib = 1, non-header, dan bukan bagian profil)
        $mandatoryQuestions = $allQuestions->filter(function ($q) use ($profileCodesUpper) {
            $code = strtoupper(trim((string) $q->kode_pertanyaan));

            return $q->wajib && $q->type !== 'header' && ! in_array($code, $profileCodesUpper);
        });

        $missing = [];
        $applicableMandatory = [];

        foreach ($mandatoryQuestions as $q) {
            $code = strtoupper(trim((string) $q->kode_pertanyaan));

            // Jika pertanyaan ini dilewati oleh alur percabangan (jump_to / IF),
            // maka dianggap sudah selesai / tidak wajib diisi alumni
            if (isset($skippedCodes[$code]) || isset($skippedCodes[$q->id])) {
                continue;
            }

            $applicableMandatory[] = $q;
            $resp = $responses->get($code) ?? $responses->get((string) $q->id);
            $hasAnswer = false;

            if ($resp) {
                $rawAnswer = $resp->answer ?? $resp->answer_text;
                if (! empty($rawAnswer) && trim((string) $rawAnswer) !== '') {
                    $hasAnswer = true;
                } elseif (is_array($resp->answer_json) && count($resp->answer_json) > 0) {
                    $hasAnswer = true;
                }
            }

            if (! $hasAnswer) {
                $missing[] = "{$q->kode_pertanyaan} ({$q->subpertanyaan})";
            }
        }

        $totalMandatory = count($applicableMandatory);
        $missingCount = count($missing);
        $answeredCount = max(0, $totalMandatory - $missingCount);
        $percentage = $totalMandatory > 0 ? (int) round(($answeredCount / $totalMandatory) * 100) : 100;

        return [
            'is_complete' => ($missingCount === 0),
            'percentage' => $percentage,
            'answered_count' => $answeredCount,
            'total_mandatory' => $totalMandatory,
            'missing_questions' => $missing,
        ];
    }

    /**
     * Evaluasi total akhir status alumni (Profil + Kuesioner Wajib).
     *
     * @return array Shape: ['status' => string, 'is_complete' => bool, 'profile' => array, 'questionnaire' => array]
     */
    public static function evaluasiKelengkapanTotal(Biodata $biodata): array
    {
        $evalProfil = self::evaluasiProfil($biodata);
        $evalKuesioner = self::evaluasiKuesionerWajib($biodata);

        $isComplete = $evalProfil['is_complete'] && $evalKuesioner['is_complete'];

        return [
            'status' => $isComplete ? 'Selesai' : 'Belum Selesai',
            'is_complete' => $isComplete,
            'profile' => $evalProfil,
            'questionnaire' => $evalKuesioner,
        ];
    }
}
