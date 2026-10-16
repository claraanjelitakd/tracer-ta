<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    /**
     * Run the migrations.
     *
     * Membuat Database View `v_alumni_linkedin_plot`:
     * Memetakan data staging hasil scraping LinkedIn (linkedin_sync_results)
     * langsung berdampingan dengan profil aktual di tabel `biodata`,
     * termasuk verifikasi kecocokan nama alumni, deteksi kesamaan perusahaan/jabatan,
     * dan status plotting data ke profil biodata.
     */
    public function up(): void
    {
        DB::statement('DROP VIEW IF EXISTS v_alumni_linkedin_plot');

        $driver = DB::connection()->getDriverName();

        $unquote = fn (string $expr) => $driver === 'sqlite' ? $expr : "json_unquote({$expr})";

        $rawScrapedName = $unquote("COALESCE(
            json_extract(lsr.scraped_data, '$.full_name'),
            json_extract(lsr.scraped_data, '$[0].full_name'),
            json_extract(lsr.scraped_data, '$.name'),
            json_extract(lsr.scraped_data, '$[0].name'),
            json_extract(lsr.scraped_data, '$.fullName'),
            json_extract(lsr.scraped_data, '$[0].fullName')
        )");

        $rawScrapedJob = $unquote("COALESCE(
            json_extract(lsr.scraped_data, '$.job_title'),
            json_extract(lsr.scraped_data, '$[0].job_title'),
            json_extract(lsr.scraped_data, '$.current_job'),
            json_extract(lsr.scraped_data, '$[0].current_job'),
            json_extract(lsr.scraped_data, '$.title'),
            json_extract(lsr.scraped_data, '$[0].title'),
            json_extract(lsr.scraped_data, '$.position'),
            json_extract(lsr.scraped_data, '$[0].position'),
            json_extract(lsr.scraped_data, '$.experiences[0].title'),
            json_extract(lsr.scraped_data, '$[0].experiences[0].title'),
            json_extract(lsr.scraped_data, '$.headline'),
            json_extract(lsr.scraped_data, '$[0].headline')
        )");

        $rawScrapedCompany = $unquote("COALESCE(
            json_extract(lsr.scraped_data, '$.company_name'),
            json_extract(lsr.scraped_data, '$[0].company_name'),
            json_extract(lsr.scraped_data, '$.current_company'),
            json_extract(lsr.scraped_data, '$[0].current_company'),
            json_extract(lsr.scraped_data, '$.companyName'),
            json_extract(lsr.scraped_data, '$[0].companyName'),
            json_extract(lsr.scraped_data, '$.company'),
            json_extract(lsr.scraped_data, '$[0].company'),
            json_extract(lsr.scraped_data, '$.experiences[0].company_name'),
            json_extract(lsr.scraped_data, '$[0].experiences[0].company_name'),
            json_extract(lsr.scraped_data, '$.experiences[0].company'),
            json_extract(lsr.scraped_data, '$[0].experiences[0].company')
        )");

        $rawScrapedLocation = $unquote("COALESCE(
            json_extract(lsr.scraped_data, '$.location'),
            json_extract(lsr.scraped_data, '$[0].location'),
            json_extract(lsr.scraped_data, '$.locationName'),
            json_extract(lsr.scraped_data, '$[0].locationName')
        )");

        $matchNameCondition = $driver === 'sqlite'
            ? "(
                LOWER(COALESCE(da.nama, u.name, '')) LIKE '%' || LOWER({$rawScrapedName}) || '%'
                OR LOWER({$rawScrapedName}) LIKE '%' || LOWER(COALESCE(da.nama, u.name, '')) || '%'
            )"
            : "(
                LOWER(COALESCE(da.nama, u.name, '')) LIKE CONCAT('%', LOWER({$rawScrapedName}), '%')
                OR LOWER({$rawScrapedName}) LIKE CONCAT('%', LOWER(COALESCE(da.nama, u.name, '')), '%')
            )";

        DB::statement("
            CREATE VIEW v_alumni_linkedin_plot AS
            SELECT 
                b.id AS biodata_id,
                b.user_id,
                b.nim,
                COALESCE(da.nama, u.name, 'Mahasiswa UKDW') AS nama_alumni,
                b.prodi_id,
                p.kode_prodi,
                p.nama_prodi,
                p.fakultas_id,
                f.kode_fakultas,
                f.nama_fakultas,
                da.tahun_lulus,
                da.tahun_akademik_lulus,
                b.linkedin_url AS linkedin_url_biodata,
                b.linkedin_username AS linkedin_username_biodata,
                b.posisi_jabatan AS posisi_biodata,
                b.perusahaan_id AS perusahaan_id_biodata,
                c.nama_perusahaan AS perusahaan_biodata,
                b.skills AS skills_biodata,
                b.experience AS experience_biodata,
                
                lsr.id AS sync_result_id,
                lsr.status AS sync_status,
                lsr.scraped_at,
                lsr.reviewed_at,
                lsr.reviewed_by,
                u_rev.name AS reviewer_name,
                lsr.linkedin_url AS linkedin_url_scraped,
                lsr.linkedin_username AS linkedin_username_scraped,
                
                {$rawScrapedName} AS nama_linkedin,
                {$rawScrapedJob} AS posisi_linkedin,
                {$rawScrapedCompany} AS perusahaan_linkedin,
                {$rawScrapedLocation} AS lokasi_linkedin,

                CASE 
                    WHEN lsr.id IS NULL OR {$rawScrapedName} IS NULL THEN 0
                    WHEN LOWER(TRIM(COALESCE(da.nama, u.name, ''))) = LOWER(TRIM({$rawScrapedName})) THEN 1
                    WHEN (
                        LENGTH({$rawScrapedName}) >= 3
                        AND {$matchNameCondition}
                    ) THEN 1
                    ELSE 0
                END AS is_nama_sesuai,

                CASE 
                    WHEN lsr.id IS NULL OR {$rawScrapedName} IS NULL THEN 'Belum Ada Data LinkedIn'
                    WHEN LOWER(TRIM(COALESCE(da.nama, u.name, ''))) = LOWER(TRIM({$rawScrapedName})) THEN 'Nama Sesuai Eksak'
                    WHEN (
                        LENGTH({$rawScrapedName}) >= 3
                        AND {$matchNameCondition}
                    ) THEN 'Nama Sesuai Parsial'
                    ELSE 'Nama Berbeda (Perlu Verifikasi)'
                END AS keterangan_nama,

                CASE 
                    WHEN lsr.status = 'approved' THEN 1
                    WHEN (
                        b.perusahaan_id IS NOT NULL 
                        AND {$rawScrapedCompany} IS NOT NULL
                        AND LOWER(TRIM(COALESCE(c.nama_perusahaan, ''))) = LOWER(TRIM({$rawScrapedCompany}))
                    ) THEN 1
                    ELSE 0
                END AS is_terplot_ke_biodata,

                CASE 
                    WHEN lsr.id IS NULL THEN 'Belum Pernah Sync'
                    WHEN lsr.status = 'approved' THEN 'Sudah Terplot (Approved)'
                    WHEN lsr.status = 'pending' AND (
                        (
                            {$rawScrapedCompany} IS NOT NULL
                            AND LOWER(TRIM(COALESCE(c.nama_perusahaan, ''))) = LOWER(TRIM({$rawScrapedCompany}))
                        )
                        OR (
                            {$rawScrapedJob} IS NOT NULL
                            AND LOWER(TRIM(COALESCE(b.posisi_jabatan, ''))) = LOWER(TRIM({$rawScrapedJob}))
                        )
                    ) THEN 'Data Sama / Siap Diplot'
                    WHEN lsr.status = 'pending' THEN 'Menunggu Review (Pending)'
                    WHEN lsr.status = 'rejected' THEN 'Ditolak (Rejected)'
                    ELSE 'Belum Terplot'
                END AS status_plot_label

            FROM biodata b
            LEFT JOIN users u ON b.user_id = u.id
            LEFT JOIN data_akademik da ON da.nim = b.nim
            LEFT JOIN prodi p ON b.prodi_id = p.id
            LEFT JOIN ref_fakultas f ON p.fakultas_id = f.id
            LEFT JOIN perusahaan c ON b.perusahaan_id = c.id
            LEFT JOIN (
                SELECT l1.*
                FROM linkedin_sync_results l1
                INNER JOIN (
                    SELECT biodata_id, MAX(id) AS max_id
                    FROM linkedin_sync_results
                    GROUP BY biodata_id
                ) l2 ON l1.id = l2.max_id
            ) lsr ON lsr.biodata_id = b.id
            LEFT JOIN users u_rev ON lsr.reviewed_by = u_rev.id
        ");
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        DB::statement('DROP VIEW IF EXISTS v_alumni_linkedin_plot');
    }
};
