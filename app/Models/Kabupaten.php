<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

/**
 * Model Kabupaten
 *
 * Mengelola data master wilayah kabupaten/kota di Indonesia.
 */
class Kabupaten extends Model
{
    use HasFactory;

    protected $table = 'kabupaten';

    protected $fillable = [
        'propinsi_id',
        'kode_kabupaten',
        'nama_kabupaten',
    ];

    protected $appends = ['province_id'];

    public function getProvinceIdAttribute()
    {
        return $this->attributes['propinsi_id'] ?? null;
    }

    public function propinsi()
    {
        return $this->belongsTo(Propinsi::class, 'propinsi_id');
    }

    public function perusahaan()
    {
        return $this->hasMany(Perusahaan::class, 'kabupaten_id');
    }
}
