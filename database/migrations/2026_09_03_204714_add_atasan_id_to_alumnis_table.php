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
        Schema::table('biodatas', function (Blueprint $table) {
            $table->foreignId('atasan_id')->nullable()->constrained('atasans')->nullOnDelete()->comment('Atasan / Pimpinan Tempat Bekerja');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('biodatas', function (Blueprint $table) {
            $table->dropForeign(['atasan_id']);
            $table->dropColumn('atasan_id');
        });
    }
};
