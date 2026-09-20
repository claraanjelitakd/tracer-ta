<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        $driver = DB::getDriverName();
        $castText = $driver === 'sqlite' ? 'TEXT' : 'CHAR';
        $orderCol = $driver === 'sqlite' ? '"order"' : '`order`';

        // 1. Drop existing views jika ada
        DB::statement('DROP VIEW IF EXISTS v_alumni_audit_rekap');
        DB::statement('DROP VIEW IF EXISTS v_alumni_tracer_export');
        DB::statement('DROP VIEW IF EXISTS v_alumni_tracer_prodi_status');
        DB::statement('DROP VIEW IF EXISTS v_alumni_tracer_univ_status');
        DB::statement('DROP VIEW IF EXISTS v_alumni_profile_summary');

        // 2. VIEW 1: v_alumni_profile_summary
        // Menggabungkan seluruh data profil alumni (biodata, akademik, yudisium, prodi, fakultas, perusahaan, orang tua)
        DB::statement("
            CREATE VIEW v_alumni_profile_summary AS
            SELECT 
                b.id AS biodata_id,
                b.user_id,
                b.nim,
                COALESCE(da.nama, b.nama, u.name, 'Mahasiswa UKDW') AS nama,
                COALESCE(b.nik, da.nik) AS nik,
                b.npwp,
                COALESCE(b.email_pribadi, da.email_pribadi, u.email) AS email,
                COALESCE(b.nomor_telepon, da.nomor_telepon) AS nomor_telepon,
                COALESCE(b.alamat, da.alamat_saat_ini) AS alamat,
                COALESCE(b.tempat_lahir, da.tempat_lahir) AS tempat_lahir,
                COALESCE(b.tanggal_lahir, da.tanggal_lahir) AS tanggal_lahir,
                COALESCE(b.jenis_kelamin, da.jenis_kelamin) AS jenis_kelamin,
                COALESCE(b.agama, da.agama) AS agama,
                b.kategori_pekerjaan,
                b.posisi_jabatan,
                b.gaji,
                b.prodi_id,
                p.kode_prodi,
                p.nama_prodi,
                p.fakultas_id,
                f.kode_fakultas,
                f.nama_fakultas,
                f.singkatan AS singkatan_fakultas,
                da.ip_kumulatif AS ipk,
                da.tahun_akademik_lulus,
                da.tahun_lulus,
                da.angkatan_masuk,
                da.total_sks,
                COALESCE(y.proses_yudisium, 'Lulus') AS status_yudisium,
                y.judul_ta,
                y.keterangan_hasil_yudisium,
                b.perusahaan_id,
                c.nama_perusahaan,
                c.alamat AS alamat_perusahaan,
                a.nama AS nama_atasan,
                a.email AS email_atasan,
                a.telepon AS telepon_atasan,
                ot.nama_orang_tua,
                ot.nomor_telepon AS telepon_orang_tua,
                CASE 
                    WHEN COALESCE(da.nama, b.nama) IS NOT NULL 
                         AND b.nim IS NOT NULL 
                         AND COALESCE(b.nik, da.nik) IS NOT NULL
                         AND b.npwp IS NOT NULL
                         AND COALESCE(b.nomor_telepon, da.nomor_telepon) IS NOT NULL
                         AND COALESCE(b.email_pribadi, da.email_pribadi, u.email) IS NOT NULL
                    THEN 1 
                    ELSE 0 
                END AS is_profile_complete
            FROM biodata b
            LEFT JOIN users u ON b.user_id = u.id
            LEFT JOIN data_akademik da ON da.nim = b.nim
            LEFT JOIN yudisium y ON y.nim = b.nim
            LEFT JOIN prodi p ON b.prodi_id = p.id
            LEFT JOIN ref_fakultas f ON p.fakultas_id = f.id
            LEFT JOIN perusahaan c ON b.perusahaan_id = c.id
            LEFT JOIN atasan a ON b.atasan_id = a.id
            LEFT JOIN data_orang_tua ot ON ot.nim = b.nim
        ");

        // 3. VIEW 2: v_alumni_tracer_univ_status
        // Mengagregasi status pengisian kuesioner universitas per alumni
        DB::statement("
            CREATE VIEW v_alumni_tracer_univ_status AS
            SELECT 
                b.id AS biodata_id,
                (
                    SELECT COUNT(*) 
                    FROM ref_subpertanyaan2021 q 
                    WHERE q.wajib = 1 
                      AND q.type != 'header'
                      AND UPPER(q.kode_pertanyaan) NOT IN (
                          'F1', 'F2A', 'F2B', 'F2C', 'F2D',
                          'BIO_TEMPAT_LAHIR', 'BIO_TANGGAL_LAHIR', 'BIO_JK', 'BIO_TGL_LULUS', 'BIO_JUDUL_TA', 'BIO_NIK', 'BIO_NPWP',
                          'F5A1', 'F5A2', 'F510', 'F5B', 'F5C', 'F5D',
                          'F2E', 'F2E1', 'F2E2', 'F2E3', 'F2F', 'F2G', 'F2H',
                          'F11', 'F505'
                      )
                ) AS total_mandatory_univ,
                COUNT(DISTINCT CASE 
                    WHEN q.wajib = 1 
                         AND q.type != 'header' 
                         AND UPPER(q.kode_pertanyaan) NOT IN (
                             'F1', 'F2A', 'F2B', 'F2C', 'F2D',
                             'BIO_TEMPAT_LAHIR', 'BIO_TANGGAL_LAHIR', 'BIO_JK', 'BIO_TGL_LULUS', 'BIO_JUDUL_TA', 'BIO_NIK', 'BIO_NPWP',
                             'F5A1', 'F5A2', 'F510', 'F5B', 'F5C', 'F5D',
                             'F2E', 'F2E1', 'F2E2', 'F2E3', 'F2F', 'F2G', 'F2H',
                             'F11', 'F505'
                         )
                         AND (
                             (t.answer IS NOT NULL AND TRIM(t.answer) != '')
                             OR (t.answer_json IS NOT NULL AND t.answer_json != '' AND t.answer_json != '[]' AND t.answer_json != '{}')
                         )
                    THEN q.id 
                    ELSE NULL 
                END) AS answered_mandatory_univ,
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

        // 4. VIEW 3: v_alumni_tracer_prodi_status
        // Mengagregasi status pengisian kuesioner khusus program studi per alumni
        DB::statement("
            CREATE VIEW v_alumni_tracer_prodi_status AS
            SELECT 
                b.id AS biodata_id,
                b.prodi_id,
                COALESCE((
                    SELECT COUNT(*) 
                    FROM prodi_question pq 
                    WHERE pq.prodi_id = b.prodi_id 
                      AND pq.type != 'header'
                ), 0) AS total_prodi_questions,
                COUNT(DISTINCT CASE 
                    WHEN (
                        (pr.answer_text IS NOT NULL AND TRIM(pr.answer_text) != '')
                        OR (pr.answer_json IS NOT NULL AND pr.answer_json != '' AND pr.answer_json != '[]' AND pr.answer_json != '{}')
                    )
                    THEN pr.prodi_question_id 
                    ELSE NULL 
                END) AS answered_prodi_questions
            FROM biodata b
            LEFT JOIN prodi_response pr ON pr.biodata_id = b.id
            GROUP BY b.id, b.prodi_id
        ");

        // 5. VIEW 4: v_alumni_audit_rekap (Master Rekapitulasi Berkecepatan Tinggi)
        // Menggabungkan seluruh view status untuk query instan single-row per alumni
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

        // 6. VIEW 5: v_alumni_tracer_export (Untuk Kebutuhan Ekspor Terstruktur Excel/CSV)
        DB::statement("
            CREATE VIEW v_alumni_tracer_export AS
            SELECT 
                b.id AS biodata_id,
                b.nim,
                COALESCE(da.nama, b.nama, u.name) AS nama,
                p.nama_prodi,
                f.nama_fakultas,
                'Universitas' AS scope,
                sec.title AS nama_section,
                sec.{$orderCol} AS urutan_section,
                q.kode_pertanyaan,
                q.subpertanyaan AS teks_pertanyaan,
                q.type AS tipe_pertanyaan,
                q.wajib AS is_mandatory,
                COALESCE(t.answer, CAST(t.answer_json AS {$castText})) AS jawaban,
                t.updated_at AS waktu_jawab
            FROM biodata b
            LEFT JOIN users u ON b.user_id = u.id
            LEFT JOIN data_akademik da ON da.nim = b.nim
            LEFT JOIN prodi p ON b.prodi_id = p.id
            LEFT JOIN ref_fakultas f ON p.fakultas_id = f.id
            CROSS JOIN ref_subpertanyaan2021 q
            LEFT JOIN kelompok_pertanyaan sec ON q.kelompok_pertanyaan_id = sec.id
            LEFT JOIN tracer t ON t.biodata_id = b.id AND t.question_id = q.id
            WHERE q.type != 'header'
            UNION ALL
            SELECT 
                b.id AS biodata_id,
                b.nim,
                COALESCE(da.nama, b.nama, u.name) AS nama,
                p.nama_prodi,
                f.nama_fakultas,
                'Program Studi' AS scope,
                psec.title AS nama_section,
                psec.{$orderCol} AS urutan_section,
                pq.code AS kode_pertanyaan,
                pq.question_text AS teks_pertanyaan,
                pq.type AS tipe_pertanyaan,
                pq.is_required AS is_mandatory,
                COALESCE(pr.answer_text, CAST(pr.answer_json AS {$castText})) AS jawaban,
                pr.updated_at AS waktu_jawab
            FROM biodata b
            LEFT JOIN users u ON b.user_id = u.id
            LEFT JOIN data_akademik da ON da.nim = b.nim
            LEFT JOIN prodi p ON b.prodi_id = p.id
            LEFT JOIN ref_fakultas f ON p.fakultas_id = f.id
            JOIN prodi_question pq ON pq.prodi_id = b.prodi_id
            LEFT JOIN prodi_question_section psec ON pq.prodi_question_section_id = psec.id
            LEFT JOIN prodi_response pr ON pr.biodata_id = b.id AND pr.prodi_question_id = pq.id
            WHERE pq.type != 'header'
        ");
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        DB::statement('DROP VIEW IF EXISTS v_alumni_tracer_export');
        DB::statement('DROP VIEW IF EXISTS v_alumni_audit_rekap');
        DB::statement('DROP VIEW IF EXISTS v_alumni_tracer_prodi_status');
        DB::statement('DROP VIEW IF EXISTS v_alumni_tracer_univ_status');
        DB::statement('DROP VIEW IF EXISTS v_alumni_profile_summary');
    }
};
