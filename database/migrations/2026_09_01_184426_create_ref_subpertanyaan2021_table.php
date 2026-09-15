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
            $table->id();
            $table->char('kelompok', 3); // Merujuk ke kode kelompok kuesioner (Kelompok 1..9)
            $table->foreignId('kelompok_pertanyaan_id')->nullable()->constrained('kelompok_pertanyaans')->cascadeOnDelete();
            $table->string('kode_pertanyaan', 20)->unique();
            $table->text('subpertanyaan');
            $table->string('type', 50); // text, radio, radio_input, multiple_choice, number, matrix, matrix_dual, multiple_textbox, header
            $table->string('keterangan', 100)->nullable(); // Keterangan tambahan / sub-detail pertanyaan
            $table->tinyInteger('wajib')->default(1); // 1 = wajib, 0 = opsional
            $table->integer('order')->default(0);
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
