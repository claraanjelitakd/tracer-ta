<?php

namespace App\Http\Controllers\Alumni\Profil;

use App\Http\Controllers\Controller;
use App\Models\Atasan;
use App\Models\DataOrangTua;
use App\Models\Perusahaan;
use App\Services\Kuesioner\KuesionerSyncService;
use Illuminate\Http\Request;

/**
 * SimpanProfilController
 *
 * Fungsi: Menangani logika penyimpanan (update) profil alumni ke berbagai tabel database
 * (DataAkademik, Yudisium, DataOrangTua, Biodata, dan Perusahaan).
 * Tujuan: Memisahkan logika penyimpanan dari logika penampilan data (Single Responsibility).
 */
class SimpanProfilController extends Controller
{
    /**
     * Menyimpan Perubahan Profil
     */
    public function simpanPerubahanProfil(Request $request)
    {
        $pengguna = $request->user();
        $biodata = $pengguna->biodata;

        $dataTervalidasi = $request->all();

        // 1. Data Akademik & Data Yudisium bersifat data induk (Read-Only)
        // dan tidak dimodifikasi melalui form tracer alumni.

        // 2. Simpan Data Orang Tua (Simpan null jika field dikosongkan)
        $dataOrangTua = [
            'nama_orang_tua' => ! empty($dataTervalidasi['nama_orang_tua']) ? $dataTervalidasi['nama_orang_tua'] : null,
            'pekerjaan' => ! empty($dataTervalidasi['pekerjaan_orang_tua']) ? $dataTervalidasi['pekerjaan_orang_tua'] : null,
            'alamat' => ! empty($dataTervalidasi['alamat_orang_tua']) ? $dataTervalidasi['alamat_orang_tua'] : null,
            'kota' => ! empty($dataTervalidasi['kota_orang_tua']) ? $dataTervalidasi['kota_orang_tua'] : null,
            'kabupaten_id' => ! empty($dataTervalidasi['kabupaten_id_orang_tua']) ? $dataTervalidasi['kabupaten_id_orang_tua'] : null,
            'propinsi_id' => ! empty($dataTervalidasi['propinsi_id_orang_tua']) ? $dataTervalidasi['propinsi_id_orang_tua'] : (! empty($dataTervalidasi['provinsi_id_orang_tua']) ? $dataTervalidasi['provinsi_id_orang_tua'] : null),
            'kode_pos' => ! empty($dataTervalidasi['kode_pos_orang_tua']) ? $dataTervalidasi['kode_pos_orang_tua'] : null,
            'nomor_telepon' => ! empty($dataTervalidasi['nomor_telepon_orang_tua']) ? $dataTervalidasi['nomor_telepon_orang_tua'] : null,
        ];
        DataOrangTua::updateOrCreate(
            ['nim' => $biodata->nim],
            $dataOrangTua
        );

        // =========================================================================================
        // 3. TANGANI PERUSAHAAN
        // =========================================================================================
        $idPerusahaan = null;

        // Cek apakah alumni mengisi nama perusahaan pada FormKarier
        if (! empty($dataTervalidasi['nama_perusahaan'])) {
            $perusahaan = Perusahaan::firstOrCreate(
                ['nama_perusahaan' => $dataTervalidasi['nama_perusahaan']],
                [
                    'propinsi_id' => $dataTervalidasi['perusahaan_propinsi_id'] ?? ($dataTervalidasi['company_province_id'] ?? null),
                    'kabupaten_id' => $dataTervalidasi['perusahaan_kabupaten_id'] ?? ($dataTervalidasi['company_kabupaten_id'] ?? null),
                    'alamat' => $dataTervalidasi['perusahaan_alamat'] ?? ($dataTervalidasi['company_alamat'] ?? null),
                    'skala' => $dataTervalidasi['perusahaan_skala'] ?? ($dataTervalidasi['company_skala'] ?? null),
                ]
            );
            $perusahaan->update([
                'propinsi_id' => ! empty($dataTervalidasi['perusahaan_propinsi_id']) ? $dataTervalidasi['perusahaan_propinsi_id'] : (! empty($dataTervalidasi['company_province_id']) ? $dataTervalidasi['company_province_id'] : null),
                'kabupaten_id' => ! empty($dataTervalidasi['perusahaan_kabupaten_id']) ? $dataTervalidasi['perusahaan_kabupaten_id'] : (! empty($dataTervalidasi['company_kabupaten_id']) ? $dataTervalidasi['company_kabupaten_id'] : null),
                'alamat' => ! empty($dataTervalidasi['perusahaan_alamat']) ? $dataTervalidasi['perusahaan_alamat'] : (! empty($dataTervalidasi['company_alamat']) ? $dataTervalidasi['company_alamat'] : null),
                'skala' => ! empty($dataTervalidasi['perusahaan_skala']) ? $dataTervalidasi['perusahaan_skala'] : (! empty($dataTervalidasi['company_skala']) ? $dataTervalidasi['company_skala'] : null),
            ]);
            $idPerusahaan = $perusahaan->id;
        }

        // 4. Tangani Data Atasan (Supervisor)
        $idAtasan = null;
        if (! empty($dataTervalidasi['nama_atasan'])) {
            $atasan = ! empty($biodata->atasan_id)
                ? Atasan::find($biodata->atasan_id)
                : null;

            if ($atasan) {
                $atasan->update([
                    'nama' => $dataTervalidasi['nama_atasan'],
                    'email' => ! empty($dataTervalidasi['email_atasan']) ? $dataTervalidasi['email_atasan'] : null,
                    'telepon' => ! empty($dataTervalidasi['telepon_atasan']) ? $dataTervalidasi['telepon_atasan'] : null,
                ]);
            } else {
                $atasan = Atasan::create([
                    'nama' => $dataTervalidasi['nama_atasan'],
                    'email' => ! empty($dataTervalidasi['email_atasan']) ? $dataTervalidasi['email_atasan'] : null,
                    'telepon' => ! empty($dataTervalidasi['telepon_atasan']) ? $dataTervalidasi['telepon_atasan'] : null,
                ]);
            }
            $idAtasan = $atasan->id;
        }

        // =========================================================================================
        // 5. UPDATE PROFIL BIODATA ALUMNI UTAMA
        // =========================================================================================
        $biodata->update([
            'nama' => ! empty($dataTervalidasi['nama']) ? $dataTervalidasi['nama'] : $biodata->nama,
            'nomor_telepon' => ! empty($dataTervalidasi['nomor_telepon']) ? $dataTervalidasi['nomor_telepon'] : null,
            'email' => ! empty($dataTervalidasi['email']) ? $dataTervalidasi['email'] : (! empty($dataTervalidasi['email_pribadi']) ? $dataTervalidasi['email_pribadi'] : null),
            'email_pribadi' => ! empty($dataTervalidasi['email_pribadi']) ? $dataTervalidasi['email_pribadi'] : null,
            'alamat' => ! empty($dataTervalidasi['alamat_saat_ini']) ? $dataTervalidasi['alamat_saat_ini'] : (! empty($dataTervalidasi['alamat']) ? $dataTervalidasi['alamat'] : null),
            'kelurahan' => ! empty($dataTervalidasi['kelurahan']) ? $dataTervalidasi['kelurahan'] : null,
            'kecamatan' => ! empty($dataTervalidasi['kecamatan']) ? $dataTervalidasi['kecamatan'] : null,
            'kabupaten_id' => ! empty($dataTervalidasi['kabupaten_id']) ? $dataTervalidasi['kabupaten_id'] : null,
            'propinsi_id' => ! empty($dataTervalidasi['propinsi_id']) ? $dataTervalidasi['propinsi_id'] : (! empty($dataTervalidasi['provinsi_id']) ? $dataTervalidasi['provinsi_id'] : null),
            'kode_pos' => ! empty($dataTervalidasi['kode_pos']) ? $dataTervalidasi['kode_pos'] : null,
            'agama' => ! empty($dataTervalidasi['agama']) ? $dataTervalidasi['agama'] : null,
            'nik' => ! empty($dataTervalidasi['nik']) ? $dataTervalidasi['nik'] : null,
            'no_kk' => ! empty($dataTervalidasi['no_kk']) ? $dataTervalidasi['no_kk'] : null,
            'no_bpjs' => ! empty($dataTervalidasi['no_bpjs']) ? $dataTervalidasi['no_bpjs'] : null,
            'npwp' => ! empty($dataTervalidasi['npwp']) ? $dataTervalidasi['npwp'] : null,
            'instagram_url' => ! empty($dataTervalidasi['instagram_url']) ? $dataTervalidasi['instagram_url'] : null,
            'facebook_url' => ! empty($dataTervalidasi['facebook_url']) ? $dataTervalidasi['facebook_url'] : null,
            'linkedin_url' => ! empty($dataTervalidasi['linkedin_url']) ? $dataTervalidasi['linkedin_url'] : null,
            'linkedin_username' => ! empty($dataTervalidasi['linkedin_username']) ? $dataTervalidasi['linkedin_username'] : null,
            'expert' => ! empty($dataTervalidasi['expert']) ? $dataTervalidasi['expert'] : null,
            'minat' => ! empty($dataTervalidasi['minat']) ? $dataTervalidasi['minat'] : null,
            'posisi_jabatan' => ! empty($dataTervalidasi['posisi_jabatan']) ? $dataTervalidasi['posisi_jabatan'] : null,
            'jenis_pekerjaan' => ! empty($dataTervalidasi['jenis_pekerjaan']) ? $dataTervalidasi['jenis_pekerjaan'] : null,
            'zipcode' => ! empty($dataTervalidasi['zipcode']) ? $dataTervalidasi['zipcode'] : null,
            'perusahaan_id' => $idPerusahaan,
            'atasan_id' => $idAtasan,
        ]);

        $biodata->refresh();

        // Sinkronisasi otomatis ke tabel tracer kuesioner
        KuesionerSyncService::syncProfileResponses($biodata);

        return redirect()->back()->with('success', 'Profil biodata berhasil diperbarui.');
    }

    /**
     * Menyimpan Perusahaan Baru Langsung dari Modal Pop-up Tambah Perusahaan
     */
    public function tambahPerusahaanBaru(Request $request)
    {
        $validated = $request->validate([
            'nama_perusahaan' => 'required|string|max:255',
            'propinsi_id' => 'nullable|exists:propinsi,id',
            'province_id' => 'nullable|exists:propinsi,id',
            'kabupaten_id' => 'required|exists:kabupaten,id',
            'kode_pos' => 'required|string|max:15',
            'alamat' => 'nullable|string',
            'skala' => 'nullable|string',
        ], [
            'nama_perusahaan.required' => 'Nama perusahaan wajib diisi.',
            'kabupaten_id.required' => 'Kabupaten/Kota perusahaan wajib dipilih.',
            'kode_pos.required' => 'Kode pos perusahaan wajib diisi.',
        ]);

        $propinsiId = $validated['propinsi_id'] ?? ($validated['province_id'] ?? null);

        $perusahaan = Perusahaan::create([
            'nama_perusahaan' => $validated['nama_perusahaan'],
            'propinsi_id' => $propinsiId,
            'kabupaten_id' => $validated['kabupaten_id'],
            'alamat' => $validated['alamat'] ?? null,
            'kode_pos' => $validated['kode_pos'],
            'skala' => $validated['skala'] ?? 'Lokal',
            'status_verifikasi' => 'Menunggu Verifikasi',
        ]);

        return response()->json([
            'success' => true,
            'message' => 'Perusahaan berhasil ditambahkan ke database! Status: Menunggu Verifikasi.',
            'perusahaan' => $perusahaan,
            'company' => $perusahaan,
        ]);
    }
}
