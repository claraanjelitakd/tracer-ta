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
        DB::statement('DROP VIEW IF EXISTS v_question_mappings');
        DB::statement("
            CREATE VIEW v_question_mappings AS
            SELECT 1 AS id, 'data_akademiks' AS table_name, 'nim' AS column_name, q.id AS question_id, q.kode_pertanyaan
            FROM ref_subpertanyaan2021 q WHERE q.kode_pertanyaan = 'F1'
            UNION ALL
            SELECT 2, 'data_akademiks', 'nama', q.id, q.kode_pertanyaan
            FROM ref_subpertanyaan2021 q WHERE q.kode_pertanyaan = 'F2A'
            UNION ALL
            SELECT 3, 'data_akademiks', 'nomor_telepon', q.id, q.kode_pertanyaan
            FROM ref_subpertanyaan2021 q WHERE q.kode_pertanyaan = 'F2B'
            UNION ALL
            SELECT 4, 'data_akademiks', 'email_pribadi', q.id, q.kode_pertanyaan
            FROM ref_subpertanyaan2021 q WHERE q.kode_pertanyaan = 'F2C'
            UNION ALL
            SELECT 5, 'data_akademiks', 'alamat_saat_ini', q.id, q.kode_pertanyaan
            FROM ref_subpertanyaan2021 q WHERE q.kode_pertanyaan = 'F2D'
            UNION ALL
            SELECT 6, 'data_akademiks', 'tempat_lahir', q.id, q.kode_pertanyaan
            FROM ref_subpertanyaan2021 q WHERE q.kode_pertanyaan = 'BIO_TEMPAT_LAHIR'
            UNION ALL
            SELECT 7, 'data_akademiks', 'tanggal_lahir', q.id, q.kode_pertanyaan
            FROM ref_subpertanyaan2021 q WHERE q.kode_pertanyaan = 'BIO_TANGGAL_LAHIR'
            UNION ALL
            SELECT 8, 'data_akademiks', 'nik', q.id, q.kode_pertanyaan
            FROM ref_subpertanyaan2021 q WHERE q.kode_pertanyaan = 'BIO_NIK'
            UNION ALL
            SELECT 9, 'data_akademiks', 'npwp', q.id, q.kode_pertanyaan
            FROM ref_subpertanyaan2021 q WHERE q.kode_pertanyaan = 'BIO_NPWP'
            UNION ALL
            SELECT 10, 'data_akademiks', 'jenis_kelamin', q.id, q.kode_pertanyaan
            FROM ref_subpertanyaan2021 q WHERE q.kode_pertanyaan = 'BIO_JK'
            UNION ALL
            SELECT 11, 'data_akademiks', 'tanggal_kelulusan', q.id, q.kode_pertanyaan
            FROM ref_subpertanyaan2021 q WHERE q.kode_pertanyaan = 'BIO_TGL_LULUS'
            UNION ALL
            SELECT 12, 'data_akademiks', 'judul_skripsi', q.id, q.kode_pertanyaan
            FROM ref_subpertanyaan2021 q WHERE q.kode_pertanyaan = 'BIO_JUDUL_TA'
            UNION ALL
            SELECT 13, 'companies', 'nama_perusahaan', q.id, q.kode_pertanyaan
            FROM ref_subpertanyaan2021 q WHERE q.kode_pertanyaan = 'F2E'
            UNION ALL
            SELECT 14, 'atasans', 'nama', q.id, q.kode_pertanyaan
            FROM ref_subpertanyaan2021 q WHERE q.kode_pertanyaan = 'F2E1'
            UNION ALL
            SELECT 15, 'atasans', 'telepon', q.id, q.kode_pertanyaan
            FROM ref_subpertanyaan2021 q WHERE q.kode_pertanyaan = 'F2E2'
            UNION ALL
            SELECT 16, 'atasans', 'email', q.id, q.kode_pertanyaan
            FROM ref_subpertanyaan2021 q WHERE q.kode_pertanyaan = 'F2E3'
            UNION ALL
            SELECT 17, 'companies', 'alamat', q.id, q.kode_pertanyaan
            FROM ref_subpertanyaan2021 q WHERE q.kode_pertanyaan = 'F2F'
            UNION ALL
            SELECT 18, 'alumnis', 'posisi_jabatan', q.id, q.kode_pertanyaan
            FROM ref_subpertanyaan2021 q WHERE q.kode_pertanyaan = 'F2G'
            UNION ALL
            SELECT 19, 'companies', 'skala', q.id, q.kode_pertanyaan
            FROM ref_subpertanyaan2021 q WHERE q.kode_pertanyaan = 'F2H'
            UNION ALL
            SELECT 20, 'companies', 'nama_perusahaan', q.id, q.kode_pertanyaan
            FROM ref_subpertanyaan2021 q WHERE q.kode_pertanyaan = 'F5B'
            UNION ALL
            SELECT 21, 'alumnis', 'posisi_jabatan', q.id, q.kode_pertanyaan
            FROM ref_subpertanyaan2021 q WHERE q.kode_pertanyaan = 'F5C'
            UNION ALL
            SELECT 22, 'companies', 'skala', q.id, q.kode_pertanyaan
            FROM ref_subpertanyaan2021 q WHERE q.kode_pertanyaan = 'F5D'
            UNION ALL
            SELECT 23, 'companies', 'alamat', q.id, q.kode_pertanyaan
            FROM ref_subpertanyaan2021 q WHERE q.kode_pertanyaan = 'F510'
        ");
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        DB::statement('DROP VIEW IF EXISTS v_question_mappings');
    }
};
