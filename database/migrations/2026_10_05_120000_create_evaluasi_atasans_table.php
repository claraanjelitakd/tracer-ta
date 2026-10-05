<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     * Membuat seluruh tabel yang diperlukan untuk survei Evaluasi Kepuasan Pengguna Lulusan (Atasan) UKDW:
     * 1. evaluasi_atasan: Tabel transaksi instrumen evaluasi per alumni
     * 2. pertanyaan_evaluasi_atasan: Master butir pertanyaan kuesioner evaluasi atasan
     * 3. respon_evaluasi_atasan: Jawaban responden atas butir instrumen evaluasi
     */
    public function up(): void
    {
        // 1. Tabel Transaksi Evaluasi Atasan (Menghubungkan Alumni, Perusahaan, dan Atasan)
        Schema::create('evaluasi_atasan', function (Blueprint $table) {
            $table->id()->comment('ID unik transaksi evaluasi atasan');

            $table->foreignId('biodata_id')->constrained('biodata')->cascadeOnDelete()->comment('Relasi ke alumni UKDW yang dievaluasi');
            $table->foreignId('perusahaan_id')->nullable()->constrained('perusahaan')->nullOnDelete()->comment('Relasi ke perusahaan tempat alumni bekerja');
            $table->foreignId('atasan_id')->nullable()->constrained('atasan')->nullOnDelete()->comment('Relasi ke data atasan langsung penilai');

            $table->string('token', 64)->unique()->index()->comment('Token acak unik untuk akses formulir publik tanpa login');
            $table->boolean('is_submitted')->default(false)->comment('Penanda status apakah evaluasi sudah disubmit oleh atasan');
            $table->timestamp('submitted_at')->nullable()->comment('Waktu penyerahan / submit respon evaluasi');

            // Karakteristik Keberadaan Alumni UKDW di Perusahaan
            $table->string('jumlah_alumni_ukdw', 100)->nullable()->comment('Rentang jumlah alumni UKDW yang bekerja di instansi: < 5 Orang, 6 - 10 Orang, dll');
            $table->string('standar_gaji_pertama', 100)->nullable()->comment('Standar gaji pertama per bulan untuk alumni UKDW di instansi ini');

            $table->timestamps();
        });

        // 2. Tabel Master Butir Pertanyaan Kuesioner Evaluasi Atasan (Dikelola Superadmin)
        Schema::create('pertanyaan_evaluasi_atasan', function (Blueprint $table) {
            $table->id()->comment('ID unik butir pertanyaan evaluasi atasan');
            $table->string('kode', 50)->unique()->comment('Kode unik butir pertanyaan (misal: integritas, tingkat_kesiapan_kerja)');
            $table->string('aspek')->comment('Label aspek penilaian kinerja atau kesiapan alumni');
            $table->text('deskripsi')->nullable()->comment('Deskripsi penjelasan operasional aspek yang dinilai');
            $table->string('kategori', 50)->default('Kinerja')->comment('Kategori instrumen: Kesiapan Kerja, Kinerja, dll');
            $table->string('tipe', 50)->default('likert_5')->comment('Tipe input pertanyaan: likert_5 (Sangat Tinggi s/d Sangat Kurang) atau pilihan_ganda');
            $table->json('pilihan_jawaban')->nullable()->comment('Daftar opsi pilihan jawaban bila bertipe pilihan_ganda');
            $table->boolean('is_active')->default(true)->comment('Status keaktifan butir instrumen dalam formulir');
            $table->integer('order')->default(1)->comment('Nomor urut penampilan pertanyaan');
            $table->timestamps();
        });

        // 3. Tabel Respon / Jawaban Kuesioner Evaluasi Atasan
        Schema::create('respon_evaluasi_atasan', function (Blueprint $table) {
            $table->id()->comment('ID unik respon evaluasi');
            $table->foreignId('evaluasi_atasan_id')->constrained('evaluasi_atasan')->cascadeOnDelete()->comment('Relasi ke transaksi evaluasi atasan');
            $table->foreignId('pertanyaan_id')->constrained('pertanyaan_evaluasi_atasan')->cascadeOnDelete()->comment('Relasi ke butir pertanyaan instrumen');
            $table->string('nilai', 100)->comment('Jawaban respon yang dipilih oleh atasan penilai');
            $table->text('catatan')->nullable()->comment('Catatan tambahan atau saran kualitatif');
            $table->timestamps();

            // Memastikan 1 evaluasi hanya memiliki 1 respon untuk masing-masing butir pertanyaan
            $table->unique(['evaluasi_atasan_id', 'pertanyaan_id'], 'eval_atasan_pertanyaan_unique');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('respon_evaluasi_atasan');
        Schema::dropIfExists('pertanyaan_evaluasi_atasan');
        Schema::dropIfExists('evaluasi_atasan');
    }
};
