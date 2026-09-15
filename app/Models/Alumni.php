<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Alumni extends Model
{
    use HasFactory;

    /**
     * The attributes that are mass assignable.
     *
     * @var array<int, string>
     */
    protected $fillable = [
        'user_id',
        'nim',
        'prodi_id',
        // Data Akademik Tambahan dipindah ke tabel data_akademiks

        // Data Akademik Tambahan dipindah ke tabel data_akademiks

        // Sosial Media
        'instagram_url',
        'facebook_url',
        'linkedin_url',
        'linkedin_username',

        // Profesional & Pekerjaan
        'expert',
        'minat',
        'company_id',
        'posisi_jabatan',
        'jenis_pekerjaan',
        'zipcode',
        'atasan_id', // Tambahan relasi atasan
    ];

    /**
     * Relasi ke User
     */
    public function user()
    {
        return $this->belongsTo(User::class);
    }

    /**
     * Relasi ke Atasan (Pimpinan)
     */
    public function atasan()
    {
        return $this->belongsTo(Atasan::class);
    }

    /**
     * Relasi ke master Prodi
     */
    public function prodi()
    {
        return $this->belongsTo(Prodi::class);
    }

    /**
     * Helper: Ekstrak Angkatan dan Kode Prodi dari NIM
     */
    public static function parseNim($nim)
    {
        if (strlen($nim) >= 4) {
            $kodeProdi = substr($nim, 0, 2);
            $tahunKode = substr($nim, 2, 2);

            // Konversi ke tahun (misal '24' -> 2024, '98' -> 1998)
            $angkatan = (int) $tahunKode > 50 ? 1900 + (int) $tahunKode : 2000 + (int) $tahunKode;

            return [
                'kode_prodi' => $kodeProdi,
                'angkatan' => $angkatan,
            ];
        }

        return null;
    }

    /**
     * Relasi ke DataAkademik (menggunakan NIM)
     */
    public function dataAkademik()
    {
        return $this->belongsTo(DataAkademik::class, 'nim', 'nim');
    }

    /**
     * Relasi ke Company (Perusahaan)
     */
    public function company()
    {
        return $this->belongsTo(Company::class);
    }

    /**
     * Relasi ke Tracer (Jawaban Kuesioner)
     */
    public function tracers()
    {
        return $this->hasMany(Tracer::class);
    }

    /**
     * Relasi ke Response (Alias untuk kompatibilitas)
     */
    public function responses()
    {
        return $this->tracers();
    }

    /**
     * Relasi ke Yudisium (Status Kelulusan Akademik)
     */
    public function yudisium()
    {
        return $this->hasOne(Yudisium::class, 'nim', 'nim');
    }
}
