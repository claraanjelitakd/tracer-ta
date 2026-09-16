<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

/**
 * Model untuk Pilihan Opsi Jawaban Pertanyaan Program Studi.
 */
class ProdiQuestionOption extends Model
{
    use HasFactory;

    protected $table = 'prodi_question_option';

    protected $fillable = [
        'prodi_question_id',
        'code',
        'option_text',
        'jump_to',
        'order',
    ];

    public function question()
    {
        return $this->belongsTo(ProdiQuestion::class, 'prodi_question_id');
    }
}
