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
        Schema::create('kelompok_pertanyaans', function (Blueprint $table) {
            $table->id();
            $table->foreignId('kuesioner_id')->nullable()->constrained('kuesioners')->cascadeOnDelete();
            $table->char('kode_kelompok', 3)->nullable();
            $table->string('title');
            $table->integer('order')->default(0);
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('kelompok_pertanyaans');
    }
};
