<?php

namespace Database\Seeders;

use App\Models\Kabupaten;
use App\Models\Perusahaan;
use App\Models\Propinsi;
use Illuminate\Database\Seeder;

/**
 * Seeder Perusahaan
 *
 * Mengisi data master institusi dan perusahaan tempat alumni bekerja.
 */
class PerusahaanSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        // 1. DI Yogyakarta & Sleman
        $diy = Propinsi::where('kode_provinsi', '34')
            ->orWhere('nama_provinsi', 'LIKE', '%Yogyakarta%')
            ->first() ?? Propinsi::firstOrCreate(['nama_provinsi' => 'DI Yogyakarta', 'kode_provinsi' => '34']);

        $sleman = Kabupaten::where('kode_kabupaten', '34.04')
            ->orWhere(function ($q) use ($diy) {
                $q->where('propinsi_id', $diy->id)->where('nama_kabupaten', 'LIKE', '%Sleman%');
            })
            ->first() ?? Kabupaten::firstOrCreate(['propinsi_id' => $diy->id, 'nama_kabupaten' => 'Sleman', 'kode_kabupaten' => '34.04']);

        // 2. DKI Jakarta & Kota Jakarta Pusat
        $jakarta = Propinsi::where('kode_provinsi', '31')
            ->orWhere('nama_provinsi', 'LIKE', '%Jakarta%')
            ->first() ?? Propinsi::firstOrCreate(['nama_provinsi' => 'DKI Jakarta', 'kode_provinsi' => '31']);

        $jakpus = Kabupaten::where('kode_kabupaten', '31.71')
            ->orWhere(function ($q) use ($jakarta) {
                $q->where('propinsi_id', $jakarta->id)->where('nama_kabupaten', 'LIKE', '%Jakarta Pusat%');
            })
            ->first() ?? Kabupaten::firstOrCreate(['propinsi_id' => $jakarta->id, 'nama_kabupaten' => 'Kota Jakarta Pusat', 'kode_kabupaten' => '31.71']);

        // 3. Aceh & Aceh Selatan
        $aceh = Propinsi::where('kode_provinsi', '11')
            ->orWhere('nama_provinsi', 'LIKE', '%Aceh%')
            ->first() ?? Propinsi::firstOrCreate(['nama_provinsi' => 'Aceh (NAD)', 'kode_provinsi' => '11']);

        $acehSelatan = Kabupaten::where('kode_kabupaten', '11.01')
            ->orWhere(function ($q) use ($aceh) {
                $q->where('propinsi_id', $aceh->id)->where('nama_kabupaten', 'LIKE', '%Aceh Selatan%');
            })
            ->first() ?? Kabupaten::firstOrCreate(['propinsi_id' => $aceh->id, 'nama_kabupaten' => 'Aceh Selatan', 'kode_kabupaten' => '11.01']);

        $perusahaans = [
            // ==========================================
            // Perusahaan di Sleman, DI Yogyakarta
            // ==========================================
            [
                'nama_perusahaan' => 'PT Gameloft Indonesia',
                'propinsi_id' => $diy->id,
                'kabupaten_id' => $sleman->id,
                'alamat' => 'Pacific Building, Jl. Laksda Adisucipto No. 157, Sleman',
                'kode_pos' => '55281',
                'skala' => 'Internasional',
                'status_verifikasi' => 'Terverifikasi',
            ],
            [
                'nama_perusahaan' => 'PT Niagahoster',
                'propinsi_id' => $diy->id,
                'kabupaten_id' => $sleman->id,
                'alamat' => 'Jl. Palagan Tentara Pelajar No. 81, Sleman',
                'kode_pos' => '55581',
                'skala' => 'Nasional',
                'status_verifikasi' => 'Terverifikasi',
            ],
            [
                'nama_perusahaan' => 'PT Djarum Sleman Regional',
                'propinsi_id' => $diy->id,
                'kabupaten_id' => $sleman->id,
                'alamat' => 'Jl. Magelang Km 7.5, Mlati, Sleman',
                'kode_pos' => '55284',
                'skala' => 'Nasional',
                'status_verifikasi' => 'Terverifikasi',
            ],
            [
                'nama_perusahaan' => 'CV Javan Cipta Solusi',
                'propinsi_id' => $diy->id,
                'kabupaten_id' => $sleman->id,
                'alamat' => 'Jl. Kaliurang Km 9.2, Ngaglik, Sleman',
                'kode_pos' => '55581',
                'skala' => 'Lokal',
                'status_verifikasi' => 'Terverifikasi',
            ],

            // ==========================================
            // Perusahaan di Kota Jakarta Pusat, DKI Jakarta
            // ==========================================
            [
                'nama_perusahaan' => 'PT Bank Central Asia Tbk (Kantor Pusat)',
                'propinsi_id' => $jakarta->id,
                'kabupaten_id' => $jakpus->id,
                'alamat' => 'Menara BCA, Grand Indonesia, Jl. M.H. Thamrin No. 1, Kota Jakarta Pusat',
                'kode_pos' => '10310',
                'skala' => 'Nasional',
                'status_verifikasi' => 'Terverifikasi',
            ],
            [
                'nama_perusahaan' => 'PT Telekomunikasi Indonesia Tbk (Telkom Landmark)',
                'propinsi_id' => $jakarta->id,
                'kabupaten_id' => $jakpus->id,
                'alamat' => 'The Telkom Hub, Jl. Jend. Gatot Subroto Kav. 52, Kota Jakarta Pusat',
                'kode_pos' => '12710',
                'skala' => 'Nasional',
                'status_verifikasi' => 'Terverifikasi',
            ],
            [
                'nama_perusahaan' => 'PT Tokopedia',
                'propinsi_id' => $jakarta->id,
                'kabupaten_id' => $jakpus->id,
                'alamat' => 'Tokopedia Tower Ciputra World 2, Kota Jakarta Pusat',
                'kode_pos' => '12930',
                'skala' => 'Nasional',
                'status_verifikasi' => 'Terverifikasi',
            ],
            [
                'nama_perusahaan' => 'PT Astra International Tbk',
                'propinsi_id' => $jakarta->id,
                'kabupaten_id' => $jakpus->id,
                'alamat' => 'Menara Astra, Jl. Jend. Sudirman Kav. 5-6, Kota Jakarta Pusat',
                'kode_pos' => '10220',
                'skala' => 'Internasional',
                'status_verifikasi' => 'Terverifikasi',
            ],

            // ==========================================
            // Perusahaan di Aceh Selatan, Aceh
            // ==========================================
            [
                'nama_perusahaan' => 'PT Perkebunan Nusantara I (PTPN Unit Aceh Selatan)',
                'propinsi_id' => $aceh->id,
                'kabupaten_id' => $acehSelatan->id,
                'alamat' => 'Jl. Merdeka No. 45, Tapaktuan, Aceh Selatan',
                'kode_pos' => '23715',
                'skala' => 'Nasional',
                'status_verifikasi' => 'Terverifikasi',
            ],
            [
                'nama_perusahaan' => 'PT Bank Aceh Syariah Cabang Tapaktuan',
                'propinsi_id' => $aceh->id,
                'kabupaten_id' => $acehSelatan->id,
                'alamat' => 'Jl. Jenderal Sudirman No. 18, Tapaktuan, Aceh Selatan',
                'kode_pos' => '23711',
                'skala' => 'Lokal',
                'status_verifikasi' => 'Terverifikasi',
            ],
            // ==========================================
            // Perusahaan di Luar Negeri (International / Abroad)
            // ==========================================
            [
                'nama_perusahaan' => 'Google Asia Pacific Pte. Ltd.',
                'propinsi_id' => null,
                'kabupaten_id' => null,
                'alamat' => '70 Pasir Panjang Rd, #03-71 Mapletree Business City II',
                'kode_pos' => '117371',
                'skala' => 'Internasional',
                'jenis_lokasi' => 'Luar Negeri',
                'negara' => 'Singapura',
                'status_verifikasi' => 'Terverifikasi',
            ],
            [
                'nama_perusahaan' => 'Grab Holdings Inc. (Singapore HQ)',
                'propinsi_id' => null,
                'kabupaten_id' => null,
                'alamat' => '3 Media Close, Grab@Singapore HQ',
                'kode_pos' => '138498',
                'skala' => 'Internasional',
                'jenis_lokasi' => 'Luar Negeri',
                'negara' => 'Singapura',
                'status_verifikasi' => 'Terverifikasi',
            ],
            [
                'nama_perusahaan' => 'Rakuten Group, Inc. (Tokyo HQ)',
                'propinsi_id' => null,
                'kabupaten_id' => null,
                'alamat' => '1-14-1 Tamagawa, Setagaya-ku, Tokyo',
                'kode_pos' => '158-0094',
                'skala' => 'Internasional',
                'jenis_lokasi' => 'Luar Negeri',
                'negara' => 'Jepang',
                'status_verifikasi' => 'Terverifikasi',
            ],
        ];

        foreach ($perusahaans as $perusahaan) {
            $data = array_merge([
                'jenis_lokasi' => 'Dalam Negeri',
                'negara' => 'Indonesia',
            ], $perusahaan);

            Perusahaan::updateOrCreate(
                ['nama_perusahaan' => $data['nama_perusahaan']],
                $data
            );
        }
    }
}
