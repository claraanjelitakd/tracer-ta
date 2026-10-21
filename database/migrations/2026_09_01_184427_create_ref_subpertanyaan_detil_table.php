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
        Schema::create('ref_subpertanyaan_detil', function (Blueprint $table) {
            $table->id()->comment('ID unik opsi butir pertanyaan');
            $table->foreignId('pertanyaan_id')->constrained('ref_subpertanyaan2021')->cascadeOnDelete()->comment('Relasi ke butir pertanyaan induk');
            $table->string('kode_pertanyaan', 20)->nullable()->comment('Kode pertanyaan induk');
            $table->string('kode_opsi', 20)->nullable()->comment('Kode opsi jawaban (misal: 1, 2, F401)');
            $table->text('option_text')->comment('Label teks pilihan jawaban');
            $table->string('jump_to', 20)->nullable()->comment('Kode percabangan lompat (skip logic)');
            $table->smallInteger('order')->default(0)->comment('Nomor urut penampilan opsi jawaban');
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('ref_subpertanyaan_detil');
    }
};
