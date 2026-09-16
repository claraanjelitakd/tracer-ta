<?php

namespace App\Models;

use Database\Factories\UmpFactory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * Model Ump
 *
 * Mengelola data Upah Minimum Provinsi (UMP) per wilayah dan tahun.
 */
class Ump extends Model
{
    /** @use HasFactory<UmpFactory> */
    use HasFactory;

    protected $table = 'ump';

    protected $fillable = [
        'kode_provinsi',
        'tahun',
        'besaran',
        'catatan',
    ];

    /**
     * Get the attributes that should be cast.
     *
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'tahun' => 'integer',
            'besaran' => 'decimal:2',
        ];
    }

    /**
     * Relasi ke Propinsi berdasarkan kode_provinsi.
     */
    public function propinsi(): BelongsTo
    {
        return $this->belongsTo(Propinsi::class, 'kode_provinsi', 'kode_provinsi');
    }

    public function province(): BelongsTo
    {
        return $this->propinsi();
    }
}
