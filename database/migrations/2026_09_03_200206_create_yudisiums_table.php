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
        Schema::create('yudisium', function (Blueprint $table) {
            $table->id();

            // Relasi ke tabel data_akademik (NIM)
            $table->string('nim')->unique();
            $table->foreign('nim')->references('nim')->on('data_akademik')->cascadeOnDelete();

            // Dosen
            $table->string('dosen_pembimbing_1')->nullable();
            $table->string('dosen_pembimbing_2')->nullable();
            $table->string('dosen_penguji_1')->nullable();
            $table->string('dosen_penguji_2')->nullable();

            // Tugas Akhir
            $table->text('judul_ta')->nullable()->comment('Judul Skripsi/Thesis/Disertasi/Perancangan');
            $table->text('judul_ta_inggris')->nullable();

            // Publikasi
            $table->string('url_publikasi')->nullable();
            $table->string('jenis_publikasi')->nullable();
            $table->string('status_publikasi')->nullable();

            // Yudisium & Periode Kelulusan
            $table->string('tahun_akademik_lulus')->nullable()->comment('Contoh: Gasal 2026/2027');
            $table->year('tahun_lulus')->nullable()->comment('Contoh: 2026');
            $table->string('keterangan_hasil_yudisium')->nullable();
            $table->enum('proses_yudisium', ['Belum', 'Proses', 'Lulus', 'Tidak Lulus'])->default('Belum');

            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('yudisium');
    }
};
