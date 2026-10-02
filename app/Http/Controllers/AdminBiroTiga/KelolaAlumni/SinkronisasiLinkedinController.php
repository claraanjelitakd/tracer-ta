<?php

namespace App\Http\Controllers\AdminBiroTiga\KelolaAlumni;

use App\Http\Controllers\Controller;
use App\Models\Biodata;
use App\Models\Perusahaan;
use App\Services\LinkedIn\LinkedInProfileService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

/**
 * SinkronisasiLinkedinController
 *
 * Fungsi: Menangani penarikan data dari LinkedIn melalui LinkedInProfileService resmi.
 * Tujuan: Mengotomatiskan pelacakan karier alumni.
 */
class SinkronisasiLinkedinController extends Controller
{
    /**
     * Melakukan Sinkronisasi Data LinkedIn
     */
    public function sinkronisasiDataLinkedin($id, LinkedInProfileService $linkedinService)
    {
        $alumni = Biodata::findOrFail($id);

        $identitas = $alumni->linkedin_username ?: $alumni->linkedin_url;

        if (! $identitas) {
            return back()->withErrors(['message' => 'Alumni tidak memiliki username atau URL LinkedIn.']);
        }

        $result = $linkedinService->syncAlumniProfile($alumni);

        return response()->json([
            'success' => $result['status'] === 'SUCCESS',
            'data' => $result,
        ]);
    }

    /**
     * Menyimpan Hasil Sinkronisasi LinkedIn
     */
    public function simpanDataLinkedin(Request $request, $id)
    {
        $request->validate([
            'current_job' => 'nullable|string',
            'current_company' => 'nullable|string',
            'industry' => 'nullable|string',
            'location' => 'nullable|string',
        ]);

        $alumni = Biodata::findOrFail($id);

        DB::beginTransaction();
        try {
            if ($request->filled('current_job')) {
                $alumni->expert = $request->current_job;
            }

            if ($request->filled('current_company')) {
                $namaPerusahaan = trim($request->current_company);

                $perusahaan = Perusahaan::where('nama_perusahaan', 'like', $namaPerusahaan)->first();

                if (! $perusahaan) {
                    $perusahaan = Perusahaan::create([
                        'nama_perusahaan' => $namaPerusahaan,
                        'sektor' => $request->input('industry'),
                    ]);
                }

                $alumni->perusahaan_id = $perusahaan->id;
            }

            $alumni->save();
            DB::commit();

            return back()->with('success', 'Data alumni berhasil disinkronisasi.');
        } catch (\Exception $e) {
            DB::rollBack();

            return back()->withErrors(['message' => 'Gagal menyimpan data: '.$e->getMessage()]);
        }
    }
}
