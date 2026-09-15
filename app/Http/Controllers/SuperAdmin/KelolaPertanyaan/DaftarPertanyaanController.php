<?php

namespace App\Http\Controllers\SuperAdmin\KelolaPertanyaan;

use App\Http\Controllers\Controller;
use App\Models\KelompokPertanyaan;
use App\Models\Prodi;
use App\Models\RefSubpertanyaan2021;
use Illuminate\Http\Request;
use Illuminate\Support\Str;
use Inertia\Inertia;
use Inertia\Response;

/**
 * Class DaftarPertanyaanController
 *
 * Fungsi:
 * Controller khusus untuk menampilkan daftar butir pertanyaan instrumen kuesioner Tracer Study bagi Superadmin.
 * Mengambil data section, subpertanyaans, prodi, dan mapping target percabangan (jump logic) untuk dirender di Frontend.
 */
class DaftarPertanyaanController extends Controller
{
    /**
     * Menampilkan halaman utama Kelola Pertanyaan kuesioner dengan filter section & jump target.
     *
     * @return Response
     */
    public function index(Request $request)
    {
        // 1. Ambil seluruh pertanyaan beserta relasi section dan opsi
        $subpertanyaans = RefSubpertanyaan2021::with(['kelompokPertanyaan.kuesioner', 'detils'])
            ->orderBy('order', 'asc')
            ->get();

        // 2. Ambil seluruh section kuesioner berurutan
        $sections = KelompokPertanyaan::with('kuesioner')
            ->orderBy('order', 'asc')
            ->get();

        // 3. Ambil daftar program studi aktif
        $prodis = Prodi::orderBy('kode_prodi', 'asc')->get(['id', 'kode_prodi', 'nama_prodi']);

        // 4. Map kode pertanyaan ke teks pertanyaan untuk label percabangan jump_to
        $targetQuestionMap = RefSubpertanyaan2021::pluck('subpertanyaan', 'kode_pertanyaan')->toArray();

        // 5. Susun daftar pilihan target lompatan untuk dropdown dan pemilih bertingkat (grouped by section)
        $availableJumpTargets = RefSubpertanyaan2021::with('kelompokPertanyaan')
            ->orderBy('order', 'asc')
            ->get()
            ->map(function ($q) {
                // Tentukan judul section untuk pengelompokan di dropdown bertingkat
                $sectionTitle = $q->kelompokPertanyaan
                    ? 'Kelompok '.$q->kelompokPertanyaan->order.': '.$q->kelompokPertanyaan->title
                    : 'Pertanyaan Lainnya / Tanpa Section';

                return [
                    'id' => $q->id,
                    'kode_pertanyaan' => $q->kode_pertanyaan,
                    'label' => $q->kode_pertanyaan.' — '.Str::limit($q->subpertanyaan, 65),
                    'text' => $q->subpertanyaan,
                    'kelompok_pertanyaan_id' => $q->kelompok_pertanyaan_id,
                    'section_title' => $sectionTitle,
                    'section_order' => $q->kelompokPertanyaan?->order ?? 999,
                ];
            });

        // 6. Render komponen Vue SuperAdmin/Pertanyaan/Index
        return Inertia::render('SuperAdmin/Pertanyaan/Index', [
            'subpertanyaans' => $subpertanyaans,
            'sections' => $sections,
            'prodis' => $prodis,
            'availableJumpTargets' => $availableJumpTargets,
            'targetQuestionMap' => $targetQuestionMap,
        ]);
    }
}
