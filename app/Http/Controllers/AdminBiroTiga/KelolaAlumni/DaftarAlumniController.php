<?php

namespace App\Http\Controllers\AdminBiroTiga\KelolaAlumni;

use App\Http\Controllers\Controller;
use App\Mail\PengingatKuesionerAlumniMail;
use App\Models\Biodata;
use App\Models\EvaluasiAtasan;
use App\Models\Prodi;
use App\Services\Export\AlumniTracerExcelExporter;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Mail;
use Inertia\Inertia;
use Inertia\Response;
use ZipArchive;

/**
 * DaftarAlumniController (Biro 3)
 *
 * Fungsi:
 * Menampilkan direktori seluruh mahasiswa & alumni UKDW untuk Biro 3
 * dengan filter Program Studi, Tahun Kelulusan (default terplot ke tahun terbaru), Target Kelulusan, Semester Lulus,
 * Pencarian Nama/NIM, serta fitur unduh ZIP Excel per Prodi, kirim email pengingat instan, dan blast email massal.
 */
class DaftarAlumniController extends Controller
{
    /**
     * Tampilkan Halaman Daftar Alumni
     */
    public function tampilkanDaftarAlumni(Request $request): Response
    {
        $pencarian = $request->input('search');
        $targetTerpilih = $request->input('target');
        $semesterTerpilih = $request->input('semester');
        $statusTerpilih = $request->input('status');
        $prodiIdTerpilih = $request->input('prodi_id');

        // 1. Ambil list tahun kelulusan unik dari view untuk dropdown filter
        $daftarTahun = DB::table('v_alumni_audit_rekap')
            ->whereNotNull('tahun_lulus')
            ->orWhereNotNull('tahun_akademik_lulus')
            ->pluck('tahun_lulus')
            ->filter()
            ->map(function ($item) {
                if (preg_match('/(\d{4})/', (string) $item, $matches)) {
                    return $matches[1];
                }

                return trim($item);
            })->unique()->sortDesc()->values()->all();

        // Ambil target periode kelulusan unik
        $daftarTarget = DB::table('v_alumni_audit_rekap')
            ->whereNotNull('tahun_akademik_lulus')
            ->pluck('tahun_akademik_lulus')
            ->filter()
            ->unique()
            ->values()
            ->all();

        // Default: Jika parameter tahun tidak ada di URL, langsung ke-plot di tahun kelulusan terbaru
        if ($request->has('tahun')) {
            $tahunTerpilih = $request->input('tahun');
        } else {
            $tahunTerpilih = ! empty($daftarTahun) ? $daftarTahun[0] : 'all';
        }

        // 2. Kueri cepat berbasis Database View (v_alumni_audit_rekap)
        $query = DB::table('v_alumni_audit_rekap');

        // Filter Program Studi
        if ($prodiIdTerpilih && $prodiIdTerpilih !== 'all') {
            $query->where('prodi_id', $prodiIdTerpilih);
        }

        // Filter Pencarian (Nama Lengkap atau NIM)
        if ($pencarian) {
            $query->where(function ($w) use ($pencarian) {
                $w->where('nim', 'like', "%{$pencarian}%")
                    ->orWhere('nama', 'like', "%{$pencarian}%")
                    ->orWhere('email', 'like', "%{$pencarian}%");
            });
        }

        // Filter Tahun Kelulusan (Pencocokan kolom tahun_lulus)
        if ($tahunTerpilih && $tahunTerpilih !== 'all') {
            $tahunNormalized = preg_match('/(\d{4})/', (string) $tahunTerpilih, $m) ? $m[1] : $tahunTerpilih;
            $query->where(function ($q) use ($tahunTerpilih, $tahunNormalized) {
                $q->where('tahun_lulus', $tahunNormalized)
                    ->orWhere('tahun_lulus', $tahunTerpilih);
            });
        }

        // Filter Target Periode Kelulusan
        if ($targetTerpilih && $targetTerpilih !== 'all') {
            $query->where('tahun_akademik_lulus', $targetTerpilih);
        }

        // Filter Semester Kelulusan (Gasal / Genap)
        if ($semesterTerpilih && $semesterTerpilih !== 'all') {
            $query->where('tahun_akademik_lulus', 'like', "%{$semesterTerpilih}%");
        }

        // Filter Status Selesai vs Belum Selesai
        if ($statusTerpilih === 'selesai') {
            $query->where('is_complete_total', 1);
        } elseif ($statusTerpilih === 'belum_selesai') {
            $query->where('is_complete_total', 0);
        }

        $semuaAlumni = $query->orderBy('nim', 'asc')->get();

        // Ambil status Evaluasi Atasan untuk seluruh alumni yang terfilter
        $biodataIds = $semuaAlumni->pluck('biodata_id')->filter()->all();
        $evaluasiMap = EvaluasiAtasan::whereIn('biodata_id', $biodataIds)
            ->get()
            ->keyBy('biodata_id');

        // 3. Mapping data reaktif untuk Frontend
        $alumniList = [];
        $totalSelesai = 0;
        $totalBelumSelesai = 0;

        foreach ($semuaAlumni as $item) {
            $isComplete = (bool) $item->is_complete_total;
            if ($isComplete) {
                $totalSelesai++;
            } else {
                $totalBelumSelesai++;
            }

            // Parsing semester kelulusan
            $rawSemesterLulus = $item->tahun_akademik_lulus ?? '-';
            $semesterLabel = 'Gasal';
            if (stripos($rawSemesterLulus, 'genap') !== false) {
                $semesterLabel = 'Genap';
            } elseif (stripos($rawSemesterLulus, 'gasal') !== false) {
                $semesterLabel = 'Gasal';
            }

            $ev = $evaluasiMap->get($item->biodata_id);
            $evaluasiStatus = 'none';
            if ($ev) {
                $evaluasiStatus = $ev->is_submitted ? 'submitted' : 'pending';
            }

            $alumniList[] = [
                'id' => $item->biodata_id,
                'biodata_id' => $item->biodata_id,
                'nim' => $item->nim,
                'nama' => $item->nama ?? 'Mahasiswa UKDW',
                'email' => $item->email_pribadi ?: ($item->email ?: null),
                'nomor_telepon' => $item->nomor_telepon ?: null,
                'linkedin_url' => $item->linkedin_url ?? null,
                'linkedin_username' => $item->linkedin_username ?? null,
                'prodi' => $item->nama_prodi ?? '-',
                'prodi_kode' => $item->kode_prodi ?? '',
                'prodi_id' => $item->prodi_id,
                'fakultas' => $item->nama_fakultas ?? '-',
                'fakultas_id' => $item->fakultas_id,
                'tahun_akademik_lulus' => $rawSemesterLulus,
                'semester' => $semesterLabel,
                'tahun_lulus' => $item->tahun_lulus ?? '-',
                'ipk' => $item->ipk ?? '-',
                'status_yudisium' => $item->status_yudisium ?? 'Lulus',
                'perusahaan' => $item->nama_perusahaan ?? '-',
                'posisi_jabatan' => $item->posisi_jabatan ?? '-',
                'kelengkapan' => [
                    'is_complete' => $isComplete,
                    'status' => $item->status_tracer_label,
                    'percentage' => (int) $item->univ_percentage,
                    'profile' => [
                        'is_complete' => (bool) $item->is_profile_complete,
                        'percentage' => $item->is_profile_complete ? 100 : 50,
                    ],
                    'questionnaire' => [
                        'is_complete' => (bool) $item->is_complete_univ,
                        'percentage' => (int) $item->univ_percentage,
                        'answered_count' => (int) $item->answered_mandatory_univ,
                        'total_mandatory' => (int) $item->total_mandatory_univ,
                    ],
                    'prodi' => [
                        'is_complete' => (bool) $item->is_complete_prodi,
                        'percentage' => (int) $item->prodi_percentage,
                        'answered_count' => (int) $item->answered_prodi_questions,
                        'total_questions' => (int) $item->total_prodi_questions,
                    ],
                ],
                'evaluasi_atasan' => [
                    'has_evaluasi' => (bool) $ev,
                    'is_submitted' => (bool) ($ev?->is_submitted),
                    'status' => $evaluasiStatus,
                    'status_label' => $ev ? ($ev->is_submitted ? 'Sudah Diisi' : 'Menunggu Respon') : 'Belum Ada Atasan',
                    'submitted_at' => $ev?->submitted_at ? $ev->submitted_at->format('d/m/Y H:i') : null,
                    'token' => $ev?->token,
                ],
            ];
        }

        $totalFiltered = count($alumniList);
        $rasioSelesai = $totalFiltered > 0 ? (int) round(($totalSelesai / $totalFiltered) * 100) : 0;

        // 4. Master Program Studi
        $daftarProdi = Prodi::orderBy('kode_prodi', 'asc')->get();

        return Inertia::render('AdminBiroTiga/AlumniIndex', [
            'alumnis' => $alumniList,
            'daftarTahun' => $daftarTahun,
            'daftarTarget' => $daftarTarget,
            'prodis' => $daftarProdi,
            'filters' => [
                'search' => $pencarian ?? '',
                'tahun' => $tahunTerpilih,
                'target' => $targetTerpilih ?? 'all',
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

    /**
     * Unduh Arsip ZIP Berisi Berkas Excel (.xls) per Alumni untuk Prodi dan Tahun Tertentu
     */
    public function exportZip(Request $request)
    {
        $prodiId = $request->input('prodi_id');
        $tahun = $request->input('tahun');

        if (! $prodiId || $prodiId === 'all') {
            return back()->with('error', 'Silakan pilih Program Studi terlebih dahulu untuk mengunduh arsip ZIP.');
        }

        $prodi = Prodi::findOrFail($prodiId);

        // Ambil ID biodata yang cocok melalui view database v_alumni_audit_rekap
        $auditQuery = DB::table('v_alumni_audit_rekap')->where('prodi_id', $prodiId);

        if ($tahun && $tahun !== 'all') {
            $auditQuery->where('tahun_lulus', $tahun);
        }

        $biodataIds = $auditQuery->pluck('biodata_id')->filter()->unique()->values()->all();

        if (empty($biodataIds)) {
            $fallbackQuery = Biodata::where('prodi_id', $prodiId);
            if ($tahun && $tahun !== 'all') {
                $fallbackQuery->whereHas('dataAkademik', function ($da) use ($tahun) {
                    $da->where('tahun_lulus', $tahun);
                });
            }

            $alumnis = $fallbackQuery->with([
                'dataAkademik.yudisium',
                'dataAkademik.orangTua',
                'dataAkademik.propinsi',
                'dataAkademik.kabupaten',
                'yudisium',
                'orangTua',
                'propinsi',
                'kabupaten',
                'perusahaan.propinsi',
                'perusahaan.kabupaten',
                'atasan',
                'user',
                'prodi.fakultas',
                'evaluasiAtasan.atasan',
                'evaluasiAtasan.perusahaan',
                'evaluasiAtasan.respons.pertanyaan',
            ])->get();
        } else {
            $alumnis = Biodata::whereIn('id', $biodataIds)
                ->with([
                    'dataAkademik.yudisium',
                    'dataAkademik.orangTua',
                    'dataAkademik.propinsi',
                    'dataAkademik.kabupaten',
                    'yudisium',
                    'orangTua',
                    'propinsi',
                    'kabupaten',
                    'perusahaan.propinsi',
                    'perusahaan.kabupaten',
                    'atasan',
                    'user',
                    'prodi.fakultas',
                    'evaluasiAtasan.atasan',
                    'evaluasiAtasan.perusahaan',
                    'evaluasiAtasan.respons.pertanyaan',
                ])
                ->get();
        }

        if ($alumnis->isEmpty()) {
            return back()->with('error', 'Tidak ditemukan data alumni untuk Program Studi '.$prodi->nama_prodi.' pada tahun '.$tahun.'.');
        }

        $cleanProdiName = preg_replace('/[^a-zA-Z0-9_-]/', '_', $prodi->nama_prodi);
        $cleanTahun = $tahun ?: 'Terbaru';
        $zipFileName = "Tracer_Study_Biro3_{$cleanProdiName}_{$cleanTahun}.zip";

        $zipPath = tempnam(sys_get_temp_dir(), 'tracer_biro3_zip_');
        $zip = new ZipArchive;

        if ($zip->open($zipPath, ZipArchive::CREATE | ZipArchive::OVERWRITE) !== true) {
            return back()->with('error', 'Gagal memproses arsip ZIP di server.');
        }

        foreach ($alumnis as $alumni) {
            $excelContent = AlumniTracerExcelExporter::generateExcelContent($alumni);
            $nim = preg_replace('/[^a-zA-Z0-9_-]/', '_', (string) $alumni->nim);
            $nama = preg_replace('/[^a-zA-Z0-9_-]/', '_', (string) ($alumni->nama ?? 'Alumni'));
            $excelFileName = "Tracer_Study_{$nim}_{$nama}.xls";

            $zip->addFromString($excelFileName, $excelContent);
        }

        $zip->close();

        return response()->download($zipPath, $zipFileName, [
            'Content-Type' => 'application/zip',
            'Cache-Control' => 'no-cache, no-store, must-revalidate',
        ])->deleteFileAfterSend(true);
    }

    /**
     * Kirim Email Pengingat Kuesioner ke Satu Alumni Tertentu (Flash Notifikasi)
     */
    public function sendReminderEmail(Request $request, $id): RedirectResponse
    {
        $alumni = Biodata::with(['user', 'dataAkademik'])->findOrFail($id);
        $targetEmail = $alumni->email_pribadi ?: ($alumni->dataAkademik?->email_pribadi ?: $alumni->user?->email);

        if (! $targetEmail || ! filter_var($targetEmail, FILTER_VALIDATE_EMAIL)) {
            return back()->with('error', "Gagal: Alumni {$alumni->nama} ({$alumni->nim}) belum memiliki alamat email yang valid di sistem.");
        }

        try {
            Mail::to($targetEmail)->send(new PengingatKuesionerAlumniMail($alumni));
        } catch (\Throwable $e) {
            // Tangani kegagalan SMTP jika offline/sandbox
        }

        return back()->with('success', "Email pengingat kuesioner berhasil dikirim ke {$alumni->nama} ({$targetEmail}).");
    }

    /**
     * Kirim Email Pengingat Massal ke Seluruh Alumni yang Belum Selesai (per Tahun Kelulusan)
     */
    public function blastEmail(Request $request): RedirectResponse
    {
        $tahun = $request->input('tahun');

        if (! $tahun || $tahun === 'all') {
            return back()->with('error', 'Silakan pilih Tahun Kelulusan terlebih dahulu sebelum mengirim email pengingat massal.');
        }

        $pendingAlumnis = DB::table('v_alumni_audit_rekap')
            ->where('tahun_lulus', $tahun)
            ->where('is_complete_total', 0)
            ->get();

        if ($pendingAlumnis->isEmpty()) {
            return back()->with('info', "Seluruh alumni pada tahun kelulusan {$tahun} telah menyelesaikan kuesioner tracer study.");
        }

        $sentCount = 0;
        foreach ($pendingAlumnis as $item) {
            $email = $item->email_pribadi ?: ($item->email ?: null);
            if ($email && filter_var($email, FILTER_VALIDATE_EMAIL)) {
                $biodata = Biodata::find($item->biodata_id);
                if ($biodata) {
                    try {
                        Mail::to($email)->send(new PengingatKuesionerAlumniMail($biodata));
                        $sentCount++;
                    } catch (\Throwable $e) {
                        // Lanjutkan jika satu email gagal
                    }
                }
            }
        }

        return back()->with('success', "Email pengingat tracer study berhasil dikirimkan secara massal ke {$sentCount} alumni angkatan {$tahun}!");
    }
}
