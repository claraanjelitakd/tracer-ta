<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Migrasi Penambahan Kolom Identitas, Gaji, dan Relasi ke Tabel biodata
 *
 * Lokasi: database/migrations/2026_09_19_213200_add_profile_fields_and_relations_to_biodata_table.php
 * Fungsi: Memastikan seluruh data identitas alumni yang dapat dimutasi, data Take Home Pay,
 * serta relasi data orang tua dan yudisium terintegrasi langsung pada tabel biodata.
 */
return new class extends Migration
{
    /**
     * Jalankan migrasi.
     */
    public function up(): void
    {
        Schema::table('biodata', function (Blueprint $table) {
            // Relasi ke Data Orang Tua & Yudisium
            if (! Schema::hasColumn('biodata', 'orang_tua_id')) {
                $table->foreignId('orang_tua_id')->nullable()->after('nim')->constrained('data_orang_tua')->nullOnDelete()->comment('Relasi ke tabel data_orang_tua');
            }
            if (! Schema::hasColumn('biodata', 'yudisium_id')) {
                $table->foreignId('yudisium_id')->nullable()->after('orang_tua_id')->constrained('yudisium')->nullOnDelete()->comment('Relasi ke tabel yudisium');
            }

            // Identitas Pribadi Tambahan yang dapat diubah alumni
            if (! Schema::hasColumn('biodata', 'tempat_lahir')) {
                $table->string('tempat_lahir')->nullable()->after('nama');
            }
            if (! Schema::hasColumn('biodata', 'tanggal_lahir')) {
                $table->date('tanggal_lahir')->nullable()->after('tempat_lahir');
            }
            if (! Schema::hasColumn('biodata', 'jenis_kelamin')) {
                $table->string('jenis_kelamin', 20)->nullable()->after('tanggal_lahir');
            }
            if (! Schema::hasColumn('biodata', 'golongan_darah')) {
                $table->string('golongan_darah', 5)->nullable()->after('jenis_kelamin');
            }
            if (! Schema::hasColumn('biodata', 'warga_negara')) {
                $table->string('warga_negara', 50)->default('WNI')->after('golongan_darah');
            }

            // Dokumen Kependudukan & Pendidikan
            if (! Schema::hasColumn('biodata', 'nisn')) {
                $table->string('nisn', 20)->nullable()->after('nik');
            }

            // Penghasilan / Take Home Pay (Single Field dari Kuesioner F505)
            if (! Schema::hasColumn('biodata', 'gaji')) {
                $table->unsignedBigInteger('gaji')->nullable()->after('posisi_jabatan')->comment('Take Home Pay / Penghasilan Bulanan (dalam Rupiah penuh atau ribuan)');
            }
        });
    }

    /**
     * Batalkan migrasi.
     */
    public function down(): void
    {
        Schema::table('biodata', function (Blueprint $table) {
            if (Schema::hasColumn('biodata', 'orang_tua_id')) {
                $table->dropForeign(['orang_tua_id']);
                $table->dropColumn('orang_tua_id');
            }
            if (Schema::hasColumn('biodata', 'yudisium_id')) {
                $table->dropForeign(['yudisium_id']);
                $table->dropColumn('yudisium_id');
            }

            $columnsToDrop = [
                'tempat_lahir',
                'tanggal_lahir',
                'jenis_kelamin',
                'golongan_darah',
                'warga_negara',
                'nisn',
                'gaji',
            ];

            foreach ($columnsToDrop as $col) {
                if (Schema::hasColumn('biodata', $col)) {
                    $table->dropColumn($col);
                }
            }
        });
    }
};
