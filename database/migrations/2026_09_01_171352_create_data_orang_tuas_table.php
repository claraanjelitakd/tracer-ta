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
        if (! Schema::hasTable('data_orang_tua')) {
            Schema::create('data_orang_tua', function (Blueprint $table) {
                $table->id()->comment('ID unik data orang tua/wali');

                // Relasi ke DataAkademik berdasarkan NIM (CHAR 8)
                $table->char('nim', 8)->unique()->comment('NIM mahasiswa (8 karakter, FK ke data_akademik)');
                $table->foreign('nim')->references('nim')->on('data_akademik')->cascadeOnDelete();

                $table->string('nama_orang_tua', 100)->nullable()->comment('Nama lengkap orang tua / wali');
                $table->string('pekerjaan', 100)->nullable()->comment('Pekerjaan orang tua / wali');

                // Alamat & Kontak Orang Tua
                $table->text('alamat')->nullable()->comment('Alamat lengkap tempat tinggal orang tua / wali');
                $table->string('kota', 60)->nullable()->comment('Kota tempat tinggal orang tua / wali');
                $table->foreignId('kabupaten_id')->nullable()->constrained('kabupaten')->nullOnDelete()->comment('Relasi ke kabupaten/kota domisili orang tua');
                $table->foreignId('propinsi_id')->nullable()->constrained('propinsi')->nullOnDelete()->comment('Relasi ke provinsi domisili orang tua');
                $table->char('kode_pos', 5)->nullable()->comment('Kode pos domisili orang tua (5 digit)');
                $table->string('nomor_telepon', 20)->nullable()->comment('Nomor telepon/HP orang tua / wali');

                $table->timestamps();
            });
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('data_orang_tua');
    }
};
