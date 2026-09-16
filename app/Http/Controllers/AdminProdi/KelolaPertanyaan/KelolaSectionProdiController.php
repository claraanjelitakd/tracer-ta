<?php

namespace App\Http\Controllers\AdminProdi\KelolaPertanyaan;

use App\Http\Controllers\Controller;
use App\Models\ProdiQuestion;
use App\Models\ProdiQuestionSection;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Inertia\Inertia;
use Inertia\Response;

/**
 * Controller untuk mengelola Bagian (Section) Kuesioner Program Studi.
 */
class KelolaSectionProdiController extends Controller
{
    /**
     * Menampilkan halaman utama Kelola Section Kuesioner Program Studi.
     */
    public function index(Request $request): Response
    {
        $user = Auth::user()->load('prodi');
        $prodi = $user->prodi;
        $prodiId = $user->prodi_id;

        if (! $prodiId) {
            abort(403, 'Akun Anda belum terhubung dengan program studi.');
        }

        $sections = ProdiQuestionSection::where('prodi_id', $prodiId)
            ->withCount('questions')
            ->orderBy('order', 'asc')
            ->get();

        $totalQuestions = ProdiQuestion::where('prodi_id', $prodiId)->count();

        return Inertia::render('AdminProdi/Section/Index', [
            'user' => $user,
            'prodi' => $prodi,
            'sections' => $sections,
            'stats' => [
                'total_sections' => $sections->count(),
                'total_questions' => $totalQuestions,
            ],
        ]);
    }

    /**
     * Menyimpan section kuesioner prodi baru.
     */
    public function store(Request $request): RedirectResponse
    {
        $user = Auth::user();
        if (! $user->prodi_id) {
            return redirect()->back()->with('error', 'Akun Anda belum terhubung dengan program studi.');
        }

        $validated = $request->validate([
            'title' => 'required|string|max:255',
            'description' => 'nullable|string',
            'order' => 'nullable|integer',
        ]);

        $validated['prodi_id'] = $user->prodi_id;

        if (empty($validated['order'])) {
            $maxOrder = ProdiQuestionSection::where('prodi_id', $user->prodi_id)->max('order') ?? 0;
            $validated['order'] = $maxOrder + 1;
        }

        ProdiQuestionSection::create($validated);

        return redirect()->back()->with('success', 'Section kuesioner program studi berhasil ditambahkan.');
    }

    /**
     * Memperbarui section kuesioner prodi.
     */
    public function update(Request $request, int $id): RedirectResponse
    {
        $user = Auth::user();
        $section = ProdiQuestionSection::where('id', $id)
            ->where('prodi_id', $user->prodi_id)
            ->firstOrFail();

        $validated = $request->validate([
            'title' => 'required|string|max:255',
            'description' => 'nullable|string',
            'order' => 'nullable|integer',
        ]);

        if (empty($validated['order'])) {
            unset($validated['order']);
        }

        $section->update($validated);

        return redirect()->back()->with('success', 'Section kuesioner program studi berhasil diperbarui.');
    }

    /**
     * Menghapus section kuesioner prodi beserta seluruh butir pertanyaan di dalamnya.
     */
    public function destroy(int $id): RedirectResponse
    {
        $user = Auth::user();
        $section = ProdiQuestionSection::where('id', $id)
            ->where('prodi_id', $user->prodi_id)
            ->with('questions.options')
            ->firstOrFail();

        foreach ($section->questions as $question) {
            $question->options()->delete();
        }
        $section->questions()->delete();
        $section->delete();

        return redirect()->back()->with('success', 'Section kuesioner program studi berhasil dihapus.');
    }

    /**
     * Mengatur ulang urutan posisi section kuesioner prodi.
     */
    public function reorder(Request $request): RedirectResponse
    {
        $user = Auth::user();
        $prodiId = $user->prodi_id;

        if ($request->has(['id', 'direction'])) {
            $validated = $request->validate([
                'id' => 'required|exists:prodi_question_section,id',
                'direction' => 'required|in:up,down',
            ]);

            $currentSection = ProdiQuestionSection::where('id', $validated['id'])
                ->where('prodi_id', $prodiId)
                ->firstOrFail();

            $operator = $validated['direction'] === 'up' ? '<' : '>';
            $sortOrder = $validated['direction'] === 'up' ? 'desc' : 'asc';

            $adjacentSection = ProdiQuestionSection::where('prodi_id', $prodiId)
                ->where('order', $operator, $currentSection->order)
                ->orderBy('order', $sortOrder)
                ->first();

            if ($adjacentSection) {
                $tempOrder = $currentSection->order;
                $currentSection->update(['order' => $adjacentSection->order]);
                $adjacentSection->update(['order' => $tempOrder]);
            }

            return redirect()->back()->with('success', 'Urutan section berhasil dipindahkan.');
        }

        if ($request->has('orders')) {
            $validated = $request->validate([
                'orders' => 'required|array',
                'orders.*.id' => 'required|exists:prodi_question_section,id',
                'orders.*.order' => 'required|integer',
            ]);

            foreach ($validated['orders'] as $item) {
                ProdiQuestionSection::where('id', $item['id'])
                    ->where('prodi_id', $prodiId)
                    ->update(['order' => $item['order']]);
            }

            return redirect()->back()->with('success', 'Urutan section berhasil diperbarui.');
        }

        return redirect()->back()->withErrors(['message' => 'Parameter pengurutan tidak valid.']);
    }
}
