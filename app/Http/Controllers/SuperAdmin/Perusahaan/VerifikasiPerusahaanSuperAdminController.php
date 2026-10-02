<?php

namespace App\Http\Controllers\SuperAdmin\Perusahaan;

use App\Http\Controllers\Controller;
use App\Models\Kabupaten;
use App\Models\Perusahaan;
use App\Models\Prodi;
use App\Models\Propinsi;
use App\Services\Perusahaan\PerusahaanVerificationService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Inertia\Inertia;
use Inertia\Response;

/**
 * Controller Verifikasi & Approval Perusahaan untuk Super Administrator (Otoritas Tertinggi Segenap Prodi).
 */
class VerifikasiPerusahaanSuperAdminController extends Controller
{
    public function __construct(
        protected PerusahaanVerificationService $verificationService
    ) {}

    /**
     * Menampilkan daftar pengajuan perusahaan alumni untuk disetujui/diverifikasi oleh Superadmin.
     */
    public function index(Request $request): Response
    {
        $user = Auth::user();

        $filters = [
            'search' => $request->query('search', ''),
            'scope' => $request->query('scope', 'all'), // 'all' atau 'prodi'
            'prodi_id' => $request->query('prodi_id', ''),
            'status' => $request->query('status', 'all'),
            'per_page' => (int) $request->query('per_page', 10),
        ];

        $prodis = Prodi::select('id', 'nama_prodi', 'kode_prodi')
            ->orderBy('nama_prodi')
            ->get();

        if (! empty($filters['prodi_id'])) {
            $prodiId = (int) $filters['prodi_id'];
            $companies = $this->verificationService->getPendingForProdi($prodiId, $filters);
        } else {
            $query = Perusahaan::query()
                ->with([
                    'propinsi',
                    'kabupaten',
                    'creator.biodata.prodi:id,nama_prodi',
                    'createdProdi:id,nama_prodi',
                    'biodata' => function ($q) {
                        $q->select('id', 'user_id', 'nim', 'prodi_id', 'posisi_jabatan', 'kategori_pekerjaan', 'perusahaan_id')
                            ->with(['dataAkademik:nim,nama,tahun_lulus', 'prodi:id,nama_prodi']);
                    },
                ]);

            if ($filters['status'] !== 'all') {
                $query->where('status_verifikasi', $filters['status']);
            }

            if (! empty($filters['search'])) {
                $search = trim($filters['search']);
                $query->where(function ($q) use ($search) {
                    $q->where('nama_perusahaan', 'like', "%{$search}%")
                        ->orWhere('alamat', 'like', "%{$search}%")
                        ->orWhereHas('propinsi', fn ($p) => $p->where('nama_provinsi', 'like', "%{$search}%"))
                        ->orWhereHas('kabupaten', fn ($k) => $k->where('nama_kabupaten', 'like', "%{$search}%"))
                        ->orWhereHas('biodata', fn ($b) => $b->where('nim', 'like', "%{$search}%")->orWhereHas('dataAkademik', fn ($da) => $da->where('nama', 'like', "%{$search}%")))
                        ->orWhereHas('creator', fn ($u) => $u->where('name', 'like', "%{$search}%")->orWhere('username', 'like', "%{$search}%"));
                });
            }

            $companies = $query->latest('updated_at')->paginate($filters['per_page'])->withQueryString();
        }

        // Lampirkan data rekomendasi kemiripan perusahaan terverifikasi untuk membantu konsolidasi/penggabungan data
        $companies->getCollection()->transform(function ($company) {
            $company->recommendations = $this->verificationService->getSimilarRecommendations($company, 5);

            return $company;
        });

        $propinsis = Propinsi::select('id', 'nama_provinsi')->orderBy('nama_provinsi')->get();
        $kabupatens = Kabupaten::select('id', 'propinsi_id', 'nama_kabupaten')->orderBy('nama_kabupaten')->get();

        return Inertia::render('SuperAdmin/Perusahaan/Index', [
            'user' => $user,
            'prodis' => $prodis,
            'companies' => $companies,
            'propinsis' => $propinsis,
            'kabupatens' => $kabupatens,
            'filters' => $filters,
            'stats' => [
                'total_pending_all' => Perusahaan::where('status_verifikasi', 'Menunggu Verifikasi')->count(),
                'total_verified' => Perusahaan::where('status_verifikasi', 'Terverifikasi')->count(),
            ],
        ]);
    }

    /**
     * Setujui perusahaan baru yang diinputkan oleh alumni (Verifikasi Langsung).
     */
    public function verify(int $id): RedirectResponse
    {
        $company = Perusahaan::findOrFail($id);

        $this->verificationService->verifyDirectly($company);

        return redirect()->back()->with('success', "Perusahaan \"{$company->nama_perusahaan}\" berhasil diverifikasi menjadi master data.");
    }

    /**
     * Mengganti perusahaan pengajuan alumni dengan master perusahaan terverifikasi (Auto Replace).
     */
    public function replace(Request $request, int $id): RedirectResponse
    {
        $pendingCompany = Perusahaan::findOrFail($id);

        $validated = $request->validate([
            'target_company_id' => 'required|exists:perusahaan,id',
        ]);

        $verifiedCompany = Perusahaan::where('id', $validated['target_company_id'])
            ->where('status_verifikasi', 'Terverifikasi')
            ->firstOrFail();

        $result = $this->verificationService->replaceCompany($pendingCompany, $verifiedCompany);

        return redirect()->back()->with('success', "Perusahaan berhasil disetujui & digantikan dengan \"{$result['replaced_name']}\". Data {$result['affected_count']} alumni otomatis disesuaikan.");
    }

    /**
     * Mengedit detail atribut perusahaan oleh Superadmin lalu langsung diverifikasi.
     */
    public function updateAndVerify(Request $request, int $id): RedirectResponse
    {
        $company = Perusahaan::findOrFail($id);

        $validated = $request->validate([
            'nama_perusahaan' => 'required|string|max:255',
            'alamat' => 'nullable|string',
            'propinsi_id' => 'nullable|exists:propinsi,id',
            'kabupaten_id' => 'nullable|exists:kabupaten,id',
            'kode_pos' => 'nullable|string|max:15',
            'sektor' => 'nullable|string|max:100',
            'skala' => 'nullable|string|in:Lokal,Nasional,Internasional',
            'jenis_perusahaan' => 'nullable|string|max:100',
            'jenis_perusahaan_lainnya' => 'nullable|string|max:150',
            'jenis_lokasi' => 'required|string|in:Dalam Negeri,Luar Negeri',
            'negara' => 'nullable|string|max:100',
        ]);

        $this->verificationService->updateAndVerify($company, $validated, true);

        return redirect()->back()->with('success', "Data perusahaan \"{$company->nama_perusahaan}\" berhasil diperbarui dan diverifikasi.");
    }

    /**
     * Menolak pengajuan perusahaan.
     */
    public function reject(Request $request, int $id): RedirectResponse
    {
        $company = Perusahaan::findOrFail($id);

        $this->verificationService->rejectCompany($company);

        return redirect()->back()->with('success', "Pengajuan perusahaan \"{$company->nama_perusahaan}\" telah ditolak.");
    }

    /**
     * Endpoint API pencarian master perusahaan terverifikasi untuk modal penggantian.
     */
    public function searchVerified(Request $request): JsonResponse
    {
        $query = $request->query('q', '');
        $results = $this->verificationService->searchVerified($query, 12);

        return response()->json($results);
    }
}
