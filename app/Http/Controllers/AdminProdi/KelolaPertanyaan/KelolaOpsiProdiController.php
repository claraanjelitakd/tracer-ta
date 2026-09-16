<?php

namespace App\Http\Controllers\AdminProdi\KelolaPertanyaan;

use App\Http\Controllers\Controller;
use App\Models\ProdiQuestion;
use App\Models\ProdiQuestionOption;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

/**
 * Controller untuk mengelola opsi jawaban pada pertanyaan kuesioner program studi.
 */
class KelolaOpsiProdiController extends Controller
{
    /**
     * Menambahkan opsi jawaban baru pada pertanyaan kuesioner prodi.
     */
    public function store(Request $request): RedirectResponse
    {
        $user = Auth::user();

        $validated = $request->validate([
            'question_id' => 'required|exists:prodi_question,id',
            'option_text' => 'required|string',
            'code' => 'nullable|string|max:50',
            'jump_to' => 'nullable|string|max:50',
            'order' => 'nullable|integer',
        ]);

        // Verifikasi pertanyaan milik prodi admin ini
        $question = ProdiQuestion::where('id', $validated['question_id'])
            ->where('prodi_id', $user->prodi_id)
            ->firstOrFail();

        if (empty($validated['code'])) {
            $count = $question->options()->count() + 1;
            $validated['code'] = $question->code.'-'.str_pad($count, 2, '0', STR_PAD_LEFT);
        }

        if (empty($validated['order'])) {
            $validated['order'] = ($question->options()->max('order') ?? 0) + 1;
        }

        ProdiQuestionOption::create([
            'prodi_question_id' => $question->id,
            'code' => $validated['code'],
            'option_text' => $validated['option_text'],
            'jump_to' => $validated['jump_to'] ?? null,
            'order' => $validated['order'],
        ]);

        return redirect()->back()->with('success', 'Opsi pilihan jawaban berhasil ditambahkan.');
    }

    /**
     * Memperbarui teks atau kode opsi jawaban.
     */
    public function update(Request $request, int $id): RedirectResponse
    {
        $user = Auth::user();
        $option = ProdiQuestionOption::with('question')->findOrFail($id);

        if ($option->question->prodi_id !== $user->prodi_id) {
            abort(403, 'Anda tidak memiliki hak akses untuk mengubah opsi ini.');
        }

        $validated = $request->validate([
            'option_text' => 'required|string',
            'code' => 'nullable|string|max:50',
            'jump_to' => 'nullable|string|max:50',
            'order' => 'nullable|integer',
        ]);

        $option->update($validated);

        return redirect()->back()->with('success', 'Opsi pilihan jawaban berhasil diperbarui.');
    }

    /**
     * Menghapus opsi jawaban.
     */
    public function destroy(int $id): RedirectResponse
    {
        $user = Auth::user();
        $option = ProdiQuestionOption::with('question')->findOrFail($id);

        if ($option->question->prodi_id !== $user->prodi_id) {
            abort(403, 'Anda tidak memiliki hak akses untuk menghapus opsi ini.');
        }

        $option->delete();

        return redirect()->back()->with('success', 'Opsi pilihan jawaban berhasil dihapus.');
    }
}
