<?php

namespace App\Services\LinkedIn;

use App\Models\Biodata;
use App\Models\LogActivity;
use App\Services\LinkedIn\Contracts\LinkedInProfileProvider;
use App\Services\LinkedIn\Exceptions\LinkedInProfileNotFoundException;
use App\Services\LinkedIn\Mappers\LinkedInProfileMapper;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Throwable;

/**
 * Class LinkedInProfileService
 *
 * Layanan utama pengelola orkestrasi sinkronisasi profil LinkedIn.
 * Mengkoordinasikan pemanggilan provider, pemetaan data melalui mapper,
 * manajemen transaksi per-alumni, serta pencatatan audit trail (LogActivity).
 */
class LinkedInProfileService
{
    public function __construct(
        protected LinkedInProfileProvider $provider,
        protected LinkedInProfileMapper $mapper,
    ) {}

    /**
     * Melakukan sinkronisasi data LinkedIn untuk satu alumni.
     * Dijalankan di dalam transaksi per-alumni yang terisolasi.
     *
     * @return array{
     *     status: 'SUCCESS'|'NOT_FOUND'|'SKIPPED'|'ERROR',
     *     message: string,
     *     summary: array,
     *     changes: array,
     *     alumni_id: int
     * }
     */
    public function syncBiodata(Biodata $biodata): array
    {
        $username = trim($biodata->linkedin_username ?? '');

        // 1. Skenario: Username belum terisi
        if (empty($username)) {
            return [
                'status' => 'SKIPPED',
                'message' => 'LinkedIn username belum tersedia.',
                'summary' => [],
                'changes' => [],
                'alumni_id' => $biodata->id,
            ];
        }

        // Catat nilai lama sebelum pembaruan (untuk audit trail)
        $oldValues = [
            'posisi_jabatan' => $biodata->posisi_jabatan,
            'perusahaan_id' => $biodata->perusahaan_id,
            'nama_perusahaan' => $biodata->perusahaan?->nama_perusahaan,
            'linkedin_url' => $biodata->linkedin_url,
        ];

        DB::beginTransaction();
        try {
            // Ambil data profil dari provider (abstraksi - tidak peduli mock atau live)
            $profile = $this->provider->findByUsername($username);

            if (! $profile) {
                throw new LinkedInProfileNotFoundException($username);
            }

            // Petakan profil ke atribut model aplikasi
            $mapped = $this->mapper->mapToBiodata($biodata, $profile);

            // Simpan perubahan ke tabel biodata jika ada perubahan
            if (! empty($mapped['biodata_updates'])) {
                $biodata->update($mapped['biodata_updates']);
            }

            $newValues = [
                'status' => 'Berhasil',
                'posisi_jabatan' => $biodata->fresh()->posisi_jabatan,
                'perusahaan_id' => $biodata->fresh()->perusahaan_id,
                'nama_perusahaan' => $mapped['perusahaan']?->nama_perusahaan,
                'changes' => $mapped['changes_summary'],
            ];

            // Catat log aktivitas audit trail sukses (JANGAN simpan kredensial/rahasia)
            LogActivity::record(
                action: 'linkedin_sync',
                description: "Sinkronisasi profil LinkedIn berhasil untuk alumni {$biodata->nama} ({$biodata->nim}).",
                subject: $biodata,
                oldValues: $oldValues,
                newValues: $newValues,
            );

            DB::commit();

            return [
                'status' => 'SUCCESS',
                'message' => 'Profil berhasil disinkronkan.',
                'summary' => $mapped['changes_summary'],
                'changes' => $mapped['biodata_updates'],
                'alumni_id' => $biodata->id,
            ];

        } catch (LinkedInProfileNotFoundException $e) {
            DB::rollBack();

            // Catat log kegagalan spesifik: profil tidak ditemukan
            LogActivity::record(
                action: 'linkedin_sync',
                description: "Sinkronisasi profil LinkedIn gagal: Profil '{$username}' untuk alumni {$biodata->nama} ({$biodata->nim}) tidak ditemukan.",
                subject: $biodata,
                oldValues: $oldValues,
                newValues: ['status' => 'Gagal', 'alasan' => 'Profil tidak ditemukan.'],
            );

            return [
                'status' => 'NOT_FOUND',
                'message' => 'Profil tidak ditemukan.',
                'summary' => [],
                'changes' => [],
                'alumni_id' => $biodata->id,
            ];

        } catch (Throwable $e) {
            DB::rollBack();
            Log::error("LinkedIn Sync Error for NIM {$biodata->nim}: ".$e->getMessage(), [
                'trace' => $e->getTraceAsString(),
            ]);

            // Catat log kegagalan sistem
            LogActivity::record(
                action: 'linkedin_sync',
                description: "Sinkronisasi profil LinkedIn gagal untuk alumni {$biodata->nama} ({$biodata->nim}). Terjadi kendala sistem.",
                subject: $biodata,
                oldValues: $oldValues,
                newValues: ['status' => 'Gagal', 'alasan' => 'Kesalahan sistem saat sinkronisasi.'],
            );

            return [
                'status' => 'ERROR',
                'message' => 'Sinkronisasi gagal. Silakan coba lagi.',
                'summary' => [],
                'changes' => [],
                'alumni_id' => $biodata->id,
            ];
        }
    }

    /**
     * Melakukan sinkronisasi massal (Bulk Sync) untuk sekumpulan alumni.
     * Menggunakan transaksi terisolasi per-alumni sehingga kegagalan satu alumni
     * tidak menggagalkan alumni lainnya dalam batch.
     *
     * @param  int[]  $biodataIds
     * @return array{
     *     total: int,
     *     berhasil: int,
     *     gagal: int,
     *     dilewati: int,
     *     results: array
     * }
     */
    public function syncBatch(array $biodataIds): array
    {
        $alumnis = Biodata::with(['perusahaan', 'dataAkademik'])
            ->whereIn('id', $biodataIds)
            ->get();

        $total = count($alumnis);
        $berhasil = 0;
        $gagal = 0;
        $dilewati = 0;
        $results = [];

        foreach ($alumnis as $alumni) {
            $result = $this->syncBiodata($alumni);

            switch ($result['status']) {
                case 'SUCCESS':
                    $berhasil++;
                    break;
                case 'SKIPPED':
                    $dilewati++;
                    break;
                case 'NOT_FOUND':
                case 'ERROR':
                default:
                    $gagal++;
                    break;
            }

            $results[] = [
                'id' => $alumni->id,
                'nim' => $alumni->nim,
                'nama' => $alumni->nama,
                'status' => $result['status'],
                'message' => $result['message'],
            ];
        }

        return [
            'total' => $total,
            'berhasil' => $berhasil,
            'gagal' => $gagal,
            'dilewati' => $dilewati,
            'results' => $results,
        ];
    }
}
