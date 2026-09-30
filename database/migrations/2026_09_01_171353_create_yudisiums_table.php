<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     *
     * Tabel Yudisium:
     * Menyimpan data penyelesaian tugas akhir, dosen pembimbing & penguji,
     * publikasi karya ilmiah, dan status kelulusan yudisium.
     * Catatan arsitektur: Periode kelulusan (tahun_lulus, tahun_akademik_lulus, tanggal_lulus)
     * disimpan terpusat di tabel `data_akademik` agar tidak terjadi duplikasi data.
     */
    public function up(): void
    {
        if (! Schema::hasTable('yudisium')) {
            Schema::create('yudisium', function (Blueprint $table) {
                $table->id();

                // Relasi ke tabel data_akademik (NIM)
                $table->string('nim')->unique();
                $table->foreign('nim')->references('nim')->on('data_akademik')->cascadeOnDelete();

                // Dosen Pembimbing & Penguji
                $table->string('dosen_pembimbing_1')->nullable();
                $table->string('dosen_pembimbing_2')->nullable();
                $table->string('dosen_penguji_1')->nullable();
                $table->string('dosen_penguji_2')->nullable();

                // Tugas Akhir / Skripsi / Tesis
                $table->text('judul_ta')->nullable()->comment('Judul Skripsi/Thesis/Disertasi/Perancangan');
                $table->text('judul_ta_inggris')->nullable();

                // Publikasi Ilmiah
                $table->text('url_publikasi')->nullable();
                $table->string('jenis_publikasi')->nullable();
                $table->string('status_publikasi')->nullable();

                // Status Hasil Yudisium (Tanpa duplikasi tahun lulus / tahun akademik)
                $table->string('keterangan_hasil_yudisium')->nullable()->comment('Misal: Lulus dengan Pujian, Sangat Memuaskan');
                $table->enum('status_lulus', ['Belum', 'Proses', 'Lulus', 'Tidak Lulus'])->default('Belum')->comment('Status kelulusan yudisium mahasiswa');

                $table->timestamps();
            });
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('yudisium');
    }
};
