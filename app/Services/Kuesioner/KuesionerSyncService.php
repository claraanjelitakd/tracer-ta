<?php

namespace App\Services\Kuesioner;

use App\Models\Biodata;
use App\Models\ProdiQuestion;
use App\Models\ProdiResponse;
use App\Models\RefSubpertanyaan2021;
use App\Models\Tracer;

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
        $biodata->refresh();
        $biodata->load(['dataAkademik', 'yudisium', 'perusahaan.propinsi', 'perusahaan.kabupaten', 'atasan', 'user', 'prodi']);

        $alamatPerusahaanParts = array_filter([
            $biodata->perusahaan?->alamat,
            $biodata->perusahaan?->kabupaten?->nama_kabupaten,
            $biodata->perusahaan?->propinsi?->nama_provinsi,
            $biodata->zipcode,
        ]);
        $alamatPerusahaan = ! empty($alamatPerusahaanParts) ? implode(', ', $alamatPerusahaanParts) : null;

        $tempatLahir = $biodata->dataAkademik?->tempat_lahir;
        $tanggalLahir = $biodata->dataAkademik?->tanggal_lahir
            ? date('d-m-Y', strtotime($biodata->dataAkademik->tanggal_lahir))
            : null;

        $jenisKelamin = null;
        if ($biodata->dataAkademik?->jenis_kelamin) {
            $jk = strtoupper(trim($biodata->dataAkademik->jenis_kelamin));
            $jenisKelamin = in_array($jk, ['L', 'LAKI-LAKI', 'PRIA']) ? 'Pria' : 'Wanita';
        }

        $tanggalLulus = $biodata->dataAkademik?->tanggal_kelulusan
            ? date('d-m-Y', strtotime($biodata->dataAkademik->tanggal_kelulusan))
            : ($biodata->tahun_lulus ?? ($biodata->dataAkademik?->tahun_lulus ? (string) $biodata->dataAkademik->tahun_lulus : null));

        $tahunLulus = $biodata->tahun_lulus ?? ($biodata->dataAkademik?->tahun_lulus ? (string) $biodata->dataAkademik->tahun_lulus : null);

        $profileMap = [
            'F1' => $biodata->nim,
            'F2A' => $biodata->nama ?? $biodata->dataAkademik?->nama,
            'F2B' => $biodata->nomor_telepon ?? $biodata->dataAkademik?->nomor_telepon,
            'F2C' => $biodata->email_pribadi ?? $biodata->dataAkademik?->email_pribadi,
            'F2D' => $biodata->alamat ?? $biodata->dataAkademik?->alamat_saat_ini,
            'BIO_TEMPAT_LAHIR' => $tempatLahir,
            'BIO_TANGGAL_LAHIR' => $tanggalLahir,
            'BIO_JK' => $jenisKelamin,
            'BIO_TGL_LULUS' => $tanggalLulus,
            'BIO_JUDUL_TA' => $biodata->yudisium?->judul_ta ?? $biodata->dataAkademik?->judul_skripsi,
            'BIO_NIK' => $biodata->nik ?? $biodata->dataAkademik?->nik,
            'BIO_NPWP' => $biodata->npwp,

            // Relasi ke Perusahaan & Atasan
            'F2E' => $biodata->perusahaan?->nama_perusahaan,
            'F5B' => $biodata->perusahaan?->nama_perusahaan,
            'F2E1' => $biodata->atasan?->nama,
            'F2E2' => $biodata->atasan?->telepon,
            'F2E3' => $biodata->atasan?->email,
            'F2F' => $alamatPerusahaan,
            'F510' => $alamatPerusahaan,
            'F5a1' => $biodata->perusahaan?->propinsi_id ? (string) $biodata->perusahaan->propinsi_id : null,
            'F5a2' => $biodata->perusahaan?->kabupaten_id ? (string) $biodata->perusahaan->kabupaten_id : null,
            'F2G' => $biodata->posisi_jabatan,
            'F5C' => $biodata->posisi_jabatan,
            'F2H' => $biodata->perusahaan?->skala,
            'F5D' => $biodata->perusahaan?->skala,
        ];

        foreach ($profileMap as $code => $val) {
            $question = RefSubpertanyaan2021::where('kode_pertanyaan', $code)->first();
            if (! $question) {
                continue;
            }

            if ($val === null || trim((string) $val) === '') {
                Tracer::where('biodata_id', $biodata->id)
                    ->where('question_id', $question->id)
                    ->delete();
            } else {
                Tracer::updateOrCreate(
                    ['biodata_id' => $biodata->id, 'question_id' => $question->id],
                    [
                        'nim' => $biodata->nim,
                        'kelompok' => $question->kelompok,
                        'kode_pertanyaan' => $question->kode_pertanyaan,
                        'subpertanyaan' => $question->subpertanyaan,
                        'answer' => (string) $val,
                        'answer_json' => null,
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
            'PSI-1-01' => $namaLengkap,
            'PSI-1-02' => $nim,
            'PSI-1-03' => $tahunLulus ? (string) $tahunLulus : null,
        ];

        foreach ($prodiSyncMap as $kodeSoal => $val) {
            if ($val === null || trim((string) $val) === '') {
                continue;
            }

            $pQ = $prodiQuestions->firstWhere('code', $kodeSoal);
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
