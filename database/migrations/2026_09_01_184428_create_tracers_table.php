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
        Schema::create('tracer', function (Blueprint $table) {
            $table->id();
            $table->foreignId('biodata_id')->constrained('biodata')->cascadeOnDelete();
            $table->foreignId('question_id')->nullable()->constrained('ref_subpertanyaan2021')->cascadeOnDelete();
            $table->string('nim', 20); // Ref NIM dari biodata / data akademik alumni
            $table->char('kelompok', 3); // Ref kode kelompok induk (F2, F17, F5, F18, BIO, dll)
            $table->string('kode_pertanyaan', 20); // Ref kode butir pertanyaan (F1, F21, F17a1, dll)
            $table->text('subpertanyaan'); // Teks pertanyaan disamakan dengan tabel induk
            $table->text('answer')->nullable(); // Jawaban teks tunggal alumni
            $table->json('answer_json')->nullable(); // Jawaban struktur json (array checkbox/matriks)
            $table->string('keterangan', 100)->nullable(); // Keterangan disamakan dengan tabel induk
            $table->string('tahun_lulus', 10)->nullable(); // Tahun kelulusan dari data akademik
            $table->timestamps();

            $table->index(['biodata_id', 'kode_pertanyaan']);
            $table->index('nim');
            $table->index('kelompok');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('tracer');
    }
};
