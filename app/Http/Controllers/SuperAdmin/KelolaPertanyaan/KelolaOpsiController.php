<?php

namespace App\Http\Controllers\SuperAdmin\KelolaPertanyaan;

use App\Http\Controllers\Controller;
use App\Models\RefSubpertanyaan2021;
use App\Models\RefSubpertanyaanDetil;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;

/**
 * Class KelolaOpsiController
 *
 * Fungsi:
 * Controller khusus untuk menangani aksi pengelolaan opsi pilihan jawaban pada pertanyaan:
 * 1. Tambah pilihan opsi jawaban baru (storeOption)
 * 2. Perbarui data opsi jawaban dan target lompatan alur branching (updateOption)
 * 3. Hapus opsi pilihan jawaban (destroyOption)
 */
class KelolaOpsiController extends Controller
{
    /**
     * Menyimpan opsi pilihan jawaban baru untuk butir pertanyaan tertentu.
     *
     * @param  int  $questionId
     * @return RedirectResponse
     */
    public function storeOption(Request $request, $questionId)
    {
        $subpertanyaan = RefSubpertanyaan2021::findOrFail($questionId);

        $validated = $request->validate([
            'kode_opsi' => 'nullable|string|max:50',
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

        return redirect()->back()->with('success', 'Pilihan opsi jawaban berhasil ditambahkan.');
    }

    /**
     * Memperbarui data opsi jawaban dan target alur percabangan.
     *
     * @param  int  $optionId
     * @return RedirectResponse
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

        return redirect()->back()->with('success', 'Pilihan opsi jawaban berhasil diperbarui.');
    }

    /**
     * Menghapus opsi pilihan jawaban.
     *
     * @param  int  $optionId
     * @return RedirectResponse
     */
    public function destroyOption($optionId)
    {
        $option = RefSubpertanyaanDetil::findOrFail($optionId);
        $option->delete();

        return redirect()->back()->with('success', 'Pilihan opsi jawaban berhasil dihapus.');
    }
}
