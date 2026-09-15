<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

/**
 * Model Tracer (tracers)
 *
 * Mengelola data jawaban kuesioner tracer study alumni.
 */
class Tracer extends Model
{
    use HasFactory;

    protected $table = 'tracers';

    protected $fillable = [
        'alumni_id',
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

    protected $appends = [
        'answer_text',
    ];

    public function newEloquentBuilder($query)
    {
        return new class($query) extends Builder
        {
            public function where($column, $operator = null, $value = null, $boolean = 'and')
            {
                if (is_string($column) && $column === 'answer_text') {
                    $column = 'answer';
                }

                return parent::where($column, $operator, $value, $boolean);
            }
        };
    }

    protected static function booted()
    {
        static::saving(function ($tracer) {
            unset($tracer->attributes['answer_text']);

            // Auto-populate data pendukung dari relasi jika belum terisi
            if (empty($tracer->nim) && $tracer->alumni_id) {
                $alumni = Alumni::find($tracer->alumni_id);
                if ($alumni) {
                    $tracer->nim = $alumni->nim;
                    if (empty($tracer->tahun_lulus)) {
                        $tracer->tahun_lulus = $alumni->dataAkademik?->tahun_lulus;
                    }
                }
            }

            if (empty($tracer->subpertanyaan) && $tracer->question_id) {
                $question = RefSubpertanyaan2021::find($tracer->question_id);
                if ($question) {
                    $tracer->kode_pertanyaan = $tracer->kode_pertanyaan ?: $question->kode_pertanyaan;
                    $tracer->subpertanyaan = $question->subpertanyaan;
                    $tracer->kelompok = $tracer->kelompok ?: $question->kelompok;
                    $tracer->keterangan = $tracer->keterangan ?: $question->keterangan;
                }
            }
        });
    }

    /**
     * Relasi ke alumni.
     */
    public function alumni()
    {
        return $this->belongsTo(Alumni::class);
    }

    /**
     * Relasi ke butir pertanyaan (RefSubpertanyaan2021 / Question).
     */
    public function subpertanyaanRelasi()
    {
        return $this->belongsTo(RefSubpertanyaan2021::class, 'question_id');
    }

    /**
     * Alias relasi question untuk kompatibilitas.
     */
    public function question()
    {
        return $this->subpertanyaanRelasi();
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
