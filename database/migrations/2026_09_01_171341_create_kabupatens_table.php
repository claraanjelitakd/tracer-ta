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
        Schema::create('kabupaten', function (Blueprint $table) {
            $table->id()->comment('ID unik referensi kabupaten/kota');
            $table->foreignId('propinsi_id')->constrained('propinsi')->cascadeOnDelete()->comment('Relasi ke provinsi induk');
            $table->string('kode_kabupaten', 10)->unique()->nullable()->comment('Kode BPS/Kemendagri kabupaten (misal: 34.04)');
            $table->string('nama_kabupaten', 60)->comment('Nama resmi kabupaten/kota (misal: Kabupaten Sleman)');
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('kabupaten');
    }
};
