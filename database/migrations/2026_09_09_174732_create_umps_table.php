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
        Schema::create('ump', function (Blueprint $table) {
            $table->id()->comment('ID unik referensi Upah Minimum Provinsi (UMP)');
            $table->string('kode_provinsi', 10)->comment('Kode provinsi BPS referensi');
            $table->year('tahun')->default(2026)->comment('Tahun penetapan UMP (YYYY)');
            $table->decimal('besaran', 15, 2)->nullable()->comment('Nominal besaran rupiah UMP');
            $table->string('catatan', 150)->nullable()->comment('Catatan atau dasar hukum SK Gubernur penetapan UMP');
            $table->timestamps();

            $table->foreign('kode_provinsi')
                ->references('kode_provinsi')
                ->on('propinsi')
                ->cascadeOnUpdate()
                ->cascadeOnDelete();

            $table->unique(['kode_provinsi', 'tahun']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('ump');
    }
};
