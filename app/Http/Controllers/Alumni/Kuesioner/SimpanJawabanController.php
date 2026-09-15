<?php

namespace App\Http\Controllers\Alumni\Kuesioner;

use App\Http\Controllers\Controller;
use App\Models\Atasan;
use App\Models\Company;
use App\Models\DataAkademik;
use App\Models\Question;
use App\Models\QuestionMapping;
use App\Models\Tracer;
use App\Services\Kuesioner\KuesionerSyncService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

/**
 * SimpanJawabanController
 *
 * Fungsi: Menangani proses penyimpanan jawaban kuesioner dari pengguna.
 * Tujuan: Menerima data jawaban dari Frontend, menstandarkan format respons ke tabel responses
 * (mendukung array murni pada answer_json untuk tipe multiple, format varchar rapi pada answer_text,
 * teks isian kustom pada opsi 'Lainnya', serta penanganan terstruktur untuk radio_input dan multiple_number),
 * serta melakukan sinkronisasi otomatis ke profil alumni dan data QuestionMapping.
 */
class SimpanJawabanController extends Controller
{
    /**
     * Memproses Penyimpanan Jawaban Kuesioner
     */
    public function simpanJawabanKuesioner(Request $request)
    {
        $pengguna = Auth::user();
        $alumni = $pengguna->alumni;

        if (! $alumni) {
            abort(403, 'Profil alumni tidak ditemukan.');
        }

        $jawabanMasuk = $request->input('answers', []); // Format: [question_id => answer_data]

        $kumpulanIdPertanyaan = array_filter(array_keys($jawabanMasuk), function ($k) {
            return is_numeric($k);
        });

        // Ambil data pertanyaan beserta opsi untuk referensi tipe dan pemformatan teks
        $pertanyaanModels = Question::with('options')
            ->whereIn('id', $kumpulanIdPertanyaan)
            ->get()
            ->keyBy('id');

        // Ambil mapping kolom khusus untuk tabel alumnis, data_akademiks, companies, atasans
        $pemetaan = QuestionMapping::whereIn('table_name', ['alumnis', 'data_akademiks', 'companies', 'atasans'])
            ->whereIn('question_id', $kumpulanIdPertanyaan)
            ->get()
            ->keyBy('question_id');

        $dataUpdateAlumni = [];
        $dataUpdateAkademik = [];
        $dataUpdateCompany = [];
        $dataUpdateAtasan = [];

        $kolomAlumni = $alumni->getFillable();
        $kolomAkademik = $alumni->dataAkademik ? $alumni->dataAkademik->getFillable() : (new DataAkademik)->getFillable();
        $kolomCompany = (new Company)->getFillable();
        $kolomAtasan = (new Atasan)->getFillable();

        foreach ($jawabanMasuk as $idPertanyaan => $jawaban) {
            // Lewati key string _custom karena diproses langsung bersama ID pertanyaan induknya
            if (is_string($idPertanyaan) && str_ends_with($idPertanyaan, '_custom')) {
                continue;
            }

            if (! is_numeric($idPertanyaan)) {
                continue;
            }

            $question = $pertanyaanModels[$idPertanyaan] ?? null;
            if (! $question) {
                continue;
            }

            $customText = isset($jawabanMasuk[$idPertanyaan.'_custom']) ? trim((string) $jawabanMasuk[$idPertanyaan.'_custom']) : null;

            $answerText = null;
            $answerJson = null;

            switch ($question->type) {
                case 'multiple_choice':
                case 'checkbox':
                    // Pastikan input berupa array
                    $selectedArray = is_array($jawaban) ? array_values($jawaban) : (! empty($jawaban) ? [(string) $jawaban] : []);
                    $processedArray = [];

                    foreach ($selectedArray as $item) {
                        $itemStr = (string) $item;
                        // Jika opsi ini merupakan opsi Lainnya dan user mengisi teks kustom
                        if (! empty($customText) && (
                            stripos($itemStr, 'lainnya') !== false ||
                            stripos($itemStr, 'tuliskan') !== false ||
                            str_contains($itemStr, '...') ||
                            str_contains($itemStr, '…')
                        )) {
                            $processedArray[] = 'Lainnya: '.$customText;
                        } else {
                            $processedArray[] = $itemStr;
                        }
                    }

                    // Jika user mengisi customText tapi opsi Lainnya belum ada di array
                    if (! empty($customText) && ! collect($processedArray)->contains(fn ($v) => str_starts_with($v, 'Lainnya:'))) {
                        $processedArray[] = 'Lainnya: '.$customText;
                    }

                    $processedArray = array_values(array_unique(array_filter($processedArray)));

                    // Jika tidak ada opsi yang dipilih dan tidak ada teks kustom, jangan simpan respon kosong
                    if (empty($processedArray)) {
                        continue 2;
                    }

                    $answerJson = $processedArray; // Array JSON murni: ["Opsi 1", "Opsi 2", "Lainnya: Keterangan"]
                    $answerText = implode(', ', $processedArray); // Varchar rapi untuk export
                    break;

                case 'rating_5':
                case 'single_choice':
                case 'radio':
                    if (is_string($jawaban) || is_numeric($jawaban)) {
                        $jawabanStr = trim((string) $jawaban);
                        if ($jawabanStr === '') {
                            continue 2;
                        }

                        if (! empty($customText) && (
                            stripos($jawabanStr, 'lainnya') !== false ||
                            stripos($jawabanStr, 'tuliskan') !== false ||
                            str_contains($jawabanStr, '...') ||
                            str_contains($jawabanStr, '…')
                        )) {
                            $answerText = 'Lainnya: '.$customText;
                        } else {
                            $answerText = $jawabanStr;
                        }
                    } else {
                        continue 2;
                    }
                    $answerJson = null;
                    break;

                case 'radio_input':
                case 'radio_text':
                    if (is_array($jawaban)) {
                        $selected = trim((string) ($jawaban['selected'] ?? ''));
                        if ($selected === '') {
                            continue 2;
                        }

                        // Cari opsi yang sesuai untuk membaca input independen per opsi
                        $selectedOpt = $question->options->firstWhere('option_text', $selected);
                        $inputVal = '';

                        if ($selectedOpt && isset($jawaban['inputs']) && is_array($jawaban['inputs'])) {
                            $inputVal = trim((string) ($jawaban['inputs'][$selectedOpt->id] ?? ''));
                        } elseif (isset($jawaban['input'])) {
                            $inputVal = trim((string) $jawaban['input']);
                        }

                        $answerJson = [
                            'selected' => $selected,
                            'input' => $inputVal,
                        ];

                        if (! empty($inputVal)) {
                            if (str_contains($selected, '...') || str_contains($selected, '…')) {
                                $answerText = str_replace(['...', '…'], $inputVal, $selected);
                            } elseif (stripos($selected, 'lainnya') !== false) {
                                $answerText = 'Lainnya: '.$inputVal;
                            } else {
                                $answerText = $selected.': '.$inputVal;
                            }
                        } else {
                            $answerText = $selected;
                        }
                    } elseif (is_string($jawaban) && trim($jawaban) !== '') {
                        $answerText = trim($jawaban);
                        $answerJson = ['selected' => trim($jawaban), 'input' => ''];
                    } else {
                        continue 2;
                    }
                    break;

                case 'multiple_number':
                    if (is_array($jawaban)) {
                        // Cek apakah ada minimal satu nilai terisi angka
                        $hasAnyFilled = false;
                        foreach ($jawaban as $v) {
                            if ($v !== null && $v !== '' && is_numeric($v)) {
                                $hasAnyFilled = true;
                                break;
                            }
                        }

                        // Jika semua kosong/null (belum diisi oleh alumni), jangan simpan baris kosong
                        if (! $hasAnyFilled) {
                            continue 2;
                        }

                        $cleanJson = [];
                        $parts = [];
                        $total = 0;
                        $optionCodeMap = $question->options->keyBy('code');

                        foreach ($question->options as $opt) {
                            $optCode = $opt->code;
                            $rawVal = $jawaban[$optCode] ?? null;

                            // Normalisasi input ribuan (default akhiran 000):
                            // Jika alumni menginput dalam satuan ribuan (< 1.000.000 dan > 0), dikonversi ke Rupiah penuh (x 1000).
                            // Jika sudah diinput nominal penuh (>= 1.000.000), tetap disimpan apa adanya untuk mencegah perkalian ganda.
                            if ($rawVal !== null && $rawVal !== '' && is_numeric($rawVal)) {
                                $rawInt = (int) $rawVal;
                                $numericVal = ($rawInt > 0 && $rawInt < 1000000) ? ($rawInt * 1000) : $rawInt;
                            } else {
                                $numericVal = 0;
                            }

                            $cleanJson[$optCode] = $numericVal;
                            $total += $numericVal;

                            $label = $opt->option_text;
                            $formattedVal = 'Rp '.number_format($numericVal, 0, ',', '.');
                            $parts[] = "{$label}: {$formattedVal}";
                        }

                        // Simpan total salary akumulatif pada answer_json dan sertakan di answer_text jika opsi lebih dari 1
                        $cleanJson['total'] = $total;
                        if ($question->options->count() > 1) {
                            $parts[] = 'Total Pendapatan: Rp '.number_format($total, 0, ',', '.');
                        }

                        $answerJson = $cleanJson;
                        $answerText = implode(', ', $parts);
                    } else {
                        continue 2;
                    }
                    break;

                case 'matrix':
                case 'matrix_dual':
                    $answerJson = is_array($jawaban) ? $jawaban : json_decode($jawaban, true);
                    $answerText = 'Data matriks kompetensi/metode tersimpan';
                    break;

                case 'multiple_textbox':
                    if (is_array($jawaban)) {
                        $hasAny = false;
                        $parts = [];
                        foreach ($jawaban as $k => $v) {
                            if ($v !== null && trim((string) $v) !== '') {
                                $hasAny = true;
                                $parts[] = "{$k}: {$v}";
                            }
                        }
                        if (! $hasAny) {
                            continue 2;
                        }
                        $answerJson = $jawaban;
                        $answerText = implode(', ', $parts);
                    } else {
                        continue 2;
                    }
                    break;

                default:
                    // text, number, searchable_select, dll
                    if ($jawaban === null || $jawaban === '' || (is_array($jawaban) && empty($jawaban))) {
                        continue 2;
                    }
                    $answerText = is_array($jawaban) ? implode(', ', $jawaban) : (string) $jawaban;
                    $answerJson = null;
                    break;
            }

            // ATURAN MUTLAK: Jangan pernah simpan record jika answer_text bernilai null atau kosong
            if ($answerText === null || trim($answerText) === '') {
                continue;
            }

            // Simpan atau update ke tabel tracers
            Tracer::updateOrCreate(
                ['alumni_id' => $alumni->id, 'question_id' => $idPertanyaan],
                [
                    'nim' => $alumni->nim,
                    'kelompok' => $question->kelompok,
                    'kode_pertanyaan' => $question->kode_pertanyaan,
                    'subpertanyaan' => $question->subpertanyaan,
                    'answer' => $answerText,
                    'answer_json' => $answerJson,
                    'keterangan' => $question->keterangan,
                    'tahun_lulus' => $alumni->dataAkademik?->tahun_lulus,
                ]
            );

            // Jika ada relasi mapping yang resmi tercatat di database, sinkronisasi datanya ke tabel terkait
            if (isset($pemetaan[$idPertanyaan]) && $answerText !== null) {
                $kolom = $pemetaan[$idPertanyaan]->column_name;
                $tabel = $pemetaan[$idPertanyaan]->table_name;

                if ($tabel === 'data_akademiks' && in_array($kolom, $kolomAkademik)) {
                    $dataUpdateAkademik[$kolom] = $answerText;
                } elseif ($tabel === 'alumnis' && in_array($kolom, $kolomAlumni)) {
                    $dataUpdateAlumni[$kolom] = $answerText;
                } elseif ($tabel === 'companies' && in_array($kolom, $kolomCompany)) {
                    $dataUpdateCompany[$kolom] = $answerText;
                } elseif ($tabel === 'atasans' && in_array($kolom, $kolomAtasan)) {
                    $dataUpdateAtasan[$kolom] = $answerText;
                }
            }
        }

        if (! empty($dataUpdateAlumni)) {
            $alumni->update($dataUpdateAlumni);
        }

        if (! empty($dataUpdateAkademik)) {
            DataAkademik::updateOrCreate(
                ['nim' => $alumni->nim],
                $dataUpdateAkademik
            );
        }

        if (! empty($dataUpdateCompany)) {
            if ($alumni->company_id && $alumni->company) {
                $alumni->company->update($dataUpdateCompany);
            } elseif (! empty($dataUpdateCompany['nama_perusahaan'])) {
                $comp = Company::create($dataUpdateCompany);
                $alumni->update(['company_id' => $comp->id]);
            }
        }

        if (! empty($dataUpdateAtasan)) {
            if ($alumni->atasan_id && $alumni->atasan) {
                $alumni->atasan->update($dataUpdateAtasan);
            } elseif (! empty($dataUpdateAtasan['nama']) || ! empty($dataUpdateAtasan['email'])) {
                $emailAtasan = $dataUpdateAtasan['email'] ?? ('atasan_'.$alumni->nim.'@tracerstudy.ukdw.ac.id');
                $atasan = Atasan::firstOrCreate(
                    ['email' => $emailAtasan],
                    [
                        'nama' => $dataUpdateAtasan['nama'] ?? 'Atasan',
                        'telepon' => $dataUpdateAtasan['telepon'] ?? null,
                    ]
                );
                $alumni->update(['atasan_id' => $atasan->id]);
            }
        }

        // Pastikan jawaban profil (F1..F2H) selalu tersinkronisasi di tabel responses
        KuesionerSyncService::syncProfileResponses($alumni);

        return redirect()->back()->with('success', 'Jawaban berhasil disimpan.');
    }
}
