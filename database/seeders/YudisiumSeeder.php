<?php

namespace Database\Seeders;

use App\Models\Biodata;
use App\Models\User;
use App\Models\Yudisium;
use Illuminate\Database\Seeder;

class YudisiumSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $alumniUsers = User::where('role', 'alumni')->get();

        $taIndonesia = [
            'Rancang Bangun Sistem Informasi Tracer Study Berbasis Arsitektur Microservices',
            'Analisis Sentimen Opini Publik Menggunakan Algoritma Bidirectional LSTM',
            'Implementasi Model Machine Learning untuk Prediksi Keterserapan Alumni di Dunia Kerja',
            'Perancangan Antarmuka Pengguna Adaptif untuk Aplikasi Manajemen Karier Lulusan',
            'Evaluasi Kinerja Jaringan Cloud Hybrid Menggunakan Metode Quality of Service',
            'Penerapan Natural Language Processing untuk Ekstraksi Data Dokumen Legal',
            'Desain Sistem Rekomendasi Lowongan Kerja Berdasarkan Profil dan Keahlian Lulusan',
            'Pengembangan Aplikasi Mobile Deteksi Dini Kualitas Air Menggunakan IoT',
            'Analisis Efektivitas Kurikulum Berbasis Outcome-Based Education terhadap Kesiapan Kerja',
            'Optimasi Rute Distribusi Logistik Menggunakan Algoritma Genetika',
        ];

        $taInggris = [
            'Design and Development of Tracer Study Information System Based on Microservices Architecture',
            'Public Sentiment Analysis Using Bidirectional LSTM Deep Learning Algorithm',
            'Machine Learning Model Implementation for Predicting Alumni Employability in Job Market',
            'Adaptive User Interface Design for Graduate Career Management Application',
            'Performance Evaluation of Hybrid Cloud Network Using Quality of Service Method',
            'Natural Language Processing Application for Legal Document Information Extraction',
            'Job Vacancy Recommendation System Design Based on Graduate Skill Profile',
            'Mobile Application Development for Early Water Quality Detection Utilizing IoT',
            'Effectiveness Analysis of Outcome-Based Education Curriculum on Job Readiness',
            'Logistics Distribution Route Optimization Using Genetic Algorithm',
        ];

        $dosenList = [
            'Dr. Budi Susanto, S.Kom., M.T.',
            'Prof. Dr. Ir. Eko Sediyono, M.Kom.',
            'Willy Sudiarto Raharjo, S.Kom., M.Cs.',
            'Gloria Virginia, S.Kom., MAI., Ph.D.',
            'Laurentius Kuncoro Probo Saputro, S.T., M.Eng.',
            'Umi Proboyekti, S.Kom., MLIS.',
            'Drs. Jong Jek Siang, M.Sc.',
        ];

        $predikatList = [
            'Lulus dengan Pujian',
            'Sangat Memuaskan',
            'Lulus dengan Pujian',
            'Sangat Memuaskan',
            'Memuaskan',
        ];

        $periodeLulusList = ['Gasal 2023/2024', 'Genap 2023/2024', 'Gasal 2024/2025', 'Genap 2024/2025'];

        foreach ($alumniUsers as $index => $user) {
            $nim = $user->username;
            $parsedInfo = Biodata::parseNim($nim);
            $angkatan = $parsedInfo ? (int) $parsedInfo['angkatan'] : 2020;

            Yudisium::updateOrCreate(
                ['nim' => $nim],
                [
                    'tahun_akademik_lulus' => $periodeLulusList[$index % count($periodeLulusList)],
                    'tahun_lulus' => $angkatan + 4,
                    'dosen_pembimbing_1' => $dosenList[$index % count($dosenList)],
                    'dosen_pembimbing_2' => $dosenList[($index + 1) % count($dosenList)],
                    'dosen_penguji_1' => $dosenList[($index + 2) % count($dosenList)],
                    'dosen_penguji_2' => $dosenList[($index + 3) % count($dosenList)],

                    'judul_ta' => $taIndonesia[$index % count($taIndonesia)],
                    'judul_ta_inggris' => $taInggris[$index % count($taInggris)],
                    'url_publikasi' => 'https://repository.ukdw.ac.id/handle/123456789/'.$nim,
                    'jenis_publikasi' => ($index % 2 == 0) ? 'Jurnal Nasional' : 'Prosiding Seminar',
                    'status_publikasi' => 'Terbit',
                    'keterangan_hasil_yudisium' => $predikatList[$index % count($predikatList)],
                    'proses_yudisium' => 'Lulus',
                ]
            );
        }
    }
}
