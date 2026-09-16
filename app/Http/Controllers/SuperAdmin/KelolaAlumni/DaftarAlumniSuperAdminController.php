<?php

namespace App\Http\Controllers\SuperAdmin\KelolaAlumni;

use App\Http\Controllers\Controller;
use App\Models\Biodata;
use App\Models\DataAkademik;
use App\Models\Prodi;
use App\Services\Kuesioner\KelengkapanTracerService;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

/**
 * DaftarAlumniSuperAdminController
 *
 * Fungsi:
 * Menampilkan daftar seluruh alumni terpadu untuk Super Admin lengkap dengan filter Prodi,
 * Tahun Kelulusan, Semester Lulus, Pencarian Nama/NIM, serta audit status kelengkapan data.
 */
class DaftarAlumniSuperAdminController extends Controller
{
    /**
     * Tampilkan Halaman Index Daftar Alumni
     */
    public function index(Request $request): Response
    {
        $pencarian = $request->input('search');
        $tahunTerpilih = $request->input('tahun');
        $semesterTerpilih = $request->input('semester');
        $statusTerpilih = $request->input('status');
        $prodiIdTerpilih = $request->input('prodi_id');

        // 1. Ambil list tahun kelulusan unik dari data akademik untuk dropdown filter
        $daftarTahun = DataAkademik::whereNotNull('tahun_lulus')
            ->orWhereNotNull('tahun_akademik_lulus')
            ->pluck('tahun_lulus')
            ->filter()
            ->map(function ($item) {
                if (preg_match('/(\d{4})/', (string) $item, $matches)) {
                    return $matches[1];
                }

                return trim($item);
            })->unique()->sortDesc()->values()->all();

        // 2. Kueri data biodata dengan relasi lengkap
        $query = Biodata::with([
            'dataAkademik.yudisium',
            'dataAkademik.orangTua',
            'yudisium',
            'orangTua',
            'prodi',
            'perusahaan.propinsi',
            'perusahaan.kabupaten',
            'atasan',
            'user',
        ]);

        // Filter Program Studi
        if ($prodiIdTerpilih && $prodiIdTerpilih !== 'all') {
            $query->where('prodi_id', $prodiIdTerpilih);
        }

        // Filter Pencarian (Nama Lengkap atau NIM)
        if ($pencarian) {
            $query->where(function ($w) use ($pencarian) {
                $w->where('nim', 'like', "%{$pencarian}%")
                    ->orWhereHas('dataAkademik', function ($q) use ($pencarian) {
                        $q->where('nama', 'like', "%{$pencarian}%")
                            ->orWhere('nim', 'like', "%{$pencarian}%");
                    })
                    ->orWhereHas('user', function ($q) use ($pencarian) {
                        $q->where('name', 'like', "%{$pencarian}%")
                            ->orWhere('username', 'like', "%{$pencarian}%");
                    });
            });
        }

        // Filter Tahun Kelulusan
        if ($tahunTerpilih && $tahunTerpilih !== 'all') {
            $query->whereHas('dataAkademik', function ($q) use ($tahunTerpilih) {
                $q->where('tahun_akademik_lulus', 'like', "%{$tahunTerpilih}%")
                    ->orWhere('tahun_lulus', 'like', "%{$tahunTerpilih}%");
            });
        }

        // Filter Semester Kelulusan (Gasal / Genap)
        if ($semesterTerpilih && $semesterTerpilih !== 'all') {
            $query->whereHas('dataAkademik', function ($q) use ($semesterTerpilih) {
                $q->where('tahun_akademik_lulus', 'like', "%{$semesterTerpilih}%");
            });
        }

        $semuaAlumni = $query->orderBy('nim', 'asc')->get();

        // 3. Evaluasi status kelengkapan tiap alumni
        $alumniList = [];
        $totalSelesai = 0;
        $totalBelumSelesai = 0;

        foreach ($semuaAlumni as $alumni) {
            $evaluasi = KelengkapanTracerService::evaluasiKelengkapanTotal($alumni);

            // Filter Status (Selesai vs Belum Selesai) jika dipilih
            if ($statusTerpilih === 'selesai' && ! $evaluasi['is_complete']) {
                continue;
            }
            if ($statusTerpilih === 'belum_selesai' && $evaluasi['is_complete']) {
                continue;
            }

            if ($evaluasi['is_complete']) {
                $totalSelesai++;
            } else {
                $totalBelumSelesai++;
            }

            // Semester kelulusan parsing
            $rawSemesterLulus = $alumni->dataAkademik?->tahun_akademik_lulus ?? '-';
            $semesterLabel = 'Gasal';
            if (stripos($rawSemesterLulus, 'genap') !== false) {
                $semesterLabel = 'Genap';
            } elseif (stripos($rawSemesterLulus, 'gasal') !== false) {
                $semesterLabel = 'Gasal';
            }

            $alumniList[] = [
                'id' => $alumni->id,
                'nim' => $alumni->nim,
                'nama' => $alumni->dataAkademik?->nama ?? $alumni->user?->name ?? 'Mahasiswa UKDW',
                'prodi' => $alumni->prodi?->nama_prodi ?? '-',
                'prodi_kode' => $alumni->prodi?->kode_prodi ?? '',
                'tahun_akademik_lulus' => $rawSemesterLulus,
                'semester' => $semesterLabel,
                'tahun_lulus' => $alumni->dataAkademik?->tahun_lulus ?? '-',
                'ipk' => $alumni->dataAkademik?->ipk ?? '-',
                'status_yudisium' => $alumni->dataAkademik?->yudisium?->proses_yudisium ?? 'Lulus',
                'perusahaan' => $alumni->perusahaan?->nama_perusahaan ?? '-',
                'posisi_jabatan' => $alumni->posisi_jabatan ?? '-',
                'kelengkapan' => $evaluasi,
            ];
        }

        $totalFiltered = count($alumniList);
        $rasioSelesai = $totalFiltered > 0 ? (int) round(($totalSelesai / $totalFiltered) * 100) : 0;

        // 4. Master Program Studi
        $daftarProdi = Prodi::orderBy('kode_prodi', 'asc')->get();

        return Inertia::render('SuperAdmin/Alumni/Index', [
            'biodatas' => $alumniList,
            'alumnis' => $alumniList,
            'daftarTahun' => $daftarTahun,
            'prodis' => $daftarProdi,
            'filters' => [
                'search' => $pencarian ?? '',
                'tahun' => $tahunTerpilih,
                'semester' => $semesterTerpilih,
                'status' => $statusTerpilih,
                'prodi_id' => $prodiIdTerpilih,
            ],
            'stats' => [
                'total_alumni' => $totalFiltered,
                'total_selesai' => $totalSelesai,
                'total_belum_selesai' => $totalBelumSelesai,
                'persentase_selesai' => $rasioSelesai,
            ],
        ]);
    }
}
