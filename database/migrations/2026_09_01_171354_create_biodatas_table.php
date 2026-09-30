<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     *
     * Tabel Biodata:
     * Menyimpan profil aktif, kontak pribadi, jejak karier, dan data tracer alumni.
     *
     * Arsitektur Data Bebas Duplikasi:
     * Seluruh data identitas akademik master (nama, tempat/tanggal lahir, jenis kelamin,
     * agama, golongan darah, kewarganegaraan, dokumen sekolah/kependudukan lama, tahun kelulusan,
     * dan email_students) disimpan secara tunggal di tabel `data_akademik` melalui referensi kunci `nim`.
     * Tabel `biodata` hanya menggunakan `email_pribadi` untuk korespondensi aktif alumni.
     */
    public function up(): void
    {
        if (! Schema::hasTable('biodata')) {
            Schema::create('biodata', function (Blueprint $table) {
                $table->id();
                $table->foreignId('user_id')->constrained()->cascadeOnDelete();

                // Relasi tunggal ke data_akademik melalui NIM (tidak perlu duplikasi data_akademik_id)
                $table->string('nim')->unique()->comment('NIM - referensi langsung ke data_akademik');
                $table->foreign('nim')->references('nim')->on('data_akademik')->cascadeOnDelete();

                // Relasi ke entitas pendukung
                $table->foreignId('orang_tua_id')->nullable()->constrained('data_orang_tua')->nullOnDelete();
                $table->foreignId('yudisium_id')->nullable()->constrained('yudisium')->nullOnDelete();
                $table->foreignId('prodi_id')->nullable()->constrained('prodi')->nullOnDelete();

                // Foto Profil Alumni (langsung terintegrasi tanpa migrasi add_foto terpisah)
                $table->string('foto')->nullable()->comment('Path file foto profil alumni di /uploads/profile');

                // Kontak Aktif Alumni (Hanya email_pribadi, tanpa duplikasi email / email_students)
                $table->string('nomor_telepon')->nullable();
                $table->string('email_pribadi')->nullable()->comment('Email pribadi aktif alumni');

                // Domisili Saat Ini
                $table->text('alamat')->nullable();
                $table->foreignId('kabupaten_id')->nullable()->constrained('kabupaten')->nullOnDelete();
                $table->foreignId('propinsi_id')->nullable()->constrained('propinsi')->nullOnDelete();
                $table->string('kelurahan')->nullable();
                $table->string('kecamatan')->nullable();
                $table->string('kode_pos')->nullable();

                // Dokumen Kependudukan & Pajak Aktif
                $table->string('nik', 20)->nullable()->comment('NIK KTP');
                $table->string('npwp', 30)->nullable()->comment('Nomor Pokok Wajib Pajak');

                // Media Sosial & Portofolio Profesional
                $table->text('instagram_url')->nullable();
                $table->text('facebook_url')->nullable();
                $table->text('linkedin_url')->nullable();
                $table->text('linkedin_username')->nullable();

                // Keahlian & Minat
                $table->text('expert')->nullable()->comment('Keahlian spesifik');
                $table->text('minat')->nullable()->comment('Minat bidang kerja');

                // Data Karier & Tracer Pekerjaan
                $table->foreignId('perusahaan_id')->nullable()->constrained('perusahaan')->nullOnDelete()->comment('Perusahaan tempat bekerja');
                $table->foreignId('atasan_id')->nullable()->constrained('atasan')->nullOnDelete()->comment('Atasan langsung alumni');
                $table->string('kategori_pekerjaan')->nullable()->comment('Pekerja, Wiraswasta, Melanjutkan Pendidikan, dsb');
                $table->string('posisi_jabatan', 500)->nullable();
                $table->string('posisi_wiraswasta', 500)->nullable();
                $table->string('pendidikan_tingkat', 500)->nullable();
                $table->string('perguruan_tinggi', 500)->nullable();
                $table->string('pendidikan_prodi', 500)->nullable();
                $table->decimal('gaji', 15, 2)->nullable();
                $table->string('jenis_pekerjaan', 500)->nullable();
                $table->string('zipcode')->nullable()->comment('Kode pos lokasi kerja');

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
