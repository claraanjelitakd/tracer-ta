<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

/**
 * Model Perusahaan
 *
 * Mengelola data master institusi/perusahaan tempat alumni bekerja.
 */
class Perusahaan extends Model
{
    use HasFactory;

    protected $table = 'perusahaan';

    protected $fillable = [
        'nama_perusahaan',
        'propinsi_id',
        'kabupaten_id',
        'alamat',
        'kode_pos',
        'sektor',
        'skala',
        'status_verifikasi',
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

    public function kabupaten()
    {
        return $this->belongsTo(Kabupaten::class, 'kabupaten_id');
    }

    public function biodata()
    {
        return $this->hasMany(Biodata::class, 'perusahaan_id');
    }
}
