<?php

namespace App\Models;

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
        'kode_kelompok',
        'title',
        'description',
        'order',
    ];

    /**
     * Relasi ke kuesioner induk (Kuesioner).
     */
    public function kuesioner()
    {
        return $this->belongsTo(Kuesioner::class, 'kuesioner_id');
    }

    /**
     * Relasi ke daftar pertanyaan di dalam kelompok ini.
     */
    public function subpertanyaans()
    {
        return $this->hasMany(RefSubpertanyaan2021::class, 'kelompok_pertanyaan_id')->orderBy('order');
    }

    /**
     * Alias relasi questions untuk kompatibilitas helper.
     */
    public function questions()
    {
        return $this->subpertanyaans();
    }
}
