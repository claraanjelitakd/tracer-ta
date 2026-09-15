<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

/**
 * Model Biodata (tabel: biodatas)
 *
 * Mengelola data profil aktif alumni untuk sistem Tracer Study UKDW.
 */
class Biodata extends Model
{
    use HasFactory;

    protected $table = 'biodatas';

    protected $fillable = [
        'user_id',
        'nim',
        'prodi_id',
        'kode_prodi',
        'tahun_lulus',
        'nama',
        'nomor_telepon',
        'email',
        'email_pribadi',
        'alamat',
        'kabupaten_id',
        'provinsi_id',
        'kelurahan',
        'kecamatan',
        'kode_pos',
        'agama',
        'nik',
        'no_kk',
        'no_bpjs',
        'npwp',
        'instagram_url',
        'facebook_url',
        'linkedin_url',
        'linkedin_username',
        'expert',
        'minat',
        'company_id',
        'atasan_id',
        'posisi_jabatan',
        'jenis_pekerjaan',
        'zipcode',
    ];

    /**
     * Relasi ke User akun login.
     */
    public function user()
    {
        return $this->belongsTo(User::class);
    }

    /**
     * Relasi ke Atasan (Pimpinan di Perusahaan).
     */
    public function atasan()
    {
        return $this->belongsTo(Atasan::class);
    }

    /**
     * Relasi ke master Program Studi.
     */
    public function prodi()
    {
        return $this->belongsTo(Prodi::class);
    }

    /**
     * Relasi ke Company (Perusahaan tempat bekerja).
     */
    public function company()
    {
        return $this->belongsTo(Company::class);
    }

    /**
     * Relasi ke DataAkademik (menggunakan NIM).
     */
    public function dataAkademik()
    {
        return $this->belongsTo(DataAkademik::class, 'nim', 'nim');
    }

    /**
     * Relasi ke Yudisium (Status Kelulusan Akademik & Dosen).
     */
    public function yudisium()
    {
        return $this->hasOne(Yudisium::class, 'nim', 'nim');
    }

    /**
     * Relasi ke Data Orang Tua (menggunakan NIM).
     */
    public function orangTua()
    {
        return $this->hasOne(DataOrangTua::class, 'nim', 'nim');
    }

    /**
     * Relasi ke Tracer (Jawaban Kuesioner Universitas).
     */
    public function tracers()
    {
        return $this->hasMany(Tracer::class, 'biodata_id');
    }

    /**
     * Relasi ke ProdiResponse (Jawaban Kuesioner Khusus Prodi).
     */
    public function prodiResponses()
    {
        return $this->hasMany(ProdiResponse::class, 'biodata_id');
    }

    /**
     * Helper: Ekstrak Angkatan dan Kode Prodi dari NIM.
     */
    public static function parseNim($nim)
    {
        if (strlen((string) $nim) >= 4) {
            $kodeProdi = substr((string) $nim, 0, 2);
            $tahunKode = substr((string) $nim, 2, 2);

            $angkatan = (int) $tahunKode > 50 ? 1900 + (int) $tahunKode : 2000 + (int) $tahunKode;

            return [
                'kode_prodi' => $kodeProdi,
                'angkatan' => $angkatan,
            ];
        }

        return null;
    }
}
