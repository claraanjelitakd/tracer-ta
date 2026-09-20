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
        if (! Schema::hasTable('ref_negara')) {
            Schema::create('ref_negara', function (Blueprint $table) {
                $table->id();
                $table->string('kode_iso2', 2)->unique();
                $table->string('kode_iso3', 3)->nullable();
                $table->string('nama_negara')->unique();
                $table->string('nama_resmi')->nullable();
                $table->string('benua', 50)->nullable();
                $table->string('ibu_kota', 100)->nullable();
                $table->timestamps();
            });
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('ref_negara');
    }
};
