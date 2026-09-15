<?php

namespace App\Http\Controllers\SuperAdmin\KelolaPertanyaan;

use App\Http\Controllers\Controller;
use App\Models\RefSubpertanyaan2021;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;

/**
 * Class SimpanPertanyaanController
 *
 * Fungsi:
 * Controller khusus untuk menangani aksi manipulasi data butir pertanyaan:
 * 1. Tambah pertanyaan baru (store)
 * 2. Perbarui data pertanyaan (update)
 * 3. Hapus pertanyaan beserta opsinya (destroy)
 * 4. Pindah urutan pertanyaan naik/turun dalam section yang sama (reorder)
 */
class SimpanPertanyaanController extends Controller
{
    /**
     * Menyimpan butir pertanyaan baru ke dalam database.
     *
     * @return RedirectResponse
     */
    public function store(Request $request)
    {
        $validated = $request->validate([
            'kelompok_pertanyaan_id' => 'required|exists:kelompok_pertanyaans,id',
            'kode_pertanyaan' => 'required|string|max:50|unique:ref_subpertanyaan2021,kode_pertanyaan',
            'subpertanyaan' => 'required|string',
            'type' => 'required|string|in:single_choice,multiple_choice,text,number,searchable_select,radio_input,multiple_number,matrix_dual,rating_5',
            'wajib' => 'required|boolean',
            'order' => 'nullable|integer',
        ]);

        if (empty($validated['order'])) {
            $validated['order'] = (RefSubpertanyaan2021::where('kelompok_pertanyaan_id', $validated['kelompok_pertanyaan_id'])->max('order') ?? 0) + 1;
        }

        $subpertanyaan = RefSubpertanyaan2021::create($validated);

        return redirect()->back()->with('success', 'Pertanyaan baru berhasil ditambahkan.');
    }

    /**
     * Memperbarui data butir pertanyaan yang sudah ada.
     *
     * @param  int  $id
     * @return RedirectResponse
     */
    public function update(Request $request, $id)
    {
        $subpertanyaan = RefSubpertanyaan2021::findOrFail($id);

        $validated = $request->validate([
            'kelompok_pertanyaan_id' => 'required|exists:kelompok_pertanyaans,id',
            'kode_pertanyaan' => 'required|string|max:50|unique:ref_subpertanyaan2021,kode_pertanyaan,'.$subpertanyaan->id,
            'subpertanyaan' => 'required|string',
            'type' => 'required|string|in:single_choice,multiple_choice,text,number,searchable_select,radio_input,multiple_number,matrix_dual,rating_5',
            'wajib' => 'required|boolean',
            'order' => 'nullable|integer',
        ]);

        $subpertanyaan->update($validated);

        return redirect()->back()->with('success', 'Data pertanyaan berhasil diperbarui.');
    }

    /**
     * Menghapus butir pertanyaan.
     *
     * @param  int  $id
     * @return RedirectResponse
     */
    public function destroy($id)
    {
        $subpertanyaan = RefSubpertanyaan2021::findOrFail($id);
        $subpertanyaan->delete();

        return redirect()->back()->with('success', 'Pertanyaan berhasil dihapus.');
    }

    /**
     * Mengatur ulang urutan butir pertanyaan.
     *
     * Mendukung:
     * - Direct directional reorder (naik / turun) via 'id' & 'direction'
     * - Bulk drag-and-drop reorder via array 'orders'
     *
     * @return RedirectResponse
     */
    public function reorder(Request $request)
    {
        if ($request->has(['id', 'direction'])) {
            $validated = $request->validate([
                'id' => 'required|exists:ref_subpertanyaan2021,id',
                'direction' => 'required|in:up,down',
            ]);

            $currentSubpertanyaan = RefSubpertanyaan2021::findOrFail($validated['id']);
            $operator = $validated['direction'] === 'up' ? '<' : '>';
            $sortOrder = $validated['direction'] === 'up' ? 'desc' : 'asc';

            $adjacentSubpertanyaan = RefSubpertanyaan2021::where('kelompok_pertanyaan_id', $currentSubpertanyaan->kelompok_pertanyaan_id)
                ->where('order', $operator, $currentSubpertanyaan->order)
                ->orderBy('order', $sortOrder)
                ->first();

            if ($adjacentSubpertanyaan) {
                $tempOrder = $currentSubpertanyaan->order;
                $currentSubpertanyaan->update(['order' => $adjacentSubpertanyaan->order]);
                $adjacentSubpertanyaan->update(['order' => $tempOrder]);
            }

            return redirect()->back()->with('success', 'Urutan pertanyaan berhasil diperbarui.');
        }

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
}
