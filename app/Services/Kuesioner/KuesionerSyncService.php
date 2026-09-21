<?php

namespace App\Services\Kuesioner;

use App\Models\Biodata;
use App\Models\ProdiQuestion;
use App\Models\ProdiResponse;
use App\Models\RefSubpertanyaan2021;
use App\Models\Tracer;
use Illuminate\Support\Facades\DB;

/**
 * KuesionerSyncService
 *
 * Fungsi: Menyinkronkan data profil biodata alumni (Data Akademik, Akun User, Perusahaan, dan Atasan)
 * secara otomatis ke tabel `tracer` untuk kelompok instrumen F1 s/d F2H, F5B..F5D, F510, dan BIO_*.
 */
class KuesionerSyncService
{
    /**
     * Menyinkronkan data profil biodata alumni ke tabel tracer untuk pertanyaan identitas dan pekerjaan.
     *
     * @param  Biodata  $biodata  Model biodata yang akan disinkronkan datanya.
     */
    public static function syncProfileResponses(Biodata $biodata): void
    {
        // Ambil data terpadu alumni dari Database View
        $autofill = DB::table('v_alumni_kuesioner_autofill')
            ->where('biodata_id', $biodata->id)
            ->first();

        if (! $autofill) {
            return;
        }

        $tahunLulus = $biodata->tahun_lulus ?? ($biodata->dataAkademik?->tahun_lulus ? (string) $biodata->dataAkademik->tahun_lulus : null);
        $profileMap = (array) $autofill;

        // Hilangkan kolom non-pertanyaan
        unset($profileMap['user_id'], $profileMap['biodata_id'], $profileMap['nim']);

        $profileCodesUpper = array_map('strtoupper', KelengkapanTracerService::PROFILE_MANAGED_CODES);

        foreach ($profileMap as $code => $val) {
            $codeUpper = strtoupper($code);

            $question = RefSubpertanyaan2021::whereRaw('UPPER(kode_pertanyaan) = ?', [$codeUpper])
                ->with('detils')
                ->first();

            if (! $question) {
                continue;
            }

            if ($val === null || trim((string) $val) === '') {
                // Hanya hapus baris tracer jika kolom ini memang bagian kelola profil (PROFILE_MANAGED_CODES)
                if (in_array($codeUpper, $profileCodesUpper)) {
                    Tracer::where('biodata_id', $biodata->id)
                        ->where(function ($q) use ($question) {
                            $q->where('question_id', $question->id)
                                ->orWhere('kode_pertanyaan', $question->kode_pertanyaan);
                        })
                        ->delete();
                }
            } else {
                $answerJson = null;
                $answerString = (string) $val;
                if ($codeUpper === 'F505') {
                    $intGaji = (int) round((float) $val);
                    $answerString = (string) $intGaji;
                    $answerJson = ['F5051' => (string) $intGaji, 'total' => $intGaji];
                } elseif ($codeUpper === 'F8') {
                    // Normalisasikan ke teks opsi resmi F8 di ref_subpertanyaan_detil agar selalu cocok dengan pilihan radio button di frontend
                    $vStr = strtolower(trim((string) $val));
                    $matchedOpt = $question->detils->first(function ($opt) use ($vStr) {
                        $optText = strtolower(trim((string) $opt->option_text));

                        return $vStr === $optText
                            || ($vStr === 'pekerja' && str_contains($optText, 'bekerja'))
                            || ($vStr === 'bekerja' && str_contains($optText, 'bekerja'))
                            || ($vStr === 'mencari kerja' && str_contains($optText, 'mencari kerja'))
                            || ($vStr === 'belum memungkinkan bekerja' && str_contains($optText, 'belum memungkinkan'))
                            || ($vStr === 'melanjutkan pendidikan' && str_contains($optText, 'melanjutkan pendidikan'))
                            || ($vStr === 'wiraswasta' && str_contains($optText, 'wiraswasta'));
                    });

                    if ($matchedOpt) {
                        $answerString = $matchedOpt->option_text;
                    }
                }

                // Hapus baris dengan kode_pertanyaan sama tetapi id pertanyaan berbeda jika ada
                Tracer::where('biodata_id', $biodata->id)
                    ->where('kode_pertanyaan', $question->kode_pertanyaan)
                    ->where('question_id', '!=', $question->id)
                    ->delete();

                Tracer::updateOrCreate(
                    ['biodata_id' => $biodata->id, 'question_id' => $question->id],
                    [
                        'nim' => $biodata->nim,
                        'kelompok' => $question->kelompok,
                        'kode_pertanyaan' => $question->kode_pertanyaan,
                        'subpertanyaan' => $question->subpertanyaan,
                        'answer' => $answerString,
                        'answer_json' => $answerJson,
                        'keterangan' => $question->keterangan,
                        'tahun_lulus' => $tahunLulus,
                    ]
                );
            }
        }

        // Sinkronkan juga data akademik alumni ke kuesioner program studi
        self::syncProdiResponses($biodata);
    }

    /**
     * Menyinkronkan data akademik alumni (Nama, NIM, Tahun Kelulusan)
     * secara otomatis ke tabel `prodi_response`.
     */
    public static function syncProdiResponses(Biodata $biodata): void
    {
        if (! $biodata->prodi_id) {
            return;
        }

        $prodiQuestions = ProdiQuestion::where('prodi_id', $biodata->prodi_id)->get();
        if ($prodiQuestions->isEmpty()) {
            return;
        }

        $namaLengkap = $biodata->nama ?? $biodata->dataAkademik?->nama;
        $nim = $biodata->nim;
        $tahunLulus = $biodata->tahun_lulus ?? ($biodata->dataAkademik?->tahun_lulus ?? $biodata->dataAkademik?->tahun_akademik_lulus);

        $prodiSyncMap = [
            'nama' => $namaLengkap,
            'nim' => $nim,
            'tahun kelulusan' => $tahunLulus ? (string) $tahunLulus : null,
        ];

        foreach ($prodiSyncMap as $textKey => $val) {
            if ($val === null || trim((string) $val) === '') {
                continue;
            }

            $pQ = $prodiQuestions->first(fn ($q) => strtolower(trim((string) $q->question_text)) === $textKey);
            if ($pQ) {
                ProdiResponse::updateOrCreate(
                    ['biodata_id' => $biodata->id, 'prodi_question_id' => $pQ->id],
                    [
                        'answer_text' => (string) $val,
                        'answer_json' => null,
                    ]
                );
            }
        }
    }
}
