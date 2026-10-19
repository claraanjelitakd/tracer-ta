<?php

namespace App\Http\Controllers\SuperAdmin\KelolaAlumni;

use App\Http\Controllers\Controller;
use App\Mail\PengingatKuesionerAlumniMail;
use App\Models\Biodata;
use App\Models\EvaluasiAtasan;
use App\Models\LinkedinSyncResult;
use App\Models\Prodi;
use App\Services\Export\AlumniTracerExcelExporter;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Mail;
use Inertia\Inertia;
use Inertia\Response;
use Symfony\Component\HttpFoundation\BinaryFileResponse;

/**
 * DaftarAlumniSuperAdminController
 *
 * Fungsi:
 * Menampilkan daftar seluruh alumni terpadu untuk Super Admin lengkap dengan filter Prodi,
 * Tahun Kelulusan (default tahun terbaru), Semester Lulus, Pencarian Nama/NIM, serta audit status
 * kelengkapan data & evaluasi atasan, serta fitur ekspor ZIP per Prodi untuk tahun yang difilter.
 */
class DaftarAlumniSuperAdminController extends Controller
{
    /**
     * Tampilkan Halaman Index Daftar Alumni Terpadu.
     *
     * Menangani pemfilteran dinamis berdasarkan Program Studi, Tahun Kelulusan,
     * Semester Kelulusan, Target Periode, Status Kuesioner (Selesai/Belum),
     * Pencarian Nama/NIM, serta Status Sinkronisasi LinkedIn.
     *
     * @param  Request  $request  HTTP request yang memuat parameter filter
     * @return Response Inertia render halaman direktori alumni Super Admin
     */
    public function index(Request $request): Response
    {
        $pencarian = $request->input('search');
        $semesterTerpilih = $request->input('semester');
        $statusTerpilih = $request->input('status');
        $prodiIdTerpilih = $request->input('prodi_id');
        $targetTerpilih = $request->input('target');
        $linkedinStatusTerpilih = $request->input('linkedin_status', 'all');

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

        // Sesuai kebutuhan pengguna: Tahun Kelulusan default ke tahun terbaru saja (tidak perlu 'all')
        $tahunTerbaru = $daftarTahun[0] ?? (string) date('Y');
        $tahunTerpilih = $request->input('tahun', $tahunTerbaru);
        if ($tahunTerpilih === 'all' || empty($tahunTerpilih)) {
            $tahunTerpilih = $tahunTerbaru;
        }

        // 1b. Ambil daftar target periode kelulusan unik (Semester & Tahun Lulus)
        $daftarTarget = DB::table('v_alumni_audit_rekap')
            ->whereNotNull('tahun_akademik_lulus')
            ->where('tahun_akademik_lulus', '!=', '')
            ->pluck('tahun_akademik_lulus')
            ->unique()
            ->sortDesc()
            ->values()
            ->all();

        // 2. Kueri cepat berbasis Database View (v_alumni_audit_rekap)
        $query = DB::table('v_alumni_audit_rekap');

        // Filter Program Studi
        if ($prodiIdTerpilih && $prodiIdTerpilih !== 'all') {
            $query->where('prodi_id', $prodiIdTerpilih);
        }

        // Filter Target Kelulusan (Semester + Tahun)
        if ($targetTerpilih && $targetTerpilih !== 'all') {
            $query->where('tahun_akademik_lulus', $targetTerpilih);
        }

        // Filter Pencarian (Nama Lengkap atau NIM)
        if ($pencarian) {
            $query->where(function ($w) use ($pencarian) {
                $w->where('nim', 'like', "%{$pencarian}%")
                    ->orWhere('nama', 'like', "%{$pencarian}%")
                    ->orWhere('email', 'like', "%{$pencarian}%");
            });
        }

        // Filter Tahun Kelulusan (Pencocokan eksak kolom tahun_lulus agar selaras dengan seluruh role)
        if ($tahunTerpilih && $tahunTerpilih !== 'all') {
            $tahunNormalized = preg_match('/(\d{4})/', (string) $tahunTerpilih, $m) ? $m[1] : $tahunTerpilih;
            $query->where(function ($q) use ($tahunTerpilih, $tahunNormalized) {
                $q->where('tahun_lulus', $tahunNormalized)
                    ->orWhere('tahun_lulus', $tahunTerpilih);
            });
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

        // Ambil riwayat hasil sinkronisasi LinkedIn terbaru beserta user reviewer
        $syncMap = LinkedinSyncResult::whereIn('biodata_id', $biodataIds)
            ->with('reviewer:id,name,email')
            ->orderBy('id', 'desc')
            ->get()
            ->groupBy('biodata_id');

        // 3. Mapping data reaktif untuk Frontend
        $alumniList = [];
        $totalSelesai = 0;
        $totalBelumSelesai = 0;
        $totalLinkedinSynced = 0;

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

            $syncList = $syncMap->get($item->biodata_id);
            $latestSync = $syncList ? $syncList->first() : null;
            $syncCount = $syncList ? $syncList->count() : 0;
            $currentLinkedinStatus = $latestSync?->status ?? 'belum_sinkron';

            if ($currentLinkedinStatus === 'approved') {
                $totalLinkedinSynced++;
            }

            // Filter status LinkedIn jika dispesifikasikan oleh pengguna
            if ($linkedinStatusTerpilih && $linkedinStatusTerpilih !== 'all') {
                if ($linkedinStatusTerpilih === 'sudah_sinkron' && $currentLinkedinStatus !== 'approved') {
                    continue;
                }
                if ($linkedinStatusTerpilih === 'pending' && $currentLinkedinStatus !== 'pending') {
                    continue;
                }
                if ($linkedinStatusTerpilih === 'belum_sinkron' && $currentLinkedinStatus !== 'belum_sinkron') {
                    continue;
                }
                if ($linkedinStatusTerpilih === 'ditolak' && $currentLinkedinStatus !== 'rejected') {
                    continue;
                }
            }

            // Identifikasi siapa reviewer dan kapan terakhir disinkronkan / disetujui
            $reviewerName = null;
            if ($latestSync) {
                if ($latestSync->reviewer) {
                    $reviewerName = $latestSync->reviewer->name;
                } elseif ($latestSync->status === 'approved') {
                    $reviewerName = 'Super Administrator';
                } elseif ($latestSync->status === 'pending') {
                    $reviewerName = 'Sistem (Scraper)';
                }
            }

            $waktuTerakhir = null;
            if ($latestSync) {
                if ($latestSync->reviewed_at) {
                    $waktuTerakhir = $latestSync->reviewed_at->format('d/m/Y H:i');
                } elseif ($latestSync->scraped_at) {
                    $waktuTerakhir = $latestSync->scraped_at->format('d/m/Y H:i');
                } elseif ($latestSync->updated_at) {
                    $waktuTerakhir = $latestSync->updated_at->format('d/m/Y H:i');
                }
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
                'status_yudisium' => $item->status_yudisium ?? ($item->status_mahasiswa ?? 'Lulus'),
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
                'linkedin_sync' => [
                    'has_sync' => (bool) $latestSync,
                    'sync_count' => $syncCount,
                    'status' => $currentLinkedinStatus,
                    'status_label' => match ($currentLinkedinStatus) {
                        'approved' => 'Sudah Disinkronkan',
                        'pending' => 'Pending Review',
                        'rejected' => 'Ditolak',
                        default => 'Belum Disinkron'
                    },
                    'scraped_at' => $latestSync?->scraped_at ? $latestSync->scraped_at->format('d/m/Y H:i') : null,
                    'reviewed_at' => $latestSync?->reviewed_at ? $latestSync->reviewed_at->format('d/m/Y H:i') : null,
                    'waktu_terakhir' => $waktuTerakhir,
                    'reviewer_name' => $reviewerName,
                    'last_job' => $latestSync?->preview['current_job'] ?? null,
                    'last_company' => $latestSync?->preview['current_company'] ?? null,
                    'sync_id' => $latestSync?->id,
                ],
            ];
        }

        $totalSemua = count($semuaAlumni);
        $rasioSelesai = $totalSemua > 0 ? (int) round(($totalSelesai / $totalSemua) * 100) : 0;
        $rasioLinkedin = $totalSemua > 0 ? (int) round(($totalLinkedinSynced / $totalSemua) * 100) : 0;

        // 4. Master Program Studi
        $daftarProdi = Prodi::orderBy('kode_prodi', 'asc')->get();

        return Inertia::render('SuperAdmin/Alumni/Index', [
            'biodatas' => $alumniList,
            'alumnis' => $alumniList,
            'daftarTahun' => $daftarTahun,
            'daftarTarget' => $daftarTarget,
            'prodis' => $daftarProdi,
            'filters' => [
                'search' => $pencarian ?? '',
                'tahun' => $tahunTerpilih,
                'semester' => $semesterTerpilih,
                'target' => $targetTerpilih ?? 'all',
                'status' => $statusTerpilih,
                'prodi_id' => $prodiIdTerpilih,
                'linkedin_status' => $linkedinStatusTerpilih,
            ],
            'stats' => [
                'total_alumni' => $totalSemua,
                'total_selesai' => $totalSelesai,
                'total_belum_selesai' => $totalBelumSelesai,
                'persentase_selesai' => $rasioSelesai,
                'total_linkedin_synced' => $totalLinkedinSynced,
                'persentase_linkedin' => $rasioLinkedin,
            ],
        ]);
    }

    /**
     * Download Berkas ZIP berisi Excel (.xls) per Lulusan untuk Prodi dan Tahun Tertentu.
     *
     * @param  Request  $request  HTTP request yang memuat prodi_id dan tahun
     * @return BinaryFileResponse|RedirectResponse
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
        $auditQuery = DB::table('v_alumni_audit_rekap')
            ->where('prodi_id', $prodiId);

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
        $zipFileName = "Tracer_Study_{$cleanProdiName}_{$cleanTahun}.zip";

        $zipPath = tempnam(sys_get_temp_dir(), 'tracer_zip_');
        $zip = new \ZipArchive;

        if ($zip->open($zipPath, \ZipArchive::CREATE | \ZipArchive::OVERWRITE) !== true) {
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
     * Kirim Email Pengingat Kuesioner ke Satu Alumni Tertentu.
     *
     * @param  Request  $request  HTTP request
     * @param  int|string  $id  ID biodata alumni target
     * @return RedirectResponse Redirect kembali dengan flash notification
     */
    public function sendReminderEmail(Request $request, int|string $id): RedirectResponse
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
}
