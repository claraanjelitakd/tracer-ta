<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    /**
     * Run the migrations.
     *
     * Membuat 4 Database Views Terpisah & Berkinerja Tinggi:
     * 1. v_alumni_profile_summary   : Rekapitulasi profil & data identitas alumni
     * 2. v_alumni_tracer_univ_status: Agregasi status kuesioner universitas
     * 3. v_alumni_tracer_prodi_status: Agregasi status kuesioner program studi
     * 4. v_alumni_audit_rekap       : Master rekapitulasi audit kelengkapan tracer study (Single-row fetch)
     */
    public function up(): void
    {
        // 1. Drop existing views jika ada
        DB::statement('DROP VIEW IF EXISTS v_alumni_tracer_export');
        DB::statement('DROP VIEW IF EXISTS v_alumni_audit_rekap');
        DB::statement('DROP VIEW IF EXISTS v_alumni_tracer_prodi_status');
        DB::statement('DROP VIEW IF EXISTS v_alumni_tracer_univ_status');
        DB::statement('DROP VIEW IF EXISTS v_alumni_profile_summary');

        // 2. VIEW 1: v_alumni_profile_summary
        // Menggabungkan seluruh data profil alumni lengkap 4 sub-tab (biodata pribadi, akademik, orang tua, karier/perusahaan/atasan)
        DB::statement("
            CREATE VIEW v_alumni_profile_summary AS
            SELECT 
                b.id AS biodata_id,
                b.user_id,
                b.nim,
                COALESCE(da.nama, b.nama, u.name, 'Mahasiswa UKDW') AS nama,
                COALESCE(b.nik, da.nik) AS nik,
                COALESCE(b.no_kk, da.no_kk) AS no_kk,
                COALESCE(b.no_bpjs, da.no_bpjs) AS no_bpjs,
                COALESCE(b.nisn, da.nisn) AS nisn,
                b.npwp,
                COALESCE(b.email_pribadi, da.email_pribadi, b.email, u.email) AS email,
                COALESCE(b.email_pribadi, da.email_pribadi) AS email_pribadi,
                COALESCE(b.email_students, da.email_students) AS email_students,
                COALESCE(b.nomor_telepon, da.nomor_telepon) AS nomor_telepon,
                COALESCE(b.tempat_lahir, da.tempat_lahir) AS tempat_lahir,
                COALESCE(b.tanggal_lahir, da.tanggal_lahir) AS tanggal_lahir,
                COALESCE(b.jenis_kelamin, da.jenis_kelamin) AS jenis_kelamin,
                COALESCE(b.agama, da.agama) AS agama,
                COALESCE(b.golongan_darah, da.golongan_darah) AS golongan_darah,
                COALESCE(b.warga_negara, da.warga_negara) AS warga_negara,
                COALESCE(b.alamat, da.alamat_saat_ini) AS alamat,
                COALESCE(b.kelurahan, da.kelurahan) AS kelurahan,
                COALESCE(b.kecamatan, da.kecamatan) AS kecamatan,
                COALESCE(b.kode_pos, da.kode_pos, b.zipcode) AS kode_pos,
                COALESCE(b.propinsi_id, da.propinsi_id) AS propinsi_id,
                prov.nama_provinsi,
                COALESCE(b.kabupaten_id, da.kabupaten_id) AS kabupaten_id,
                kab.nama_kabupaten,
                b.instagram_url,
                b.facebook_url,
                b.linkedin_url,
                b.linkedin_username,
                b.expert,
                b.minat,
                b.prodi_id,
                p.kode_prodi,
                p.nama_prodi,
                p.fakultas_id,
                f.kode_fakultas,
                f.nama_fakultas,
                f.singkatan AS singkatan_fakultas,
                da.angkatan_masuk,
                COALESCE(b.tahun_lulus, da.tahun_lulus) AS tahun_lulus,
                da.tahun_akademik_lulus,
                da.ip_kumulatif AS ipk,
                da.total_sks,
                da.total_angka_kualitas,
                da.status_mahasiswa,
                da.asal_sekolah,
                da.alamat_asal_sekolah,
                da.kota_kabupaten_asal_sekolah,
                da.provinsi_asal_sekolah,
                da.jurusan_asal_sekolah,
                ot.nama_orang_tua,
                ot.pekerjaan AS pekerjaan_orang_tua,
                ot.alamat AS alamat_orang_tua,
                ot.kota AS kota_orang_tua,
                ot.nomor_telepon AS nomor_telepon_orang_tua,
                ot.kode_pos AS kode_pos_orang_tua,
                b.kategori_pekerjaan,
                b.posisi_jabatan,
                b.posisi_wiraswasta,
                b.pendidikan_tingkat,
                b.perguruan_tinggi,
                b.pendidikan_prodi,
                b.gaji,
                b.jenis_pekerjaan,
                b.perusahaan_id,
                c.nama_perusahaan,
                c.alamat AS alamat_perusahaan,
                c.sektor AS sektor_perusahaan,
                c.skala AS skala_perusahaan,
                c.jenis_perusahaan,
                c.jenis_lokasi AS jenis_lokasi_perusahaan,
                c.negara AS negara_perusahaan,
                a.nama AS nama_atasan,
                a.email AS email_atasan,
                a.telepon AS telepon_atasan,
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
            LEFT JOIN prodi p ON b.prodi_id = p.id
            LEFT JOIN ref_fakultas f ON p.fakultas_id = f.id
            LEFT JOIN propinsi prov ON COALESCE(b.propinsi_id, da.propinsi_id) = prov.id
            LEFT JOIN kabupaten kab ON COALESCE(b.kabupaten_id, da.kabupaten_id) = kab.id
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
