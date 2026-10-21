<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     *
     * Tabel linkedin_sync_results:
     * Menyimpan data staging & histori hasil scraping profil LinkedIn eksternal (Apify).
     * Data disimpan dengan status 'pending' dan memerlukan persetujuan (approval) Super Admin
     * sebelum dapat diterapkan ke data utama alumni (tabel biodata & perusahaan).
     */
    public function up(): void
    {
        if (! Schema::hasTable('linkedin_sync_results')) {
            Schema::create('linkedin_sync_results', function (Blueprint $table) {
                $table->id()->comment('ID unik hasil sinkronisasi LinkedIn');
                $table->foreignId('biodata_id')->constrained('biodata')->cascadeOnDelete()->comment('Relasi ke biodata alumni target');
                $table->string('linkedin_url', 150)->comment('URL profil LinkedIn yang disinkronkan');
                $table->string('linkedin_username', 100)->nullable()->comment('Username akun LinkedIn');
                $table->json('scraped_data')->comment('Payload data mentah hasil scraping LinkedIn (JSON)');
                $table->enum('status', ['pending', 'approved', 'rejected'])->default('pending')->comment('Status persetujuan hasil sinkronisasi');
                $table->foreignId('reviewed_by')->nullable()->constrained('users')->nullOnDelete()->comment('Admin yang menyetujui/menolak hasil sinkron');
                $table->timestamp('reviewed_at')->nullable()->comment('Waktu persetujuan peninjauan');
                $table->timestamp('scraped_at')->nullable()->comment('Waktu pengambilan data scraping');
                $table->timestamps();

                $table->index(['biodata_id', 'status']);
            });
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('linkedin_sync_results');
    }
};
