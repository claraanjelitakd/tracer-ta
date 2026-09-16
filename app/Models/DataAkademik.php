<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

/**
 * Model DataAkademik
 *
 * Mengelola data master riwayat akademik mahasiswa/alumni UKDW.
 */
class DataAkademik extends Model
{
    use HasFactory;

    protected $table = 'data_akademik';

    protected $fillable = [
        'nim',
        'nama',
        'angkatan_masuk',
        'tempat_lahir',
        'tanggal_lahir',
        'agama',
        'jenis_kelamin',
        'golongan_darah',
        'warga_negara',
        'nomor_telepon',
        'email_pribadi',
        'email_students',
        'alamat_saat_ini',
        'kelurahan',
        'kecamatan',
        'kabupaten_id',
        'propinsi_id',
        'kode_pos',
        'nik',
        'no_kk',
        'nisn',
        'no_bpjs',
        'asal_sekolah',
        'alamat_asal_sekolah',
        'kota_kabupaten_asal_sekolah',
        'provinsi_asal_sekolah',
        'jurusan_asal_sekolah',
        'status_mahasiswa',
        'tahun_akademik_lulus',
        'tahun_lulus',
        'ip_kumulatif',
        'total_sks',
        'total_angka_kualitas',
    ];

    /**
     * Relasi ke model Biodata
     */
    public function biodata()
    {
        return $this->hasOne(Biodata::class, 'nim', 'nim');
    }

    public function yudisium()
    {
        return $this->hasOne(Yudisium::class, 'nim', 'nim');
    }

    /**
     * Relasi ke model DataOrangTua
     */
    public function orangTua()
    {
        return $this->hasOne(DataOrangTua::class, 'nim', 'nim');
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
