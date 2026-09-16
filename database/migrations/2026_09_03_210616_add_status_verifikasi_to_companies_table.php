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
            $table->enum('status_verifikasi', ['Menunggu Verifikasi', 'Terverifikasi', 'Ditolak'])
                ->default('Menunggu Verifikasi')
                ->comment('Status verifikasi legalitas perusahaan dari Biro 3');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('perusahaan', function (Blueprint $table) {
            $table->dropColumn('status_verifikasi');
        });
    }
};
