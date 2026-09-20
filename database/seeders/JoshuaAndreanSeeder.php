<?php

namespace Database\Seeders;

use App\Models\Biodata;
use App\Models\DataAkademik;
use App\Models\DataOrangTua;
use App\Models\Kabupaten;
use App\Models\Prodi;
use App\Models\ProdiResponse;
use App\Models\Propinsi;
use App\Models\Tracer;
use App\Models\User;
use App\Models\Yudisium;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;

/**
 * Seeder Data Alumni Joshua Andrean (Lulusan 2026 - Sistem Informasi)
 *
 * Menyiapkan akun dan master data akademik & orang tua untuk pengujian pengisian mandiri.
 */
class JoshuaAndreanSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $nim = '72220001';
        $nama = 'Joshua Andrean';
        $angkatan = 2022;
        $tahunLulus = 2026;
        $tahunAkademikLulus = 'Genap 2025/2026';

        // 1. Ambil Data Referensi Prodi Sistem Informasi & Wilayah
        $prodi = Prodi::where('kode_prodi', '72')->orWhere('nama_prodi', 'LIKE', '%Sistem Informasi%')->first();
        $diy = Propinsi::where('kode_provinsi', '34')->orWhere('nama_provinsi', 'LIKE', '%Yogyakarta%')->first();
        $sleman = Kabupaten::where('kode_kabupaten', '34.04')->orWhere('nama_kabupaten', 'LIKE', '%Sleman%')->first();

        // 2. Buat Akun User Alumni
        $user = User::updateOrCreate(
            ['username' => $nim],
            [
                'name' => $nama,
                'email' => 'joshua.andrean@students.ukdw.ac.id',
                'password' => Hash::make('password123'),
                'role' => 'alumni',
                'prodi_id' => $prodi?->id,
                'must_change_password' => false,
            ]
        );

        // 3. Buat Data Akademik Mahasiswa
        $ipk = 3.85;
        $totalSks = 144;
        $angkaKualitas = round($ipk * $totalSks, 2);

        $dataAkademik = DataAkademik::updateOrCreate(
            ['nim' => $nim],
            [
                'nama' => $nama,
                // Data Universitas
                'angkatan_masuk' => (string) $angkatan,
                'status_mahasiswa' => 'AR',
                'tahun_akademik_lulus' => $tahunAkademikLulus,
                'tahun_lulus' => $tahunLulus,
                'ip_kumulatif' => $ipk,
                'total_sks' => $totalSks,
                'total_angka_kualitas' => $angkaKualitas,

                // Identitas Diri
                'tempat_lahir' => 'Yogyakarta',
                'tanggal_lahir' => '2004-08-15',
                'agama' => 'Kristen Protestan',
                'jenis_kelamin' => 'Laki-laki',
                'golongan_darah' => 'O',
                'warga_negara' => 'WNI',

                // Dokumen Kependudukan
                'nik' => '3404011508040001',
                'no_kk' => '3404011508040001',
                'nisn' => '0041234567',
                'no_bpjs' => '0001897654321',

                // Kontak & Domisili
                'nomor_telepon' => '081234567890',
                'email_pribadi' => 'joshua.andrean@gmail.com',
                'email_students' => $nim.'@students.ukdw.ac.id',
                'alamat_saat_ini' => 'Jl. Timoho No. 45, RT 02/RW 08',
                'kelurahan' => 'Caturtunggal',
                'kecamatan' => 'Depok',
                'kabupaten_id' => $sleman?->id,
                'propinsi_id' => $diy?->id,
                'kode_pos' => '55281',

                // Data Asal Sekolah
                'asal_sekolah' => 'SMA Negeri 3 Yogyakarta',
                'alamat_asal_sekolah' => 'Jl. Yos Sudarso No. 7',
                'kota_kabupaten_asal_sekolah' => 'Kota Yogyakarta',
                'provinsi_asal_sekolah' => 'DI Yogyakarta',
                'jurusan_asal_sekolah' => 'IPA',
            ]
        );

        // 4. Buat Data Orang Tua
        $dataOrangTua = DataOrangTua::updateOrCreate(
            ['nim' => $nim],
            [
                'nama_orang_tua' => 'Ir. Hendra Andrean, M.M.',
                'pekerjaan' => 'Wiraswasta',
                'alamat' => 'Jl. Timoho No. 45, RT 02/RW 08',
                'kota' => 'Kabupaten Sleman',
                'kabupaten_id' => $sleman?->id,
                'propinsi_id' => $diy?->id,
                'kode_pos' => '55281',
                'nomor_telepon' => '081398765432',
            ]
        );

        // 5. Buat Data Yudisium (Kelulusan Akademik & Skripsi)
        $yudisium = Yudisium::updateOrCreate(
            ['nim' => $nim],
            [
                'tahun_akademik_lulus' => $tahunAkademikLulus,
                'tahun_lulus' => $tahunLulus,
                'dosen_pembimbing_1' => 'Dr. Budi Susanto, S.Kom., M.T.',
                'dosen_pembimbing_2' => 'Gloria Virginia, S.Kom., MAI., Ph.D.',
                'dosen_penguji_1' => 'Willy Sudiarto Raharjo, S.Kom., M.Cs.',
                'dosen_penguji_2' => 'Laurentius Kuncoro Probo Saputro, S.T., M.Eng.',
                'judul_ta' => 'Perancangan dan Pengembangan Sistem Informasi Tracer Study Terintegrasi Berbasis Web',
                'judul_ta_inggris' => 'Design and Development of Web-Based Integrated Tracer Study Information System',
                'url_publikasi' => 'https://repository.ukdw.ac.id/handle/123456789/'.$nim,
                'jenis_publikasi' => 'Jurnal Nasional',
                'status_publikasi' => 'Terbit',
                'keterangan_hasil_yudisium' => 'Lulus dengan Pujian',
                'proses_yudisium' => 'Lulus',
            ]
        );

        // 6. Buat Record Biodata Utama (HANYA menghubungkan referensi data akademik, orang tua, yudisium)
        // Data tambahan (perusahaan, atasan, jabatan, gaji, media sosial, minat/expert) DIBIARKAN NULL agar diisi mandiri
        $biodata = Biodata::updateOrCreate(
            ['user_id' => $user->id],
            [
                'nim' => $nim,
                'orang_tua_id' => $dataOrangTua->id,
                'yudisium_id' => $yudisium->id,
                'prodi_id' => $prodi?->id,
                'tahun_lulus' => (string) $tahunLulus,
                'nama' => $nama,
                'email' => $user->email,

                // Kosongkan data tambahan / karier / profil profesional
                'tempat_lahir' => null,
                'tanggal_lahir' => null,
                'jenis_kelamin' => null,
                'golongan_darah' => null,
                'warga_negara' => 'WNI',
                'nomor_telepon' => null,
                'email_pribadi' => null,
                'alamat' => null,
                'kabupaten_id' => null,
                'propinsi_id' => null,
                'kelurahan' => null,
                'kecamatan' => null,
                'kode_pos' => null,
                'agama' => null,
                'nik' => null,
                'no_kk' => null,
                'nisn' => null,
                'no_bpjs' => null,
                'npwp' => null,
                'instagram_url' => null,
                'facebook_url' => null,
                'linkedin_url' => null,
                'linkedin_username' => null,
                'expert' => null,
                'minat' => null,
                'perusahaan_id' => null,
                'atasan_id' => null,
                'kategori_pekerjaan' => null,
                'posisi_jabatan' => null,
                'posisi_wiraswasta' => null,
                'pendidikan_tingkat' => null,
                'perguruan_tinggi' => null,
                'pendidikan_prodi' => null,
                'gaji' => null,
                'jenis_pekerjaan' => null,
                'zipcode' => null,
            ]
        );

        // 7. Pastikan Kuesioner Bersih / Belum Terisi
        Tracer::where('biodata_id', $biodata->id)->delete();
        ProdiResponse::where('biodata_id', $biodata->id)->delete();
    }
}
