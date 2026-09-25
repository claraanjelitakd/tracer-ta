<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    /**
     * Run the migrations.
     *
     * Memperbarui Database View `v_alumni_tracer_univ_status` dan `v_alumni_audit_rekap`
     * agar memperhitungkan alur percabangan kuesioner universitas (Branching / Skip Logic)
     * secara akurat:
     * 1. Butir F502 dan F506 adalah pasangan alternatif saling menggantikan (mutually exclusive)
     *    tergantung jawaban F504 (<= 6 bulan vs > 6 bulan). Pasangan ini hanya bernilai 1 butir wajib.
     * 2. Alumni yang memilih status F8 'Melanjutkan Pendidikan' (4) atau 'Belum Memungkinkan Bekerja' (2)
     *    tidak dibebani pertanyaan pekerjaan (total soal wajib: 16 butir).
     * 3. Alumni yang bekerja/wiraswasta memiliki total soal wajib 20 butir.
     */
    public function up(): void
    {
        DB::statement('DROP VIEW IF EXISTS v_alumni_audit_rekap');
        DB::statement('DROP VIEW IF EXISTS v_alumni_tracer_univ_status');

        // 1. Rekonstruksi View: v_alumni_tracer_univ_status
        DB::statement("
            CREATE VIEW v_alumni_tracer_univ_status AS
            SELECT 
                b.id AS biodata_id,
                -- 1. Evaluasi Total Soal Wajib Berdasarkan Jalur Status F8
                CASE 
                    WHEN MAX(CASE WHEN q.kode_pertanyaan = 'F8' THEN LOWER(t.answer) ELSE '' END) LIKE '%melanjutkan pendidikan%' 
                         OR MAX(CASE WHEN q.kode_pertanyaan = 'F8' THEN t.answer ELSE '' END) = '4'
                         OR MAX(CASE WHEN q.kode_pertanyaan = 'F8' THEN LOWER(t.answer) ELSE '' END) LIKE '%belum memungkinkan%'
                         OR MAX(CASE WHEN q.kode_pertanyaan = 'F8' THEN t.answer ELSE '' END) = '2'
                    THEN 16
                    ELSE 20
                END AS total_mandatory_univ,

                -- 2. Evaluasi Jawaban Soal Wajib (F502 dan F506 saling melengkapi, maks 1 poin)
                (
                    COUNT(DISTINCT CASE 
                        WHEN q.wajib = 1 
                             AND q.type != 'header' 
                             AND UPPER(q.kode_pertanyaan) NOT IN (
                                 'F1', 'F2A', 'F2B', 'F2C', 'F2D',
                                 'BIO_TEMPAT_LAHIR', 'BIO_TANGGAL_LAHIR', 'BIO_JK', 'BIO_TGL_LULUS', 'BIO_JUDUL_TA', 'BIO_NIK', 'BIO_NPWP',
                                 'F5A1', 'F5A2', 'F510', 'F5B', 'F5C', 'F5D',
                                 'F2E', 'F2E1', 'F2E2', 'F2E3', 'F2F', 'F2G', 'F2H',
                                 'F11', 'F505', 'F502', 'F506'
                             )
                             AND (
                                 (t.answer IS NOT NULL AND TRIM(t.answer) != '')
                                 OR (t.answer_json IS NOT NULL AND t.answer_json != '' AND t.answer_json != '[]' AND t.answer_json != '{}')
                             )
                        THEN q.id 
                        ELSE NULL 
                    END)
                    +
                    MAX(CASE 
                        WHEN UPPER(q.kode_pertanyaan) IN ('F502', 'F506') 
                             AND (
                                 (t.answer IS NOT NULL AND TRIM(t.answer) != '')
                                 OR (t.answer_json IS NOT NULL AND t.answer_json != '' AND t.answer_json != '[]' AND t.answer_json != '{}')
                             )
                        THEN 1 
                        ELSE 0 
                    END)
                ) AS answered_mandatory_univ,

                -- 3. Total Keseluruhan Soal yang Dijawab
                COUNT(DISTINCT CASE 
                    WHEN (
                        (t.answer IS NOT NULL AND TRIM(t.answer) != '')
                        OR (t.answer_json IS NOT NULL AND t.answer_json != '' AND t.answer_json != '[]' AND t.answer_json != '{}')
                    )
                    THEN t.question_id 
                    ELSE NULL 
                END) AS total_answered_univ
            FROM biodata b
            LEFT JOIN tracer t ON t.biodata_id = b.id
            LEFT JOIN ref_subpertanyaan2021 q ON t.question_id = q.id
            GROUP BY b.id
        ");

        // 2. Rekonstruksi View: v_alumni_audit_rekap
        DB::statement("
            CREATE VIEW v_alumni_audit_rekap AS
            SELECT 
                ps.*,
                COALESCE(tus.total_mandatory_univ, 0) AS total_mandatory_univ,
                COALESCE(tus.answered_mandatory_univ, 0) AS answered_mandatory_univ,
                COALESCE(tus.total_answered_univ, 0) AS total_answered_univ,
                CASE 
                    WHEN COALESCE(tus.total_mandatory_univ, 0) > 0 
                    THEN CASE 
                        WHEN ROUND((COALESCE(tus.answered_mandatory_univ, 0) * 100.0) / tus.total_mandatory_univ) > 100 THEN 100 
                        ELSE ROUND((COALESCE(tus.answered_mandatory_univ, 0) * 100.0) / tus.total_mandatory_univ) 
                    END
                    ELSE 100 
                END AS univ_percentage,
                CASE 
                    WHEN COALESCE(tus.total_mandatory_univ, 0) = 0 OR COALESCE(tus.answered_mandatory_univ, 0) >= tus.total_mandatory_univ 
                    THEN 1 
                    ELSE 0 
                END AS is_complete_univ,
                COALESCE(tps.total_prodi_questions, 0) AS total_prodi_questions,
                COALESCE(tps.answered_prodi_questions, 0) AS answered_prodi_questions,
                CASE 
                    WHEN COALESCE(tps.total_prodi_questions, 0) > 0 
                    THEN CASE 
                        WHEN ROUND((COALESCE(tps.answered_prodi_questions, 0) * 100.0) / tps.total_prodi_questions) > 100 THEN 100 
                        ELSE ROUND((COALESCE(tps.answered_prodi_questions, 0) * 100.0) / tps.total_prodi_questions) 
                    END
                    ELSE 100 
                END AS prodi_percentage,
                CASE 
                    WHEN COALESCE(tps.total_prodi_questions, 0) = 0 OR COALESCE(tps.answered_prodi_questions, 0) >= tps.total_prodi_questions 
                    THEN 1 
                    ELSE 0 
                END AS is_complete_prodi,
                CASE 
                    WHEN ps.is_profile_complete = 1 
                         AND (COALESCE(tus.total_mandatory_univ, 0) = 0 OR COALESCE(tus.answered_mandatory_univ, 0) >= tus.total_mandatory_univ)
                         AND (COALESCE(tps.total_prodi_questions, 0) = 0 OR COALESCE(tps.answered_prodi_questions, 0) >= tps.total_prodi_questions)
                    THEN 1 
                    ELSE 0 
                END AS is_complete_total,
                CASE 
                    WHEN ps.is_profile_complete = 1 
                         AND (COALESCE(tus.total_mandatory_univ, 0) = 0 OR COALESCE(tus.answered_mandatory_univ, 0) >= tus.total_mandatory_univ)
                         AND (COALESCE(tps.total_prodi_questions, 0) = 0 OR COALESCE(tps.answered_prodi_questions, 0) >= tps.total_prodi_questions)
                    THEN 'Selesai' 
                    ELSE 'Belum Selesai' 
                END AS status_tracer_label
            FROM v_alumni_profile_summary ps
            LEFT JOIN v_alumni_tracer_univ_status tus ON ps.biodata_id = tus.biodata_id
            LEFT JOIN v_alumni_tracer_prodi_status tps ON ps.biodata_id = tps.biodata_id
        ");
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        DB::statement('DROP VIEW IF EXISTS v_alumni_audit_rekap');
        DB::statement('DROP VIEW IF EXISTS v_alumni_tracer_univ_status');
    }
};
