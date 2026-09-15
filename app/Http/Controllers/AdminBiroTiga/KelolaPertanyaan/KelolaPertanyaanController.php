<?php

namespace App\Http\Controllers\AdminBiroTiga\KelolaPertanyaan;

use App\Http\Controllers\Controller;
use App\Models\Prodi;
use App\Models\Question;
use App\Models\QuestionOption;
use App\Models\QuestionSection;
use Illuminate\Http\Request;
use Illuminate\Support\Str;
use Inertia\Inertia;

/**
 * KelolaPertanyaanController
 *
 * Fungsi: Mengelola instrumen kuesioner tracer study (Pertanyaan, Opsi, Jump Logic, dan Scoping Prodi).
 * Fitur:
 * 1. Menampilkan daftar pertanyaan bergaya Google Forms dengan alur percabangan (jump logic) transparan.
 * 2. Menambah, mengedit, dan menghapus pertanyaan.
 * 3. Menyusun / memindahkan urutan pertanyaan (reorder / move up / down).
 * 4. Mengelola opsi jawaban tanpa mengharuskan pengisian urutan manual.
 */
class KelolaPertanyaanController extends Controller
{
    /**
     * Menampilkan daftar pertanyaan beserta opsi dan jump logic
     */
    public function index(Request $request)
    {
        // Memuat semua pertanyaan beserta relasi section dan options
        $questions = Question::with(['section.questionnaire', 'options'])
            ->orderBy('order', 'asc')
            ->get();

        $sections = QuestionSection::with('questionnaire')
            ->orderBy('order', 'asc')
            ->get();

        // Daftar program studi untuk target pertanyaan khusus
        $prodis = Prodi::orderBy('kode_prodi', 'asc')->get(['id', 'kode_prodi', 'nama_prodi']);

        // Peta kode pertanyaan ke teks pertanyaan untuk referensi jump_to di tabel
        $targetQuestionMap = Question::pluck('subpertanyaan', 'kode_pertanyaan')->toArray();

        // Pilihan target jump_to untuk dropdown opsi (dengan label kode + teks lengkap)
        $availableJumpTargets = Question::orderBy('order', 'asc')
            ->get()
            ->map(function ($q) {
                return [
                    'code' => $q->code,
                    'label' => $q->code.' — '.Str::limit($q->question_text, 65),
                    'text' => $q->question_text,
                ];
            });

        return Inertia::render('AdminBiroTiga/Pertanyaan/Index', [
            'questions' => $questions,
            'sections' => $sections,
            'prodis' => $prodis,
            'availableJumpTargets' => $availableJumpTargets,
            'targetQuestionMap' => $targetQuestionMap,
        ]);
    }

    /**
     * Menyimpan pertanyaan baru
     */
    public function store(Request $request)
    {
        $validated = $request->validate([
            'question_section_id' => 'required|exists:kelompok_pertanyaans,id',
            'code' => 'required|string|unique:ref_subpertanyaan2021,kode_pertanyaan|max:50',
            'question_text' => 'required|string',
            'type' => 'required|string',
            'is_required' => 'boolean',
            'order' => 'nullable|integer',
        ]);

        $validated['is_required'] = $request->boolean('is_required');

        // Otomatisasi urutan pertanyaan jika tidak diisi manual
        if (empty($validated['order'])) {
            $validated['order'] = (Question::max('order') ?? 0) + 1;
        }

        Question::create($validated);

        return redirect()->back()->with('success', 'Pertanyaan baru berhasil ditambahkan.');
    }

    /**
     * Memperbarui data butir pertanyaan.
     *
     * @param  int  $id
     * @return RedirectResponse
     */
    public function update(Request $request, $id)
    {
        $question = Question::findOrFail($id);

        $validated = $request->validate([
            'question_section_id' => 'required|exists:kelompok_pertanyaans,id',
            'code' => 'required|string|max:50|unique:ref_subpertanyaan2021,kode_pertanyaan,'.$question->id,
            'question_text' => 'required|string',
            'type' => 'required|string',
            'is_required' => 'boolean',
            'order' => 'nullable|integer',
        ]);

        $validated['is_required'] = $request->boolean('is_required');

        if (empty($validated['order'])) {
            unset($validated['order']);
        }

        $question->update($validated);

        return redirect()->back()->with('success', 'Pertanyaan berhasil diperbarui.');
    }

    /**
     * Menghapus data pertanyaan beserta opsi-opsinya
     */
    public function destroy($id)
    {
        $question = Question::findOrFail($id);
        $question->options()->delete();
        $question->delete();

        return redirect()->back()->with('success', 'Pertanyaan dan seluruh opsinya berhasil dihapus.');
    }

    /**
     * Memindahkan / mengubah urutan pertanyaan (Google Forms style)
     */
    public function reorder(Request $request)
    {
        // Opsi A: Pemindahan langsung berdasarkan arah (direction: 'up' atau 'down') dalam section yang sama
        if ($request->has(['id', 'direction'])) {
            $validated = $request->validate([
                'id' => 'required|exists:ref_subpertanyaan2021,id',
                'direction' => 'required|in:up,down',
            ]);

            $currentQuestion = Question::findOrFail($validated['id']);
            $operator = $validated['direction'] === 'up' ? '<' : '>';
            $sortOrder = $validated['direction'] === 'up' ? 'desc' : 'asc';

            // Pindah hanya dalam section yang sama
            $adjacentQuestion = Question::where('question_section_id', $currentQuestion->question_section_id)
                ->where('order', $operator, $currentQuestion->order)
                ->orderBy('order', $sortOrder)
                ->first();

            if ($adjacentQuestion) {
                $tempOrder = $currentQuestion->order;
                $currentQuestion->update(['order' => $adjacentQuestion->order]);
                $adjacentQuestion->update(['order' => $tempOrder]);
            }

            return redirect()->back()->with('success', 'Posisi pertanyaan berhasil dipindahkan.');
        }

        // Opsi B: Pemindahan melalui array urutan batch
        if ($request->has('orders')) {
            $validated = $request->validate([
                'orders' => 'required|array',
                'orders.*.id' => 'required|exists:ref_subpertanyaan2021,id',
                'orders.*.order' => 'required|integer',
            ]);

            foreach ($validated['orders'] as $item) {
                Question::where('id', $item['id'])->update(['order' => $item['order']]);
            }

            return redirect()->back()->with('success', 'Seluruh urutan pertanyaan berhasil diperbarui.');
        }

        return redirect()->back()->withErrors(['message' => 'Parameter pengurutan tidak valid.']);
    }

    /**
     * Menyimpan opsi baru untuk pertanyaan (tanpa perlu input manual urutan)
     */
    public function storeOption(Request $request, $questionId)
    {
        $question = Question::findOrFail($questionId);

        $validated = $request->validate([
            'code' => 'nullable|string|max:50',
            'option_text' => 'required|string',
            'jump_to' => 'nullable|string|max:50',
            'order' => 'nullable|integer',
        ]);

        // Urutan otomatis dihitung di backend (urutan terakhir + 1)
        if (empty($validated['order'])) {
            $validated['order'] = ($question->options()->max('order') ?? 0) + 1;
        }

        // Kode opsi otomatis jika dikosongkan (misal: F3-01)
        if (empty($validated['code'])) {
            $optionCount = $question->options()->count() + 1;
            $validated['code'] = $question->code.'-'.str_pad($optionCount, 2, '0', STR_PAD_LEFT);
        }

        // Bersihkan empty string menjadi null
        if (empty($validated['jump_to'])) {
            $validated['jump_to'] = null;
        }

        $question->options()->create($validated);

        return redirect()->back()->with('success', 'Pilihan opsi berhasil ditambahkan.');
    }

    /**
     * Memperbarui opsi pertanyaan termasuk jump_to
     */
    public function updateOption(Request $request, $optionId)
    {
        $option = QuestionOption::findOrFail($optionId);

        $validated = $request->validate([
            'code' => 'nullable|string|max:50',
            'option_text' => 'required|string',
            'jump_to' => 'nullable|string|max:50',
            'order' => 'nullable|integer',
        ]);

        if (empty($validated['jump_to'])) {
            $validated['jump_to'] = null;
        }

        if (empty($validated['order'])) {
            unset($validated['order']); // Pertahankan urutan yang sudah ada
        }

        $option->update($validated);

        return redirect()->back()->with('success', 'Pilihan opsi berhasil diperbarui.');
    }

    /**
     * Menghapus opsi pertanyaan
     */
    public function destroyOption($optionId)
    {
        $option = QuestionOption::findOrFail($optionId);
        $option->delete();

        return redirect()->back()->with('success', 'Pilihan opsi berhasil dihapus.');
    }
}
