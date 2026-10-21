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
        Schema::create('ref_subpertanyaan2021', function (Blueprint $table) {
            $table->id()->comment('ID unik butir pertanyaan kuesioner');
            $table->char('kelompok', 5)->comment('Kode kelompok pertanyaan (misal: K1, K2)');
            $table->foreignId('kelompok_pertanyaan_id')->nullable()->constrained('kelompok_pertanyaan')->cascadeOnDelete()->comment('Relasi ke bagian kelompok pertanyaan');
            $table->string('kode_pertanyaan', 20)->unique()->comment('Kode butir pertanyaan (misal: F5B, F11, F2A)');
            $table->text('subpertanyaan')->comment('Teks redaksi pertanyaan');
            $table->string('type', 50)->comment('Tipe input pertanyaan: text, radio, multiple_choice, number, matrix, dll');
            $table->string('keterangan', 100)->nullable()->comment('Keterangan operasional sub-pertanyaan');
            $table->tinyInteger('wajib')->default(1)->comment('Indikator kewajiban: 1 = Wajib, 0 = Opsional');
            $table->string('tampil_di', 100)->nullable()->comment('Logika percabangan / kondisi penampil pertanyaan');
            $table->smallInteger('order')->default(0)->comment('Nomor urut penampilan pertanyaan');
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('ref_subpertanyaan2021');
    }
};
