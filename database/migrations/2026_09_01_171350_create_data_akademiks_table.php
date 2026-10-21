<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('data_akademik', function (Blueprint $table) {
            $table->id()->comment('ID unik rekam data akademik');
            // NIM sebagai primary identifier mahasiswa UKDW (8 karakter)
            $table->char('nim', 8)->unique()->comment('Nomor Induk Mahasiswa (8 karakter unik)');

            // Identitas Dasar Mahasiswa
            $table->string('nama', 100)->nullable()->comment('Nama lengkap mahasiswa');
            $table->year('angkatan_masuk')->nullable()->comment('Tahun angkatan masuk perkuliahan (YYYY)');
            $table->string('tempat_lahir', 60)->nullable()->comment('Kota/kabupaten tempat lahir');
            $table->date('tanggal_lahir')->nullable()->comment('Tanggal lahir');
            $table->enum('agama', ['Islam', 'Kristen', 'Kristen Protestan', 'Katolik', 'Hindu', 'Buddha', 'Konghucu', 'Lainnya'])->nullable()->comment('Agama yang dianut');
            $table->enum('jenis_kelamin', ['Laki-laki', 'Perempuan'])->nullable()->comment('Jenis kelamin');
            $table->string('golongan_darah', 5)->nullable()->comment('Golongan darah (A, B, AB, O)');
            $table->string('warga_negara', 10)->default('WNI')->comment('Kewarganegaraan (WNI/WNA)');

            // Kontak & Alamat Asal Mahasiswa
            $table->string('nomor_telepon', 20)->nullable()->comment('Nomor telepon/HP mahasiswa');
            $table->string('email_pribadi', 100)->nullable()->comment('Alamat email pribadi mahasiswa');
            $table->string('email_students', 100)->nullable()->comment('Alamat email resmi kampus (@students.ukdw.ac.id)');
            $table->text('alamat_saat_ini')->nullable()->comment('Alamat domisili saat kuliah / asal KTP');
            $table->string('kelurahan', 50)->nullable()->comment('Kelurahan alamat asal KTP');
            $table->string('kecamatan', 50)->nullable()->comment('Kecamatan alamat asal KTP');
            $table->foreignId('kabupaten_id')->nullable()->constrained('kabupaten')->nullOnDelete()->comment('Relasi ke kabupaten asal KTP');
            $table->foreignId('propinsi_id')->nullable()->constrained('propinsi')->nullOnDelete()->comment('Relasi ke provinsi asal KTP');
            $table->char('kode_pos', 5)->nullable()->comment('Kode pos alamat asal KTP (5 digit)');

            // Identitas Kependudukan & Dokumen Kenegaraan
            $table->char('nik', 16)->nullable()->comment('Nomor Induk Kependudukan KTP (16 digit)');
            $table->char('no_kk', 16)->nullable()->comment('Nomor Kartu Keluarga (16 digit)');
            $table->string('nisn', 15)->nullable()->comment('Nomor Induk Siswa Nasional');
            $table->string('no_bpjs', 20)->nullable()->comment('Nomor BPJS Kesehatan');
            $table->string('npwp', 25)->nullable()->comment('Nomor Pokok Wajib Pajak');

            // Asal Sekolah Menengah
            $table->string('asal_sekolah', 100)->nullable()->comment('Nama sekolah menengah asal');
            $table->text('alamat_asal_sekolah')->nullable()->comment('Alamat lengkap sekolah menengah asal');
            $table->string('kota_kabupaten_asal_sekolah', 60)->nullable()->comment('Kota/kabupaten sekolah asal');
            $table->string('provinsi_asal_sekolah', 60)->nullable()->comment('Provinsi sekolah asal');
            $table->string('jurusan_asal_sekolah', 50)->nullable()->comment('Jurusan sekolah asal (IPA, IPS, SMK, dll)');

            // Data Kelulusan Akademik
            $table->enum('status_mahasiswa', ['Lulus', 'Aktif', 'Cuti', 'Drop Out', 'Lainnya', 'AR', 'AK', 'AS', 'L'])->default('Lulus')->comment('Status akademik mahasiswa');
            $table->string('tahun_akademik_lulus', 30)->nullable()->comment('Periode lulus akademik, contoh: Gasal 2024/2025');
            $table->year('tahun_lulus')->nullable()->comment('Tahun kelulusan resmi mahasiswa (YYYY)');
            $table->date('tanggal_lulus')->nullable()->comment('Tanggal resmi dinyatakan lulus akademik');
            $table->decimal('ip_kumulatif', 3, 2)->nullable()->comment('Indeks Prestasi Kumulatif (IPK)');
            $table->smallInteger('total_sks')->nullable()->comment('Total SKS kelulusan yang diselesaikan');
            $table->decimal('total_angka_kualitas', 8, 2)->nullable()->comment('Total angka kualitas kelulusan');

            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('data_akademik');
    }
};
