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
            $table->id();
            $table->foreignId('prodi_id')->constrained('prodi')->cascadeOnDelete();
            $table->string('title');
            $table->text('description')->nullable();
            $table->integer('order')->default(1);
            $table->timestamps();
        });

        // 2. Tabel Butir Pertanyaan Khusus Kuesioner Program Studi
        Schema::create('prodi_question', function (Blueprint $table) {
            $table->id();
            $table->foreignId('prodi_id')->constrained('prodi')->cascadeOnDelete();
            $table->foreignId('prodi_question_section_id')->constrained('prodi_question_section')->cascadeOnDelete();
            $table->string('code')->comment('Contoh: PSI-01, PTI-01');
            $table->text('question_text');
            $table->string('type')->default('single_choice')->comment('single_choice, multiple_choice, text, number, rating_5, radio_input');
            $table->boolean('is_required')->default(true);
            $table->integer('order')->default(1);
            $table->timestamps();

            $table->unique(['prodi_id', 'code']);
        });

        // 3. Tabel Pilihan Opsi Jawaban Khusus Pertanyaan Program Studi
        Schema::create('prodi_question_option', function (Blueprint $table) {
            $table->id();
            $table->foreignId('prodi_question_id')->constrained('prodi_question')->cascadeOnDelete();
            $table->string('code')->nullable();
            $table->string('option_text');
            $table->string('jump_to')->nullable();
            $table->integer('order')->default(1);
            $table->timestamps();
        });

        // 4. Tabel Respon / Jawaban Alumni Khusus Kuesioner Program Studi
        Schema::create('prodi_response', function (Blueprint $table) {
            $table->id();
            $table->foreignId('biodata_id')->constrained('biodata')->cascadeOnDelete();
            $table->foreignId('prodi_question_id')->constrained('prodi_question')->cascadeOnDelete();
            $table->text('answer_text')->nullable();
            $table->json('answer_json')->nullable();
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
