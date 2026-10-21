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
        Schema::create('kelompok_pertanyaan', function (Blueprint $table) {
            $table->id()->comment('ID unik bagian / kelompok pertanyaan kuesioner');
            $table->foreignId('kuesioner_id')->nullable()->constrained('kuesioner')->cascadeOnDelete()->comment('Relasi ke kuesioner induk');
            $table->char('kode_kelompok', 5)->nullable()->comment('Kode pengelompokan pertanyaan (misal: K1, K2, F)');
            $table->string('title', 150)->comment('Nama bagian / section kuesioner');
            $table->smallInteger('order')->default(0)->comment('Nomor urut penampilan bagian kuesioner');
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('kelompok_pertanyaan');
    }
};
