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
                $table->id();

                // Relasi ke User Login (bisa null jika belum dibuatkan akun)
                $table->foreignId('user_id')->nullable()->constrained()->nullOnDelete();

                $table->string('nama')->comment('Nama Atasan');
                $table->string('email')->nullable()->comment('Email Atasan');
                $table->string('telepon')->nullable()->comment('Nomor Telepon Atasan');

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
