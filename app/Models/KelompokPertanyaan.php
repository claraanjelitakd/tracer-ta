<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

/**
 * Model KelompokPertanyaan (kelompok_pertanyaans)
 *
 * Mengelola seksi / kelompok pertanyaan (misal: Kelompok 1 s/d 10).
 */
class KelompokPertanyaan extends Model
{
    use HasFactory;

    protected $table = 'kelompok_pertanyaans';

    protected $fillable = [
        'kuesioner_id',
        'questionnaire_id',
        'kode_kelompok',
        'title',
        'description',
        'order',
    ];

    protected $appends = [
        'questionnaire_id',
    ];

    public function newEloquentBuilder($query)
    {
        return new class($query) extends Builder
        {
            public function where($column, $operator = null, $value = null, $boolean = 'and')
            {
                if (is_string($column) && $column === 'questionnaire_id') {
                    $column = 'kuesioner_id';
                }

                return parent::where($column, $operator, $value, $boolean);
            }
        };
    }

    protected static function booted()
    {
        static::saving(function ($section) {
            if (isset($section->attributes['questionnaire_id'])) {
                if (empty($section->kuesioner_id)) {
                    $section->kuesioner_id = $section->attributes['questionnaire_id'];
                }
                unset($section->attributes['questionnaire_id']);
            }
        });
    }

    /**
     * Relasi ke kuesioner induk (Kuesioner / Questionnaire).
     */
    public function kuesioner()
    {
        return $this->belongsTo(Kuesioner::class, 'kuesioner_id');
    }

    /**
     * Alias relasi questionnaire untuk kompatibilitas.
     */
    public function questionnaire()
    {
        return $this->kuesioner();
    }

    /**
     * Relasi ke daftar pertanyaan di dalam kelompok ini.
     */
    public function subpertanyaans()
    {
        return $this->hasMany(RefSubpertanyaan2021::class, 'kelompok_pertanyaan_id')->orderBy('order');
    }

    /**
     * Alias relasi questions untuk kompatibilitas.
     */
    public function questions()
    {
        return $this->subpertanyaans();
    }

    public function getQuestionnaireIdAttribute()
    {
        return $this->attributes['kuesioner_id'] ?? null;
    }

    public function setQuestionnaireIdAttribute($value)
    {
        $this->attributes['kuesioner_id'] = $value;
    }
}
