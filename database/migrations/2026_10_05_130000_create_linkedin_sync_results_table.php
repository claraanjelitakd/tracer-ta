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
                $table->id();
                $table->foreignId('biodata_id')->constrained('biodata')->cascadeOnDelete();
                $table->text('linkedin_url');
                $table->string('linkedin_username')->nullable();
                $table->json('scraped_data');
                $table->enum('status', ['pending', 'approved', 'rejected'])->default('pending');
                $table->foreignId('reviewed_by')->nullable()->constrained('users')->nullOnDelete();
                $table->timestamp('reviewed_at')->nullable();
                $table->timestamp('scraped_at')->nullable();
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
