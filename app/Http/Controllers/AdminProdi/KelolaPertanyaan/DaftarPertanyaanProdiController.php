<?php

namespace App\Http\Controllers\AdminProdi\KelolaPertanyaan;

use App\Http\Controllers\Controller;
use App\Models\ProdiQuestion;
use App\Models\ProdiQuestionSection;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Inertia\Inertia;
use Inertia\Response;

/**
 * Controller untuk menampilkan daftar butir pertanyaan kuesioner khusus Program Studi yang sedang login.
 */
class DaftarPertanyaanProdiController extends Controller
{
    /**
     * Menampilkan halaman utama Kelola Pertanyaan Program Studi.
     */
    public function index(Request $request): Response
    {
        $user = Auth::user()->load('prodi');
        $prodi = $user->prodi;
        $prodiId = $user->prodi_id;

        // Ambil seluruh section kuesioner prodi ini
        $sections = $prodiId
            ? ProdiQuestionSection::where('prodi_id', $prodiId)
                ->withCount('questions')
                ->orderBy('order', 'asc')
                ->get()
            : collect();

        // Ambil seluruh pertanyaan khusus prodi ini beserta relasi section dan options
        $questions = $prodiId
            ? ProdiQuestion::where('prodi_id', $prodiId)
                ->with(['section', 'options'])
                ->orderBy('order', 'asc')
                ->get()
            : collect();

        // Ambil kode pertanyaan yang tersedia untuk target lompatan (jump_to)
        $availableJumpTargets = $prodiId
            ? ProdiQuestion::where('prodi_id', $prodiId)
                ->whereNotNull('code')
                ->orderBy('order', 'asc')
                ->pluck('code')
            : collect();

        return Inertia::render('AdminProdi/Pertanyaan/Index', [
            'user' => $user,
            'prodi' => $prodi,
            'questions' => $questions,
            'sections' => $sections,
            'availableJumpTargets' => $availableJumpTargets,
        ]);
    }
}
