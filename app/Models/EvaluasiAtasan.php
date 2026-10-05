<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

/**
 * EvaluasiAtasan Model
 *
 * Mengelola data respon kuesioner evaluasi tingkat kepuasan dan kinerja lulusan dari Atasan/Pengguna Lulusan UKDW.
 */
class EvaluasiAtasan extends Model
{
    use HasFactory;

    protected $table = 'evaluasi_atasan';

    protected $fillable = [
        'biodata_id',
        'perusahaan_id',
        'atasan_id',
        'token',
        'is_submitted',
        'submitted_at',
        'jumlah_alumni_ukdw',
        'standar_gaji_pertama',
    ];

    protected $casts = [
        'is_submitted' => 'boolean',
        'submitted_at' => 'datetime',
    ];

    /**
     * Relasi ke model Biodata Alumni
     */
    public function biodata(): BelongsTo
    {
        return $this->belongsTo(Biodata::class, 'biodata_id');
    }

    /**
     * Relasi ke model Perusahaan
     */
    public function perusahaan(): BelongsTo
    {
        return $this->belongsTo(Perusahaan::class, 'perusahaan_id');
    }

    /**
     * Relasi ke model Atasan
     */
    public function atasan(): BelongsTo
    {
        return $this->belongsTo(Atasan::class, 'atasan_id');
    }

    /**
     * Relasi ke jawaban butir dinamis
     */
    public function respons(): HasMany
    {
        return $this->hasMany(ResponEvaluasiAtasan::class, 'evaluasi_atasan_id');
    }
}
