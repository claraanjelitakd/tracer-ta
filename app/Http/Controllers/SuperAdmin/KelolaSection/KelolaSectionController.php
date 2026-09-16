<?php

namespace App\Http\Controllers\SuperAdmin\KelolaSection;

use App\Http\Controllers\Controller;
use App\Models\KelompokPertanyaan;
use App\Models\Kuesioner;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

/**
 * Class KelolaSectionController
 *
 * Fungsi:
 * Controller untuk mengelola seluruh siklus hidup Bagian Kuesioner (KelompokPertanyaan)
 * pada instrumen Tracer Study bagi pengguna berhak akses SuperAdmin.
 *
 * Cakupan Operasi:
 * 1. Menampilkan daftar section kuesioner berserta jumlah soal dan kuesioner induk (index)
 * 2. Menambah section baru dengan otomatisasi penomoran urutan (store)
 * 3. Memperbarui informasi judul/kuesioner section (update)
 * 4. Menghapus section beserta seluruh pertanyaan dan opsi di dalamnya (destroy)
 * 5. Mengatur ulang urutan naik/turun posisi section (reorder)
 */
class KelolaSectionController extends Controller
{
    /**
     * Menampilkan halaman utama Kelola Section Kuesioner.
     *
     * @param  Request  $request  Objek HTTP request yang masuk
     * @return Response Respons Inertia yang merender komponen SuperAdmin/Section/Index
     */
    public function index(Request $request): Response
    {
        // 1. Ambil seluruh data section berurutan beserta relasi kuesioner dan total pertanyaan
        $sections = KelompokPertanyaan::with('kuesioner')
            ->withCount('subpertanyaans')
            ->orderBy('order', 'asc')
            ->get();

        // 2. Ambil daftar kuesioner aktif untuk pilihan dropdown saat menambah atau mengedit section
        $kuesioners = Kuesioner::orderBy('year', 'desc')
            ->orderBy('id', 'desc')
            ->get(['id', 'title', 'year', 'is_active']);

        // 3. Render halaman Vue dengan data yang dibutuhkan
        return Inertia::render('SuperAdmin/Section/Index', [
            'sections' => $sections,
            'kuesioners' => $kuesioners,
            'questionnaires' => $kuesioners,
        ]);
    }

    /**
     * Menyimpan data section kuesioner baru ke dalam database.
     *
     * @param  Request  $request  Objek HTTP request berisi data form section
     * @return RedirectResponse Redirect kembali ke halaman sebelumnya dengan flash message
     */
    public function store(Request $request): RedirectResponse
    {
        // Validasi input form section baru
        $validated = $request->validate([
            'kuesioner_id' => 'nullable|exists:kuesioner,id',
            'questionnaire_id' => 'nullable|exists:kuesioner,id',
            'title' => 'required|string|max:255',
            'order' => 'nullable|integer|min:1',
        ]);

        $kuesionerId = $validated['kuesioner_id'] ?? $validated['questionnaire_id'] ?? null;
        if (! $kuesionerId) {
            return redirect()->back()->withErrors(['kuesioner_id' => 'Kuesioner induk wajib dipilih.']);
        }

        $validated['kuesioner_id'] = $kuesionerId;
        $validated['questionnaire_id'] = $kuesionerId;

        // Jika nomor urutan dikosongkan oleh pengguna, tentukan nomor urut berikutnya secara otomatis
        if (empty($validated['order'])) {
            $maxOrder = KelompokPertanyaan::where('kuesioner_id', $kuesionerId)->max('order') ?? 0;
            $validated['order'] = $maxOrder + 1;
        }

        // Buat record section baru di database
        KelompokPertanyaan::create($validated);

        return redirect()->back()->with('success', 'Bagian kuesioner baru berhasil ditambahkan.');
    }

    /**
     * Memperbarui data judul atau urutan section kuesioner yang sudah ada.
     *
     * @param  Request  $request  Objek HTTP request berisi data pembaruan
     * @param  int  $id  ID primary key dari KelompokPertanyaan yang diperbarui
     * @return RedirectResponse Redirect kembali dengan feedback notifikasi
     */
    public function update(Request $request, int $id): RedirectResponse
    {
        // Cari record section berdasarkan ID
        $section = KelompokPertanyaan::findOrFail($id);

        // Validasi masukan data perubahan
        $validated = $request->validate([
            'kuesioner_id' => 'nullable|exists:kuesioner,id',
            'questionnaire_id' => 'nullable|exists:kuesioner,id',
            'title' => 'required|string|max:255',
            'order' => 'nullable|integer|min:1',
        ]);

        $kuesionerId = $validated['kuesioner_id'] ?? $validated['questionnaire_id'] ?? $section->kuesioner_id;
        $validated['kuesioner_id'] = $kuesionerId;
        $validated['questionnaire_id'] = $kuesionerId;

        // Jika order tidak diisi, jangan timpa nilai urutan yang sudah ada
        if (empty($validated['order'])) {
            unset($validated['order']);
        }

        // Terapkan perubahan data section
        $section->update($validated);

        return redirect()->back()->with('success', 'Data bagian kuesioner berhasil diperbarui.');
    }

    /**
     * Menghapus section kuesioner beserta pertanyaan-pertanyaan yang bernaung di bawahnya.
     *
     * @param  int  $id  ID primary key KelompokPertanyaan yang akan dihapus
     * @return RedirectResponse Redirect kembali dengan pesan konfirmasi penghapusan
     */
    public function destroy(int $id): RedirectResponse
    {
        // Temukan data section yang dimaksud
        $section = KelompokPertanyaan::findOrFail($id);

        // Bersihkan opsi jawaban dari setiap pertanyaan dalam section ini untuk integritas relasi
        foreach ($section->subpertanyaans as $subpertanyaan) {
            $subpertanyaan->detils()->delete();
        }

        // Hapus seluruh pertanyaan dalam section
        $section->subpertanyaans()->delete();

        // Hapus section itu sendiri
        $section->delete();

        return redirect()->back()->with('success', 'Bagian kuesioner beserta seluruh pertanyaannya berhasil dihapus.');
    }

    /**
     * Memindahkan atau mengatur ulang urutan posisi section (naik/turun atau massal).
     *
     * @param  Request  $request  Objek HTTP request berisi instruksi pengurutan
     * @return RedirectResponse Redirect kembali ke halaman dengan susunan urutan terbaru
     */
    public function reorder(Request $request): RedirectResponse
    {
        // Skenario 1: Pemindahan posisi satu tingkat naik ('up') atau turun ('down')
        if ($request->has(['id', 'direction'])) {
            $validated = $request->validate([
                'id' => 'required|exists:kelompok_pertanyaan,id',
                'direction' => 'required|in:up,down',
            ]);

            // Ambil section target yang sedang dipindahkan
            $currentSection = KelompokPertanyaan::findOrFail($validated['id']);
            $operator = $validated['direction'] === 'up' ? '<' : '>';
            $sortOrder = $validated['direction'] === 'up' ? 'desc' : 'asc';

            // Temukan section tetangga langsung dalam kuesioner yang sama
            $adjacentSection = KelompokPertanyaan::where('kuesioner_id', $currentSection->kuesioner_id)
                ->where('order', $operator, $currentSection->order)
                ->orderBy('order', $sortOrder)
                ->first();

            // Lakukan penukaran nomor urut (swap order) jika ditemukan tetangga
            if ($adjacentSection) {
                $tempOrder = $currentSection->order;
                $currentSection->update(['order' => $adjacentSection->order]);
                $adjacentSection->update(['order' => $tempOrder]);
            }

            return redirect()->back()->with('success', 'Urutan bagian kuesioner berhasil dipindahkan.');
        }

        // Skenario 2: Pembaruan massal array urutan (misal: drag and drop)
        if ($request->has('orders')) {
            $validated = $request->validate([
                'orders' => 'required|array',
                'orders.*.id' => 'required|exists:kelompok_pertanyaan,id',
                'orders.*.order' => 'required|integer',
            ]);

            foreach ($validated['orders'] as $item) {
                KelompokPertanyaan::where('id', $item['id'])->update(['order' => $item['order']]);
            }

            return redirect()->back()->with('success', 'Seluruh susunan urutan bagian kuesioner berhasil diperbarui.');
        }

        return redirect()->back()->withErrors(['message' => 'Parameter pengurutan tidak valid.']);
    }
}
