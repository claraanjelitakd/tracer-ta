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
        if (! Schema::hasTable('tracer')) {
            Schema::create('tracer', function (Blueprint $table) {
                $table->id()->comment('ID unik respon kuesioner tracer study');
                $table->foreignId('biodata_id')->constrained('biodata')->cascadeOnDelete()->comment('Relasi ke biodata responden alumni');
                $table->foreignId('question_id')->nullable()->constrained('ref_subpertanyaan2021')->cascadeOnDelete()->comment('Relasi ke master butir pertanyaan kuesioner');
                $table->char('nim', 8)->comment('NIM mahasiswa responden (8 karakter)');
                $table->char('kelompok', 3)->comment('Kode kelompok kuesioner tracer (misal: K1, K2, F)');
                $table->string('kode_pertanyaan', 20)->comment('Kode butir pertanyaan (misal: F5B, F504, F11)');
                $table->text('subpertanyaan')->comment('Teks redaksi pertanyaan');
                $table->text('answer')->nullable()->comment('Nilai jawaban teks atau opsi yang dipilih');
                $table->json('answer_json')->nullable()->comment('Data jawaban berbentuk array / JSON bila bernilai ganda');
                $table->string('keterangan', 100)->nullable()->comment('Keterangan label opsi terpilih');
                $table->year('tahun_lulus')->nullable()->comment('Tahun kelulusan responden saat mengisi kuesioner (YYYY)');
                $table->timestamps();

                $table->unique(['biodata_id', 'question_id'], 'tracer_biodata_question_unique');
                $table->unique(['biodata_id', 'kode_pertanyaan'], 'tracer_biodata_kode_pertanyaan_unique');
                $table->index(['biodata_id', 'kode_pertanyaan']);
                $table->index('nim');
                $table->index('kelompok');
            });
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('tracer');
    }
};
