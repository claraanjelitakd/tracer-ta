<?php

namespace Database\Seeders;

use App\Models\RefNegara;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\File;

/**
 * Seeder Master Referensi Negara Seluruh Dunia
 *
 * Membaca data dari berkas CSV daftar_negara_dunia.csv
 * dan melakukan seed ke tabel `ref_negara`.
 */
class RefNegaraSeeder extends Seeder
{
    /**
     * Jalankan seed master negara.
     */
    public function run(): void
    {
        $csvPath = database_path('data/daftar_negara_dunia.csv');
        if (! File::exists($csvPath)) {
            $csvPath = base_path('daftar_negara_dunia.csv');
        }

        if (! File::exists($csvPath)) {
            return;
        }

        $lines = file($csvPath, FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES);
        if (empty($lines)) {
            return;
        }

        // Ambil header dan parse baris data
        $header = str_getcsv(array_shift($lines));

        foreach ($lines as $line) {
            $row = str_getcsv($line);
            if (count($row) < 4) {
                continue;
            }

            $namaNegara = trim($row[0] ?? '');
            $ibuKota = trim($row[1] ?? '');
            $kodeIso2 = trim($row[2] ?? '');
            $benua = trim($row[3] ?? '');

            if (! empty($namaNegara)) {
                RefNegara::updateOrCreate(
                    ['nama_negara' => $namaNegara],
                    [
                        'ibu_kota' => $ibuKota ?: null,
                        'kode_iso2' => $kodeIso2 ?: null,
                        'benua' => $benua ?: null,
                    ]
                );
            }
        }
    }
}
