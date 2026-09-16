<?php

namespace App\Http\Controllers\AdminBiroTiga\KelolaAlumni;

use App\Http\Controllers\Controller;
use App\Models\Biodata;
use App\Models\DataAkademik;
use App\Models\Prodi;
use Illuminate\Http\Request;
use Inertia\Inertia;

/**
 * DaftarAlumniController
 *
 * Fungsi: Menampilkan direktori alumni yang berstatus yudisium 'Lulus' per tahun/periode kelulusan.
 * Tujuan: Menyediakan data alumni terfilter berdasarkan dropdown tahun yudisium kelulusan
 *         terpilih (bukan menampilkan seluruh semester sekaligus), memfilter program studi,
 *         dan melakukan pencarian cepat.
 */
class DaftarAlumniController extends Controller
{
    /**
     * Tampilkan Halaman Daftar Alumni (Khusus Status Yudisium Lulus per Tahun Kelulusan)
     */
    public function tampilkanDaftarAlumni(Request $request)
    {
        $pencarian = $request->input('search');
        $idProdi = $request->input('prodi_id');

        // 1. Ambil daftar seluruh semester / periode yudisium kelulusan unik yang memiliki status Lulus
        $daftarSemester = DataAkademik::whereHas('yudisium', function ($q) {
            $q->where('proses_yudisium', 'Lulus');
        })
            ->whereNotNull('tahun_akademik_lulus')
            ->distinct()
            ->orderBy('tahun_akademik_lulus', 'desc')
            ->pluck('tahun_akademik_lulus')
            ->values();

        // 2. Tentukan periode kelulusan aktif: default ke periode kelulusan terbaru (pertama)
        // Data TIDAK ditampilkan sekaligus (semua semester), melainkan dibatasi per tahun/semester kelulusan yang dipilih di dropdown
        $semesterTerpilih = $request->input('semester');
        if (! $semesterTerpilih || ! $daftarSemester->contains($semesterTerpilih)) {
            $semesterTerpilih = $daftarSemester->first() ?? '';
        }

        // 3. Kueri Utama: Mengambil data biodata yang berstatus Yudisium 'Lulus' pada tahun kelulusan terpilih
        $query = Biodata::with(['dataAkademik', 'prodi', 'perusahaan', 'user', 'yudisium'])
            ->whereHas('yudisium', function ($q) {
                $q->where('proses_yudisium', 'Lulus');
            })
            // Filter Wajib: Hanya tampilkan data alumni untuk tahun kelulusan yang sedang dipilih di dropdown
            ->when($semesterTerpilih, function ($query, $semesterTerpilih) {
                $query->where(function ($q) use ($semesterTerpilih) {
                    $q->where('tahun_lulus', $semesterTerpilih)
                        ->orWhereHas('dataAkademik', function ($qa) use ($semesterTerpilih) {
                            $qa->where('tahun_akademik_lulus', $semesterTerpilih);
                        });
                });
            })
            // Filter Pencarian (Nama Lengkap, NIM, atau Akun LinkedIn)
            ->when($pencarian, function ($query, $pencarian) {
                $query->where(function ($w) use ($pencarian) {
                    $w->where('nim', 'like', "%{$pencarian}%")
                        ->orWhere('nama', 'like', "%{$pencarian}%")
                        ->orWhereHas('dataAkademik', function ($q) use ($pencarian) {
                            $q->where('nama', 'like', "%{$pencarian}%")
                                ->orWhere('nim', 'like', "%{$pencarian}%");
                        })
                        ->orWhere('linkedin_username', 'like', "%{$pencarian}%")
                        ->orWhere('linkedin_url', 'like', "%{$pencarian}%");
                });
            })
            // Filter Berdasarkan Program Studi
            ->when($idProdi, function ($query, $idProdi) {
                $query->where('prodi_id', $idProdi);
            });

        // Urutkan data berdasarkan nama atau NIM secara alfabetis/rapi
        $alumnis = $query->orderBy('nim', 'asc')->get();

        // 4. Hitung jumlah wisudawan per periode untuk ditampilkan di opsi dropdown
        $semesterCounts = DataAkademik::whereHas('yudisium', function ($q) {
            $q->where('proses_yudisium', 'Lulus');
        })
            ->whereNotNull('tahun_akademik_lulus')
            ->selectRaw('tahun_akademik_lulus, count(*) as total')
            ->groupBy('tahun_akademik_lulus')
            ->pluck('total', 'tahun_akademik_lulus')
            ->toArray();

        // 5. Ambil data master seluruh program studi
        $daftarProdi = Prodi::orderBy('kode_prodi', 'asc')->get();

        return Inertia::render('AdminBiroTiga/AlumniIndex', [
            'biodatas' => $alumnis,
            'alumnis' => $alumnis,
            'daftarSemester' => $daftarSemester,
            'semesterCounts' => $semesterCounts,
            'semesterAktif' => $semesterTerpilih,
            'prodis' => $daftarProdi,
            'totalLulusPeriode' => $alumnis->count(),
            'filters' => [
                'search' => $pencarian ?? '',
                'prodi_id' => $idProdi ?? '',
                'semester' => $semesterTerpilih,
            ],
        ]);
    }
}
