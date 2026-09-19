<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Migrasi Pembuatan Tabel ref_fakultas
 *
 * Lokasi: database/migrations/2026_09_19_213000_create_ref_fakultas_table.php
 * Fungsi: Menyimpan master data Fakultas di lingkungan Universitas Kristen Duta Wacana (UKDW).
 */
return new class extends Migration
{
    /**
     * Jalankan migrasi.
     */
    public function up(): void
    {
        Schema::create('ref_fakultas', function (Blueprint $table) {
            $table->id();
            $table->string('kode_fakultas', 5)->unique()->comment('Contoh: 1, 2, 3, 4, 6, 7, 8');
            $table->string('nama_fakultas')->comment('Contoh: Fakultas Teknologi Informasi, Fakultas Bisnis');
            $table->timestamps();
        });
    }

    /**
     * Batalkan migrasi.
     */
    public function down(): void
    {
        Schema::dropIfExists('ref_fakultas');
    }
};
