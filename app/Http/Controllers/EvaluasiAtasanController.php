<?php

namespace App\Http\Controllers;

use App\Models\EvaluasiAtasan;
use App\Models\PertanyaanEvaluasiAtasan;
use App\Models\ResponEvaluasiAtasan;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

/**
 * EvaluasiAtasanController
 *
 * Fungsi: Mengelola halaman kuesioner evaluasi atasan / pengguna lulusan UKDW yang diakses secara publik via token tanpa perlu login.
 */
class EvaluasiAtasanController extends Controller
{
    /**
     * Menampilkan Halaman Formulir Evaluasi Atasan (Public Access via Token)
     */
    public function show(string $token): Response
    {
        $evaluasi = EvaluasiAtasan::where('token', $token)
            ->with(['biodata.prodi', 'biodata.perusahaan', 'perusahaan', 'atasan', 'respons.pertanyaan'])
            ->first();

        if (! $evaluasi) {
            return Inertia::render('Public/EvaluasiAtasanNotFound', [
                'message' => 'Tautan formulir evaluasi tidak ditemukan atau sudah tidak berlaku.',
            ]);
        }

        $biodata = $evaluasi->biodata;
        $atasan = $evaluasi->atasan;
        $perusahaan = $evaluasi->perusahaan ?? $biodata?->perusahaan;

        $alumniName = $biodata?->nama ?? $biodata?->dataAkademik?->nama ?? 'Alumni UKDW';
        $alumniProdi = $biodata?->prodi?->nama_prodi ?? $biodata?->dataAkademik?->program_studi ?? '-';
        $namaPerusahaan = $perusahaan?->nama_perusahaan ?? '-';
        $alamatPerusahaan = $perusahaan?->alamat ?? '-';
        $homepagePerusahaan = $perusahaan?->homepage ?? '';
        $telpPerusahaan = $perusahaan?->no_telp_fax ?? '';
        $bentukPerusahaan = $perusahaan?->bentuk_perusahaan ?? '';
        $skalaPerusahaan = $perusahaan?->skala ?? '';
        $jumlahPegawai = $perusahaan?->jumlah_pegawai ?? '';

        // Ambil daftar butir pertanyaan instrumen evaluasi atasan yang aktif dari master table
        $pertanyaanList = PertanyaanEvaluasiAtasan::where('is_active', true)
            ->orderBy('order', 'asc')
            ->get();

        // Map jawaban yang sudah ada jika formulir sudah pernah diisi
        $existingResponses = [];
        $existingCatatan = '';
        foreach ($evaluasi->respons as $resp) {
            if ($resp->pertanyaan) {
                $existingResponses[$resp->pertanyaan->kode] = $resp->nilai;
            }
            if (! empty($resp->catatan) && empty($existingCatatan)) {
                $existingCatatan = $resp->catatan;
            }
        }

        return Inertia::render('Public/EvaluasiAtasanForm', [
            'token' => $token,
            'evaluasi' => $evaluasi,
            'pertanyaanList' => $pertanyaanList,
            'existingResponses' => $existingResponses,
            'existingCatatan' => $existingCatatan,
            'alumniInfo' => [
                'nama' => $alumniName,
                'prodi' => $alumniProdi,
                'nim' => $biodata?->nim ?? '-',
            ],
            'initialCompany' => [
                'id' => $perusahaan?->id,
                'nama' => $namaPerusahaan,
                'alamat' => $alamatPerusahaan,
                'no_telp_fax' => $telpPerusahaan,
                'homepage' => $homepagePerusahaan,
                'bentuk' => $bentukPerusahaan,
                'skala' => $skalaPerusahaan,
                'jumlah_pegawai' => $jumlahPegawai,
                'nama_atasan' => $atasan?->nama ?? '',
                'email_atasan' => $atasan?->email ?? '',
                'telepon_atasan' => $atasan?->telepon ?? '',
            ],
            'isSubmitted' => (bool) $evaluasi->is_submitted,
        ]);
    }

    /**
     * Menyimpan Respon Hasil Evaluasi Kinerja Alumni dari Atasan
     */
    public function store(string $token, Request $request)
    {
        $evaluasi = EvaluasiAtasan::where('token', $token)
            ->with(['perusahaan', 'biodata.perusahaan', 'atasan'])
            ->firstOrFail();

        $validated = $request->validate([
            // Bagian I: Informasi Perusahaan (Tersimpan ke tabel Perusahaan)
            'nama_perusahaan' => 'required|string|max:255',
            'alamat_lengkap' => 'required|string',
            'no_telp_fax' => 'nullable|string|max:100',
            'homepage' => 'nullable|string|max:255',
            'bentuk_perusahaan' => 'nullable|string|max:100',
            'skala_perusahaan' => 'nullable|string|max:100',
            'jumlah_pegawai' => 'nullable|string|max:100',

            // Karakteristik Keberadaan Alumni UKDW (Tersimpan ke evaluasi_atasan)
            'jumlah_alumni_ukdw' => 'nullable|string|max:100',
            'standar_gaji_pertama' => 'nullable|string|max:100',

            // Data Atasan/Pimpinan (Tersimpan ke tabel Atasan)
            'nama_atasan' => 'nullable|string|max:255',
            'email_atasan' => 'nullable|email|max:255',
            'telepon_atasan' => 'nullable|string|max:100',

            // Bagian II: Jawaban Kuesioner Evaluasi (Termasuk Kesiapan Kerja & 12 Aspek Kinerja)
            'jawaban' => 'required|array',
            'catatan_lainnya' => 'nullable|string',
        ]);

        // 1. Perbarui data master perusahaan terkait
        $perusahaan = $evaluasi->perusahaan ?? $evaluasi->biodata?->perusahaan;
        if ($perusahaan) {
            $companyUpdates = [];
            if (! empty($validated['nama_perusahaan'])) {
                $companyUpdates['nama_perusahaan'] = $validated['nama_perusahaan'];
            }
            if (! empty($validated['alamat_lengkap'])) {
                $companyUpdates['alamat'] = $validated['alamat_lengkap'];
            }
            if (! empty($validated['homepage'])) {
                $companyUpdates['homepage'] = $validated['homepage'];
            }
            if (! empty($validated['no_telp_fax'])) {
                $companyUpdates['no_telp_fax'] = $validated['no_telp_fax'];
            }
            if (! empty($validated['bentuk_perusahaan'])) {
                $companyUpdates['bentuk_perusahaan'] = $validated['bentuk_perusahaan'];
            }
            if (! empty($validated['skala_perusahaan'])) {
                $companyUpdates['skala'] = $validated['skala_perusahaan'];
            }
            if (! empty($validated['jumlah_pegawai'])) {
                $companyUpdates['jumlah_pegawai'] = $validated['jumlah_pegawai'];
            }
            if (! empty($companyUpdates)) {
                $perusahaan->update($companyUpdates);
            }
        }

        // 2. Perbarui data profil atasan jika ada perubahan nama/telepon/email
        if ($evaluasi->atasan) {
            $atasanUpdates = [];
            if (! empty($validated['nama_atasan'])) {
                $atasanUpdates['nama'] = $validated['nama_atasan'];
            }
            if (! empty($validated['email_atasan'])) {
                $atasanUpdates['email'] = $validated['email_atasan'];
            }
            if (! empty($validated['telepon_atasan'])) {
                $atasanUpdates['telepon'] = $validated['telepon_atasan'];
            }
            if (! empty($atasanUpdates)) {
                $evaluasi->atasan->update($atasanUpdates);
            }
        }

        // 3. Simpan data transaksi ke evaluasi_atasan (hanya atribut spesifik survei alumni)
        $columnValues = [
            'perusahaan_id' => $perusahaan?->id ?? $evaluasi->perusahaan_id,
            'jumlah_alumni_ukdw' => $validated['jumlah_alumni_ukdw'] ?? null,
            'standar_gaji_pertama' => $validated['standar_gaji_pertama'] ?? null,
            'is_submitted' => true,
            'submitted_at' => now(),
        ];

        // 4. Simpan jawaban butir-butir pertanyaan ke tabel respon_evaluasi_atasan
        $masterPertanyaan = PertanyaanEvaluasiAtasan::all()->keyBy('kode');

        foreach ($validated['jawaban'] as $kode => $nilai) {
            $pertanyaanObj = $masterPertanyaan->get($kode);
            if ($pertanyaanObj && ! empty($nilai)) {
                ResponEvaluasiAtasan::updateOrCreate(
                    [
                        'evaluasi_atasan_id' => $evaluasi->id,
                        'pertanyaan_id' => $pertanyaanObj->id,
                    ],
                    [
                        'nilai' => (string) $nilai,
                        'catatan' => $validated['catatan_lainnya'] ?? null,
                    ]
                );
            }
        }

        $evaluasi->update($columnValues);

        return redirect()->back()->with('success', 'Terima kasih! Respon evaluasi kinerja lulusan UKDW berhasil disimpan.');
    }
}
