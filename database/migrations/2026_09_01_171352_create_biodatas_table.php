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
        Schema::create('biodatas', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained()->cascadeOnDelete();

            // Relasi ke tabel data_akademiks (NIM)
            $table->string('nim')->unique()->comment('NIM - referensi ke data_akademiks');
            $table->foreign('nim')->references('nim')->on('data_akademiks')->cascadeOnDelete();

            // Program Studi & Periode
            $table->foreignId('prodi_id')->nullable()->constrained('prodis')->nullOnDelete();
            $table->string('kode_prodi', 10)->nullable();
            $table->string('tahun_lulus', 10)->nullable();

            // Identitas & Kontak Aktif Alumni
            $table->string('nama')->nullable();
            $table->string('nomor_telepon')->nullable();
            $table->string('email')->nullable();
            $table->string('email_pribadi')->nullable();
            $table->text('alamat')->nullable();
            $table->foreignId('kabupaten_id')->nullable()->constrained('kabupatens')->nullOnDelete();
            $table->foreignId('provinsi_id')->nullable()->constrained('provinces')->nullOnDelete();
            $table->string('kelurahan')->nullable();
            $table->string('kecamatan')->nullable();
            $table->string('kode_pos')->nullable();
            $table->string('agama')->nullable();

            // Dokumen Kependudukan & Pajak
            $table->string('nik', 20)->nullable();
            $table->string('no_kk', 20)->nullable();
            $table->string('no_bpjs', 30)->nullable();
            $table->string('npwp', 30)->nullable()->comment('Nomor Pokok Wajib Pajak');

            // Media Sosial & Jejaring Profesional
            $table->string('instagram_url')->nullable();
            $table->string('facebook_url')->nullable();
            $table->string('linkedin_url')->nullable();
            $table->string('linkedin_username')->nullable();

            // Profesional, Karier & Keahlian
            $table->string('expert')->nullable()->comment('Keahlian Spesifik');
            $table->string('minat')->nullable()->comment('Minat/Interest');
            $table->foreignId('company_id')->nullable()->constrained('companies')->nullOnDelete()->comment('Perusahaan tempat bekerja');
            $table->string('posisi_jabatan')->nullable();
            $table->string('jenis_pekerjaan')->nullable();
            $table->string('zipcode')->nullable()->comment('Kode Pos Wilayah Kerja');

            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('biodatas');
    }
};
