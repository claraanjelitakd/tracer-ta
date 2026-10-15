<?php

namespace App\Services\Kuesioner;

use App\Models\ProdiQuestion;
use App\Models\RefSubpertanyaan2021;

/**
 * Service untuk menyediakan opsi pilihan jawaban default saat butir pertanyaan baru dibuat
 * oleh Administrator (Super Admin maupun Admin Program Studi).
 */
class QuestionDefaultOptionsService
{
    /**
     * Dapatkan daftar teks opsi default berdasarkan tipe pertanyaan.
     *
     * @return array<int, string>
     */
    public static function getDefaultOptionTexts(string $type): array
    {
        $normalizedType = match ($type) {
            'radio' => 'single_choice',
            'checkbox' => 'multiple_choice',
            default => $type,
        };

        return match ($normalizedType) {
            'single_choice' => [
                'Pilihan 1',
                'Pilihan 2',
            ],
            'radio_text' => [
                'Pilihan 1',
                'Pilihan 2',
                'Lainnya (Tuliskan)',
            ],
            'radio_input' => [
                'Bulan sebelum lulus (Isi angka)',
                'Bulan sesudah lulus (Isi angka)',
                'Tidak mencari kerja / Tidak berlaku',
            ],
            'rating_5' => [
                '1 - Sangat Rendah',
                '2 - Rendah',
                '3 - Cukup',
                '4 - Tinggi',
                '5 - Sangat Tinggi',
            ],
            'multiple_choice' => [
                'Pilihan 1',
                'Pilihan 2',
            ],
            'dropdown' => [
                'Pilihan 1',
                'Pilihan 2',
            ],
            'searchable_select' => [
                'Pilihan 1',
                'Pilihan 2',
            ],
            'multiple_number' => [
                'Komponen Utama',
                'Komponen Tambahan',
            ],
            default => [],
        };
    }

    /**
     * Generate opsi default untuk ProdiQuestion jika belum memiliki opsi.
     */
    public static function generateForProdiQuestion(ProdiQuestion $question): void
    {
        if ($question->options()->exists()) {
            return;
        }

        $defaultTexts = self::getDefaultOptionTexts($question->type);
        if (empty($defaultTexts)) {
            return;
        }

        $order = 1;
        foreach ($defaultTexts as $idx => $text) {
            $code = $question->code.'-'.str_pad((string) ($idx + 1), 2, '0', STR_PAD_LEFT);
            $question->options()->create([
                'code' => $code,
                'option_text' => $text,
                'order' => $order++,
            ]);
        }
    }

    /**
     * Generate opsi default untuk RefSubpertanyaan2021 jika belum memiliki opsi.
     */
    public static function generateForUnivQuestion(RefSubpertanyaan2021 $question): void
    {
        if ($question->detils()->exists()) {
            return;
        }

        $defaultTexts = self::getDefaultOptionTexts($question->type);
        if (empty($defaultTexts)) {
            return;
        }

        $order = 1;
        foreach ($defaultTexts as $idx => $text) {
            $code = $question->kode_pertanyaan.'-'.str_pad((string) ($idx + 1), 2, '0', STR_PAD_LEFT);
            $question->detils()->create([
                'kode_pertanyaan' => $question->kode_pertanyaan,
                'kode_opsi' => $code,
                'option_text' => $text,
                'order' => $order++,
            ]);
        }
    }
}
