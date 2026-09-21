<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::table('ref_subpertanyaan2021', function (Blueprint $table) {
            $table->string('tampil_di', 20)->default('kuesioner')->after('wajib');
        });

        // Inisialisasi butir pertanyaan yang dikelola pada profil alumni
        $profileManagedCodes = [
            'F1', 'F2A', 'F2B', 'F2C', 'F2D',
            'BIO_TEMPAT_LAHIR', 'BIO_TANGGAL_LAHIR', 'BIO_JK', 'BIO_TGL_LULUS', 'BIO_JUDUL_TA', 'BIO_NIK', 'BIO_NPWP',
            'F5a1', 'F5a2', 'F510', 'F5B', 'F5C', 'F5D',
            'F2E', 'F2E1', 'F2E2', 'F2E3', 'F2F', 'F2G', 'F2H',
            'F11', 'F505',
        ];

        DB::table('ref_subpertanyaan2021')
            ->whereIn('kode_pertanyaan', $profileManagedCodes)
            ->update(['tampil_di' => 'profile']);
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('ref_subpertanyaan2021', function (Blueprint $table) {
            $table->dropColumn('tampil_di');
        });
    }
};
