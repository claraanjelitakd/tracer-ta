<?php

namespace App\Http\Controllers\Alumni\Kuesioner;

use App\Http\Controllers\Controller;
use App\Models\ProdiQuestion;
use App\Models\ProdiQuestionSection;
use App\Models\ProdiResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Inertia\Inertia;
use Inertia\Response as InertiaResponse;

/**
 * Controller untuk Kuesioner Khusus Program Studi bagi Alumni.
 */
class KuesionerProdiController extends Controller
{
    /**
     * Menampilkan halaman kuesioner khusus program studi alumni.
     */
    public function tampilkanKuesionerProdi(): InertiaResponse
    {
        $user = Auth::user();
        $biodata = $user->biodata;

        if (! $biodata || ! $biodata->prodi_id) {
            return Inertia::render('Alumni/KuesionerProdi', [
                'prodi' => null,
                'sections' => [],
                'questions' => [],
                'initialAnswers' => [],
                'message' => 'Profil Anda belum terhubung dengan Program Studi.',
            ]);
        }

        $biodata->load(['dataAkademik', 'yudisium', 'user', 'prodi']);
        $prodi = $biodata->prodi;

        // Ambil sections kuesioner prodi beserta seluruh butir pertanyaan dan opsi
        $sections = ProdiQuestionSection::where('prodi_id', $biodata->prodi_id)
            ->with(['questions.options'])
            ->orderBy('order', 'asc')
            ->get();

        // Ambil seluruh butir pertanyaan khusus prodi alumni ini
        $questions = ProdiQuestion::where('prodi_id', $biodata->prodi_id)
            ->with(['section', 'options'])
            ->orderBy('order', 'asc')
            ->get();

        // Ambil riwayat respon kuesioner prodi alumni jika sudah pernah mengisi
        $existingResponses = ProdiResponse::where('biodata_id', $biodata->id)
            ->whereIn('prodi_question_id', $questions->pluck('id'))
            ->get()
            ->keyBy('prodi_question_id');

        // Data akademik default alumni untuk auto-prefill pertanyaan identitas
        $namaAkademik = $biodata->nama ?? $biodata->dataAkademik?->nama ?? $biodata->user?->name ?? '';
        $nimAkademik = $biodata->nim ?? $biodata->dataAkademik?->nim ?? '';
        $tahunLulusAkademik = $biodata->tahun_lulus ?? $biodata->yudisium?->tahun_lulus ?? $biodata->dataAkademik?->tahun_lulus ?? '';

        $initialAnswers = [];
        foreach ($questions as $q) {
            $resp = $existingResponses->get($q->id);
            $qText = strtolower(trim($q->question_text));

            // Cek apakah butir pertanyaan merupakan data akademik otomatis
            $autoVal = null;
            if ($q->code === 'PSI-1-01' || $qText === 'nama') {
                $autoVal = $namaAkademik;
            } elseif ($q->code === 'PSI-1-02' || $qText === 'nim') {
                $autoVal = $nimAkademik;
            } elseif ($q->code === 'PSI-1-03' || $qText === 'tahun kelulusan' || $qText === 'tahun lulus') {
                $autoVal = $tahunLulusAkademik;
            }

            if ($resp) {
                if ($q->type === 'multiple_choice' || $q->type === 'checkbox') {
                    $initialAnswers[$q->id] = $resp->answer_json ?? (array) $resp->answer_text;
                } elseif ($q->type === 'radio_input' || $q->type === 'radio_text') {
                    $initialAnswers[$q->id] = [
                        'selected' => $resp->answer_text ?? '',
                        'input' => $resp->answer_json['input'] ?? '',
                        'inputs' => $resp->answer_json['inputs'] ?? [],
                    ];
                } else {
                    $val = $resp->answer_text ?? '';
                    // Jika data tersimpan kosong tetapi ada data akademik otomatis, gunakan data akademik
                    $initialAnswers[$q->id] = (! empty($val) || $autoVal === null) ? $val : (string) $autoVal;
                }
            } else {
                // Jika belum ada respon tersimpan di DB
                if ($autoVal !== null && $autoVal !== '') {
                    $initialAnswers[$q->id] = (string) $autoVal;

                    // Simpan otomatis ke tabel prodi_responses agar langsung tersinkronisasi
                    ProdiResponse::updateOrCreate(
                        [
                            'biodata_id' => $biodata->id,
                            'prodi_question_id' => $q->id,
                        ],
                        [
                            'answer_text' => (string) $autoVal,
                            'answer_json' => null,
                        ]
                    );
                }
            }
        }

        return Inertia::render('Alumni/KuesionerProdi', [
            'biodata' => $biodata,
            'alumni' => $biodata,
            'prodi' => $prodi,
            'sections' => $sections,
            'questions' => $questions,
            'initialAnswers' => $initialAnswers,
        ]);
    }

    /**
     * Menyimpan jawaban kuesioner prodi alumni.
     */
    public function simpanJawaban(Request $request): RedirectResponse
    {
        $user = Auth::user();
        $biodata = $user->biodata;

        if (! $biodata || ! $biodata->prodi_id) {
            return redirect()->back()->with('error', 'Profil alumni tidak valid.');
        }

        $jawabanMasuk = $request->input('answers', []);
        $questions = ProdiQuestion::where('prodi_id', $biodata->prodi_id)->get()->keyBy('id');

        foreach ($jawabanMasuk as $qId => $jawaban) {
            if (! is_numeric($qId) || ! isset($questions[$qId])) {
                continue;
            }

            $q = $questions[$qId];
            $answerValue = null;
            $answerJson = null;

            if (is_array($jawaban)) {
                if ($q->type === 'multiple_choice' || $q->type === 'checkbox') {
                    $cleaned = array_values(array_filter($jawaban));
                    if (empty($cleaned)) {
                        continue;
                    }
                    $answerJson = $cleaned;
                    $answerValue = implode(', ', $cleaned);
                } elseif ($q->type === 'radio_input' || $q->type === 'radio_text') {
                    $selected = trim((string) ($jawaban['selected'] ?? ''));
                    $inputVal = trim((string) ($jawaban['input'] ?? ''));
                    if ($selected === '') {
                        continue;
                    }
                    $answerJson = ['selected' => $selected, 'input' => $inputVal];
                    $answerValue = ! empty($inputVal) ? "{$selected}: {$inputVal}" : $selected;
                } else {
                    $answerJson = $jawaban;
                    $answerValue = json_encode($jawaban);
                }
            } elseif ($jawaban !== null && trim((string) $jawaban) !== '') {
                $answerValue = trim((string) $jawaban);
            }

            // Fallback otomatis mengambil langsung dari Biodata / Data Akademik jika input kosong
            if ($answerValue === null || $answerValue === '') {
                $qText = strtolower(trim($q->question_text));
                if ($q->code === 'PSI-1-01' || $qText === 'nama') {
                    $answerValue = $biodata->nama ?? $biodata->dataAkademik?->nama ?? $biodata->user?->name;
                } elseif ($q->code === 'PSI-1-02' || $qText === 'nim') {
                    $answerValue = $biodata->nim ?? $biodata->dataAkademik?->nim;
                } elseif ($q->code === 'PSI-1-03' || $qText === 'tahun kelulusan' || $qText === 'tahun lulus') {
                    $answerValue = $biodata->tahun_lulus ?? $biodata->yudisium?->tahun_lulus ?? $biodata->dataAkademik?->tahun_lulus;
                }
            }

            if ($answerValue !== null && $answerValue !== '') {
                ProdiResponse::updateOrCreate(
                    [
                        'biodata_id' => $biodata->id,
                        'prodi_question_id' => $q->id,
                    ],
                    [
                        'answer_text' => $answerValue,
                        'answer_json' => $answerJson,
                    ]
                );
            }
        }

        return redirect('/alumni/dashboard')->with('success', 'Jawaban Kuesioner Program Studi berhasil disimpan. Terima kasih!');
    }
}
