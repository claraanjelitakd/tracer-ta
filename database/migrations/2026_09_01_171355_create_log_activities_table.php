<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     *
     * Tabel log_activity untuk audit trail menyeluruh:
     * Menyimpan setiap aktivitas penting seperti verifikasi perusahaan (ACC, Tolak, Ganti Master),
     * reset password, pembaruan akun, dan pengubahan profil.
     */
    public function up(): void
    {
        if (! Schema::hasTable('log_activities')) {
            Schema::create('log_activities', function (Blueprint $table) {
                $table->id()->comment('ID unik rekam jejak aktivitas audit');

                // Pengguna yang melakukan aksi (bisa null jika dilakukan sistem / tamu)
                $table->foreignId('user_id')->nullable()->constrained('users')->nullOnDelete()->comment('Relasi ke user pelaku aktivitas');

                // Jenis aksi audit (e.g. ACC_VERIFIKASI_PERUSAHAAN, GANTI_PERUSAHAAN, REJECT_PERUSAHAAN, RESET_PASSWORD_DOB, UPDATE_USER)
                $table->string('action', 100)->comment('Kode jenis aktivitas (e.g. GANTI_PERUSAHAAN_MASTER)');

                // Entitas target (Polymorphic-friendly)
                $table->string('model_type', 100)->nullable()->comment('Nama kelas model target aktivitas');
                $table->unsignedBigInteger('model_id')->nullable()->comment('ID record model target aktivitas');

                // Deskripsi detail aktivitas
                $table->text('description')->comment('Uraian naratif lengkap aktivitas sistem');

                // Payload perubahan (data lama vs data baru dalam JSON)
                $table->json('old_values')->nullable()->comment('Nilai atribut sebelum perubahan (JSON)');
                $table->json('new_values')->nullable()->comment('Nilai atribut sesudah perubahan (JSON)');

                // Informasi jaringan & perangkat klien
                $table->string('ip_address', 45)->nullable()->comment('Alamat IP perangkat pengakses');
                $table->text('user_agent')->nullable()->comment('User agent browser atau aplikasi pengakses');

                $table->timestamps();

                // Index untuk pencarian & filtering riwayat audit yang cepat
                $table->index(['action', 'created_at']);
                $table->index(['model_type', 'model_id']);
            });
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('log_activities');
    }
};
