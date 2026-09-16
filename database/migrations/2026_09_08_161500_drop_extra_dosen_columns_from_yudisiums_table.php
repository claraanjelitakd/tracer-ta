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
        Schema::table('yudisium', function (Blueprint $table) {
            $colsToDrop = [];
            if (Schema::hasColumn('yudisium', 'dosen_pembimbing_3')) {
                $colsToDrop[] = 'dosen_pembimbing_3';
            }
            if (Schema::hasColumn('yudisium', 'dosen_penguji_3')) {
                $colsToDrop[] = 'dosen_penguji_3';
            }
            if (Schema::hasColumn('yudisium', 'dosen_penguji_4')) {
                $colsToDrop[] = 'dosen_penguji_4';
            }
            if (! empty($colsToDrop)) {
                $table->dropColumn($colsToDrop);
            }
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('yudisium', function (Blueprint $table) {
            $table->string('dosen_pembimbing_3')->nullable();
            $table->string('dosen_penguji_3')->nullable();
            $table->string('dosen_penguji_4')->nullable();
        });
    }
};
