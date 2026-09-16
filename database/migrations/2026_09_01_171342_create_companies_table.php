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
        Schema::create('perusahaan', function (Blueprint $table) {
            $table->id();
            $table->string('nama_perusahaan');
            $table->foreignId('propinsi_id')->nullable()->constrained('propinsi')->nullOnDelete();
            $table->foreignId('kabupaten_id')->nullable()->constrained('kabupaten')->nullOnDelete();
            $table->text('alamat')->nullable();
            $table->string('kode_pos', 15)->nullable();
            $table->string('sektor')->nullable();
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('perusahaan');
    }
};
