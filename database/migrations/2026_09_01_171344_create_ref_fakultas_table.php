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
        if (! Schema::hasTable('ref_fakultas')) {
            Schema::create('ref_fakultas', function (Blueprint $table) {
                $table->id()->comment('ID unik master fakultas');
                $table->string('kode_fakultas', 10)->unique()->comment('Kode resmi fakultas (misal: 7, 1)');
                $table->string('nama_fakultas', 100)->comment('Nama resmi fakultas (misal: Fakultas Teknologi Informasi)');
                $table->string('singkatan', 20)->nullable()->comment('Singkatan nama fakultas (misal: FTI, FB)');
                $table->timestamps();
            });
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('ref_fakultas');
    }
};
