<?php

namespace App\Services\Kuesioner;

use App\Models\Biodata;
use App\Models\RefSubpertanyaan2021;
use App\Models\Tracer;

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
     * Mengambil seluruh butir pertanyaan kuesioner yang berstatus WAJIB (wajib = 1) dari database.
     *
     * @return array<int, string>
     */
    public static function getMandatoryQuestionCodes(): array
    {
        return RefSubpertanyaan2021::where('wajib', true)
            ->where('type', '!=', 'header')
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
     * Evaluasi kelengkapan kuesioner tracer study wajib bagi alumni.
     * Murni didasarkan pada butir pertanyaan dengan status wajib = 1 di tabel ref_subpertanyaan2021.
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
                return $item->subpertanyaan?->kode_pertanyaan ?? $item->kode_pertanyaan;
            });

        // Ambil seluruh butir pertanyaan wajib (wajib = 1 dan non-header)
        $mandatoryQuestions = RefSubpertanyaan2021::where('wajib', true)
            ->where('type', '!=', 'header')
            ->orderBy('order', 'asc')
            ->get();

        $missing = [];

        foreach ($mandatoryQuestions as $q) {
            $code = $q->kode_pertanyaan;
            $resp = $responses->get($code);
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
                $missing[] = "{$code} ({$q->subpertanyaan})";
            }
        }

        $totalMandatory = $mandatoryQuestions->count();
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
