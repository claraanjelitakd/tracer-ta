<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Migrasi Penambahan Relasi fakultas_id ke Tabel prodi
 *
 * Lokasi: database/migrations/2026_09_19_213100_add_fakultas_id_to_prodi_table.php
 * Fungsi: Menghubungkan setiap program studi dengan fakultas penaungnya di tabel ref_fakultas.
 */
return new class extends Migration
{
    /**
     * Jalankan migrasi.
     */
    public function up(): void
    {
        Schema::table('prodi', function (Blueprint $table) {
            $table->foreignId('fakultas_id')
                ->nullable()
                ->after('id')
                ->constrained('ref_fakultas')
                ->nullOnDelete()
                ->comment('Relasi ke tabel ref_fakultas');
        });
    }

    /**
     * Batalkan migrasi.
     */
    public function down(): void
    {
        Schema::table('prodi', function (Blueprint $table) {
            $table->dropForeign(['fakultas_id']);
            $table->dropColumn('fakultas_id');
        });
    }
};
