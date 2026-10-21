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
        // 1. Tabel Section Khusus Kuesioner Program Studi
        Schema::create('prodi_question_section', function (Blueprint $table) {
            $table->id()->comment('ID unik bagian kuesioner prodi');
            $table->foreignId('prodi_id')->constrained('prodi')->cascadeOnDelete()->comment('Relasi ke program studi');
            $table->string('title', 150)->comment('Nama bagian / section kuesioner prodi');
            $table->text('description')->nullable()->comment('Deskripsi penjelasan bagian kuesioner prodi');
            $table->smallInteger('order')->default(1)->comment('Nomor urut penampilan bagian');
            $table->timestamps();
        });

        // 2. Tabel Butir Pertanyaan Khusus Kuesioner Program Studi
        Schema::create('prodi_question', function (Blueprint $table) {
            $table->id()->comment('ID unik butir pertanyaan kuesioner prodi');
            $table->foreignId('prodi_id')->constrained('prodi')->cascadeOnDelete()->comment('Relasi ke program studi');
            $table->foreignId('prodi_question_section_id')->constrained('prodi_question_section')->cascadeOnDelete()->comment('Relasi ke bagian section kuesioner prodi');
            $table->string('code', 30)->comment('Kode pertanyaan (contoh: PSI-01, PTI-01)');
            $table->text('question_text')->comment('Teks redaksi pertanyaan');
            $table->string('type', 40)->default('single_choice')->comment('Tipe input: single_choice, multiple_choice, text, number, rating_5, radio_input');
            $table->boolean('is_required')->default(true)->comment('Indikator kewajiban pengisian');
            $table->smallInteger('order')->default(1)->comment('Nomor urut penampilan pertanyaan');
            $table->timestamps();

            $table->unique(['prodi_id', 'code']);
        });

        // 3. Tabel Pilihan Opsi Jawaban Khusus Pertanyaan Program Studi
        Schema::create('prodi_question_option', function (Blueprint $table) {
            $table->id()->comment('ID unik pilihan opsi jawaban kuesioner prodi');
            $table->foreignId('prodi_question_id')->constrained('prodi_question')->cascadeOnDelete()->comment('Relasi ke butir pertanyaan prodi');
            $table->string('code', 30)->nullable()->comment('Kode opsi jawaban');
            $table->text('option_text')->comment('Label teks pilihan jawaban');
            $table->string('jump_to', 30)->nullable()->comment('Kode percabangan lompat pertanyaan');
            $table->smallInteger('order')->default(1)->comment('Nomor urut penampilan opsi');
            $table->timestamps();
        });

        // 4. Tabel Respon / Jawaban Alumni Khusus Kuesioner Program Studi
        Schema::create('prodi_response', function (Blueprint $table) {
            $table->id()->comment('ID unik respon kuesioner prodi');
            $table->foreignId('biodata_id')->constrained('biodata')->cascadeOnDelete()->comment('Relasi ke profil biodata alumni');
            $table->foreignId('prodi_question_id')->constrained('prodi_question')->cascadeOnDelete()->comment('Relasi ke pertanyaan kuesioner prodi');
            $table->text('answer_text')->nullable()->comment('Nilai jawaban teks atau opsi yang dipilih');
            $table->json('answer_json')->nullable()->comment('Nilai jawaban berupa array/JSON bila bernilai jamak');
            $table->timestamps();

            $table->unique(['biodata_id', 'prodi_question_id']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('prodi_response');
        Schema::dropIfExists('prodi_question_option');
        Schema::dropIfExists('prodi_question');
        Schema::dropIfExists('prodi_question_section');
    }
};
