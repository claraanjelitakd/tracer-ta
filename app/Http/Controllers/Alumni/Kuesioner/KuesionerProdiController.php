<?php

namespace App\Http\Controllers\Alumni\Kuesioner;

use App\Http\Controllers\Controller;
use App\Models\ProdiQuestion;
use App\Models\ProdiQuestionSection;
use App\Models\ProdiResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
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

        // Ambil data terpadu alumni dari Database View
        $autofill = DB::table('v_alumni_kuesioner_autofill')
            ->where('biodata_id', $biodata->id)
            ->first();

        $namaAkademik = $autofill->F2A ?? $biodata->nama ?? $biodata->user?->name ?? '';
        $nimAkademik = $autofill->F1 ?? $biodata->nim ?? '';
        $tahunLulusAkademik = $autofill->BIO_TGL_LULUS ?? $biodata->tahun_lulus ?? '';

        $initialAnswers = [];
        foreach ($questions as $q) {
            $resp = $existingResponses->get($q->id);
            $qText = strtolower(trim((string) $q->question_text));

            // Cek apakah butir pertanyaan merupakan data identitas/akademik otomatis jika secara khusus ada
            $autoVal = null;
            if ($qText === 'nama' || $qText === 'nama lengkap') {
                $autoVal = $namaAkademik;
            } elseif ($qText === 'nim' || $qText === 'nomor induk mahasiswa') {
                $autoVal = $nimAkademik;
            } elseif ($qText === 'tahun kelulusan' || $qText === 'tahun lulus') {
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
                } else {
                    $initialAnswers[$q->id] = ($q->type === 'multiple_choice' || $q->type === 'checkbox') ? [] : '';
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

        $autofill = DB::table('v_alumni_kuesioner_autofill')
            ->where('biodata_id', $biodata->id)
            ->first();

        $jawabanMasuk = $request->input('answers', []);
        $questions = ProdiQuestion::where('prodi_id', $biodata->prodi_id)->get()->keyBy('id');

        foreach ($questions as $qId => $q) {
            $jawaban = $jawabanMasuk[$qId] ?? null;
            $answerValue = null;
            $answerJson = null;

            if (is_array($jawaban)) {
                if ($q->type === 'multiple_choice' || $q->type === 'checkbox') {
                    $cleaned = array_values(array_filter($jawaban));
                    if (! empty($cleaned)) {
                        $answerJson = $cleaned;
                        $answerValue = implode(', ', $cleaned);
                    }
                } elseif ($q->type === 'radio_input' || $q->type === 'radio_text') {
                    $selected = trim((string) ($jawaban['selected'] ?? ''));
                    $inputVal = trim((string) ($jawaban['input'] ?? ''));
                    if ($selected !== '') {
                        $answerJson = ['selected' => $selected, 'input' => $inputVal];
                        $answerValue = ! empty($inputVal) ? "{$selected}: {$inputVal}" : $selected;
                    }
                } elseif (! empty($jawaban)) {
                    $answerJson = $jawaban;
                    $answerValue = json_encode($jawaban);
                }
            } elseif ($jawaban !== null && trim((string) $jawaban) !== '') {
                $answerValue = trim((string) $jawaban);
            }

            // Cek autofill HANYA jika pertanyaan tersebut memang butir identitas khusus
            $qText = strtolower(trim((string) $q->question_text));
            $isIdentityQuestion = in_array($qText, ['nama', 'nama lengkap', 'nim', 'nomor induk mahasiswa', 'tahun kelulusan', 'tahun lulus']);

            if (($answerValue === null || $answerValue === '') && $isIdentityQuestion) {
                if ($qText === 'nama' || $qText === 'nama lengkap') {
                    $answerValue = $autofill->F2A ?? $biodata->nama ?? $biodata->user?->name;
                } elseif ($qText === 'nim' || $qText === 'nomor induk mahasiswa') {
                    $answerValue = $autofill->F1 ?? $biodata->nim;
                } elseif ($qText === 'tahun kelulusan' || $qText === 'tahun lulus') {
                    $answerValue = $autofill->BIO_TGL_LULUS ?? $biodata->tahun_lulus;
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
            } else {
                // Jika jawaban kosong atau tidak diisi, hapus record dari database agar persentase di dashboard berkurang secara akurat
                ProdiResponse::where('biodata_id', $biodata->id)
                    ->where('prodi_question_id', $q->id)
                    ->delete();
            }
        }

        if ($request->boolean('stay_on_page')) {
            return redirect()->back()->with('success', 'Progres jawaban kuesioner program studi berhasil disimpan.');
        }

        return redirect('/alumni/dashboard')->with('success', 'Jawaban Kuesioner Program Studi berhasil disimpan. Terima kasih!');
    }
}
