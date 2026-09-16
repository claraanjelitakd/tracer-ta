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
            if (! Schema::hasColumn('perusahaan', 'kode_pos')) {
                $table->string('kode_pos', 15)->nullable()->after('alamat');
            }
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('perusahaan', function (Blueprint $table) {
            if (Schema::hasColumn('perusahaan', 'kode_pos')) {
                $table->dropColumn('kode_pos');
            }
        });
    }
};
