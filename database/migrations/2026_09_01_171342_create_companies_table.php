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
            $table->id()->comment('ID unik perusahaan');
            $table->string('nama_perusahaan', 500)->comment('Nama resmi perusahaan / lembaga / institusi');
            $table->foreignId('propinsi_id')->nullable()->constrained('propinsi')->nullOnDelete()->comment('Relasi ke provinsi lokasi kantor');
            $table->foreignId('kabupaten_id')->nullable()->constrained('kabupaten')->nullOnDelete()->comment('Relasi ke kabupaten/kota lokasi kantor');
            $table->text('alamat')->nullable()->comment('Alamat lengkap kantor perusahaan');
            $table->string('homepage')->nullable()->comment('Website / URL homepage resmi instansi');
            $table->string('no_telp_fax', 100)->nullable()->comment('Nomor telepon atau fax resmi instansi');
            $table->string('kode_pos', 15)->nullable()->comment('Kode pos kantor instansi');
            $table->string('sektor')->nullable()->comment('Sektor bidang industri / usaha');
            $table->string('skala')->nullable()->default('Nasional')->comment('Skala usaha: Lokal, Nasional, Internasional');
            $table->string('bentuk_perusahaan', 100)->nullable()->comment('Bentuk instansi: BUMN, Perusahaan Terbatas, Koperasi, CV, Firma');
            $table->string('jumlah_pegawai', 100)->nullable()->comment('Rentang jumlah pegawai keseluruhan: < 50 Orang, 51 - 100 Orang, dll');
            $table->enum('status_verifikasi', ['Menunggu Verifikasi', 'Terverifikasi', 'Ditolak'])->default('Menunggu Verifikasi')->comment('Status verifikasi data instansi oleh admin');
            $table->enum('jenis_lokasi', ['Dalam Negeri', 'Luar Negeri'])->default('Dalam Negeri')->comment('Lokasi operasional instansi');
            $table->string('negara')->default('Indonesia')->comment('Negara kedudukan instansi');
            $table->string('jenis_perusahaan')->nullable()->comment('Kategori: Instansi pemerintah, BUMN, Swasta, Organisasi non-profit, dll');
            $table->text('jenis_perusahaan_lainnya')->nullable()->comment('Keterangan jenis instansi jika opsi lainnya');
            $table->foreignId('created_by_user_id')->nullable()->constrained('users')->nullOnDelete()->comment('User pembuat record perusahaan');
            $table->foreignId('created_by_prodi_id')->nullable()->index()->comment('Prodi pembuat record');
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
