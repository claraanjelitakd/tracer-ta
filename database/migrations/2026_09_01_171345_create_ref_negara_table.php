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
        if (! Schema::hasTable('ref_negara')) {
            Schema::create('ref_negara', function (Blueprint $table) {
                $table->id()->comment('ID unik referensi negara');
                $table->string('kode_iso2', 2)->unique()->comment('Kode 2 huruf ISO-3166-1 alpha-2 (misal: ID, SG)');
                $table->string('kode_iso3', 3)->nullable()->comment('Kode 3 huruf ISO-3166-1 alpha-3 (misal: IDN, SGP)');
                $table->string('nama_negara', 80)->unique()->comment('Nama umum negara dalam Bahasa Indonesia');
                $table->string('nama_resmi', 120)->nullable()->comment('Nama resmi kenegaraan lengkap');
                $table->string('benua', 50)->nullable()->comment('Benua lokasi negara: Asia, Eropa, dll');
                $table->string('ibu_kota', 80)->nullable()->comment('Nama ibu kota negara');
                $table->timestamps();
            });
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('ref_negara');
    }
};
