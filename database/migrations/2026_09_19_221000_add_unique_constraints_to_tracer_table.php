<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

/**
 * Migrasi Penambahan Constraint Unik pada Tabel Tracer
 *
 * Mencegah terjadinya duplikasi jawaban kuesioner universitas untuk alumni (NIM / biodata_id)
 * dan butir pertanyaan (question_id / kode_pertanyaan).
 * Setiap butir pertanyaan yang dijawab oleh alumni yang sama akan otomatis diperbarui (update),
 * bukan menambah baris baru.
 */
return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        // 1. Bersihkan data duplikat yang mungkin sudah ada (pertahankan record dengan ID terbesar/terbaru)
        $duplicates = DB::table('tracer')
            ->select('biodata_id', 'question_id', DB::raw('MAX(id) as max_id'), DB::raw('COUNT(*) as total'))
            ->whereNotNull('question_id')
            ->groupBy('biodata_id', 'question_id')
            ->having('total', '>', 1)
            ->get();

        foreach ($duplicates as $dup) {
            DB::table('tracer')
                ->where('biodata_id', $dup->biodata_id)
                ->where('question_id', $dup->question_id)
                ->where('id', '<', $dup->max_id)
                ->delete();
        }

        $duplicatesKode = DB::table('tracer')
            ->select('biodata_id', 'kode_pertanyaan', DB::raw('MAX(id) as max_id'), DB::raw('COUNT(*) as total'))
            ->groupBy('biodata_id', 'kode_pertanyaan')
            ->having('total', '>', 1)
            ->get();

        foreach ($duplicatesKode as $dup) {
            DB::table('tracer')
                ->where('biodata_id', $dup->biodata_id)
                ->where('kode_pertanyaan', $dup->kode_pertanyaan)
                ->where('id', '<', $dup->max_id)
                ->delete();
        }

        // 2. Tambahkan Index Unik pada tabel tracer
        Schema::table('tracer', function (Blueprint $table) {
            // Buat UNIQUE index agar database menolak duplikasi secara permanen
            $table->unique(['biodata_id', 'question_id'], 'tracer_biodata_question_unique');
            $table->unique(['biodata_id', 'kode_pertanyaan'], 'tracer_biodata_kode_pertanyaan_unique');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('tracer', function (Blueprint $table) {
            $table->dropUnique('tracer_biodata_question_unique');
            $table->dropUnique('tracer_biodata_kode_pertanyaan_unique');
        });
    }
};
