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
        Schema::table('biodatas', function (Blueprint $table) {
            if (! Schema::hasColumn('biodatas', 'posisi_jabatan')) {
                $table->string('posisi_jabatan')->nullable()->after('company_id');
            }
            if (! Schema::hasColumn('biodatas', 'jenis_pekerjaan')) {
                $table->string('jenis_pekerjaan')->nullable()->after('posisi_jabatan');
            }
        });

        Schema::table('companies', function (Blueprint $table) {
            if (! Schema::hasColumn('companies', 'skala')) {
                $table->string('skala')->nullable()->after('sektor');
            }
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('biodatas', function (Blueprint $table) {
            if (Schema::hasColumn('biodatas', 'posisi_jabatan')) {
                $table->dropColumn(['posisi_jabatan', 'jenis_pekerjaan']);
            }
        });

        Schema::table('companies', function (Blueprint $table) {
            if (Schema::hasColumn('companies', 'skala')) {
                $table->dropColumn(['skala']);
            }
        });
    }
};
