<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('data_akademiks', function (Blueprint $table) {
            $table->id();
            // NIM sebagai primary identifier
            $table->string('nim')->unique();

            // Identitas Dasar
            $table->string('nama')->nullable();
            $table->string('angkatan_masuk')->nullable();
            $table->string('tempat_lahir')->nullable();
            $table->date('tanggal_lahir')->nullable();
            $table->string('agama')->nullable();
            $table->enum('jenis_kelamin', ['Laki-laki', 'Perempuan'])->nullable();
            $table->string('golongan_darah', 5)->nullable();
            $table->string('warga_negara')->default('WNI');

            // Kontak & Alamat
            $table->string('nomor_telepon')->nullable();
            $table->string('email_pribadi')->nullable();
            $table->string('email_students')->nullable();
            $table->text('alamat_saat_ini')->nullable();
            $table->string('kelurahan')->nullable();
            $table->string('kecamatan')->nullable();
            $table->foreignId('kabupaten_id')->nullable()->constrained('kabupatens')->nullOnDelete();
            $table->foreignId('provinsi_id')->nullable()->constrained('provinces')->nullOnDelete();
            $table->string('kode_pos')->nullable();

            // Identitas Negara/Institusi
            $table->string('nik', 20)->nullable()->comment('Nomor KTP');
            $table->string('no_kk', 20)->nullable()->comment('Nomor Kartu Keluarga');
            $table->string('nisn', 20)->nullable()->comment('Nomor Induk Siswa Nasional');
            $table->string('no_bpjs', 30)->nullable()->comment('Nomor BPJS Kesehatan');

            // Asal Sekolah Menengah
            $table->string('asal_sekolah')->nullable();
            $table->text('alamat_asal_sekolah')->nullable();
            $table->string('kota_kabupaten_asal_sekolah')->nullable();
            $table->string('provinsi_asal_sekolah')->nullable();
            $table->string('jurusan_asal_sekolah')->nullable();

            // Data Kelulusan Akademik
            $table->char('status_mahasiswa', 2)->default('AR')->comment('AR, DO, CT, NA');
            $table->string('tahun_akademik_lulus')->nullable()->comment('Periode lulus, contoh: Gasal 2026/2027');
            $table->year('tahun_lulus')->nullable();
            $table->decimal('ip_kumulatif', 3, 2)->nullable()->comment('Indeks Prestasi Kumulatif');
            $table->integer('total_sks')->nullable();
            $table->decimal('total_angka_kualitas', 8, 2)->nullable();

            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('data_akademiks');
    }
};
