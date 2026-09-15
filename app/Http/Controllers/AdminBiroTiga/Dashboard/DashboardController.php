<?php

namespace App\Http\Controllers\AdminBiroTiga\Dashboard;

use App\Http\Controllers\Controller;
use App\Models\Biodata;
use App\Models\Prodi;
use App\Models\RefSubpertanyaan2021;
use App\Models\Tracer;
use Illuminate\Http\Request;
use Inertia\Inertia;

/**
 * DashboardController (Biro 3)
 *
 * Fungsi: Menyajikan dashboard eksekutif resmi untuk Administrator Biro 3 (Biro Kemahasiswaan, Alumni, dan Pengembangan Karir).
 * Tujuan: Memberikan ringkasan indikator kinerja utama (KPI) pelacakan alumni, tingkat partisipasi pengisian kuesioner,
 *         serta pintu gerbang navigasi utama ke pengelolaan data alumni dan instrumen pertanyaan.
 */
class DashboardController extends Controller
{
    /**
     * Menampilkan Halaman Dashboard Utama Biro 3
     *
     * Hubungan dengan Frontend:
     * Method ini adalah pasangan dari file: resources/js/Pages/AdminBiroTiga/Dashboard.vue
     * Data di dalam Inertia::render(...) akan diterima sebagai props oleh komponen Vue tersebut.
     */
    public function tampilkanDashboard(Request $request)
    {
        // $user: Menyimpan data akun Admin Biro 3 yang sedang aktif login (id, name, email, role)
        $user = $request->user();

        // -----------------------------------------------------------------
        // 1. Perhitungan Metrik KPI Utama (Ringkasan Angka Statistik)
        // -----------------------------------------------------------------

        // $totalAlumni: Menghitung jumlah seluruh baris data biodata di tabel 'biodatas'
        $totalAlumni = Biodata::count();

        // $totalResponden: Menghitung berapa banyak alumni unik yang sudah mengisi minimal satu jawaban di tabel 'tracers'
        // (distinct memastikan alumni yang jawab banyak pertanyaan hanya dihitung 1 responden)
        $totalResponden = Tracer::distinct('biodata_id')->count('biodata_id');

        // $persentaseRespon: Menghitung rasio responden dibandingkan total alumni dalam bentuk persen (misal: 65.5%)
        // Rumus: (Total Responden / Total Alumni) * 100
        $persentaseRespon = $totalAlumni > 0 ? round(($totalResponden / $totalAlumni) * 100, 1) : 0;

        // $totalPertanyaan: Menghitung jumlah seluruh butir instrumen pertanyaan kuesioner di tabel 'ref_subpertanyaan2021'
        $totalPertanyaan = RefSubpertanyaan2021::count();

        // $totalProdi: Menghitung jumlah program studi yang terdaftar di tabel 'prodis'
        $totalProdi = Prodi::count();

        // $alumniLinkedIn: Menghitung jumlah alumni yang profilnya sudah terhubung dengan LinkedIn
        // (kondisinya: kolom linkedin_url atau linkedin_username tidak kosong)
        $alumniLinkedIn = Biodata::where(function ($q) {
            $q->whereNotNull('linkedin_url')
                ->where('linkedin_url', '!=', '')
                ->orWhereNotNull('linkedin_username')
                ->where('linkedin_username', '!=', '');
        })->count();

        // -----------------------------------------------------------------
        // 2. Ringkasan Statistik Partisipasi per Program Studi
        // -----------------------------------------------------------------

        // $prodiSummaries: Mengambil daftar prodi beserta kalkulasi jumlah alumni & responden masing-masing prodi
        $prodiSummaries = Prodi::withCount('biodatas') // Otomatis menambahkan kolom 'biodatas_count' pada setiap prodi
            ->orderBy('kode_prodi', 'asc')
            ->get()
            ->map(function ($prodi) {
                // $respondenCount: Menghitung alumni di prodi ini yang sudah memiliki jawaban kuesioner (whereHas 'tracers')
                $respondenCount = Biodata::where('prodi_id', $prodi->id)
                    ->whereHas('tracers')
                    ->count();

                $totalBiodata = $prodi->biodatas_count ?? 0;

                // $rate: Menghitung persentase tingkat partisipasi per prodi (contoh: 80.0%)
                $rate = $totalBiodata > 0
                    ? round(($respondenCount / $totalBiodata) * 100, 1)
                    : 0;

                // Bentuk array rapi untuk dikirim ke tabel / grafik di Vue
                return [
                    'id' => $prodi->id,              // ID prodi
                    'kode_prodi' => $prodi->kode_prodi,      // Kode prodi (misal: 71, 72)
                    'nama_prodi' => $prodi->nama_prodi,      // Nama lengkap prodi (misal: Sistem Informasi)
                    'total_alumni' => $totalBiodata,   // Total alumni terdaftar di prodi ini
                    'total_responden' => $respondenCount,         // Total alumni yang sudah mengisi kuesioner
                    'response_rate' => $rate,                   // Persentase partisipasi prodi (%)
                ];
            });

        // -----------------------------------------------------------------
        // 3. Data Alumni Terbaru untuk Pratinjau Cepat (Tabel 5 Teratas)
        // -----------------------------------------------------------------

        // $recentAlumni: Mengambil 5 alumni terbaru yang baru terdaftar atau diimpor
        $recentAlumni = Biodata::with(['prodi', 'dataAkademik', 'user']) // Eager loading relasi agar database tidak lambat
            ->latest() // Urutkan dari yang paling baru didaftarkan
            ->take(5)  // Ambil 5 baris saja
            ->get()
            ->map(function ($alumni) {
                // Merapikan data alumni untuk ditampilkan di tabel ringkasan dashboard Vue
                return [
                    'id' => $alumni->id,                                                     // ID alumni
                    'nim' => $alumni->user?->username ?? $alumni->nim,                         // NIM alumni
                    'nama' => $alumni->nama ?? $alumni->dataAkademik?->nama ?? $alumni->user?->name ?? 'Belum terisi', // Nama lengkap alumni
                    'prodi' => $alumni->prodi?->nama_prodi ?? '-',                               // Nama prodi
                    'has_linkedin' => ! empty($alumni->linkedin_url) || ! empty($alumni->linkedin_username),  // Status LinkedIn (true/false)
                    'has_responded' => $alumni->tracers()->exists(),                                  // Status sudah isi kuesioner (true/false)
                ];
            });

        // -----------------------------------------------------------------
        // 4. Melempar Data ke Halaman Vue (Frontend)
        // -----------------------------------------------------------------
        // Membuka file: resources/js/Pages/AdminBiroTiga/Dashboard.vue
        return Inertia::render('AdminBiroTiga/Dashboard', [
            'user' => $user,           // Props: Informasi admin yang sedang login
            'stats' => [                // Props: Kumpulan metrik KPI untuk kartu-kartu statistik atas
                'total_alumni' => $totalAlumni,
                'total_responden' => $totalResponden,
                'persentase_respon' => $persentaseRespon,
                'total_pertanyaan' => $totalPertanyaan,
                'total_prodi' => $totalProdi,
                'alumni_linkedin' => $alumniLinkedIn,
            ],
            'prodiSummaries' => $prodiSummaries, // Props: Data untuk tabel statistik partisipasi per prodi
            'recentAlumni' => $recentAlumni,   // Props: Data untuk tabel 5 alumni terbaru
        ]);
    }
}
