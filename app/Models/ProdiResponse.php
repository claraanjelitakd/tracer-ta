<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

/**
 * Model untuk Jawaban Alumni pada Kuesioner Program Studi.
 */
class ProdiResponse extends Model
{
    use HasFactory;

    protected $fillable = [
        'biodata_id',
        'prodi_question_id',
        'answer_text',
        'answer_json',
    ];

    protected $casts = [
        'answer_json' => 'array',
    ];

    public function biodata()
    {
        return $this->belongsTo(Biodata::class, 'biodata_id');
    }

    public function question()
    {
        return $this->belongsTo(ProdiQuestion::class, 'prodi_question_id');
    }
}
