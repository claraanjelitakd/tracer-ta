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
        Schema::table('perusahaan', function (Blueprint $table) {
            if (! Schema::hasColumn('perusahaan', 'created_by_user_id')) {
                $table->foreignId('created_by_user_id')->nullable()->after('jenis_perusahaan_lainnya')->constrained('users')->nullOnDelete();
            }
            if (! Schema::hasColumn('perusahaan', 'created_by_prodi_id')) {
                $table->foreignId('created_by_prodi_id')->nullable()->after('created_by_user_id')->index();
            }
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('perusahaan', function (Blueprint $table) {
            if (Schema::hasColumn('perusahaan', 'created_by_user_id')) {
                $table->dropForeign(['created_by_user_id']);
                $table->dropColumn('created_by_user_id');
            }
            if (Schema::hasColumn('perusahaan', 'created_by_prodi_id')) {
                $table->dropColumn('created_by_prodi_id');
            }
        });
    }
};
