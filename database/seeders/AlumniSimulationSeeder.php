<?php

namespace Database\Seeders;

use App\Models\Atasan;
use App\Models\Biodata;
use App\Models\DataAkademik;
use App\Models\DataOrangTua;
use App\Models\Kabupaten;
use App\Models\Perusahaan;
use App\Models\Prodi;
use App\Models\ProdiQuestion;
use App\Models\ProdiResponse;
use App\Models\Propinsi;
use App\Models\RefSubpertanyaan2021;
use App\Models\Tracer;
use App\Models\User;
use App\Models\Yudisium;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;

/**
 * Seeder Simulasi Alumni Tracer Study Komprehensif (12 Program Studi UKDW)
 *
 * Tujuan:
 * Menyiapkan data simulasi riil kuesioner Tracer Study untuk seluruh 12 Program Studi di UKDW.
 * Setiap Program Studi memiliki minimal 10 alumni dengan distribusi data:
 * - 4 Orang: Lengkap 100% (Status: Selesai) dengan 4 Kategori Karier Berbeda:
 *     1. Bekerja di Perusahaan Swasta / BUMN (Dapat kerja sebelum lulus / F504 = Ya)
 *     2. Wiraswasta / Pendiri Usaha Mandiri (Founder & Owner)
 *     3. Melanjutkan Pendidikan S2 (Studi Lanjut Pascasarjana & Beasiswa LPDP)
 *     4. Bekerja di Startup / Industri Kreatif / Multinasional (Dapat kerja setelah lulus / F504 = Tidak)
 * - 3 Orang: Sedang Mengisi (Status: Belum Selesai, progress pengisian 40% - 75%)
 * - 3 Orang: Belum Mengisi (Status: Belum Selesai, hanya data induk akademik & yudisium)
 *
 * Seluruh alumni memiliki catatan kelulusan Yudisium dengan proses_yudisium = 'Lulus'
 * serta tahun_akademik_lulus yang sinkron persis dengan tahun_lulus.
 */
class AlumniSimulationSeeder extends Seeder
{
    /**
     * Jalankan database seeds simulasi alumni.
     */
    public function run(): void
    {
        // 1. Ambil seluruh master Program Studi UKDW
        $prodis = Prodi::all()->keyBy('kode_prodi');
        if ($prodis->isEmpty()) {
            $this->command->error('Master Prodi belum di-seed!');

            return;
        }

        // 2. Siapkan data referensi wilayah & instansi
        $propinsiDiy = Propinsi::where('kode_provinsi', '34')->orWhere('nama_provinsi', 'LIKE', '%Yogyakarta%')->first();
        $kabSleman = Kabupaten::where('kode_kabupaten', '34.04')->orWhere('nama_kabupaten', 'LIKE', '%Sleman%')->first();
        $kabKotaYogya = Kabupaten::where('kode_kabupaten', '34.71')->orWhere('nama_kabupaten', 'LIKE', '%Yogyakarta%')->first();

        $propinsiJkt = Propinsi::where('kode_provinsi', '31')->orWhere('nama_provinsi', 'LIKE', '%Jakarta%')->first();
        $kabJaksel = Kabupaten::where('kode_kabupaten', '31.74')->orWhere('nama_kabupaten', 'LIKE', '%Jakarta Selatan%')->first();
        $kabJakpus = Kabupaten::where('kode_kabupaten', '31.71')->orWhere('nama_kabupaten', 'LIKE', '%Jakarta Pusat%')->first();

        $perusahaans = Perusahaan::all();

        // 3. Matriks Target Kelulusan (Semester & Tahun Lulus Sinkron)
        // Format: [angkatan_masuk, tahun_akademik_lulus, tahun_lulus]
        $targetKelulusanList = [
            ['angkatan' => '2022', 'semester' => 'Genap 2025/2026', 'tahun' => 2026], // Index 0: Selesai (Kategori 1)
            ['angkatan' => '2021', 'semester' => 'Gasal 2025/2026', 'tahun' => 2025], // Index 1: Selesai (Kategori 2)
            ['angkatan' => '2021', 'semester' => 'Genap 2024/2025', 'tahun' => 2025], // Index 2: Selesai (Kategori 3)
            ['angkatan' => '2020', 'semester' => 'Gasal 2024/2025', 'tahun' => 2024], // Index 3: Selesai (Kategori 4)
            ['angkatan' => '2022', 'semester' => 'Genap 2025/2026', 'tahun' => 2026], // Index 4: Sedang Mengisi
            ['angkatan' => '2021', 'semester' => 'Gasal 2025/2026', 'tahun' => 2025], // Index 5: Sedang Mengisi
            ['angkatan' => '2021', 'semester' => 'Genap 2024/2025', 'tahun' => 2025], // Index 6: Sedang Mengisi
            ['angkatan' => '2020', 'semester' => 'Gasal 2024/2025', 'tahun' => 2024], // Index 7: Belum Mengisi
            ['angkatan' => '2020', 'semester' => 'Genap 2023/2024', 'tahun' => 2024], // Index 8: Belum Mengisi
            ['angkatan' => '2019', 'semester' => 'Gasal 2023/2024', 'tahun' => 2023], // Index 9: Belum Mengisi
        ];

        // 4. Koleksi Bank Nama Mahasiswa Riil per Urutan
        $namaKoleksi = [
            0 => ['L' => 'Benedictus Daniel Setiawan', 'P' => 'Clara Anjelita Kusumadewi'],
            1 => ['L' => 'Christian Aditya Pratama', 'P' => 'Theresia Cindy Paramitha'],
            2 => ['L' => 'Jonathan Edward Wibowo', 'P' => 'Gabriella Maria Santoso'],
            3 => ['L' => 'Timothy Kevin Kurniawan', 'P' => 'Angelina Fiona Putri'],
            4 => ['L' => 'Samuel Evan Prasetya', 'P' => 'Jessica Laurencia Tan'],
            5 => ['L' => 'Michael Steven Nugroho', 'P' => 'Stephanie Amanda Sihombing'],
            6 => ['L' => 'David Alexander Haryanto', 'P' => 'Nathania Grace Gunawan'],
            7 => ['L' => 'Andreas Timothy Wijaya', 'P' => 'Felicia Michelle Chandra'],
            8 => ['L' => 'Reynaldi Bagus Prakoso', 'P' => 'Patricia Clarissa Dewanti'],
            9 => ['L' => 'Vincentius Bagas Baskoro', 'P' => 'Vanessa Stephanie Hartono'],
        ];

        // 5. Bank Judul Tugas Akhir / Skripsi Representatif per Program Studi
        $bankJudulTA = [
            '71' => 'Rancang Bangun Sistem Klasifikasi Citra Medis Berbasis Convolutional Neural Network',
            '72' => 'Analisis dan Perancangan Sistem Informasi Enterprise Resource Planning Terintegrasi',
            '11' => 'Pengaruh Digital Marketing dan Brand Trust terhadap Keputusan Pembelian Konsumen Generasi Z',
            '12' => 'Analisis Pengaruh Fraud Triangle dan Whistleblowing System terhadap Pencegahan Kecurangan Finansial',
            '21' => 'Perancangan Kawasan Pusat Kebudayaan Tropis Berkelanjutan dengan Konsep Biophilic Design',
            '22' => 'Desain Alat Bantu Fisioterapi Portabel Ramah Disabilitas Berbasis Ergonomi Produk',
            '41' => 'Uji Aktivitas Antibakteri Ekstrak Daun Kelor terhadap Pertumbuhan Bakteri Patogen Pangan',
            '42' => 'Karakterisasi Fisikokimia dan Sensoris Produk Fermentasi Tepung Mocaf Fortifikasi Serat Alami',
            '61' => 'Hubungan Pola Tidur dan Tingkat Stres terhadap Indeks Prestasi Akademik Mahasiswa Kedokteran',
            '31' => 'Konstruksi Teologi Persahabatan Kontekstual dalam Pelayanan Komunitas Lintas Agama di Era Post-Truth',
            '81' => 'The Effectiveness of Task-Based Language Teaching in Improving ESL Students Speaking Fluency',
            '82' => 'Representasi Identitas Sosio-Kultural Masyarakat Urban dalam Film Indonesia Kontemporer',
        ];

        // 6. Ambil master butir kuesioner universitas (ref_subpertanyaan2021)
        $univQuestions = RefSubpertanyaan2021::all()->keyBy('kode_pertanyaan');

        // Iterasi ke seluruh 12 Program Studi
        foreach ($prodis as $kodeProdi => $prodi) {
            // Butir pertanyaan spesifik prodi (jika ada)
            $prodiQuestions = ProdiQuestion::where('prodi_id', $prodi->id)
                ->where('type', '!=', 'header')
                ->get();

            // Iterasi 10 Alumni untuk Program Studi Ini
            for ($i = 0; $i < 10; $i++) {
                $cfg = $targetKelulusanList[$i];
                $angkatan = $cfg['angkatan'];
                $duaDigitAngkatan = substr($angkatan, 2, 2);

                // NIM format standar 8 digit UKDW: [kode_prodi (2 digit)][angkatan (2 digit)][nomor_urut (4 digit)]
                $nomorUrut = str_pad((string) ($i + 1), 4, '0', STR_PAD_LEFT);
                $nim = (string) $kodeProdi.$duaDigitAngkatan.$nomorUrut;

                // Khusus Sistem Informasi (72): Lewati 72220001 agar akun pengujian Joshua Andrean tetap murni
                if ((string) $kodeProdi === '72' && $nim === '72220001') {
                    $nomorUrut = str_pad((string) ($i + 2), 4, '0', STR_PAD_LEFT);
                    $nim = (string) $kodeProdi.$duaDigitAngkatan.$nomorUrut;
                }

                $isLaki = ($i % 2 === 0);
                $nama = $isLaki ? $namaKoleksi[$i]['L'] : $namaKoleksi[$i]['P'];

                // A. Buat / Perbarui Akun User Alumni
                $user = User::updateOrCreate(
                    ['username' => $nim],
                    [
                        'name' => $nama,
                        'email' => strtolower(str_replace(' ', '.', explode(' ', $nama)[0])).'.'.$nim.'@students.ukdw.ac.id',
                        'password' => Hash::make('15082001'), // Format default tanggal lahir DDMMYYYY
                        'role' => 'alumni',
                        'prodi_id' => $prodi->id,
                        'must_change_password' => ($i >= 7), // Belum mengisi wajib ganti password awal
                    ]
                );

                // B. Buat Data Induk Akademik Mahasiswa
                $ipk = round(3.40 + (($i * 6) % 55) / 100, 2);
                $totalSks = ($kodeProdi === '61') ? 160 : 144;
                $dataAkademik = DataAkademik::updateOrCreate(
                    ['nim' => $nim],
                    [
                        'nama' => $nama,
                        'angkatan_masuk' => $angkatan,
                        'status_mahasiswa' => 'AR',
                        'tahun_akademik_lulus' => $cfg['semester'],
                        'tahun_lulus' => $cfg['tahun'],
                        'ip_kumulatif' => $ipk,
                        'total_sks' => $totalSks,
                        'total_angka_kualitas' => round($ipk * $totalSks, 2),
                        'tempat_lahir' => ($i % 2 === 0 ? 'Yogyakarta' : 'Jakarta'),
                        'tanggal_lahir' => '2001-08-15',
                        'agama' => ($kodeProdi === '31' ? 'Kristen Protestan' : (($i % 3 === 0) ? 'Kristen Protestan' : 'Katolik')),
                        'jenis_kelamin' => $isLaki ? 'Laki-laki' : 'Perempuan',
                        'golongan_darah' => ['O', 'A', 'B', 'AB'][$i % 4],
                        'warga_negara' => 'WNI',
                        'nik' => '3404'.str_pad((string) (100000000000 + (int) $kodeProdi * 10000 + $i), 12, '0', STR_PAD_LEFT),
                        'no_kk' => '340411'.str_pad((string) (2000000000 + (int) $kodeProdi * 10000 + $i), 10, '0', STR_PAD_LEFT),
                        'nisn' => '003'.str_pad((string) (1234500 + $i), 7, '0', STR_PAD_LEFT),
                        'no_bpjs' => '000189'.str_pad((string) (7654000 + $i), 7, '0', STR_PAD_LEFT),
                        'nomor_telepon' => '0812'.rand(1000, 9999).str_pad((string) $i, 4, '0', STR_PAD_LEFT),
                        'email_pribadi' => strtolower(str_replace(' ', '', explode(' ', $nama)[0])).($i + 1).'@gmail.com',
                        'email_students' => $user->email,
                        'alamat_saat_ini' => 'Jl. Solo Km. '.($i + 4).' No. '.($i * 7 + 10).', Caturtunggal',
                        'kelurahan' => 'Caturtunggal',
                        'kecamatan' => 'Depok',
                        'kabupaten_id' => $kabSleman?->id,
                        'propinsi_id' => $propinsiDiy?->id,
                        'kode_pos' => '55281',
                        'asal_sekolah' => 'SMA Negeri '.(($i % 5) + 1).' Yogyakarta',
                        'alamat_asal_sekolah' => 'Jl. Kebangsaan No. 10',
                        'kota_kabupaten_asal_sekolah' => 'Kota Yogyakarta',
                        'provinsi_asal_sekolah' => 'DI Yogyakarta',
                        'jurusan_asal_sekolah' => ($kodeProdi === '11' || $kodeProdi === '12' ? 'IPS' : 'IPA'),
                    ]
                );

                // C. Buat Data Orang Tua Mahasiswa
                $namaOrtu = 'Ir. Herman '.explode(' ', $nama)[count(explode(' ', $nama)) - 1].', M.M.';
                $dataOrangTua = DataOrangTua::updateOrCreate(
                    ['nim' => $nim],
                    [
                        'nama_orang_tua' => $namaOrtu,
                        'pekerjaan' => ['Pegawai Swasta', 'Wiraswasta', 'PNS / ASN', 'Dosen / Akademisi'][$i % 4],
                        'alamat' => $dataAkademik->alamat_saat_ini,
                        'kota' => 'Kabupaten Sleman',
                        'kabupaten_id' => $kabSleman?->id,
                        'propinsi_id' => $propinsiDiy?->id,
                        'kode_pos' => '55281',
                        'nomor_telepon' => '08139876'.str_pad((string) ($i * 111), 4, '0', STR_PAD_LEFT),
                    ]
                );

                // D. Buat Catatan Yudisium Kelulusan (Target Tracer Study Resmi)
                $judulTA = $bankJudulTA[$kodeProdi] ?? 'Analisis dan Implementasi Sistem Terintegrasi di Lingkungan Profesional';
                $yudisium = Yudisium::updateOrCreate(
                    ['nim' => $nim],
                    [
                        'tahun_akademik_lulus' => $cfg['semester'],
                        'tahun_lulus' => $cfg['tahun'],
                        'dosen_pembimbing_1' => 'Dr. Budi Susanto, S.Kom., M.T.',
                        'dosen_pembimbing_2' => 'Gloria Virginia, S.Kom., MAI., Ph.D.',
                        'dosen_penguji_1' => 'Willy Sudiarto Raharjo, S.Kom., M.Cs.',
                        'dosen_penguji_2' => 'Laurentius Kuncoro Probo Saputro, S.T., M.Eng.',
                        'judul_ta' => $judulTA,
                        'judul_ta_inggris' => 'Design and Analysis of Integrated System in Professional Environment',
                        'url_publikasi' => 'https://repository.ukdw.ac.id/handle/123456789/'.$nim,
                        'jenis_publikasi' => ($i % 2 === 0) ? 'Jurnal Nasional Terakreditasi' : 'Prosiding Seminar Internasional',
                        'status_publikasi' => 'Terbit',
                        'keterangan_hasil_yudisium' => ($ipk >= 3.75 ? 'Lulus dengan Pujian (Cumlaude)' : 'Sangat Memuaskan'),
                        'proses_yudisium' => 'Lulus', // WAJIB Lulus agar terdeteksi sebagai target tracer
                    ]
                );

                // E. Bangun Profil Biodata & Variasi Status Responden
                $cleanUsername = strtolower(str_replace(' ', '', explode(' ', $nama)[0])).$nim;

                // =========================================================================
                // SKENARIO 1: LENGKAP 100% (Status: Selesai) - 4 Kategori Karier Berbeda
                // =========================================================================
                if ($i < 4) {
                    $this->seedAlumniLengkap(
                        $i,
                        $user,
                        $nim,
                        $nama,
                        $prodi,
                        $dataAkademik,
                        $dataOrangTua,
                        $yudisium,
                        $cfg,
                        $perusahaans,
                        $propinsiDiy,
                        $kabSleman,
                        $propinsiJkt,
                        $kabJaksel,
                        $cleanUsername,
                        $univQuestions,
                        $prodiQuestions
                    );
                }
                // =========================================================================
                // SKENARIO 2: SEDANG MENGISI (Status: Belum Selesai, Progress 40% - 75%)
                // =========================================================================
                elseif ($i >= 4 && $i <= 6) {
                    $this->seedAlumniSedangMengisi(
                        $i,
                        $user,
                        $nim,
                        $nama,
                        $prodi,
                        $dataAkademik,
                        $dataOrangTua,
                        $yudisium,
                        $cfg,
                        $perusahaans,
                        $propinsiDiy,
                        $kabSleman,
                        $cleanUsername,
                        $univQuestions
                    );
                }
                // =========================================================================
                // SKENARIO 3: BELUM MENGISI (Status: Belum Selesai, 0% Pengisian Kuesioner)
                // =========================================================================
                else {
                    $this->seedAlumniBelumMengisi(
                        $user,
                        $nim,
                        $nama,
                        $prodi,
                        $dataAkademik,
                        $dataOrangTua,
                        $yudisium,
                        $cfg
                    );
                }
            }
        }
    }

    /**
     * Seeder untuk Alumni Lengkap 100% (Status Selesai)
     * Mengimplementasikan 4 Kategori Berbeda:
     * - Kategori 1 (Index 0): Bekerja Swasta / BUMN (Bekerja sebelum lulus / F504 Ya)
     * - Kategori 2 (Index 1): Wiraswasta / Pendiri Usaha Sendiri
     * - Kategori 3 (Index 2): Melanjutkan Pendidikan S2 (Studi Lanjut)
     * - Kategori 4 (Index 3): Bekerja Startup / Multinasional (Mencari setelah lulus / F504 Tidak)
     */
    protected function seedAlumniLengkap(
        int $kategoriIdx,
        User $user,
        string $nim,
        string $nama,
        Prodi $prodi,
        DataAkademik $dataAkademik,
        DataOrangTua $dataOrangTua,
        Yudisium $yudisium,
        array $cfg,
        $perusahaans,
        $propinsiDiy,
        $kabSleman,
        $propinsiJkt,
        $kabJaksel,
        string $cleanUsername,
        $univQuestions,
        $prodiQuestions
    ): void {
        // Data Perusahaan & Atasan Berdasarkan Kategori
        $perusahaanId = null;
        $atasanId = null;
        $kategoriPekerjaan = 'Bekerja (Full Time)';
        $posisiJabatan = 'Professional Staff';
        $posisiWiraswasta = null;
        $pendidikanTingkat = null;
        $perguruanTinggi = null;
        $pendidikanProdi = null;
        $gaji = 8500000;

        if ($kategoriIdx === 0) {
            // Kategori 1: Bekerja Swasta / BUMN
            $kategoriPekerjaan = 'Bekerja (Full Time)';
            $posisiJabatan = ($prodi->kode_prodi === '71' || $prodi->kode_prodi === '72') ? 'Software Engineer' :
                (($prodi->kode_prodi === '11' || $prodi->kode_prodi === '12') ? 'Senior Financial Analyst' :
                (($prodi->kode_prodi === '21') ? 'Junior Architect' : 'Professional Specialist'));
            $gaji = 9500000;

            $perusahaan = Perusahaan::firstOrCreate(
                ['nama_perusahaan' => 'PT Bank Mandiri (Persero) Tbk'],
                [
                    'propinsi_id' => $propinsiJkt?->id,
                    'kabupaten_id' => $kabJaksel?->id,
                    'alamat' => 'Jl. Gatot Subroto Kav. 36-38, Senayan',
                    'sektor' => 'Perbankan & Keuangan',
                    'skala' => 'Nasional',
                    'jenis_perusahaan' => 'BUMN/BUMD',
                    'jenis_lokasi' => 'Dalam Negeri',
                    'negara' => 'Indonesia',
                    'status_verifikasi' => 'Terverifikasi',
                ]
            );
            $perusahaanId = $perusahaan->id;

            $atasan = Atasan::firstOrCreate(
                ['email' => 'atasan.mandiri.'.$nim.'@mandiri.co.id'],
                [
                    'nama' => 'Hartono Kusumo, S.E., MBA.',
                    'telepon' => '081223344001',
                ]
            );
            $atasanId = $atasan->id;
        } elseif ($kategoriIdx === 1) {
            // Kategori 2: Wiraswasta / Founder
            $kategoriPekerjaan = 'Wiraswasta';
            $posisiWiraswasta = 'Founder / Owner';
            $posisiJabatan = 'Founder & Chief Executive Officer';
            $gaji = 15000000;

            $perusahaan = Perusahaan::firstOrCreate(
                ['nama_perusahaan' => 'CV '.explode(' ', $nama)[0].' Inovasi Mandiri'],
                [
                    'propinsi_id' => $propinsiDiy?->id,
                    'kabupaten_id' => $kabSleman?->id,
                    'alamat' => 'Jl. Kaliurang Km. 5, Pogung Kidul',
                    'sektor' => 'Industri Kreatif & Digital Agency',
                    'skala' => 'Nasional',
                    'jenis_perusahaan' => 'Wiraswasta',
                    'jenis_lokasi' => 'Dalam Negeri',
                    'negara' => 'Indonesia',
                    'status_verifikasi' => 'Terverifikasi',
                ]
            );
            $perusahaanId = $perusahaan->id;

            $atasan = Atasan::firstOrCreate(
                ['email' => 'partner.'.$nim.'@inovasimandiri.id'],
                [
                    'nama' => 'Rendra Pratama, S.Kom. (Co-Founder)',
                    'telepon' => '081223344002',
                ]
            );
            $atasanId = $atasan->id;
        } elseif ($kategoriIdx === 2) {
            // Kategori 3: Melanjutkan Pendidikan S2
            $kategoriPekerjaan = 'Melanjutkan Pendidikan';
            $pendidikanTingkat = 'S2';
            $perguruanTinggi = 'Universitas Gadjah Mada';
            $pendidikanProdi = 'Magister '.$prodi->nama_prodi;
            $posisiJabatan = 'Mahasiswa Pascasarjana (Awardee Beasiswa LPDP)';
            $gaji = 5000000; // Tunjangan hidup bulanan beasiswa

            $perusahaan = Perusahaan::firstOrCreate(
                ['nama_perusahaan' => 'Sekolah Pascasarjana Universitas Gadjah Mada'],
                [
                    'propinsi_id' => $propinsiDiy?->id,
                    'kabupaten_id' => $kabSleman?->id,
                    'alamat' => 'Jl. Teknika Utara, Pogung',
                    'sektor' => 'Pendidikan Tinggi & Riset',
                    'skala' => 'Internasional',
                    'jenis_perusahaan' => 'Instansi pemerintah',
                    'jenis_lokasi' => 'Dalam Negeri',
                    'negara' => 'Indonesia',
                    'status_verifikasi' => 'Terverifikasi',
                ]
            );
            $perusahaanId = $perusahaan->id;

            $atasan = Atasan::firstOrCreate(
                ['email' => 'kaprodi.s2.'.$nim.'@mail.ugm.ac.id'],
                [
                    'nama' => 'Prof. Dr. Ir. Wahyu Wibowo, M.Sc. (Dosen Pembimbing S2)',
                    'telepon' => '081223344003',
                ]
            );
            $atasanId = $atasan->id;
        } else {
            // Kategori 4: Bekerja di Startup / Tech Multinasional (F504 = Tidak)
            $kategoriPekerjaan = 'Bekerja (Full Time)';
            $posisiJabatan = ($prodi->kode_prodi === '71' || $prodi->kode_prodi === '72') ? 'Product Data Analyst' : 'Business Development Specialist';
            $gaji = 11000000;

            $perusahaan = Perusahaan::firstOrCreate(
                ['nama_perusahaan' => 'PT GoTo Gojek Tokopedia Tbk'],
                [
                    'propinsi_id' => $propinsiJkt?->id,
                    'kabupaten_id' => $kabJaksel?->id,
                    'alamat' => 'Pasaraya Blok M Gedung B Lt. 6, Kebayoran Baru',
                    'sektor' => 'Teknologi Informasi & E-Commerce',
                    'skala' => 'Internasional',
                    'jenis_perusahaan' => 'Perusahaan swasta',
                    'jenis_lokasi' => 'Dalam Negeri',
                    'negara' => 'Indonesia',
                    'status_verifikasi' => 'Terverifikasi',
                ]
            );
            $perusahaanId = $perusahaan->id;

            $atasan = Atasan::firstOrCreate(
                ['email' => 'lead.goto.'.$nim.'@gotocompany.com'],
                [
                    'nama' => 'Maya Indrawati, S.T., M.Kom. (Engineering Manager)',
                    'telepon' => '081223344004',
                ]
            );
            $atasanId = $atasan->id;
        }

        // Simpan Record Biodata Lengkap (100% Profil)
        $biodata = Biodata::updateOrCreate(
            ['user_id' => $user->id],
            [
                'nim' => $nim,
                'orang_tua_id' => $dataOrangTua->id,
                'yudisium_id' => $yudisium->id,
                'prodi_id' => $prodi->id,
                'tahun_lulus' => (string) $cfg['tahun'],
                'nama' => $nama,
                'email' => $user->email,
                'email_pribadi' => $dataAkademik->email_pribadi,
                'nomor_telepon' => $dataAkademik->nomor_telepon,
                'tempat_lahir' => $dataAkademik->tempat_lahir,
                'tanggal_lahir' => $dataAkademik->tanggal_lahir,
                'jenis_kelamin' => $dataAkademik->jenis_kelamin,
                'golongan_darah' => $dataAkademik->golongan_darah,
                'warga_negara' => $dataAkademik->warga_negara,
                'agama' => $dataAkademik->agama,
                'nik' => $dataAkademik->nik,
                'no_kk' => $dataAkademik->no_kk,
                'nisn' => $dataAkademik->nisn,
                'no_bpjs' => $dataAkademik->no_bpjs,
                'npwp' => '09.'.rand(100, 999).'.'.rand(100, 999).'.'.rand(1, 9).'-541.000',
                'alamat' => $dataAkademik->alamat_saat_ini,
                'kelurahan' => $dataAkademik->kelurahan,
                'kecamatan' => $dataAkademik->kecamatan,
                'kabupaten_id' => $dataAkademik->kabupaten_id,
                'propinsi_id' => $dataAkademik->propinsi_id,
                'kode_pos' => $dataAkademik->kode_pos,
                'instagram_url' => 'https://instagram.com/'.$cleanUsername,
                'facebook_url' => 'https://facebook.com/'.$cleanUsername,
                'linkedin_url' => 'https://linkedin.com/in/'.$cleanUsername,
                'linkedin_username' => $cleanUsername,
                'expert' => 'Professional Expertise in '.$prodi->nama_prodi,
                'minat' => 'Innovation, Research, and Community Development',
                'perusahaan_id' => $perusahaanId,
                'atasan_id' => $atasanId,
                'kategori_pekerjaan' => $kategoriPekerjaan,
                'posisi_jabatan' => $posisiJabatan,
                'posisi_wiraswasta' => $posisiWiraswasta,
                'pendidikan_tingkat' => $pendidikanTingkat,
                'perguruan_tinggi' => $perguruanTinggi,
                'pendidikan_prodi' => $pendidikanProdi,
                'gaji' => $gaji,
                'jenis_pekerjaan' => ($prodi->kode_prodi === '31' ? 'Gerejawi' : null),
                'zipcode' => '55281',
            ]
        );

        // Jangan reset kuesioner Joshua Andrean jika sudah memiliki jawaban
        if ($nim === '72220001' && Tracer::where('biodata_id', $biodata->id)->count() > 0) {
            return;
        }

        // Isi Jawaban Kuesioner Universitas Lengkap (100% Wajib Terpenuhi)
        $this->seedTracerAnswersLengkap($biodata, $kategoriIdx, $cfg['tahun'], $univQuestions);

        // Isi Jawaban Kuesioner Khusus Prodi (Jika Prodi Memiliki Instrumen Soal)
        $this->seedProdiAnswersLengkap($biodata, $prodiQuestions);
    }

    /**
     * Mengisi Butir Kuesioner Universitas 100% Sesuai Kategori
     */
    protected function seedTracerAnswersLengkap(Biodata $biodata, int $kategoriIdx, int $tahunLulus, $univQuestions): void
    {
        // Bersihkan jawaban tracer sebelumnya untuk alumni ini
        Tracer::where('biodata_id', $biodata->id)->delete();

        $answers = [];

        // 1. Status Pekerjaan F8 (Standar Dikti)
        if ($kategoriIdx === 0) {
            $answers['F8'] = 'Bekerja (full time/part time)';
        } elseif ($kategoriIdx === 1) {
            $answers['F8'] = 'Wiraswasta';
        } elseif ($kategoriIdx === 2) {
            $answers['F8'] = 'Melanjutkan Pendidikan';
        } else {
            $answers['F8'] = 'Bekerja (full time/part time)';
        }

        // 2. Pembiayaan Kuliah (F12)
        $answers['F12'] = ($kategoriIdx === 2) ? 'Beasiswa' : 'Biaya Sendiri / Keluarga';

        // 3. Butir Kompetensi Wajib (F17a1 s/d F17b7) - Nilai skala 1 s/d 5
        $kompetensiCodes = [
            'F17a1', 'F17b1', 'F17a2', 'F17b2', 'F17a3', 'F17b3',
            'F17a4', 'F17b4', 'F17a5', 'F17b5', 'F17a6', 'F17b6',
            'F17a7', 'F17b7',
        ];
        foreach ($kompetensiCodes as $c) {
            $answers[$c] = '4'; // 4 = Setuju / Tinggi
        }

        // 4. Jalur Non-Pendidikan (Bekerja / Wiraswasta: Kategori 0, 1, 3)
        if ($kategoriIdx !== 2) {
            $answers['F14'] = 'Sangat Erat';
            $answers['F15'] = 'Tingkat yang Sama';

            if ($kategoriIdx === 0 || $kategoriIdx === 1) {
                // Mendapatkan kerja sebelum lulus
                $answers['F504'] = 'Ya';
                $answers['F502'] = '3'; // 3 bulan sebelum lulus
                $answers['F3'] = 'Sebelum lulus ... bulan: 4';
            } else {
                // Mendapatkan kerja setelah lulus (Kategori 3 / Index 3)
                $answers['F504'] = 'Tidak';
                $answers['F506'] = '2'; // 2 bulan setelah lulus
                $answers['F3'] = 'Setelah lulus ... bulan: 2';
            }

            $answers['F4'] = 'Mencari lewat internet/iklan online/milis';
            $answers['F6'] = '4';
            $answers['F7'] = '3';
            $answers['F7A'] = '2';
            $answers['F505'] = '8500000';
        } else {
            // Jalur Melanjutkan Pendidikan (Kategori 2 / Index 2)
            $answers['F18a'] = 'Beasiswa Lembaga Pengelola Dana Pendidikan (LPDP)';
            $answers['F18b'] = 'Universitas Gadjah Mada';
            $answers['F18c'] = 'Program Magister Pascasarjana';
            $answers['F18d'] = '2025-09-01';
        }

        // Simpan seluruh record ke tabel tracer
        foreach ($answers as $kode => $ans) {
            $q = $univQuestions->get($kode);
            if ($q) {
                Tracer::create([
                    'biodata_id' => $biodata->id,
                    'question_id' => $q->id,
                    'nim' => $biodata->nim,
                    'kelompok' => $q->kelompok,
                    'kode_pertanyaan' => $q->kode_pertanyaan,
                    'subpertanyaan' => $q->subpertanyaan,
                    'answer' => $ans,
                    'answer_json' => null,
                    'keterangan' => null,
                    'tahun_lulus' => (string) $tahunLulus,
                ]);
            }
        }
    }

    /**
     * Mengisi Kuesioner Khusus Prodi Lengkap 100%
     */
    protected function seedProdiAnswersLengkap(Biodata $biodata, $prodiQuestions): void
    {
        if ($prodiQuestions->isEmpty()) {
            return;
        }

        ProdiResponse::where('biodata_id', $biodata->id)->delete();

        foreach ($prodiQuestions as $pq) {
            $ans = '4'; // Default skala likert 4 = Setuju
            if ($pq->type === 'text' || $pq->type === 'textarea') {
                $ans = 'Program studi memberikan bekal keahlian yang relevan dan aplikatif di dunia profesional.';
            } elseif ($pq->type === 'radio' || $pq->type === 'dropdown') {
                $ans = 'Sangat Setuju';
            }

            ProdiResponse::create([
                'biodata_id' => $biodata->id,
                'prodi_question_id' => $pq->id,
                'answer_text' => $ans,
                'answer_json' => null,
            ]);
        }
    }

    /**
     * Seeder untuk Alumni Status: Sedang Mengisi (Progress 40% - 75%, Status Belum Selesai)
     */
    protected function seedAlumniSedangMengisi(
        int $idx,
        User $user,
        string $nim,
        string $nama,
        Prodi $prodi,
        DataAkademik $dataAkademik,
        DataOrangTua $dataOrangTua,
        Yudisium $yudisium,
        array $cfg,
        $perusahaans,
        $propinsiDiy,
        $kabSleman,
        string $cleanUsername,
        $univQuestions
    ): void {
        // Ambil salah satu perusahaan terdaftar
        $perusahaan = $perusahaans->first();

        $biodata = Biodata::updateOrCreate(
            ['user_id' => $user->id],
            [
                'nim' => $nim,
                'orang_tua_id' => $dataOrangTua->id,
                'yudisium_id' => $yudisium->id,
                'prodi_id' => $prodi->id,
                'tahun_lulus' => (string) $cfg['tahun'],
                'nama' => $nama,
                'email' => $user->email,
                'email_pribadi' => $dataAkademik->email_pribadi,
                'nomor_telepon' => $dataAkademik->nomor_telepon,
                'tempat_lahir' => $dataAkademik->tempat_lahir,
                'tanggal_lahir' => $dataAkademik->tanggal_lahir,
                'jenis_kelamin' => $dataAkademik->jenis_kelamin,
                'golongan_darah' => $dataAkademik->golongan_darah,
                'warga_negara' => 'WNI',
                'agama' => $dataAkademik->agama,
                'nik' => $dataAkademik->nik,
                'no_kk' => $dataAkademik->no_kk,
                'nisn' => $dataAkademik->nisn,
                'no_bpjs' => $dataAkademik->no_bpjs,
                'npwp' => '09.'.rand(100, 999).'.'.rand(100, 999).'.'.rand(1, 9).'-541.000',
                'alamat' => $dataAkademik->alamat_saat_ini,
                'kelurahan' => $dataAkademik->kelurahan,
                'kecamatan' => $dataAkademik->kecamatan,
                'kabupaten_id' => $kabSleman?->id,
                'propinsi_id' => $propinsiDiy?->id,
                'kode_pos' => '55281',
                'instagram_url' => 'https://instagram.com/'.$cleanUsername,
                'facebook_url' => null,
                'linkedin_url' => 'https://linkedin.com/in/'.$cleanUsername,
                'linkedin_username' => $cleanUsername,
                'expert' => 'Bidang Peminatan '.$prodi->nama_prodi,
                'minat' => 'Karier Profesional',
                'perusahaan_id' => $perusahaan?->id,
                'atasan_id' => null,
                'kategori_pekerjaan' => 'Bekerja (Full Time)',
                'posisi_jabatan' => 'Junior Associate',
                'gaji' => 6500000,
            ]
        );

        Tracer::where('biodata_id', $biodata->id)->delete();
        ProdiResponse::where('biodata_id', $biodata->id)->delete();

        // Hanya jawab sebagian kuesioner wajib (misal 8 dari 20 butir = 40% - 50%)
        $partialAnswers = [
            'F8' => 'Bekerja (full time/part time)',
            'F504' => 'Ya',
            'F502' => '2',
            'F12' => 'Biaya Sendiri / Keluarga',
            'F14' => 'Erat',
            'F17a1' => '4',
            'F17b1' => '4',
            'F17a2' => '4',
        ];

        foreach ($partialAnswers as $kode => $ans) {
            $q = $univQuestions->get($kode);
            if ($q) {
                Tracer::create([
                    'biodata_id' => $biodata->id,
                    'question_id' => $q->id,
                    'nim' => $biodata->nim,
                    'kelompok' => $q->kelompok,
                    'kode_pertanyaan' => $q->kode_pertanyaan,
                    'subpertanyaan' => $q->subpertanyaan,
                    'answer' => $ans,
                    'answer_json' => null,
                    'keterangan' => null,
                    'tahun_lulus' => (string) $cfg['tahun'],
                ]);
            }
        }
    }

    /**
     * Seeder untuk Alumni Status: Belum Mengisi (Hanya memiliki data induk akademik & yudisium)
     */
    protected function seedAlumniBelumMengisi(
        User $user,
        string $nim,
        string $nama,
        Prodi $prodi,
        DataAkademik $dataAkademik,
        DataOrangTua $dataOrangTua,
        Yudisium $yudisium,
        array $cfg
    ): void {
        // Record Biodata dasar (tanpa data mandiri / NPWP / karier)
        $biodata = Biodata::updateOrCreate(
            ['user_id' => $user->id],
            [
                'nim' => $nim,
                'orang_tua_id' => $dataOrangTua->id,
                'yudisium_id' => $yudisium->id,
                'prodi_id' => $prodi->id,
                'tahun_lulus' => (string) $cfg['tahun'],
                'nama' => $nama,
                'email' => $user->email,
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
                'npwp' => null, // NPWP NULL menandakan profil mandiri belum lengkap
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

        // Pastikan tidak ada jawaban kuesioner tersimpan (0% Progress)
        Tracer::where('biodata_id', $biodata->id)->delete();
        ProdiResponse::where('biodata_id', $biodata->id)->delete();
    }
}
