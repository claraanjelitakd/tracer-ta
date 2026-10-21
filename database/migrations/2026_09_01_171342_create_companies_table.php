<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     * Membuat tabel master perusahaan / instansi tempat alumni bekerja.
     */
    public function up(): void
    {
        Schema::create('perusahaan', function (Blueprint $table) {
            $table->id()->comment('ID unik master perusahaan');
            $table->string('nama_perusahaan', 150)->comment('Nama resmi perusahaan / lembaga / institusi');
            $table->foreignId('propinsi_id')->nullable()->constrained('propinsi')->nullOnDelete()->comment('Relasi ke provinsi lokasi kantor');
            $table->foreignId('kabupaten_id')->nullable()->constrained('kabupaten')->nullOnDelete()->comment('Relasi ke kabupaten/kota lokasi kantor');
            $table->text('alamat')->nullable()->comment('Alamat lengkap jalan atau gedung kantor perusahaan');
            $table->string('homepage', 150)->nullable()->comment('Website / URL homepage resmi instansi');
            $table->string('no_telp_fax', 30)->nullable()->comment('Nomor telepon atau fax resmi instansi');
            $table->string('kode_pos', 10)->nullable()->comment('Kode pos kantor instansi (hingga 10 karakter untuk domestik/internasional)');
            $table->string('sektor', 100)->nullable()->comment('Sektor bidang industri / usaha');
            $table->enum('skala', ['Lokal', 'Regional/Lokal', 'Nasional', 'Internasional'])->default('Nasional')->comment('Skala usaha: Lokal, Regional/Lokal, Nasional, Internasional');
            $table->string('bentuk_perusahaan', 60)->nullable()->comment('Bentuk instansi: BUMN, Perusahaan Terbatas, Koperasi, CV, Firma');
            $table->string('jumlah_pegawai', 60)->nullable()->comment('Rentang jumlah pegawai keseluruhan: < 50 Orang, 51 - 100 Orang, dll');
            $table->enum('status_verifikasi', ['Menunggu Verifikasi', 'Terverifikasi', 'Ditolak'])->default('Menunggu Verifikasi')->comment('Status verifikasi data instansi oleh admin');
            $table->enum('jenis_lokasi', ['Dalam Negeri', 'Luar Negeri'])->default('Dalam Negeri')->comment('Lokasi operasional instansi (Dalam Negeri / Luar Negeri)');
            $table->string('negara', 100)->default('Indonesia')->comment('Negara kedudukan instansi');
            $table->string('jenis_perusahaan', 100)->nullable()->comment('Kategori Dikti F11: Instansi pemerintah, BUMN, Swasta, dll');
            $table->text('jenis_perusahaan_lainnya')->nullable()->comment('Keterangan jenis instansi jika opsi lainnya');
            $table->foreignId('created_by_user_id')->nullable()->constrained('users')->nullOnDelete()->comment('User pembuat record perusahaan');
            $table->foreignId('created_by_prodi_id')->nullable()->index()->comment('Prodi asal pengusul record perusahaan');
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
