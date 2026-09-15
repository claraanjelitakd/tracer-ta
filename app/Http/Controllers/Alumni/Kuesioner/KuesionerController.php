<?php

namespace App\Http\Controllers\Alumni\Kuesioner;

use App\Http\Controllers\Controller;
use App\Models\QuestionMapping;
use App\Models\Questionnaire;
use App\Models\Response;
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
        $alumni = $pengguna->alumni;

        if (! $alumni) {
            abort(403, 'Profil Alumni tidak ditemukan.');
        }

        $alumniProdiId = $alumni->prodi_id;
        $alumni->load(['dataAkademik', 'company.province', 'company.kabupaten', 'atasan', 'user']);

        // Sinkronisasi otomatis data profil (Identitas & Perusahaan/Atasan) ke responses
        KuesionerSyncService::syncProfileResponses($alumni);

        // Ambil kuesioner aktif mulai dari Section 3 (Waktu Mulai Mencari Kerja).
        // Section 1 (Identitas) & Section 2 (Perusahaan & Atasan) tidak perlu diisi ulang
        // karena sudah terisi dari profil alumni dan tersimpan otomatis di responses.
        $kuesioner = Questionnaire::where('is_active', true)
            ->with(['sections' => function ($query) {
                $query->where('order', '>=', 3)
                    ->orderBy('order', 'asc')
                    ->with(['questions' => function ($qQuery) {
                        $qQuery->orderBy('order', 'asc')
                            ->with('options');
                    }]);
            }])
            ->first();

        if (! $kuesioner) {
            return Inertia::render('Alumni/Kuesioner', [
                'error' => 'Tidak ada kuesioner aktif saat ini.',
                'questionnaire' => null,
                'initialAnswers' => [],
            ]);
        }

        // Ambil jawaban yang sudah ada
        $jawabanTersimpan = Response::where('alumni_id', $alumni->id)
            ->get()
            ->keyBy('question_id');

        // Ambil mapping untuk prefill otomatis dari database
        $pemetaan = QuestionMapping::all()->keyBy('question_id');

        // Merakit default jawaban di backend agar Vue murni sebagai UI
        $jawabanAwal = [];
        if ($kuesioner) {
            foreach ($kuesioner->sections as $bagian) {
                foreach ($bagian->questions as $pertanyaan) {
                    if (isset($jawabanTersimpan[$pertanyaan->id])) {
                        $saved = $jawabanTersimpan[$pertanyaan->id];

                        if (in_array($pertanyaan->type, ['checkbox', 'multiple_choice'])) {
                            $arr = is_array($saved->answer_json) ? $saved->answer_json : [];
                            $cleanArr = [];
                            $customText = '';

                            // Cari opsi database yang merupakan opsi lainnya jika ada
                            $otherOption = $pertanyaan->options->first(function ($opt) {
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
                        } elseif (in_array($pertanyaan->type, ['single_choice', 'radio'])) {
                            $textVal = $saved->answer_text ?? '';
                            if (str_starts_with($textVal, 'Lainnya: ')) {
                                $customText = trim(substr($textVal, 9));
                                $otherOption = $pertanyaan->options->first(function ($opt) {
                                    $t = strtolower($opt->option_text);

                                    return str_contains($t, 'lainnya') || str_contains($t, 'tuliskan') || str_contains($t, '...');
                                });
                                $jawabanAwal[$pertanyaan->id] = $otherOption ? $otherOption->option_text : 'Lainnya';
                                $jawabanAwal[$pertanyaan->id.'_custom'] = $customText;
                            } else {
                                $jawabanAwal[$pertanyaan->id] = $textVal;
                            }
                        } elseif (in_array($pertanyaan->type, ['radio_input', 'radio_text'])) {
                            $json = $saved->answer_json ?? [];
                            $selected = $json['selected'] ?? ($saved->answer_text ?? '');
                            $input = $json['input'] ?? '';
                            $selectedOpt = $pertanyaan->options->firstWhere('option_text', $selected);
                            $inputsObj = [];
                            foreach ($pertanyaan->options as $o) {
                                $inputsObj[$o->id] = ($selectedOpt && $selectedOpt->id === $o->id) ? $input : '';
                            }
                            $jawabanAwal[$pertanyaan->id] = [
                                'selected' => $selected,
                                'input' => $input,
                                'inputs' => $inputsObj,
                            ];
                        } elseif ($pertanyaan->type === 'multiple_number') {
                            $rawArr = $saved->answer_json ?? [];
                            $formattedArr = [];
                            foreach ($pertanyaan->options as $opsi) {
                                $val = $rawArr[$opsi->code] ?? '';
                                if ($val !== '' && $val !== null && is_numeric($val)) {
                                    $intVal = (int) $val;
                                    // Jika nilai tersimpan kelipatan 1000 (> 0), ubah ke ribuan untuk input berakhiran .000
                                    $formattedArr[$opsi->code] = ($intVal >= 1000 && $intVal % 1000 === 0) ? ($intVal / 1000) : $intVal;
                                } else {
                                    $formattedArr[$opsi->code] = '';
                                }
                            }
                            $jawabanAwal[$pertanyaan->id] = $formattedArr;
                        } elseif (in_array($pertanyaan->type, ['matrix_dual', 'matrix'])) {
                            $jawabanAwal[$pertanyaan->id] = $saved->answer_json ?? [];
                        } elseif ($pertanyaan->type === 'multiple_textbox') {
                            $jawabanAwal[$pertanyaan->id] = is_array($saved->answer_json) ? $saved->answer_json : [];
                        } else {
                            $jawabanAwal[$pertanyaan->id] = $saved->answer_text ?? '';
                        }
                    } else {
                        if (in_array($pertanyaan->type, ['checkbox', 'multiple_choice', 'multiple_textbox'])) {
                            $jawabanAwal[$pertanyaan->id] = [];
                        } elseif ($pertanyaan->type === 'matrix_dual') {
                            $obj = [];
                            foreach ($pertanyaan->options as $opsi) {
                                $obj[$opsi->id] = ['A' => null, 'B' => null];
                            }
                            $jawabanAwal[$pertanyaan->id] = $obj;
                        } elseif ($pertanyaan->type === 'matrix') {
                            $obj = [];
                            foreach ($pertanyaan->options as $opsi) {
                                $obj[$opsi->id] = null;
                            }
                            $jawabanAwal[$pertanyaan->id] = $obj;
                        } elseif ($pertanyaan->type === 'multiple_number') {
                            $obj = [];
                            foreach ($pertanyaan->options as $opsi) {
                                $obj[$opsi->code] = '';
                            }
                            $jawabanAwal[$pertanyaan->id] = $obj;
                        } elseif (in_array($pertanyaan->type, ['radio_input', 'radio_text'])) {
                            $inputsObj = [];
                            foreach ($pertanyaan->options as $o) {
                                $inputsObj[$o->id] = '';
                            }
                            $jawabanAwal[$pertanyaan->id] = [
                                'selected' => '',
                                'input' => '',
                                'inputs' => $inputsObj,
                            ];
                        } else {
                            $jawabanAwal[$pertanyaan->id] = '';

                            // Auto-fill dari database alumni, data_akademiks, companies, atau atasans jika ada mapping
                            if (isset($pemetaan[$pertanyaan->id])) {
                                $namaKolom = $pemetaan[$pertanyaan->id]->column_name;
                                $namaTabel = $pemetaan[$pertanyaan->id]->table_name;

                                if ($namaTabel === 'data_akademiks') {
                                    $jawabanAwal[$pertanyaan->id] = $alumni->dataAkademik->$namaKolom ?? '';
                                } elseif ($namaTabel === 'alumnis') {
                                    $jawabanAwal[$pertanyaan->id] = $alumni->$namaKolom ?? '';
                                } elseif ($namaTabel === 'companies') {
                                    if ($namaKolom === 'alamat' && $alumni->company) {
                                        $parts = array_filter([
                                            $alumni->company->alamat,
                                            $alumni->company->kabupaten?->nama_kabupaten,
                                            $alumni->company->province?->nama_provinsi,
                                            $alumni->zipcode,
                                        ]);
                                        $jawabanAwal[$pertanyaan->id] = ! empty($parts) ? implode(', ', $parts) : ($alumni->company->alamat ?? '');
                                    } else {
                                        $jawabanAwal[$pertanyaan->id] = $alumni->company->$namaKolom ?? '';
                                    }
                                } elseif ($namaTabel === 'atasans') {
                                    $jawabanAwal[$pertanyaan->id] = $alumni->atasan->$namaKolom ?? '';
                                } elseif ($namaTabel === 'users') {
                                    $jawabanAwal[$pertanyaan->id] = $pengguna->$namaKolom ?? '';
                                }
                            }

                            // Fallback eksplisit per kode bila belum terpetakan
                            if (empty($jawabanAwal[$pertanyaan->id])) {
                                switch ($pertanyaan->code) {
                                    case 'F1':
                                        $jawabanAwal[$pertanyaan->id] = $alumni->nim ?? '';
                                        break;
                                    case 'F2A':
                                        $jawabanAwal[$pertanyaan->id] = $alumni->dataAkademik?->nama ?? $pengguna->name ?? '';
                                        break;
                                    case 'F2B':
                                        $jawabanAwal[$pertanyaan->id] = $alumni->dataAkademik?->nomor_telepon ?? '';
                                        break;
                                    case 'F2C':
                                        $jawabanAwal[$pertanyaan->id] = $alumni->dataAkademik?->email_pribadi ?? $pengguna->email ?? '';
                                        break;
                                    case 'F2D':
                                        $jawabanAwal[$pertanyaan->id] = $alumni->dataAkademik?->alamat_saat_ini ?? '';
                                        break;
                                    case 'F2E':
                                        $jawabanAwal[$pertanyaan->id] = $alumni->company?->nama_perusahaan ?? '';
                                        break;
                                    case 'F2E1':
                                        $jawabanAwal[$pertanyaan->id] = $alumni->atasan?->nama ?? '';
                                        break;
                                    case 'F2E2':
                                        $jawabanAwal[$pertanyaan->id] = $alumni->atasan?->telepon ?? '';
                                        break;
                                    case 'F2E3':
                                        $jawabanAwal[$pertanyaan->id] = $alumni->atasan?->email ?? '';
                                        break;
                                    case 'F2F':
                                        if ($alumni->company) {
                                            $parts = array_filter([
                                                $alumni->company->alamat,
                                                $alumni->company->kabupaten?->nama_kabupaten,
                                                $alumni->company->province?->nama_provinsi,
                                                $alumni->zipcode,
                                            ]);
                                            $jawabanAwal[$pertanyaan->id] = ! empty($parts) ? implode(', ', $parts) : ($alumni->company->alamat ?? '');
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
            'questionnaire' => $kuesioner,
            'initialAnswers' => $jawabanAwal,
            'error' => null,
        ]);
    }
}
