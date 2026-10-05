<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

/**
 * Seeder Master Butir Instrumen Evaluasi Kepuasan Pengguna Lulusan (Atasan) UKDW
 *
 * Mengisi master butir pertanyaan evaluasi atasan yang mencakup:
 * 1. Butir Evaluasi Kesiapan Kerja Alumni (Pilihan Ganda: Sangat siap, Cukup Siap, Kurang siap, Tidak siap)
 * 2. 12 Butir Penilaian Aspek Kinerja Lulusan (Skala Likert 5: Sangat Tinggi, Tinggi, Cukup, Kurang, Sangat Kurang)
 */
class PertanyaanEvaluasiAtasanSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $pertanyaans = [
            // =========================================================================
            // 1. TINGKAT KESIAPAN KERJA ALUMNI (EVALUASI KESIAPAN UMUM)
            // =========================================================================
            [
                'kode' => 'tingkat_kesiapan_kerja',
                'aspek' => 'Tingkat Kesiapan Alumni dalam Bekerja',
                'deskripsi' => 'Tingkat kesiapan alumni UKDW dalam bekerja dan beradaptasi di Perusahaan / Institusi ini.',
                'kategori' => 'Kesiapan Kerja',
                'tipe' => 'pilihan_ganda',
                'pilihan_jawaban' => json_encode(['Sangat siap', 'Cukup Siap', 'Kurang siap', 'Tidak siap']),
                'order' => 1,
            ],

            // =========================================================================
            // 2. 12 BUTIR ASPEK PENILAIAN KINERJA LULUSAN (STANDAR KEMENDIKBUD / DIKTI)
            // =========================================================================
            [
                'kode' => 'integritas',
                'aspek' => 'Integritas (Etika dan Moral)',
                'deskripsi' => 'Kejujuran, etika profesi, kedisiplinan, dan tanggung jawab kerja.',
                'kategori' => 'Kinerja',
                'tipe' => 'likert_5',
                'pilihan_jawaban' => null,
                'order' => 2,
            ],
            [
                'kode' => 'keahlian_ilmu',
                'aspek' => 'Keahlian Berdasarkan Bidang Ilmu (Profesionalisme)',
                'deskripsi' => 'Kemampuan penguasaan teori dan keilmuan yang dipelajari selama kuliah.',
                'kategori' => 'Kinerja',
                'tipe' => 'likert_5',
                'pilihan_jawaban' => null,
                'order' => 3,
            ],
            [
                'kode' => 'komunikasi',
                'aspek' => 'Komunikasi',
                'deskripsi' => 'Kemampuan menyampaikan gagasan, koordinasi lisan maupun tulisan.',
                'kategori' => 'Kinerja',
                'tipe' => 'likert_5',
                'pilihan_jawaban' => null,
                'order' => 4,
            ],
            [
                'kode' => 'kerjasama_tim',
                'aspek' => 'Kerjasama Tim',
                'deskripsi' => 'Kemampuan berkolaborasi dan bekerja sama efektif dalam kelompok kerja.',
                'kategori' => 'Kinerja',
                'tipe' => 'likert_5',
                'pilihan_jawaban' => null,
                'order' => 5,
            ],
            [
                'kode' => 'pengembangan_diri',
                'aspek' => 'Pengembangan Diri',
                'deskripsi' => 'Kemauan belajar hal baru, adaptif, dan terus meningkatkan keterampilan.',
                'kategori' => 'Kinerja',
                'tipe' => 'likert_5',
                'pilihan_jawaban' => null,
                'order' => 6,
            ],
            [
                'kode' => 'kreativitas',
                'aspek' => 'Kreativitas',
                'deskripsi' => 'Kemampuan memberikan gagasan segar dan ide solutif.',
                'kategori' => 'Kinerja',
                'tipe' => 'likert_5',
                'pilihan_jawaban' => null,
                'order' => 7,
            ],
            [
                'kode' => 'bahasa_asing',
                'aspek' => 'Kemampuan Bahasa Asing (Misal: Bahasa Inggris)',
                'deskripsi' => 'Kemampuan berbahasa internasional dalam konteks pekerjaan.',
                'kategori' => 'Kinerja',
                'tipe' => 'likert_5',
                'pilihan_jawaban' => null,
                'order' => 8,
            ],
            [
                'kode' => 'penggunaan_teknologi',
                'aspek' => 'Penggunaan Alat / Teknologi Modern (Teknologi IT)',
                'deskripsi' => 'Kecakapan memanfaatkan perangkat lunak dan teknologi digital penunjang.',
                'kategori' => 'Kinerja',
                'tipe' => 'likert_5',
                'pilihan_jawaban' => null,
                'order' => 9,
            ],
            [
                'kode' => 'kemampuan_manajerial',
                'aspek' => 'Kemampuan Manajerial',
                'deskripsi' => 'Kemampuan merencanakan, mengorganisasi, dan memimpin tugas.',
                'kategori' => 'Kinerja',
                'tipe' => 'likert_5',
                'pilihan_jawaban' => null,
                'order' => 10,
            ],
            [
                'kode' => 'kemampuan_analisis',
                'aspek' => 'Kemampuan Melakukan Analisis',
                'deskripsi' => 'Kemampuan menganalisis masalah secara kritis dan berbasis data.',
                'kategori' => 'Kinerja',
                'tipe' => 'likert_5',
                'pilihan_jawaban' => null,
                'order' => 11,
            ],
            [
                'kode' => 'menulis_laporan',
                'aspek' => 'Menulis Laporan',
                'deskripsi' => 'Kemampuan menyusun dokumentasi kerja dan laporan resmi yang rapi.',
                'kategori' => 'Kinerja',
                'tipe' => 'likert_5',
                'pilihan_jawaban' => null,
                'order' => 12,
            ],
            [
                'kode' => 'inovasi_kreativitas',
                'aspek' => 'Inovasi / Kreativitas',
                'deskripsi' => 'Inisiatif menciptakan terobosan atau perbaikan proses kerja baru.',
                'kategori' => 'Kinerja',
                'tipe' => 'likert_5',
                'pilihan_jawaban' => null,
                'order' => 13,
            ],
        ];

        foreach ($pertanyaans as $p) {
            DB::table('pertanyaan_evaluasi_atasan')->updateOrInsert(
                ['kode' => $p['kode']],
                [
                    'aspek' => $p['aspek'],
                    'deskripsi' => $p['deskripsi'],
                    'kategori' => $p['kategori'],
                    'tipe' => $p['tipe'],
                    'pilihan_jawaban' => $p['pilihan_jawaban'],
                    'is_active' => true,
                    'order' => $p['order'],
                    'created_at' => now(),
                    'updated_at' => now(),
                ]
            );
        }
    }
}
