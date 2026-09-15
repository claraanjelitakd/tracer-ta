<?php

namespace Database\Seeders;

use App\Models\Alumni;
use App\Services\Kuesioner\KuesionerSyncService;
use Illuminate\Database\Seeder;

class QuestionMappingSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        // View v_question_mappings sudah terdefinisi di migrasi database.
        // Seeder ini menyinkronkan data profil alumni (F1..F2H, BIO_*) langsung ke responses.
        foreach (Alumni::all() as $alumni) {
            KuesionerSyncService::syncProfileResponses($alumni);
        }
    }
}
