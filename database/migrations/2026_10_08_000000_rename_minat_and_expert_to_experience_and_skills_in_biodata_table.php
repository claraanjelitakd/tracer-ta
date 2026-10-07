<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     * Mengganti kolom 'expert' menjadi 'skills' dan 'minat' menjadi 'experience'
     * pada tabel biodata untuk integrasi langsung dengan LinkedIn.
     */
    public function up(): void
    {
        // 1. Drop database view yang merujuk pada b.expert dan b.minat sebelum rename kolom
        DB::statement('DROP VIEW IF EXISTS v_alumni_audit_rekap');
        DB::statement('DROP VIEW IF EXISTS v_alumni_profile_summary');

        // 2. Rename kolom di tabel biodata
        Schema::table('biodata', function (Blueprint $table) {
            if (Schema::hasColumn('biodata', 'expert') && ! Schema::hasColumn('biodata', 'skills')) {
                $table->renameColumn('expert', 'skills');
            }
            if (Schema::hasColumn('biodata', 'minat') && ! Schema::hasColumn('biodata', 'experience')) {
                $table->renameColumn('minat', 'experience');
            }
        });

        // 3. Buat kembali v_alumni_profile_summary dengan kolom skills dan experience
        DB::statement("
            CREATE VIEW v_alumni_profile_summary AS
            SELECT 
                b.id AS biodata_id,
                b.user_id,
                b.nim,
                COALESCE(da.nama, u.name, 'Mahasiswa UKDW') AS nama,
                COALESCE(b.nik, da.nik) AS nik,
                da.no_kk,
                da.no_bpjs,
                da.nisn,
                b.npwp,
                COALESCE(b.email_pribadi, da.email_pribadi, u.email) AS email,
                COALESCE(b.email_pribadi, da.email_pribadi, u.email) AS email_pribadi,
                da.email_students AS email_students,
                COALESCE(b.nomor_telepon, da.nomor_telepon) AS nomor_telepon,
                da.tempat_lahir,
                da.tanggal_lahir,
                da.jenis_kelamin,
                da.agama,
                da.golongan_darah,
                da.warga_negara,
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
                b.skills,
                b.experience,
                b.prodi_id,
                p.kode_prodi,
                p.nama_prodi,
                p.fakultas_id,
                f.kode_fakultas,
                f.nama_fakultas,
                f.singkatan AS singkatan_fakultas,
                da.angkatan_masuk,
                da.tahun_lulus,
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
                ot.kota AS kode_orang_tua,
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
                    WHEN COALESCE(da.nama, u.name) IS NOT NULL 
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

        // 4. Buat kembali v_alumni_audit_rekap
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
        DB::statement('DROP VIEW IF EXISTS v_alumni_profile_summary');

        Schema::table('biodata', function (Blueprint $table) {
            if (Schema::hasColumn('biodata', 'skills') && ! Schema::hasColumn('biodata', 'expert')) {
                $table->renameColumn('skills', 'expert');
            }
            if (Schema::hasColumn('biodata', 'experience') && ! Schema::hasColumn('biodata', 'minat')) {
                $table->renameColumn('experience', 'minat');
            }
        });
    }
};
