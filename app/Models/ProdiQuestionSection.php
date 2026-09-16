<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

/**
 * Model untuk Bagian/Section Kuesioner Program Studi.
 */
class ProdiQuestionSection extends Model
{
    use HasFactory;

    protected $table = 'prodi_question_section';

    protected $fillable = [
        'prodi_id',
        'title',
        'description',
        'order',
    ];

    public function prodi()
    {
        return $this->belongsTo(Prodi::class, 'prodi_id');
    }

    public function questions()
    {
        return $this->hasMany(ProdiQuestion::class, 'prodi_question_section_id')->orderBy('order');
    }
}
