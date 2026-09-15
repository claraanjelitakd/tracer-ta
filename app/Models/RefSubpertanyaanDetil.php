<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

/**
 * Model RefSubpertanyaanDetil (ref_subpertanyaan_detil)
 *
 * Mengelola opsi jawaban untuk setiap butir pertanyaan kuesioner.
 */
class RefSubpertanyaanDetil extends Model
{
    use HasFactory;

    protected $table = 'ref_subpertanyaan_detil';

    protected $fillable = [
        'pertanyaan_id',
        'kode_pertanyaan',
        'kode_opsi',
        'option_text',
        'jump_to',
        'order',
    ];

    protected static function booted()
    {
        static::saving(function ($option) {
            if (empty($option->kode_pertanyaan) && $option->pertanyaan_id) {
                $q = RefSubpertanyaan2021::find($option->pertanyaan_id);
                if ($q) {
                    $option->kode_pertanyaan = $q->kode_pertanyaan;
                }
            }
        });
    }

    /**
     * Relasi ke butir pertanyaan induk (RefSubpertanyaan2021).
     */
    public function subpertanyaan()
    {
        return $this->belongsTo(RefSubpertanyaan2021::class, 'pertanyaan_id');
    }
}
