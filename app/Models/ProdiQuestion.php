<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

/**
 * Model untuk Butir Pertanyaan Kuesioner Program Studi.
 */
class ProdiQuestion extends Model
{
    use HasFactory;

    protected $table = 'prodi_question';

    protected $fillable = [
        'prodi_id',
        'prodi_question_section_id',
        'code',
        'question_text',
        'type',
        'is_required',
        'order',
    ];

    protected $casts = [
        'is_required' => 'boolean',
    ];

    public function prodi()
    {
        return $this->belongsTo(Prodi::class, 'prodi_id');
    }

    public function section()
    {
        return $this->belongsTo(ProdiQuestionSection::class, 'prodi_question_section_id');
    }

    public function options()
    {
        return $this->hasMany(ProdiQuestionOption::class, 'prodi_question_id')->orderBy('order');
    }

    public function responses()
    {
        return $this->hasMany(ProdiResponse::class, 'prodi_question_id');
    }
}
