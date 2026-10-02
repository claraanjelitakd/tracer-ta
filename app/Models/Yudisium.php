<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

/**
 * Model Yudisium (tabel: yudisium)
 *
 * Mengelola data kelulusan yudisium, tugas akhir/skripsi, dan publikasi ilmiah.
 *
 * Aturan Keterkaitan Otomatis:
 * Setiap kali `status_lulus` berubah menjadi 'Lulus', model ini secara otomatis
 * menyinkronkan status kelulusan ke tabel `data_akademik` (status_mahasiswa = 'L',
 * tanggal_lulus, tahun_lulus, dan tahun_akademik_lulus).
 */
class Yudisium extends Model
{
    use HasFactory;

    protected $table = 'yudisium';

    protected $guarded = ['id'];

    /**
     * Bootstrap model events.
     * Keterkaitan otomatis antara status yudisium dengan riwayat kelulusan data akademik.
     */
    protected static function booted()
    {
        static::saved(function (Yudisium $yudisium) {
            // Ketika status kelulusan disetujui menjadi 'Lulus'
            if ($yudisium->status_lulus === 'Lulus') {
                $dataAkademik = DataAkademik::where('nim', $yudisium->nim)->first();
                if ($dataAkademik) {
                    $updates = [
                        'status_mahasiswa' => 'L', // 'L' = Lulus
                    ];

                    // Tetapkan tahun lulus jika belum terisi
                    if (empty($dataAkademik->tahun_lulus)) {
                        $updates['tahun_lulus'] = (int) date('Y');
                    }

                    // Tetapkan tanggal kelulusan resmi
                    if (empty($dataAkademik->tanggal_lulus)) {
                        $updates['tanggal_lulus'] = now()->toDateString();
                    }

                    // Tetapkan periode tahun akademik lulus jika belum terisi
                    if (empty($dataAkademik->tahun_akademik_lulus)) {
                        $bulan = (int) date('n');
                        $tahun = (int) date('Y');
                        $semester = ($bulan >= 8 || $bulan <= 1) ? 'Gasal' : 'Genap';
                        $periodeTahun = ($bulan <= 1) ? ($tahun - 1).'/'.$tahun : $tahun.'/'.($tahun + 1);
                        $updates['tahun_akademik_lulus'] = "{$semester} {$periodeTahun}";
                    }

                    $dataAkademik->update($updates);
                }

                // Jika lulus di Yudisium, otomatis masukkan ke Biodata alumni!
                // Seluruh relasi yudisium_id, orang_tua_id, prodi_id, dan akun user
                // otomatis dirakit langsung oleh Model Biodata::booted (creating hook).
                Biodata::firstOrCreate(['nim' => $yudisium->nim]);
            }
        });
    }

    /**
     * Relasi ke Data Akademik (NIM).
     */
    public function dataAkademik()
    {
        return $this->belongsTo(DataAkademik::class, 'nim', 'nim');
    }

    /**
     * Relasi ke Biodata Alumni.
     */
    public function biodata()
    {
        return $this->hasOne(Biodata::class, 'nim', 'nim');
    }

    // =========================================================================
    // ACCESSOR & MUTATOR UNTUK MENCEGAH BREAKING CHANGE
    // =========================================================================

    /**
     * Kompatibilitas mundur dengan nama lama `proses_yudisium`.
     */
    public function getProsesYudisiumAttribute(): ?string
    {
        return $this->status_lulus ?? 'Belum';
    }

    public function setProsesYudisiumAttribute(?string $value): void
    {
        $this->attributes['status_lulus'] = $value ?? 'Belum';
    }

    /**
     * Akses tahun kelulusan resmi langsung dari relasi DataAkademik.
     */
    public function getTahunLulusAttribute(): ?int
    {
        return $this->dataAkademik?->tahun_lulus;
    }

    /**
     * Akses periode akademik lulus langsung dari relasi DataAkademik.
     */
    public function getTahunAkademikLulusAttribute(): ?string
    {
        return $this->dataAkademik?->tahun_akademik_lulus;
    }
}
