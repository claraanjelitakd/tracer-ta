<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::table('perusahaan', function (Blueprint $table) {
            $table->unsignedBigInteger('created_by_user_id')
                ->nullable()
                ->after('status_verifikasi');

            $table->unsignedBigInteger('created_by_prodi_id')
                ->nullable()
                ->after('created_by_user_id');

            if (DB::getDriverName() !== 'sqlite') {
                $table->foreign('created_by_user_id')->references('id')->on('users')->nullOnDelete();
                $table->foreign('created_by_prodi_id')->references('id')->on('prodi')->nullOnDelete();
            }
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('perusahaan', function (Blueprint $table) {
            if (DB::getDriverName() !== 'sqlite') {
                $table->dropForeign(['created_by_user_id']);
                $table->dropForeign(['created_by_prodi_id']);
            }
            $table->dropColumn(['created_by_user_id', 'created_by_prodi_id']);
        });
    }
};
