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
        if (! Schema::hasTable('atasan')) {
            Schema::create('atasan', function (Blueprint $table) {
                $table->id()->comment('ID unik data atasan langsung');

                // Relasi ke User Login (bisa null jika belum dibuatkan akun)
                $table->foreignId('user_id')->nullable()->constrained('users')->nullOnDelete()->comment('Relasi ke akun pengguna login atasan penilai');

                $table->string('nama', 100)->comment('Nama lengkap atasan penilai');
                $table->string('email', 100)->nullable()->comment('Alamat email resmi atasan');
                $table->string('telepon', 20)->nullable()->comment('Nomor telepon/WhatsApp atasan penilai');

                $table->timestamps();
            });
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('atasan');
    }
};
