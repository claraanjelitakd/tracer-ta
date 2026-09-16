<?php

namespace App\Http\Controllers\AdminProdi\KelolaPertanyaan;

use App\Http\Controllers\Controller;
use App\Models\ProdiQuestion;
use App\Models\ProdiQuestionSection;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Validation\Rule;

/**
 * Controller untuk menangani aksi Tambah, Update, dan Hapus butir pertanyaan kuesioner program studi.
 */
class SimpanPertanyaanProdiController extends Controller
{
    /**
     * Menyimpan butir pertanyaan kuesioner prodi baru.
     */
    public function store(Request $request): RedirectResponse
    {
        $user = Auth::user();
        if (! $user->prodi_id) {
            return redirect()->back()->with('error', 'Akun Anda belum terhubung dengan program studi.');
        }

        $validated = $request->validate([
            'code' => [
                'required',
                'string',
                'max:50',
                Rule::unique('prodi_question', 'code')->where('prodi_id', $user->prodi_id),
            ],
            'question_text' => 'required|string',
            'type' => 'required|string|in:text,textarea,number,single_choice,radio_input,radio_text,multiple_choice,dropdown,searchable_select,rating_5,multiple_number,matrix,matrix_dual,multiple_textbox,date,time,file,header',
            'is_required' => 'boolean',
            'prodi_question_section_id' => 'nullable|exists:prodi_question_section,id',
            'order' => 'nullable|integer',
        ]);

        // Jika section tidak dipilih atau tidak ada, ambil section pertama milik prodi ini atau buat default
        if (empty($validated['prodi_question_section_id'])) {
            $section = ProdiQuestionSection::where('prodi_id', $user->prodi_id)->orderBy('order', 'asc')->first();
            if (! $section) {
                $section = ProdiQuestionSection::create([
                    'prodi_id' => $user->prodi_id,
                    'title' => 'Bagian Evaluasi & Relevansi Program Studi',
                    'order' => 1,
                ]);
            }
            $validated['prodi_question_section_id'] = $section->id;
        } else {
            // Pastikan section yang dipilih benar milik prodi ini
            $section = ProdiQuestionSection::where('id', $validated['prodi_question_section_id'])
                ->where('prodi_id', $user->prodi_id)
                ->firstOrFail();
        }

        $validated['prodi_id'] = $user->prodi_id;
        $validated['is_required'] = $request->boolean('is_required');

        if (empty($validated['order'])) {
            $maxOrder = ProdiQuestion::where('prodi_id', $user->prodi_id)
                ->where('prodi_question_section_id', $validated['prodi_question_section_id'])
                ->max('order') ?? 0;
            $validated['order'] = $maxOrder + 1;
        }

        $question = ProdiQuestion::create($validated);

        // Jika terdapat opsi awal yang dikirimkan bersama form (misal array teks opsi)
        if ($request->has('options') && is_array($request->options) && in_array($validated['type'], ['single_choice', 'multiple_choice', 'radio_input'])) {
            $orderCounter = 1;
            foreach ($request->options as $idx => $optText) {
                $trimmed = trim((string) $optText);
                if ($trimmed !== '') {
                    $question->options()->create([
                        'code' => $question->code.'-'.str_pad($idx + 1, 2, '0', STR_PAD_LEFT),
                        'option_text' => $trimmed,
                        'order' => $orderCounter++,
                    ]);
                }
            }
        }

        return redirect()->back()->with('success', 'Pertanyaan kuesioner prodi berhasil ditambahkan.');
    }

    /**
     * Memperbarui data butir pertanyaan kuesioner prodi.
     */
    public function update(Request $request, int $id): RedirectResponse
    {
        $user = Auth::user();
        $question = ProdiQuestion::where('id', $id)
            ->where('prodi_id', $user->prodi_id)
            ->firstOrFail();

        $validated = $request->validate([
            'code' => [
                'required',
                'string',
                'max:50',
                Rule::unique('prodi_question', 'code')
                    ->where('prodi_id', $user->prodi_id)
                    ->ignore($question->id),
            ],
            'question_text' => 'required|string',
            'type' => 'required|string|in:text,textarea,number,single_choice,radio_input,radio_text,multiple_choice,dropdown,searchable_select,rating_5,multiple_number,matrix,matrix_dual,multiple_textbox,date,time,file,header',
            'is_required' => 'boolean',
            'prodi_question_section_id' => 'nullable|exists:prodi_question_section,id',
            'order' => 'nullable|integer',
        ]);

        if (! empty($validated['prodi_question_section_id'])) {
            // Pastikan section milik prodi user
            ProdiQuestionSection::where('id', $validated['prodi_question_section_id'])
                ->where('prodi_id', $user->prodi_id)
                ->firstOrFail();
        }

        $validated['is_required'] = $request->boolean('is_required');

        if (empty($validated['order'])) {
            unset($validated['order']);
        }

        $question->update($validated);

        return redirect()->back()->with('success', 'Pertanyaan kuesioner prodi berhasil diperbarui.');
    }

    /**
     * Menghapus butir pertanyaan kuesioner prodi beserta seluruh opsinya.
     */
    public function destroy(int $id): RedirectResponse
    {
        $user = Auth::user();
        $question = ProdiQuestion::where('id', $id)
            ->where('prodi_id', $user->prodi_id)
            ->firstOrFail();

        $question->options()->delete();
        $question->delete();

        return redirect()->back()->with('success', 'Pertanyaan kuesioner prodi berhasil dihapus.');
    }
}
