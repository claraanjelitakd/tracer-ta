<?php

namespace App\Models;

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
        'kode_pertanyaan',
        'subpertanyaan',
        'type',
        'keterangan',
        'wajib',
        'tampil_di',
        'order',
    ];

    protected $casts = [
        'wajib' => 'boolean',
    ];

    protected static function booted()
    {
        static::saving(function ($subpertanyaan) {
            if (empty($subpertanyaan->attributes['kelompok'])) {
                $subpertanyaan->attributes['kelompok'] = 'F1';
            }
        });
    }

    /**
     * Relasi ke kelompok pertanyaan (KelompokPertanyaan).
     */
    public function kelompokPertanyaan()
    {
        return $this->belongsTo(KelompokPertanyaan::class, 'kelompok_pertanyaan_id');
    }

    /**
     * Relasi ke opsi jawaban (RefSubpertanyaanDetil).
     */
    public function detils()
    {
        return $this->hasMany(RefSubpertanyaanDetil::class, 'pertanyaan_id')->orderBy('order');
    }

    /**
     * Relasi ke jawaban alumni di tabel tracers.
     */
    public function tracers()
    {
        return $this->hasMany(Tracer::class, 'question_id');
    }
}
