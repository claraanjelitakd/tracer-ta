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
        Schema::table('perusahaan', function (Blueprint $table) {
            if (! Schema::hasColumn('perusahaan', 'jenis_perusahaan')) {
                $table->string('jenis_perusahaan', 50)->nullable()->after('skala')->comment('F11 - Jenis instansi/perusahaan: Instansi pemerintah, BUMN, Swasta, dll.');
            }
            if (! Schema::hasColumn('perusahaan', 'jenis_perusahaan_lainnya')) {
                $table->string('jenis_perusahaan_lainnya', 150)->nullable()->after('jenis_perusahaan')->comment('Isian teks jika jenis_perusahaan memilih Lainnya');
            }
        });

        Schema::table('biodata', function (Blueprint $table) {
            if (! Schema::hasColumn('biodata', 'kategori_pekerjaan')) {
                $table->string('kategori_pekerjaan', 50)->nullable()->after('posisi_jabatan')->comment('Kategori peran: Pekerja / Wirausaha / Melanjutkan Pendidikan');
            }
            if (! Schema::hasColumn('biodata', 'posisi_wiraswasta')) {
                $table->string('posisi_wiraswasta', 100)->nullable()->after('kategori_pekerjaan')->comment('F5C - Jabatan jika berwiraswasta / founder');
            }
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('biodata', function (Blueprint $table) {
            if (Schema::hasColumn('biodata', 'posisi_wiraswasta')) {
                $table->dropColumn('posisi_wiraswasta');
            }
            if (Schema::hasColumn('biodata', 'kategori_pekerjaan')) {
                $table->dropColumn('kategori_pekerjaan');
            }
        });

        Schema::table('perusahaan', function (Blueprint $table) {
            if (Schema::hasColumn('perusahaan', 'jenis_perusahaan_lainnya')) {
                $table->dropColumn('jenis_perusahaan_lainnya');
            }
            if (Schema::hasColumn('perusahaan', 'jenis_perusahaan')) {
                $table->dropColumn('jenis_perusahaan');
            }
        });
    }
};
