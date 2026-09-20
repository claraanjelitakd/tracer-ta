<?php

namespace Database\Seeders;

use App\Models\KelompokPertanyaan;
use App\Models\Kuesioner;
use Illuminate\Database\Seeder;

class KelompokPertanyaanSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $tracerStudy2021 = Kuesioner::where('title', 'Tracer Study UKDW 2021')->first();
        $qId = $tracerStudy2021 ? $tracerStudy2021->id : 1;

        $sections = [
            ['kode_kelompok' => '1', 'kuesioner_id' => $qId, 'title' => 'Identitas & Biodata Mahasiswa', 'order' => 1],
            ['kode_kelompok' => '2', 'kuesioner_id' => $qId, 'title' => 'Status Pekerjaan & Aktivitas Saat Ini', 'order' => 2],
            ['kode_kelompok' => '3', 'kuesioner_id' => $qId, 'title' => 'Waktu Mulai & Cara Mencari Pekerjaan', 'order' => 3],
            ['kode_kelompok' => '4', 'kuesioner_id' => $qId, 'title' => 'Mendapatkan Pekerjaan & Data Pekerjaan', 'order' => 4],
            ['kode_kelompok' => '5', 'kuesioner_id' => $qId, 'title' => 'Riwayat Lamaran Pekerjaan', 'order' => 5],
            ['kode_kelompok' => '6', 'kuesioner_id' => $qId, 'title' => 'Pembiayaan Kuliah', 'order' => 6],
            ['kode_kelompok' => '7', 'kuesioner_id' => $qId, 'title' => 'Keselarasan & Relevansi Pekerjaan', 'order' => 7],
            ['kode_kelompok' => '8', 'kuesioner_id' => $qId, 'title' => 'Evaluasi Kompetensi Lulusan', 'order' => 8],
            ['kode_kelompok' => '9', 'kuesioner_id' => $qId, 'title' => 'Penekanan Metode Pembelajaran', 'order' => 9],
        ];

        // Hapus seksi lama jika ada yang berlebih
        KelompokPertanyaan::where('kuesioner_id', $qId)
            ->whereNotIn('kode_kelompok', array_column($sections, 'kode_kelompok'))
            ->delete();

        foreach ($sections as $section) {
            KelompokPertanyaan::updateOrCreate(
                ['kode_kelompok' => $section['kode_kelompok'], 'kuesioner_id' => $qId],
                $section
            );
        }
    }
}
