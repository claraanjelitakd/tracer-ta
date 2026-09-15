<?php

namespace App\Services\Kuesioner;

use App\Models\Alumni;
use App\Models\ProdiQuestion;
use App\Models\ProdiResponse;
use App\Models\RefSubpertanyaan2021;
use App\Models\Tracer;

/**
 * KuesionerSyncService
 *
 * Fungsi: Menyinkronkan data profil alumni (Data Akademik, Akun User, Perusahaan, dan Atasan)
 * secara otomatis ke tabel `tracers` untuk kelompok instrumen F1 s/d F2H, F5B..F5D, F510, dan BIO_*.
 */
class KuesionerSyncService
{
    /**
     * Menyinkronkan data profil alumni ke tabel tracers untuk pertanyaan identitas dan pekerjaan.
     *
     * @param  Alumni  $alumni  Model alumni yang akan disinkronkan datanya.
     */
    public static function syncProfileResponses(Alumni $alumni): void
    {
        $alumni->refresh();
        $alumni->load(['dataAkademik', 'company.province', 'company.kabupaten', 'atasan', 'user', 'prodi']);

        $alamatPerusahaanParts = array_filter([
            $alumni->company?->alamat,
            $alumni->company?->kabupaten?->nama_kabupaten,
            $alumni->company?->province?->nama_provinsi,
            $alumni->zipcode,
        ]);
        $alamatPerusahaan = ! empty($alamatPerusahaanParts) ? implode(', ', $alamatPerusahaanParts) : null;

        $tempatLahir = $alumni->dataAkademik?->tempat_lahir;
        $tanggalLahir = $alumni->dataAkademik?->tanggal_lahir
            ? date('d-m-Y', strtotime($alumni->dataAkademik->tanggal_lahir))
            : null;

        $jenisKelamin = null;
        if ($alumni->dataAkademik?->jenis_kelamin) {
            $jk = strtoupper(trim($alumni->dataAkademik->jenis_kelamin));
            $jenisKelamin = in_array($jk, ['L', 'LAKI-LAKI', 'PRIA']) ? 'Pria' : 'Wanita';
        }

        $tanggalLulus = $alumni->dataAkademik?->tanggal_kelulusan
            ? date('d-m-Y', strtotime($alumni->dataAkademik->tanggal_kelulusan))
            : ($alumni->dataAkademik?->tahun_lulus ? (string) $alumni->dataAkademik->tahun_lulus : null);

        $tahunLulus = $alumni->dataAkademik?->tahun_lulus ? (string) $alumni->dataAkademik->tahun_lulus : null;

        $profileMap = [
            'F1' => $alumni->nim,
            'F2A' => $alumni->dataAkademik?->nama,
            'F2B' => $alumni->dataAkademik?->nomor_telepon,
            'F2C' => $alumni->dataAkademik?->email_pribadi,
            'F2D' => $alumni->dataAkademik?->alamat_saat_ini,
            'BIO_TEMPAT_LAHIR' => $tempatLahir,
            'BIO_TANGGAL_LAHIR' => $tanggalLahir,
            'BIO_JK' => $jenisKelamin,
            'BIO_TGL_LULUS' => $tanggalLulus,
            'BIO_JUDUL_TA' => $alumni->dataAkademik?->judul_skripsi,
            'BIO_NIK' => $alumni->dataAkademik?->nik,
            'BIO_NPWP' => $alumni->dataAkademik?->npwp,

            // Relasi ke Perusahaan & Atasan
            'F2E' => $alumni->company?->nama_perusahaan,
            'F5B' => $alumni->company?->nama_perusahaan,
            'F2E1' => $alumni->atasan?->nama,
            'F2E2' => $alumni->atasan?->telepon,
            'F2E3' => $alumni->atasan?->email,
            'F2F' => $alamatPerusahaan,
            'F510' => $alamatPerusahaan,
            'F2G' => $alumni->posisi_jabatan,
            'F5C' => $alumni->posisi_jabatan,
            'F2H' => $alumni->company?->skala,
            'F5D' => $alumni->company?->skala,
        ];

        foreach ($profileMap as $code => $val) {
            $question = RefSubpertanyaan2021::where('kode_pertanyaan', $code)->first();
            if (! $question) {
                continue;
            }

            if ($val === null || trim((string) $val) === '') {
                Tracer::where('alumni_id', $alumni->id)
                    ->where('question_id', $question->id)
                    ->delete();
            } else {
                Tracer::updateOrCreate(
                    ['alumni_id' => $alumni->id, 'question_id' => $question->id],
                    [
                        'nim' => $alumni->nim,
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
        self::syncProdiResponses($alumni);
    }

    /**
     * Menyinkronkan data akademik alumni (Nama, NIM, Tahun Kelulusan)
     * secara otomatis ke tabel `prodi_responses`.
     */
    public static function syncProdiResponses(Alumni $alumni): void
    {
        if (! $alumni->prodi_id) {
            return;
        }

        $prodiQuestions = ProdiQuestion::where('prodi_id', $alumni->prodi_id)->get();
        if ($prodiQuestions->isEmpty()) {
            return;
        }

        $namaLengkap = $alumni->dataAkademik?->nama;
        $nim = $alumni->nim;
        $tahunLulus = $alumni->dataAkademik?->tahun_lulus ?? $alumni->dataAkademik?->tahun_akademik_lulus;

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
                    ['alumni_id' => $alumni->id, 'prodi_question_id' => $pQ->id],
                    [
                        'answer_text' => (string) $val,
                        'answer_json' => null,
                    ]
                );
            }
        }
    }
}
