<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Migrasi Tabel Master Referensi Negara (ref_negara)
 *
 * Menyimpan data referensi negara seluruh dunia (ISO 2, Ibu Kota, Benua)
 * untuk mendukung data perusahaan luar negeri dan kewarganegaraan alumni.
 */
return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::create('ref_negara', function (Blueprint $table) {
            $table->id();
            $table->string('nama_negara')->unique();
            $table->string('ibu_kota')->nullable();
            $table->string('kode_iso2', 10)->nullable();
            $table->string('benua')->nullable();
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('ref_negara');
    }
};
