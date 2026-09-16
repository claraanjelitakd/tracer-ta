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
        Schema::create('ump', function (Blueprint $table) {
            $table->id();
            $table->string('kode_provinsi');
            $table->integer('tahun')->default(2026);
            $table->decimal('besaran', 15, 2)->nullable();
            $table->string('catatan')->nullable();
            $table->timestamps();

            $table->foreign('kode_provinsi')
                ->references('kode_provinsi')
                ->on('propinsi')
                ->cascadeOnUpdate()
                ->cascadeOnDelete();

            $table->unique(['kode_provinsi', 'tahun']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('ump');
    }
};
