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
        Schema::create('prodi', function (Blueprint $table) {
            $table->id()->comment('ID unik master program studi');
            $table->foreignId('fakultas_id')->nullable()->constrained('ref_fakultas')->nullOnDelete()->comment('Relasi ke fakultas pengampu');
            $table->string('kode_prodi', 10)->unique()->comment('Kode resmi program studi (misal: 71, 72)');
            $table->string('nama_prodi', 100)->comment('Nama resmi program studi (misal: Informatika)');
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('prodi');
    }
};
