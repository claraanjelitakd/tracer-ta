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
        Schema::create('propinsi', function (Blueprint $table) {
            $table->id()->comment('ID unik referensi provinsi');
            $table->string('kode_provinsi', 10)->unique()->nullable()->comment('Kode BPS/Kemendagri provinsi (misal: 34)');
            $table->string('nama_provinsi', 60)->comment('Nama resmi provinsi (misal: DI Yogyakarta)');
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('propinsi');
    }
};
