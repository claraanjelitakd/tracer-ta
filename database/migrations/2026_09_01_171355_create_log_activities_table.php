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
                $table->id();

                // Pengguna yang melakukan aksi (bisa null jika dilakukan sistem / tamu)
                $table->foreignId('user_id')->nullable()->constrained('users')->nullOnDelete();

                // Jenis aksi audit (e.g. ACC_VERIFIKASI_PERUSAHAAN, GANTI_PERUSAHAAN, REJECT_PERUSAHAAN, RESET_PASSWORD_DOB, UPDATE_USER)
                $table->string('action', 100);

                // Entitas target (Polymorphic-friendly)
                $table->string('model_type')->nullable();
                $table->unsignedBigInteger('model_id')->nullable();

                // Deskripsi detail aktivitas
                $table->text('description');

                // Payload perubahan (data lama vs data baru dalam JSON)
                $table->json('old_values')->nullable();
                $table->json('new_values')->nullable();

                // Informasi jaringan & perangkat klien
                $table->string('ip_address', 45)->nullable();
                $table->text('user_agent')->nullable();

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
