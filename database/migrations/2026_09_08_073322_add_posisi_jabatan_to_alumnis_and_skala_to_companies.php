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
        Schema::table('biodata', function (Blueprint $table) {
            if (! Schema::hasColumn('biodata', 'posisi_jabatan')) {
                $table->string('posisi_jabatan')->nullable()->after('perusahaan_id');
            }
            if (! Schema::hasColumn('biodata', 'jenis_pekerjaan')) {
                $table->string('jenis_pekerjaan')->nullable()->after('posisi_jabatan');
            }
        });

        Schema::table('perusahaan', function (Blueprint $table) {
            if (! Schema::hasColumn('perusahaan', 'skala')) {
                $table->string('skala')->nullable()->after('sektor');
            }
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('biodata', function (Blueprint $table) {
            if (Schema::hasColumn('biodata', 'posisi_jabatan')) {
                $table->dropColumn(['posisi_jabatan', 'jenis_pekerjaan']);
            }
        });

        Schema::table('perusahaan', function (Blueprint $table) {
            if (Schema::hasColumn('perusahaan', 'skala')) {
                $table->dropColumn(['skala']);
            }
        });
    }
};
