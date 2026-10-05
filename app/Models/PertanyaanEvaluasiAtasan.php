<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

/**
 * Model PertanyaanEvaluasiAtasan
 *
 * Mengelola daftar butir pertanyaan instrumen kepuasan pengguna lulusan / atasan UKDW yang dapat dikonfigurasi dinamis oleh Superadmin.
 */
class PertanyaanEvaluasiAtasan extends Model
{
    use HasFactory;

    protected $table = 'pertanyaan_evaluasi_atasan';

    protected $fillable = [
        'kode',
        'aspek',
        'deskripsi',
        'kategori',
        'tipe',
        'pilihan_jawaban',
        'is_active',
        'order',
    ];

    protected $casts = [
        'is_active' => 'boolean',
        'order' => 'integer',
        'pilihan_jawaban' => 'array',
    ];

    public function respons(): HasMany
    {
        return $this->hasMany(ResponEvaluasiAtasan::class, 'pertanyaan_id');
    }
}
