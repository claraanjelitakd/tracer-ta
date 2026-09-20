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
        if (DB::getDriverName() !== 'sqlite' && ! Schema::hasColumn('users', 'fakultas_id')) {
            Schema::table('users', function (Blueprint $table) {
                $table->foreignId('fakultas_id')->nullable()->after('prodi_id')->constrained('ref_fakultas')->nullOnDelete();
            });
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        if (DB::getDriverName() !== 'sqlite' && Schema::hasColumn('users', 'fakultas_id')) {
            Schema::table('users', function (Blueprint $table) {
                $table->dropForeign(['fakultas_id']);
                $table->dropColumn('fakultas_id');
            });
        }
    }
};
