<?php

namespace Database\Seeders;

use App\Models\Atasan;
use App\Models\Biodata;
use App\Models\Kabupaten;
use App\Models\Perusahaan;
use App\Models\Prodi;
use App\Models\Propinsi;
use App\Models\User;
use Illuminate\Database\Seeder;

/**
 * Seeder Biodata
 *
 * Mengisi data profil biodata alumni untuk sistem Tracer Study UKDW.
 */
class BiodataSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $alumniUsers = User::where('role', 'alumni')->get();
        $perusahaans = Perusahaan::all();

        // Ambil wilayah untuk relasi ID yang valid
        $diy = Propinsi::where('kode_provinsi', '34')->orWhere('nama_provinsi', 'LIKE', '%Yogyakarta%')->first();
        $sleman = Kabupaten::where('kode_kabupaten', '34.04')->orWhere('nama_kabupaten', 'LIKE', '%Sleman%')->first();

        $jakarta = Propinsi::where('kode_provinsi', '31')->orWhere('nama_provinsi', 'LIKE', '%Jakarta%')->first();
        $jakpus = Kabupaten::where('kode_kabupaten', '31.71')->orWhere('nama_kabupaten', 'LIKE', '%Jakarta Pusat%')->first();

        $aceh = Propinsi::where('kode_provinsi', '11')->orWhere('nama_provinsi', 'LIKE', '%Aceh%')->first();
        $acehSelatan = Kabupaten::where('kode_kabupaten', '11.01')->orWhere('nama_kabupaten', 'LIKE', '%Aceh Selatan%')->first();

        $lokasiList = [
            ['prov' => $diy?->id, 'kab' => $sleman?->id, 'kota' => 'Kabupaten Sleman', 'kec' => 'Depok', 'kel' => 'Caturtunggal', 'kodepos' => '55281'],
            ['prov' => $jakarta?->id, 'kab' => $jakpus?->id, 'kota' => 'Kota Jakarta Pusat', 'kec' => 'Gambir', 'kel' => 'Kebon Kelapa', 'kodepos' => '10120'],
            ['prov' => $aceh?->id, 'kab' => $acehSelatan?->id, 'kota' => 'Kabupaten Aceh Selatan', 'kec' => 'Tapaktuan', 'kel' => 'Lhok Ketapang', 'kodepos' => '23715'],
        ];

        $jabatanList = [
            'Staff',
            'Supervisor',
            'Low manager',
            'Midle Manager',
            'Direksi / Top Manager',
            'Staff',
            'Supervisor',
            'Midle Manager',
            'Staff',
            'Supervisor',
        ];

        $expertList = [
            'Web Development & Cloud Computing',
            'Data Science & Predictive Analytics',
            'UI/UX Design & User Research',
            'Cyber Security & Penetration Testing',
            'Financial Auditing & Taxation',
            'Architectural 3D Modeling & BIM',
            'Food Quality Assurance & HACCP',
            'Clinical Medicine & Diagnostics',
            'Pastoral Care & Christian Ministry',
            'English Curriculum & Instructional Design',
        ];

        $minatList = [
            'Open Source, IoT, Artificial Intelligence',
            'Big Data, Deep Learning, Cloud Architecture',
            'Design Systems, Accessibility, Interaction Design',
            'Network Security, Cryptography, Blockchain',
            'Investment Portfolio, Stock Analysis, FinTech',
            'Sustainable Architecture, Green Building Design',
            'Food Biotechnology, Fermentation Science',
            'Public Health, Medical Research, Telemedicine',
            'Community Leadership, Interfaith Dialogue',
            'Language Acquisition, EdTech, Translation',
        ];

        $agamaList = ['Kristen Protestan', 'Katolik', 'Islam', 'Kristen Protestan', 'Katolik'];

        $atasanDataList = [
            ['nama' => 'Ir. Bambang Trihatmojo, M.Eng.', 'email' => 'bambang.t@perusahaan.co.id', 'telepon' => '081223344551'],
            ['nama' => 'Dra. Sri Wahyuni, M.Si.', 'email' => 'sri.wahyuni@perusahaan.co.id', 'telepon' => '081223344552'],
            ['nama' => 'Hartono Kusuma, S.E., Ak.', 'email' => 'hartono.k@perusahaan.co.id', 'telepon' => '081223344553'],
            ['nama' => 'Maya Indrawati, S.T., M.Kom.', 'email' => 'maya.indrawati@perusahaan.co.id', 'telepon' => '081223344554'],
            ['nama' => 'Rendra Pratama, S.Kom., MBA.', 'email' => 'rendra.pratama@perusahaan.co.id', 'telepon' => '081223344555'],
        ];

        foreach ($alumniUsers as $index => $user) {
            $nim = $user->username;
            $parsedInfo = Biodata::parseNim($nim);
            $angkatan = $parsedInfo ? (int) $parsedInfo['angkatan'] : 2020;
            $kodeProdi = $parsedInfo ? $parsedInfo['kode_prodi'] : '71';
            $lokasi = $lokasiList[$index % count($lokasiList)];

            $prodiId = null;
            if ($parsedInfo) {
                $prodiDb = Prodi::where('kode_prodi', $parsedInfo['kode_prodi'])->first();
                if ($prodiDb) {
                    $prodiId = $prodiDb->id;
                }
            }

            // Assign perusahaan secara bergantian jika ada perusahaan
            $perusahaanId = null;
            if ($perusahaans->isNotEmpty()) {
                $comp = $perusahaans[$index % $perusahaans->count()];
                $perusahaanId = $comp->id;
            }

            // Buat atau kaitkan data atasan
            $atasanInfo = $atasanDataList[$index % count($atasanDataList)];
            $atasan = Atasan::firstOrCreate(
                ['email' => 'atasan.'.$index.'.'.$atasanInfo['email']],
                [
                    'nama' => $atasanInfo['nama'],
                    'telepon' => $atasanInfo['telepon'],
                ]
            );

            $cleanUsername = strtolower(str_replace(' ', '', explode(' ', $user->name ?? 'alumni')[0])).$index;

            Biodata::updateOrCreate(
                ['user_id' => $user->id],
                [
                    'nim' => $nim,
                    'tahun_lulus' => $angkatan + 4,
                    'kode_prodi' => $kodeProdi,
                    'prodi_id' => $prodiId,
                    'nama' => $user->name,
                    'nomor_telepon' => '0812'.rand(1000, 9999).str_pad((string) $index, 4, '0', STR_PAD_LEFT),
                    'email' => $user->email,
                    'email_pribadi' => strtolower(str_replace(' ', '', explode(' ', $user->name)[0])).($index + 1).'@gmail.com',
                    'alamat' => 'Jl. Kenanga No. '.($index + 12).', RT 03/RW 05',
                    'kabupaten_id' => $lokasi['kab'],
                    'propinsi_id' => $lokasi['prov'],
                    'kelurahan' => $lokasi['kel'],
                    'kecamatan' => $lokasi['kec'],
                    'kode_pos' => $lokasi['kodepos'],
                    'agama' => $agamaList[$index % count($agamaList)],
                    'nik' => '3404'.str_pad((string) (100000000000 + $index * 1234), 12, '0', STR_PAD_LEFT),
                    'no_kk' => '340411'.str_pad((string) (2000000000 + $index * 5678), 10, '0', STR_PAD_LEFT),
                    'no_bpjs' => '000189'.str_pad((string) (7654321 + $index), 7, '0', STR_PAD_LEFT),
                    'npwp' => '09.'.rand(100, 999).'.'.rand(100, 999).'.'.rand(1, 9).'-541.000',
                    'instagram_url' => 'https://instagram.com/'.$cleanUsername,
                    'facebook_url' => 'https://facebook.com/'.$cleanUsername,
                    'linkedin_url' => 'https://linkedin.com/in/'.$cleanUsername,
                    'linkedin_username' => $cleanUsername,
                    'expert' => $expertList[$index % count($expertList)],
                    'minat' => $minatList[$index % count($minatList)],
                    'perusahaan_id' => $perusahaanId,
                    'atasan_id' => $atasan?->id,
                    'posisi_jabatan' => $jabatanList[$index % count($jabatanList)],
                    'jenis_pekerjaan' => ($parsedInfo && $parsedInfo['kode_prodi'] === '31') ? 'Gerejawi' : null,
                    'zipcode' => ($index % 3 == 0) ? '55281' : (($index % 3 == 1) ? '10120' : '23715'),
                ]
            );
        }
    }
}
