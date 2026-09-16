<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

/**
 * Model Tracer (tracer)
 *
 * Mengelola data jawaban kuesioner tracer study alumni.
 */
class Tracer extends Model
{
    use HasFactory;

    protected $table = 'tracer';

    protected $fillable = [
        'biodata_id',
        'question_id',
        'nim',
        'kelompok',
        'kode_pertanyaan',
        'subpertanyaan',
        'answer',
        'answer_text',
        'answer_json',
        'keterangan',
        'tahun_lulus',
    ];

    protected $casts = [
        'answer_json' => 'array',
    ];

    protected static function booted()
    {
        static::saving(function ($tracer) {
            // Auto-populate data pendukung dari relasi jika belum terisi
            if (empty($tracer->nim) && $tracer->biodata_id) {
                $biodata = Biodata::find($tracer->biodata_id);
                if ($biodata) {
                    $tracer->nim = $biodata->nim;
                    if (empty($tracer->tahun_lulus)) {
                        $tracer->tahun_lulus = $biodata->tahun_lulus ?? $biodata->dataAkademik?->tahun_lulus;
                    }
                }
            }

            if ((empty($tracer->attributes['kode_pertanyaan']) || empty($tracer->attributes['subpertanyaan'])) && $tracer->question_id) {
                $question = RefSubpertanyaan2021::find($tracer->question_id);
                if ($question) {
                    $tracer->kode_pertanyaan = $tracer->kode_pertanyaan ?: $question->kode_pertanyaan;
                    $tracer->subpertanyaan = $tracer->attributes['subpertanyaan'] ?? $question->subpertanyaan;
                    $tracer->kelompok = $tracer->kelompok ?: ($question->kelompok ?: substr($question->kode_pertanyaan ?: 'F1', 0, 3));
                    $tracer->keterangan = $tracer->keterangan ?: $question->keterangan;
                }
            }

            if (empty($tracer->kelompok)) {
                $tracer->kelompok = substr($tracer->kode_pertanyaan ?: 'F1', 0, 3);
            }
        });
    }

    /**
     * Relasi ke biodata.
     */
    public function biodata()
    {
        return $this->belongsTo(Biodata::class, 'biodata_id');
    }

    /**
     * Relasi ke butir pertanyaan (RefSubpertanyaan2021).
     */
    public function subpertanyaan()
    {
        return $this->belongsTo(RefSubpertanyaan2021::class, 'question_id');
    }

    // Accessors & Mutators
    public function getAnswerTextAttribute()
    {
        return $this->attributes['answer'] ?? null;
    }

    public function setAnswerTextAttribute($value)
    {
        $this->attributes['answer'] = $value;
    }
}
