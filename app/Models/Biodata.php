<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

/**
 * Model Biodata (tabel: biodata)
 *
 * Mengelola data profil aktif alumni untuk sistem Tracer Study UKDW.
 */
class Biodata extends Model
{
    use HasFactory;

    protected $table = 'biodata';

    protected $fillable = [
        'user_id',
        'nim',
        'orang_tua_id',
        'yudisium_id',
        'prodi_id',
        'kode_prodi',
        'tahun_lulus',
        'nama',
        'tempat_lahir',
        'tanggal_lahir',
        'jenis_kelamin',
        'golongan_darah',
        'warga_negara',
        'nomor_telepon',
        'email',
        'email_pribadi',
        'alamat',
        'kabupaten_id',
        'propinsi_id',
        'kelurahan',
        'kecamatan',
        'kode_pos',
        'agama',
        'nik',
        'no_kk',
        'nisn',
        'no_bpjs',
        'npwp',
        'instagram_url',
        'facebook_url',
        'linkedin_url',
        'linkedin_username',
        'expert',
        'minat',
        'perusahaan_id',
        'atasan_id',
        'posisi_jabatan',
        'kategori_pekerjaan',
        'posisi_wiraswasta',
        'jenis_pekerjaan',
        'zipcode',
        'gaji',
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
     * Relasi ke Perusahaan tempat bekerja.
     */
    public function perusahaan()
    {
        return $this->belongsTo(Perusahaan::class, 'perusahaan_id');
    }

    /**
     * Relasi ke Propinsi tempat tinggal alumni.
     */
    public function propinsi()
    {
        return $this->belongsTo(Propinsi::class, 'propinsi_id');
    }

    /**
     * Relasi ke Kabupaten tempat tinggal alumni.
     */
    public function kabupaten()
    {
        return $this->belongsTo(Kabupaten::class, 'kabupaten_id');
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
        if ($this->yudisium_id) {
            return $this->belongsTo(Yudisium::class, 'yudisium_id');
        }

        return $this->hasOne(Yudisium::class, 'nim', 'nim');
    }

    /**
     * Relasi ke Data Orang Tua.
     */
    public function orangTua()
    {
        if ($this->orang_tua_id) {
            return $this->belongsTo(DataOrangTua::class, 'orang_tua_id');
        }

        return $this->hasOne(DataOrangTua::class, 'nim', 'nim');
    }

    /**
     * Relasi ke Tracer (Jawaban Kuesioner Universitas).
     */
    public function tracer()
    {
        return $this->hasMany(Tracer::class, 'biodata_id');
    }

    /**
     * Relasi ke Tracer (Plural alias untuk kompatibilitas template jika diperlukan).
     */
    public function tracers()
    {
        return $this->tracer();
    }

    /**
     * Relasi ke ProdiResponse (Jawaban Kuesioner Khusus Prodi).
     */
    public function prodiResponse()
    {
        return $this->hasMany(ProdiResponse::class, 'biodata_id');
    }

    /**
     * Relasi ke ProdiResponse (Plural alias).
     */
    public function prodiResponses()
    {
        return $this->prodiResponse();
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
