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
        if (! Schema::hasTable('biodata')) {
            Schema::create('biodata', function (Blueprint $table) {
                $table->id();
                $table->foreignId('user_id')->constrained()->cascadeOnDelete();

                // Relasi ke tabel data_akademik (NIM)
                $table->string('nim')->unique()->comment('NIM - referensi ke data_akademik');
                $table->foreign('nim')->references('nim')->on('data_akademik')->cascadeOnDelete();

                // Relasi Tambahan ke entitas akademik
                $table->foreignId('data_akademik_id')->nullable()->constrained('data_akademik')->nullOnDelete();
                $table->foreignId('orang_tua_id')->nullable()->constrained('data_orang_tua')->nullOnDelete();
                $table->foreignId('yudisium_id')->nullable()->constrained('yudisium')->nullOnDelete();

                // Program Studi & Periode
                $table->foreignId('prodi_id')->nullable()->constrained('prodi')->nullOnDelete();
                $table->string('tahun_lulus', 10)->nullable();

                // Identitas & Kontak Aktif Alumni
                $table->string('nama')->nullable();
                $table->string('nomor_telepon')->nullable();
                $table->string('email')->nullable();
                $table->string('email_pribadi')->nullable();
                $table->string('email_students')->nullable();
                $table->text('alamat')->nullable();
                $table->foreignId('kabupaten_id')->nullable()->constrained('kabupaten')->nullOnDelete();
                $table->foreignId('propinsi_id')->nullable()->constrained('propinsi')->nullOnDelete();
                $table->string('kelurahan')->nullable();
                $table->string('kecamatan')->nullable();
                $table->string('kode_pos')->nullable();
                $table->string('agama')->nullable();

                // Data Pribadi Lengkap
                $table->string('tempat_lahir')->nullable();
                $table->date('tanggal_lahir')->nullable();
                $table->string('jenis_kelamin', 20)->nullable();
                $table->string('golongan_darah', 10)->nullable();
                $table->string('warga_negara', 50)->nullable()->default('WNI');

                // Dokumen Kependudukan & Pajak
                $table->string('nik', 20)->nullable();
                $table->string('no_kk', 20)->nullable();
                $table->string('no_bpjs', 30)->nullable();
                $table->string('nisn', 30)->nullable();
                $table->string('npwp', 30)->nullable()->comment('Nomor Pokok Wajib Pajak');

                // Media Sosial & Jejaring Profesional
                $table->text('instagram_url')->nullable();
                $table->text('facebook_url')->nullable();
                $table->text('linkedin_url')->nullable();
                $table->text('linkedin_username')->nullable();

                // Profesional, Karier & Keahlian
                $table->text('expert')->nullable()->comment('Keahlian Spesifik');
                $table->text('minat')->nullable()->comment('Minat/Interest');
                $table->foreignId('perusahaan_id')->nullable()->constrained('perusahaan')->nullOnDelete()->comment('Perusahaan tempat bekerja');
                $table->foreignId('atasan_id')->nullable()->constrained('atasan')->nullOnDelete()->comment('Atasan langsung alumni');
                $table->string('kategori_pekerjaan')->nullable()->comment('Pekerja, Wiraswasta, Melanjutkan Pendidikan');
                $table->string('posisi_jabatan', 500)->nullable();
                $table->string('posisi_wiraswasta', 500)->nullable();
                $table->string('pendidikan_tingkat', 500)->nullable();
                $table->string('perguruan_tinggi', 500)->nullable();
                $table->string('pendidikan_prodi', 500)->nullable();
                $table->decimal('gaji', 15, 2)->nullable();
                $table->string('jenis_pekerjaan', 500)->nullable();
                $table->string('zipcode')->nullable()->comment('Kode Pos Wilayah Kerja');

                $table->timestamps();
            });
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('biodata');
    }
};
