<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class DataOrangTua extends Model
{
    protected $fillable = [
        'nim',
        'nama_orang_tua',
        'pekerjaan',
        'alamat',
        'kota',
        'kabupaten_id',
        'provinsi_id',
        'kode_pos',
        'nomor_telepon',
    ];

    /**
     * Relasi ke model DataAkademik
     */
    public function dataAkademik()
    {
        return $this->belongsTo(DataAkademik::class, 'nim', 'nim');
    }

    /**
     * Relasi ke model Biodata
     */
    public function biodata()
    {
        return $this->hasOne(Biodata::class, 'nim', 'nim');
    }
}
