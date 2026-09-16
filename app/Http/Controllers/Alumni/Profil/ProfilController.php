<?php

namespace App\Http\Controllers\Alumni\Profil;

use App\Http\Controllers\Controller;
use App\Models\Kabupaten;
use App\Models\Perusahaan;
use App\Models\Propinsi;
use Illuminate\Http\Request;

/**
 * ProfilController
 *
 * Fungsi: Mengelola halaman pengisian profil alumni (biodata diri, data akademik, dan orang tua).
 * Tujuan: Menyediakan antarmuka bagi alumni untuk memperbarui data pribadi dan pekerjaan mereka.
 */
class ProfilController extends Controller
{
    /**
     * Menampilkan Halaman Profil
     */
    public function tampilkanHalamanProfil(Request $request)
    {
        $pengguna = $request->user();
        $biodata = $pengguna->biodata()->with(['prodi', 'perusahaan', 'perusahaan.propinsi', 'perusahaan.kabupaten', 'dataAkademik', 'yudisium', 'orangTua', 'atasan'])->first();

        $propinsi = Propinsi::all();
        $kabupaten = Kabupaten::all();
        $dataAkademik = $biodata?->dataAkademik;
        $orangTua = $biodata?->orangTua ?? $biodata?->dataAkademik?->orangTua;
        $yudisium = $biodata?->yudisium ?? $biodata?->dataAkademik?->yudisium;
        $atasan = $biodata?->atasan;

        // Merakit formData murni di backend agar frontend Vue tidak perlu logika inisialisasi / pengecekan manual
        $formData = [
            // Identitas Pribadi
            'nim' => $biodata?->nim ?? '',
            'nama' => $biodata?->nama ?? ($dataAkademik?->nama ?? ''),
            'tempat_lahir' => $dataAkademik?->tempat_lahir ?? '',
            'tanggal_lahir' => $dataAkademik?->tanggal_lahir ?? '',
            'agama' => $biodata?->agama ?? ($dataAkademik?->agama ?? ''),
            'jenis_kelamin' => $dataAkademik?->jenis_kelamin ?? '',
            'golongan_darah' => $dataAkademik?->golongan_darah ?? '',
            'warga_negara' => $dataAkademik?->warga_negara ?? 'WNI',
            'nik' => $biodata?->nik ?? ($dataAkademik?->nik ?? ''),
            'no_kk' => $biodata?->no_kk ?? ($dataAkademik?->no_kk ?? ''),
            'nisn' => $dataAkademik?->nisn ?? '',
            'no_bpjs' => $biodata?->no_bpjs ?? ($dataAkademik?->no_bpjs ?? ''),
            'npwp' => $biodata?->npwp ?? '',

            // Kontak & Alamat Pribadi
            'alamat_saat_ini' => $biodata?->alamat ?? ($dataAkademik?->alamat_saat_ini ?? ''),
            'alamat' => $biodata?->alamat ?? ($dataAkademik?->alamat_saat_ini ?? ''),
            'kelurahan' => $biodata?->kelurahan ?? ($dataAkademik?->kelurahan ?? ''),
            'kecamatan' => $biodata?->kecamatan ?? ($dataAkademik?->kecamatan ?? ''),
            'kabupaten_id' => $biodata?->kabupaten_id ?? ($dataAkademik?->kabupaten_id ?? ''),
            'propinsi_id' => $biodata?->propinsi_id ?? ($dataAkademik?->propinsi_id ?? ''),
            'provinsi_id' => $biodata?->propinsi_id ?? ($dataAkademik?->propinsi_id ?? ''),
            'kode_pos' => $biodata?->kode_pos ?? ($dataAkademik?->kode_pos ?? ''),
            'nomor_telepon' => $biodata?->nomor_telepon ?? ($dataAkademik?->nomor_telepon ?? ''),
            'email' => $biodata?->email ?? ($biodata?->email_pribadi ?? ($dataAkademik?->email_pribadi ?? '')),
            'email_pribadi' => $biodata?->email_pribadi ?? ($dataAkademik?->email_pribadi ?? ''),
            'email_students' => $dataAkademik?->email_students ?? '',

            // Data Akademik Utama
            'angkatan_masuk' => $dataAkademik?->angkatan_masuk ?? '',
            'status_mahasiswa' => $dataAkademik?->status_mahasiswa ?? 'AR',
            'tahun_akademik_lulus' => $yudisium?->tahun_akademik_lulus ?? ($dataAkademik?->tahun_akademik_lulus ?? ''),
            'tahun_lulus' => $biodata?->tahun_lulus ?? ($yudisium?->tahun_lulus ?? ($dataAkademik?->tahun_lulus ?? '')),
            'ipk' => $dataAkademik?->ip_kumulatif ?? '',
            'ip_kumulatif' => $dataAkademik?->ip_kumulatif ?? '',
            'total_sks' => $dataAkademik?->total_sks ?? '',
            'total_angka_kualitas' => $dataAkademik?->total_angka_kualitas ?? '',
            'asal_sekolah' => $dataAkademik?->asal_sekolah ?? '',

            // Yudisium (Skripsi & Dosen)
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
            'instagram_url' => $biodata?->instagram_url ?? '',
            'facebook_url' => $biodata?->facebook_url ?? '',
            'linkedin_url' => $biodata?->linkedin_url ?? '',
            'linkedin_username' => $biodata?->linkedin_username ?? '',

            'expert' => $biodata?->expert ?? '',
            'minat' => $biodata?->minat ?? '',
            'posisi_jabatan' => $biodata?->posisi_jabatan ?? '',
            'jenis_pekerjaan' => $biodata?->jenis_pekerjaan ?? '',
            'zipcode' => $biodata?->zipcode ?? '',

            'nama_perusahaan' => $biodata?->perusahaan?->nama_perusahaan ?? '',
            'perusahaan_alamat' => $biodata?->perusahaan?->alamat ?? '',
            'perusahaan_skala' => $biodata?->perusahaan?->skala ?? '',
            'perusahaan_propinsi_id' => $biodata?->perusahaan?->propinsi_id ?? '',
            'perusahaan_kabupaten_id' => $biodata?->perusahaan?->kabupaten_id ?? '',
            'perusahaan_status_verifikasi' => $biodata?->perusahaan?->status_verifikasi ?? '',

            // Fallback key untuk kompatibilitas frontend
            'company_alamat' => $biodata?->perusahaan?->alamat ?? '',
            'company_skala' => $biodata?->perusahaan?->skala ?? '',
            'company_province_id' => $biodata?->perusahaan?->propinsi_id ?? '',
            'company_kabupaten_id' => $biodata?->perusahaan?->kabupaten_id ?? '',
            'company_status_verifikasi' => $biodata?->perusahaan?->status_verifikasi ?? '',

            // Data Atasan
            'nama_atasan' => $atasan?->nama ?? '',
            'email_atasan' => $atasan?->email ?? '',
            'telepon_atasan' => $atasan?->telepon ?? '',
        ];

        // Get all perusahaan for Autocomplete
        $perusahaans = Perusahaan::select('id', 'nama_perusahaan', 'propinsi_id', 'kabupaten_id', 'alamat', 'kode_pos', 'skala', 'status_verifikasi')->get();

        return inertia('Alumni/Profil/Index', [
            'biodataData' => $biodata,
            'alumniData' => $biodata,
            'formData' => $formData,
            'propinsis' => $propinsi,
            'provinces' => $propinsi,
            'kabupatens' => $kabupaten,
            'perusahaans' => $perusahaans,
            'companies' => $perusahaans,
        ]);
    }
}
