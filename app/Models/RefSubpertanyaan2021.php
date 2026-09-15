<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

/**
 * Model RefSubpertanyaan2021 (ref_subpertanyaan2021)
 *
 * Mengelola referensi butir pertanyaan Tracer Study 2021.
 */
class RefSubpertanyaan2021 extends Model
{
    use HasFactory;

    protected $table = 'ref_subpertanyaan2021';

    protected $fillable = [
        'kelompok',
        'kelompok_pertanyaan_id',
        'question_section_id',
        'kode_pertanyaan',
        'code',
        'subpertanyaan',
        'question_text',
        'type',
        'keterangan',
        'sub_detail',
        'wajib',
        'is_required',
        'order',
    ];

    protected $casts = [
        'wajib' => 'boolean',
    ];

    protected $appends = [
        'code',
        'question_text',
        'is_required',
        'question_section_id',
    ];

    public function newEloquentBuilder($query)
    {
        return new class($query) extends Builder
        {
            public function where($column, $operator = null, $value = null, $boolean = 'and')
            {
                if (is_string($column)) {
                    $column = match ($column) {
                        'code' => 'kode_pertanyaan',
                        'question_text' => 'subpertanyaan',
                        'question_section_id' => 'kelompok_pertanyaan_id',
                        'is_required' => 'wajib',
                        'sub_detail' => 'keterangan',
                        default => $column,
                    };
                }

                return parent::where($column, $operator, $value, $boolean);
            }
        };
    }

    protected static function booted()
    {
        static::saving(function ($question) {
            unset($question->attributes['code']);
            unset($question->attributes['question_text']);
            unset($question->attributes['question_section_id']);
            unset($question->attributes['is_required']);

            if (isset($question->attributes['sub_detail']) && ! isset($question->attributes['keterangan'])) {
                $question->attributes['keterangan'] = $question->attributes['sub_detail'];
            }
            unset($question->attributes['sub_detail']);

            if (empty($question->attributes['kelompok'])) {
                $question->attributes['kelompok'] = 'F1';
            }
        });
    }

    /**
     * Relasi ke kelompok pertanyaan (KelompokPertanyaan / QuestionSection).
     */
    public function kelompokPertanyaan()
    {
        return $this->belongsTo(KelompokPertanyaan::class, 'kelompok_pertanyaan_id');
    }

    /**
     * Alias relasi section untuk kompatibilitas.
     */
    public function section()
    {
        return $this->kelompokPertanyaan();
    }

    /**
     * Relasi ke opsi jawaban (RefSubpertanyaanDetil / QuestionOption).
     */
    public function detils()
    {
        return $this->hasMany(RefSubpertanyaanDetil::class, 'pertanyaan_id')->orderBy('order');
    }

    /**
     * Alias relasi options untuk kompatibilitas.
     */
    public function options()
    {
        return $this->detils();
    }

    /**
     * Relasi ke jawaban alumni di tabel tracers.
     */
    public function tracers()
    {
        return $this->hasMany(Tracer::class, 'question_id');
    }

    /**
     * Alias relasi responses untuk kompatibilitas kode lama.
     */
    public function responses()
    {
        return $this->tracers();
    }

    // Accessors & Mutators untuk kompatibilitas
    public function getCodeAttribute()
    {
        return $this->attributes['kode_pertanyaan'] ?? null;
    }

    public function setCodeAttribute($value)
    {
        $this->attributes['kode_pertanyaan'] = $value;
    }

    public function getQuestionTextAttribute()
    {
        return $this->attributes['subpertanyaan'] ?? null;
    }

    public function setQuestionTextAttribute($value)
    {
        $this->attributes['subpertanyaan'] = $value;
    }

    public function getIsRequiredAttribute()
    {
        return (bool) ($this->attributes['wajib'] ?? true);
    }

    public function setIsRequiredAttribute($value)
    {
        $this->attributes['wajib'] = $value ? 1 : 0;
    }

    public function getQuestionSectionIdAttribute()
    {
        return $this->attributes['kelompok_pertanyaan_id'] ?? null;
    }

    public function setQuestionSectionIdAttribute($value)
    {
        $this->attributes['kelompok_pertanyaan_id'] = $value;
    }

    public function getSubDetailAttribute()
    {
        return $this->attributes['keterangan'] ?? null;
    }

    public function setSubDetailAttribute($value)
    {
        $this->attributes['keterangan'] = $value;
    }
}
