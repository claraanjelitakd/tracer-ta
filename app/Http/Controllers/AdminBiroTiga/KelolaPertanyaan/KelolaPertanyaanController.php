<?php

namespace App\Http\Controllers\AdminBiroTiga\KelolaPertanyaan;

use App\Http\Controllers\Controller;
use App\Models\KelompokPertanyaan;
use App\Models\Prodi;
use App\Models\RefSubpertanyaan2021;
use App\Models\RefSubpertanyaanDetil;
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
        $subpertanyaans = RefSubpertanyaan2021::with(['kelompokPertanyaan.kuesioner', 'detils'])
            ->orderBy('order', 'asc')
            ->get();

        $sections = KelompokPertanyaan::with('kuesioner')
            ->orderBy('order', 'asc')
            ->get();

        // Daftar program studi untuk target pertanyaan khusus
        $prodis = Prodi::orderBy('kode_prodi', 'asc')->get(['id', 'kode_prodi', 'nama_prodi']);

        // Peta kode pertanyaan ke teks pertanyaan untuk referensi jump_to di tabel
        $targetQuestionMap = RefSubpertanyaan2021::pluck('subpertanyaan', 'kode_pertanyaan')->toArray();

        // Pilihan target jump_to untuk dropdown opsi (dengan label kode + teks lengkap)
        $availableJumpTargets = RefSubpertanyaan2021::orderBy('order', 'asc')
            ->get()
            ->map(function ($q) {
                return [
                    'kode_pertanyaan' => $q->kode_pertanyaan,
                    'label' => $q->kode_pertanyaan.' — '.Str::limit($q->subpertanyaan, 65),
                    'text' => $q->subpertanyaan,
                ];
            });

        return Inertia::render('AdminBiroTiga/Pertanyaan/Index', [
            'subpertanyaans' => $subpertanyaans,
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
            'kelompok_pertanyaan_id' => 'required|exists:kelompok_pertanyaan,id',
            'kode_pertanyaan' => 'required|string|unique:ref_subpertanyaan2021,kode_pertanyaan|max:50',
            'subpertanyaan' => 'required|string',
            'type' => 'required|string',
            'wajib' => 'boolean',
            'order' => 'nullable|integer',
        ]);

        $validated['wajib'] = $request->boolean('wajib');

        // Otomatisasi urutan pertanyaan jika tidak diisi manual
        if (empty($validated['order'])) {
            $validated['order'] = (RefSubpertanyaan2021::max('order') ?? 0) + 1;
        }

        RefSubpertanyaan2021::create($validated);

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
        $subpertanyaan = RefSubpertanyaan2021::findOrFail($id);

        $validated = $request->validate([
            'kelompok_pertanyaan_id' => 'required|exists:kelompok_pertanyaan,id',
            'kode_pertanyaan' => 'required|string|max:50|unique:ref_subpertanyaan2021,kode_pertanyaan,'.$subpertanyaan->id,
            'subpertanyaan' => 'required|string',
            'type' => 'required|string',
            'wajib' => 'boolean',
            'order' => 'nullable|integer',
        ]);

        $validated['wajib'] = $request->boolean('wajib');

        if (empty($validated['order'])) {
            unset($validated['order']);
        }

        $subpertanyaan->update($validated);

        return redirect()->back()->with('success', 'Pertanyaan berhasil diperbarui.');
    }

    /**
     * Menghapus data pertanyaan beserta opsi-opsinya
     */
    public function destroy($id)
    {
        $subpertanyaan = RefSubpertanyaan2021::findOrFail($id);
        $subpertanyaan->detils()->delete();
        $subpertanyaan->delete();

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

            $currentSubpertanyaan = RefSubpertanyaan2021::findOrFail($validated['id']);
            $operator = $validated['direction'] === 'up' ? '<' : '>';
            $sortOrder = $validated['direction'] === 'up' ? 'desc' : 'asc';

            // Pindah hanya dalam section yang sama
            $adjacentSubpertanyaan = RefSubpertanyaan2021::where('kelompok_pertanyaan_id', $currentSubpertanyaan->kelompok_pertanyaan_id)
                ->where('order', $operator, $currentSubpertanyaan->order)
                ->orderBy('order', $sortOrder)
                ->first();

            if ($adjacentSubpertanyaan) {
                $tempOrder = $currentSubpertanyaan->order;
                $currentSubpertanyaan->update(['order' => $adjacentSubpertanyaan->order]);
                $adjacentSubpertanyaan->update(['order' => $tempOrder]);
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
                RefSubpertanyaan2021::where('id', $item['id'])->update(['order' => $item['order']]);
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
        $subpertanyaan = RefSubpertanyaan2021::findOrFail($questionId);

        $validated = $request->validate([
            'code' => 'nullable|string|max:50',
            'option_text' => 'required|string',
            'jump_to' => 'nullable|string|max:50',
            'order' => 'nullable|integer',
        ]);

        $order = $validated['order'] ?? (($subpertanyaan->detils()->max('order') ?? 0) + 1);
        $kodeOpsi = $validated['kode_opsi'] ?? $validated['code'] ?? null;

        if (empty($kodeOpsi)) {
            $optionCount = $subpertanyaan->detils()->count() + 1;
            $kodeOpsi = $subpertanyaan->kode_pertanyaan.'-'.str_pad($optionCount, 2, '0', STR_PAD_LEFT);
        }

        $subpertanyaan->detils()->create([
            'kode_pertanyaan' => $subpertanyaan->kode_pertanyaan,
            'kode_opsi' => $kodeOpsi,
            'option_text' => $validated['option_text'],
            'jump_to' => $validated['jump_to'] ?? null,
            'order' => $order,
        ]);

        return redirect()->back()->with('success', 'Pilihan opsi berhasil ditambahkan.');
    }

    /**
     * Memperbarui opsi pertanyaan termasuk jump_to
     */
    public function updateOption(Request $request, $optionId)
    {
        $option = RefSubpertanyaanDetil::findOrFail($optionId);

        $validated = $request->validate([
            'kode_opsi' => 'nullable|string|max:50',
            'code' => 'nullable|string|max:50',
            'option_text' => 'required|string',
            'jump_to' => 'nullable|string|max:50',
            'order' => 'nullable|integer',
        ]);

        $data = [
            'option_text' => $validated['option_text'],
            'jump_to' => $validated['jump_to'] ?? null,
        ];

        if (isset($validated['kode_opsi']) || isset($validated['code'])) {
            $data['kode_opsi'] = $validated['kode_opsi'] ?? $validated['code'];
        }

        if (! empty($validated['order'])) {
            $data['order'] = $validated['order'];
        }

        $option->update($data);

        return redirect()->back()->with('success', 'Pilihan opsi berhasil diperbarui.');
    }

    /**
     * Menghapus opsi pertanyaan
     */
    public function destroyOption($optionId)
    {
        $option = RefSubpertanyaanDetil::findOrFail($optionId);
        $option->delete();

        return redirect()->back()->with('success', 'Pilihan opsi berhasil dihapus.');
    }
}
