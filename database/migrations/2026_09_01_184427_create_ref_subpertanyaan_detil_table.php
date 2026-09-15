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
            $table->id();
            $table->foreignId('pertanyaan_id')->constrained('ref_subpertanyaan2021')->cascadeOnDelete();
            $table->string('kode_pertanyaan', 20)->nullable();
            $table->string('kode_opsi', 20)->nullable(); // e.g. 1, 2, F401, F1601
            $table->text('option_text');
            $table->string('jump_to', 10)->nullable();
            $table->integer('order')->default(0);
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
