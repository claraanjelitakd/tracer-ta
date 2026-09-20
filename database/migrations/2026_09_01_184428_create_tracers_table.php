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
                $table->id();
                $table->foreignId('biodata_id')->constrained('biodata')->cascadeOnDelete();
                $table->foreignId('question_id')->nullable()->constrained('ref_subpertanyaan2021')->cascadeOnDelete();
                $table->string('nim', 20);
                $table->char('kelompok', 3);
                $table->string('kode_pertanyaan', 20);
                $table->text('subpertanyaan');
                $table->text('answer')->nullable();
                $table->json('answer_json')->nullable();
                $table->string('keterangan', 100)->nullable();
                $table->string('tahun_lulus', 10)->nullable();
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
