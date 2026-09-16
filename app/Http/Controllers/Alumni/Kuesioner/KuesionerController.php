<?php

namespace App\Http\Controllers\Alumni\Kuesioner;

use App\Http\Controllers\Controller;
use App\Models\Kuesioner;
use App\Models\QuestionMapping;
use App\Models\Tracer;
use App\Services\Kuesioner\KuesionerSyncService;
use Illuminate\Support\Facades\Auth;
use Inertia\Inertia;

/**
 * KuesionerController
 *
 * Fungsi: Menampilkan daftar pertanyaan kuesioner kepada alumni.
 * Tujuan: Menyediakan data kuesioner yang sudah diformat rapi dari sisi backend sehingga Frontend (Vue) tidak perlu melakukan logika kompleks.
 */
class KuesionerController extends Controller
{
    /**
     * Menampilkan Halaman Kuesioner
     */
    public function tampilkanKuesioner()
    {
        $pengguna = Auth::user();
        $biodata = $pengguna->biodata;

        if (! $biodata) {
            abort(403, 'Profil Biodata tidak ditemukan.');
        }

        $biodata->load(['dataAkademik', 'yudisium', 'perusahaan.propinsi', 'perusahaan.kabupaten', 'atasan', 'user']);

        // Sinkronisasi otomatis data profil (Identitas & Perusahaan/Atasan) ke tabel tracer
        KuesionerSyncService::syncProfileResponses($biodata);

        // Ambil kuesioner aktif mulai dari Section 3 (Waktu Mulai Mencari Kerja).
        // Section 1 (Identitas) & Section 2 (Perusahaan & Atasan) tidak perlu diisi ulang
        // karena sudah terisi dari profil alumni dan tersimpan otomatis di tracer.
        $kuesioner = Kuesioner::where('is_active', true)
            ->with(['sections' => function ($query) {
                $query->where('order', '>=', 3)
                    ->orderBy('order', 'asc')
                    ->with(['subpertanyaans' => function ($qQuery) {
                        $qQuery->orderBy('order', 'asc')
                            ->with('detils');
                    }]);
            }])
            ->first();

        if (! $kuesioner) {
            return Inertia::render('Alumni/Kuesioner', [
                'error' => 'Tidak ada kuesioner aktif saat ini.',
                'questionnaire' => null,
                'kuesioner' => null,
                'initialAnswers' => [],
            ]);
        }

        // Ambil jawaban yang sudah ada
        $jawabanTersimpan = Tracer::where('biodata_id', $biodata->id)
            ->get()
            ->keyBy('question_id');

        // Ambil mapping untuk prefill otomatis dari database
        $pemetaan = QuestionMapping::all()->keyBy('question_id');

        // Merakit default jawaban di backend agar Vue murni sebagai UI
        $jawabanAwal = [];
        if ($kuesioner) {
            foreach ($kuesioner->sections as $bagian) {
                foreach ($bagian->subpertanyaans as $pertanyaan) {
                    if (isset($jawabanTersimpan[$pertanyaan->id])) {
                        $saved = $jawabanTersimpan[$pertanyaan->id];

                        if (in_array($pertanyaan->type, ['checkbox', 'multiple_choice'])) {
                            $arr = is_array($saved->answer_json) ? $saved->answer_json : [];
                            $cleanArr = [];
                            $customText = '';

                            // Cari opsi database yang merupakan opsi lainnya jika ada
                            $otherOption = $pertanyaan->detils->first(function ($opt) {
                                $t = strtolower($opt->option_text);

                                return str_contains($t, 'lainnya') || str_contains($t, 'tuliskan') || str_contains($t, '...');
                            });

                            foreach ($arr as $item) {
                                if (is_string($item) && str_starts_with($item, 'Lainnya: ')) {
                                    $customText = trim(substr($item, 9));
                                    if ($otherOption) {
                                        $cleanArr[] = $otherOption->option_text;
                                    } else {
                                        $cleanArr[] = 'Lainnya';
                                    }
                                } else {
                                    $cleanArr[] = $item;
                                }
                            }

                            $jawabanAwal[$pertanyaan->id] = $cleanArr;
                            if (! empty($customText)) {
                                $jawabanAwal[$pertanyaan->id.'_custom'] = $customText;
                            }
                        } elseif (in_array($pertanyaan->type, ['single_choice', 'radio', 'dropdown', 'searchable_select'])) {
                            $textVal = $saved->answer_text ?? '';
                            if (str_starts_with($textVal, 'Lainnya: ')) {
                                $customText = trim(substr($textVal, 9));
                                $otherOption = $pertanyaan->detils->first(function ($opt) {
                                    $t = strtolower($opt->option_text);

                                    return str_contains($t, 'lainnya') || str_contains($t, 'tuliskan') || str_contains($t, '...');
                                });
                                $jawabanAwal[$pertanyaan->id] = $otherOption ? $otherOption->option_text : 'Lainnya';
                                if (! empty($customText)) {
                                    $jawabanAwal[$pertanyaan->id.'_custom'] = $customText;
                                }
                            } else {
                                $jawabanAwal[$pertanyaan->id] = $saved->answer_text;
                            }
                        } elseif (in_array($pertanyaan->type, ['radio_input', 'radio_text'])) {
                            $jsonVal = is_array($saved->answer_json) ? $saved->answer_json : [];
                            $textVal = $saved->answer_text ?? '';

                            $inputsObj = [];
                            foreach ($pertanyaan->detils as $o) {
                                $inputsObj[$o->id] = '';
                            }

                            if (! empty($jsonVal)) {
                                $pilihan = $jsonVal['pilihan'] ?? ($jsonVal['selected'] ?? '');
                                $input = $jsonVal['input'] ?? ($jsonVal['text'] ?? '');
                                $inputs = is_array($jsonVal['inputs'] ?? null) ? $jsonVal['inputs'] : $inputsObj;

                                $jawabanAwal[$pertanyaan->id] = [
                                    'selected' => $pilihan,
                                    'input' => $input,
                                    'inputs' => array_merge($inputsObj, $inputs),
                                ];
                            } elseif (! empty($textVal)) {
                                $matchedOption = null;
                                $matchedInput = '';

                                foreach ($pertanyaan->detils as $o) {
                                    if (str_starts_with($textVal, $o->option_text)) {
                                        $matchedOption = $o->option_text;
                                        $remainder = trim(substr($textVal, strlen($o->option_text)));
                                        $matchedInput = ltrim($remainder, ': ');
                                        $inputsObj[$o->id] = $matchedInput;
                                        break;
                                    }
                                }

                                $jawabanAwal[$pertanyaan->id] = [
                                    'selected' => $matchedOption ?? $textVal,
                                    'input' => $matchedInput,
                                    'inputs' => $inputsObj,
                                ];
                            } else {
                                $jawabanAwal[$pertanyaan->id] = [
                                    'selected' => '',
                                    'input' => '',
                                    'inputs' => $inputsObj,
                                ];
                            }
                        } elseif (in_array($pertanyaan->type, ['matrix', 'matrix_dual', 'multiple_textbox'])) {
                            if (! empty($saved->answer_json) && is_array($saved->answer_json)) {
                                $jawabanAwal[$pertanyaan->id] = $saved->answer_json;
                            } elseif (! empty($saved->answer_text)) {
                                $decoded = json_decode($saved->answer_text, true);
                                $jawabanAwal[$pertanyaan->id] = is_array($decoded) ? $decoded : [];
                            } else {
                                $jawabanAwal[$pertanyaan->id] = [];
                            }
                        } elseif ($pertanyaan->type === 'multiple_number') {
                            $jsonVal = is_array($saved->answer_json) ? $saved->answer_json : [];
                            $formatted = [];
                            foreach ($jsonVal as $k => $v) {
                                if ($k === 'total') {
                                    continue;
                                }
                                $numVal = is_numeric($v) ? (float) $v : 0;
                                $formatted[$k] = ($numVal >= 1000) ? (int) ($numVal / 1000) : (int) $numVal;
                            }
                            $jawabanAwal[$pertanyaan->id] = $formatted;
                        } else {
                            $jawabanAwal[$pertanyaan->id] = $saved->answer_text ?? '';
                        }
                    } else {
                        // Belum ada jawaban tersimpan sebelumnya, lakukan penyiapan nilai default
                        if (in_array($pertanyaan->type, ['checkbox', 'multiple_choice', 'matrix', 'matrix_dual', 'multiple_textbox', 'multiple_number'])) {
                            $jawabanAwal[$pertanyaan->id] = [];
                        } elseif (in_array($pertanyaan->type, ['radio_input', 'radio_text'])) {
                            $inputsObj = [];
                            foreach ($pertanyaan->detils as $o) {
                                $inputsObj[$o->id] = '';
                            }
                            $jawabanAwal[$pertanyaan->id] = [
                                'selected' => '',
                                'input' => '',
                                'inputs' => $inputsObj,
                            ];
                        } else {
                            $jawabanAwal[$pertanyaan->id] = '';

                            // Auto-fill dari database biodata, data_akademik, perusahaan, atau atasan jika ada mapping
                            if (isset($pemetaan[$pertanyaan->id])) {
                                $namaKolom = $pemetaan[$pertanyaan->id]->column_name;
                                $namaTabel = $pemetaan[$pertanyaan->id]->table_name;

                                if (in_array($namaTabel, ['data_akademik', 'data_akademiks'])) {
                                    $jawabanAwal[$pertanyaan->id] = $biodata->dataAkademik->$namaKolom ?? '';
                                } elseif (in_array($namaTabel, ['biodata', 'biodatas', 'alumnis'])) {
                                    $jawabanAwal[$pertanyaan->id] = $biodata->$namaKolom ?? '';
                                } elseif (in_array($namaTabel, ['perusahaan', 'companies'])) {
                                    if ($namaKolom === 'alamat' && $biodata->perusahaan) {
                                        $parts = array_filter([
                                            $biodata->perusahaan->alamat,
                                            $biodata->perusahaan->kabupaten?->nama_kabupaten,
                                            $biodata->perusahaan->propinsi?->nama_provinsi,
                                            $biodata->zipcode,
                                        ]);
                                        $jawabanAwal[$pertanyaan->id] = ! empty($parts) ? implode(', ', $parts) : ($biodata->perusahaan->alamat ?? '');
                                    } else {
                                        $jawabanAwal[$pertanyaan->id] = $biodata->perusahaan->$namaKolom ?? '';
                                    }
                                } elseif (in_array($namaTabel, ['atasan', 'atasans'])) {
                                    $jawabanAwal[$pertanyaan->id] = $biodata->atasan->$namaKolom ?? '';
                                } elseif ($namaTabel === 'users') {
                                    $jawabanAwal[$pertanyaan->id] = $pengguna->$namaKolom ?? '';
                                }
                            }

                            // Fallback eksplisit per kode bila belum terpetakan
                            if (empty($jawabanAwal[$pertanyaan->id])) {
                                switch ($pertanyaan->code) {
                                    case 'F1':
                                        $jawabanAwal[$pertanyaan->id] = $biodata->nim ?? '';
                                        break;
                                    case 'F2A':
                                        $jawabanAwal[$pertanyaan->id] = $biodata->dataAkademik?->nama ?? $pengguna->name ?? '';
                                        break;
                                    case 'F2B':
                                        $jawabanAwal[$pertanyaan->id] = $biodata->dataAkademik?->nomor_telepon ?? '';
                                        break;
                                    case 'F2C':
                                        $jawabanAwal[$pertanyaan->id] = $biodata->dataAkademik?->email_pribadi ?? $pengguna->email ?? '';
                                        break;
                                    case 'F2D':
                                        $jawabanAwal[$pertanyaan->id] = $biodata->dataAkademik?->alamat_saat_ini ?? '';
                                        break;
                                    case 'F2E':
                                        $jawabanAwal[$pertanyaan->id] = $biodata->perusahaan?->nama_perusahaan ?? '';
                                        break;
                                    case 'F2E1':
                                        $jawabanAwal[$pertanyaan->id] = $biodata->atasan?->nama ?? '';
                                        break;
                                    case 'F2E2':
                                        $jawabanAwal[$pertanyaan->id] = $biodata->atasan?->telepon ?? '';
                                        break;
                                    case 'F2E3':
                                        $jawabanAwal[$pertanyaan->id] = $biodata->atasan?->email ?? '';
                                        break;
                                    case 'F2F':
                                        if ($biodata->perusahaan) {
                                            $parts = array_filter([
                                                $biodata->perusahaan->alamat,
                                                $biodata->perusahaan->kabupaten?->nama_kabupaten,
                                                $biodata->perusahaan->propinsi?->nama_provinsi,
                                                $biodata->zipcode,
                                            ]);
                                            $jawabanAwal[$pertanyaan->id] = ! empty($parts) ? implode(', ', $parts) : ($biodata->perusahaan->alamat ?? '');
                                        }
                                        break;
                                }
                            }
                        }
                    }
                }
            }
        }

        return Inertia::render('Alumni/Kuesioner', [
            'kuesioner' => $kuesioner,
            'questionnaire' => $kuesioner,
            'initialAnswers' => $jawabanAwal,
            'error' => null,
        ]);
    }
}
