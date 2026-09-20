<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        if (Schema::getConnection()->getDriverName() !== 'sqlite') {
            // 1. Ubah tipe kolom URL dan teks panjang pada tabel biodata di MySQL
            Schema::table('biodata', function (Blueprint $table) {
                $table->text('instagram_url')->nullable()->change();
                $table->text('facebook_url')->nullable()->change();
                $table->text('linkedin_url')->nullable()->change();
                $table->text('linkedin_username')->nullable()->change();
                $table->text('expert')->nullable()->change();
                $table->text('minat')->nullable()->change();
                $table->string('posisi_jabatan', 500)->nullable()->change();
                $table->string('posisi_wiraswasta', 500)->nullable()->change();
                $table->string('pendidikan_tingkat', 500)->nullable()->change();
                $table->string('perguruan_tinggi', 500)->nullable()->change();
                $table->string('pendidikan_prodi', 500)->nullable()->change();
                $table->string('jenis_pekerjaan', 500)->nullable()->change();
            });

            // 2. Ubah tipe kolom pada tabel yudisium
            if (Schema::hasTable('yudisium')) {
                Schema::table('yudisium', function (Blueprint $table) {
                    if (Schema::hasColumn('yudisium', 'url_publikasi')) {
                        $table->text('url_publikasi')->nullable()->change();
                    }
                });
            }

            // 3. Ubah tipe kolom pada tabel perusahaan
            if (Schema::hasTable('perusahaan')) {
                Schema::table('perusahaan', function (Blueprint $table) {
                    if (Schema::hasColumn('perusahaan', 'nama_perusahaan')) {
                        $table->string('nama_perusahaan', 500)->change();
                    }
                    if (Schema::hasColumn('perusahaan', 'jenis_perusahaan_lainnya')) {
                        $table->text('jenis_perusahaan_lainnya')->nullable()->change();
                    }
                });
            }

            // 4. Re-create all database views setelah perubahan tipe kolom
            if (file_exists(database_path('migrations/2026_09_20_234000_create_alumni_audit_database_views.php'))) {
                $m3 = require database_path('migrations/2026_09_20_234000_create_alumni_audit_database_views.php');
                $m3->up();
            }
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        if (Schema::getConnection()->getDriverName() !== 'sqlite') {
            Schema::table('biodata', function (Blueprint $table) {
                $table->string('instagram_url', 255)->nullable()->change();
                $table->string('facebook_url', 255)->nullable()->change();
                $table->string('linkedin_url', 255)->nullable()->change();
                $table->string('linkedin_username', 255)->nullable()->change();
                $table->string('expert', 255)->nullable()->change();
                $table->string('minat', 255)->nullable()->change();
                $table->string('posisi_jabatan', 255)->nullable()->change();
                $table->string('posisi_wiraswasta', 255)->nullable()->change();
                $table->string('pendidikan_tingkat', 255)->nullable()->change();
                $table->string('perguruan_tinggi', 255)->nullable()->change();
                $table->string('pendidikan_prodi', 255)->nullable()->change();
                $table->string('jenis_pekerjaan', 255)->nullable()->change();
            });
        }
    }
};
