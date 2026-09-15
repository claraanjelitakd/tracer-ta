<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Builder;
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
        'question_id',
        'kode_pertanyaan',
        'kode_opsi',
        'code',
        'option_text',
        'jump_to',
        'order',
    ];

    protected $appends = [
        'code',
        'question_id',
    ];

    public function newEloquentBuilder($query)
    {
        return new class($query) extends Builder
        {
            public function where($column, $operator = null, $value = null, $boolean = 'and')
            {
                if (is_string($column)) {
                    $column = match ($column) {
                        'code' => 'kode_opsi',
                        'question_id' => 'pertanyaan_id',
                        default => $column,
                    };
                }

                return parent::where($column, $operator, $value, $boolean);
            }
        };
    }

    protected static function booted()
    {
        static::saving(function ($option) {
            if (isset($option->attributes['code'])) {
                if (empty($option->kode_opsi)) {
                    $option->kode_opsi = $option->attributes['code'];
                }
                unset($option->attributes['code']);
            }
            if (isset($option->attributes['question_id'])) {
                if (empty($option->pertanyaan_id)) {
                    $option->pertanyaan_id = $option->attributes['question_id'];
                }
                unset($option->attributes['question_id']);
            }

            if (empty($option->kode_pertanyaan) && $option->pertanyaan_id) {
                $q = RefSubpertanyaan2021::find($option->pertanyaan_id);
                if ($q) {
                    $option->kode_pertanyaan = $q->kode_pertanyaan;
                }
            }
        });
    }

    /**
     * Relasi ke butir pertanyaan induk (RefSubpertanyaan2021 / Question).
     */
    public function subpertanyaan()
    {
        return $this->belongsTo(RefSubpertanyaan2021::class, 'pertanyaan_id');
    }

    /**
     * Alias relasi question untuk kompatibilitas.
     */
    public function question()
    {
        return $this->subpertanyaan();
    }

    public function getCodeAttribute()
    {
        return $this->attributes['kode_opsi'] ?? null;
    }

    public function setCodeAttribute($value)
    {
        $this->attributes['kode_opsi'] = $value;
    }

    public function getQuestionIdAttribute()
    {
        return $this->attributes['pertanyaan_id'] ?? null;
    }

    public function setQuestionIdAttribute($value)
    {
        $this->attributes['pertanyaan_id'] = $value;
    }
}
