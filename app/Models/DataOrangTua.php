<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

/**
 * Model DataOrangTua
 *
 * Mengelola data kontak dan identitas orang tua mahasiswa/alumni.
 */
class DataOrangTua extends Model
{
    protected $table = 'data_orang_tua';

    protected $fillable = [
        'nim',
        'nama_orang_tua',
        'pekerjaan',
        'alamat',
        'kota',
        'kabupaten_id',
        'propinsi_id',
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

    public function propinsi()
    {
        return $this->belongsTo(Propinsi::class, 'propinsi_id');
    }

    public function kabupaten()
    {
        return $this->belongsTo(Kabupaten::class, 'kabupaten_id');
    }
}
