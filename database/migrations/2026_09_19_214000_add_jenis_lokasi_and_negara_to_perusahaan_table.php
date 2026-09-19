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
            if (! Schema::hasColumn('perusahaan', 'jenis_lokasi')) {
                $table->string('jenis_lokasi', 30)->default('Dalam Negeri')->after('skala')->comment('Jenis Lokasi: Dalam Negeri / Luar Negeri');
            }
            if (! Schema::hasColumn('perusahaan', 'negara')) {
                $table->string('negara', 100)->nullable()->default('Indonesia')->after('jenis_lokasi')->comment('Nama Negara jika Perusahaan berada di Luar Negeri');
            }
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('perusahaan', function (Blueprint $table) {
            if (Schema::hasColumn('perusahaan', 'negara')) {
                $table->dropColumn('negara');
            }
            if (Schema::hasColumn('perusahaan', 'jenis_lokasi')) {
                $table->dropColumn('jenis_lokasi');
            }
        });
    }
};
