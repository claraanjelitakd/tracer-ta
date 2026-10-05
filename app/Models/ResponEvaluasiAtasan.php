<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * Model ResponEvaluasiAtasan
 *
 * Menyimpan respon jawaban instrumen evaluasi atasan terhadap alumni secara butir per butir.
 */
class ResponEvaluasiAtasan extends Model
{
    use HasFactory;

    protected $table = 'respon_evaluasi_atasan';

    protected $fillable = [
        'evaluasi_atasan_id',
        'pertanyaan_id',
        'nilai',
        'catatan',
    ];

    public function evaluasiAtasan(): BelongsTo
    {
        return $this->belongsTo(EvaluasiAtasan::class, 'evaluasi_atasan_id');
    }

    public function pertanyaan(): BelongsTo
    {
        return $this->belongsTo(PertanyaanEvaluasiAtasan::class, 'pertanyaan_id');
    }
}
