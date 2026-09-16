<?php

namespace App\Http\Controllers\Alumni\Kuesioner;

use App\Http\Controllers\Controller;
use App\Models\Atasan;
use App\Models\DataAkademik;
use App\Models\Perusahaan;
use App\Models\QuestionMapping;
use App\Models\RefSubpertanyaan2021;
use App\Models\Tracer;
use App\Services\Kuesioner\KuesionerSyncService;
use Illuminate\Http\Request;

/**
 * SimpanJawabanController
 *
 * Fungsi: Menangani proses validasi dan penyimpanan array jawaban kuesioner alumni ke database
 * serta melakukan sinkronisasi otomatis ke profil biodata dan data QuestionMapping.
 *
 * Fitur:
 * 1. Menerima payload jawaban dari Frontend (single choice, multiple choice, matriks, text, number).
 * 2. Menyimpan jawaban ke tabel `tracer`.
 * 3. Memperbarui otomatis tabel `biodata`, `data_akademik`, `perusahaan`, dan `atasan` jika butir pertanyaan
 *    terhubung ke mapping profil.
 * 4. Menjalankan sinkronisasi terpadu melalui `KuesionerSyncService`.
 */
class SimpanJawabanController extends Controller
{
    /**
     * Simpan respon kuesioner tracer study
     */
    public function store(Request $request)
    {
        return $this->simpanJawabanKuesioner($request);
    }

    /**
     * Method pemroses utama penyimpanan jawaban kuesioner alumni
     */
    public function simpanJawabanKuesioner(Request $request)
    {
        $pengguna = $request->user();
        $biodata = $pengguna->biodata;

        if (! $biodata) {
            return redirect()->back()->with('error', 'Data biodata alumni tidak ditemukan.');
        }

        $jawabanMasuk = $request->input('answers', []);
        if (empty($jawabanMasuk) || ! is_array($jawabanMasuk)) {
            return redirect()->back()->with('error', 'Tidak ada data jawaban yang dikirim.');
        }

        $kumpulanIdPertanyaan = array_filter(array_keys($jawabanMasuk), 'is_numeric');

        $subpertanyaanModels = RefSubpertanyaan2021::whereIn('id', $kumpulanIdPertanyaan)
            ->with('detils')
            ->get()
            ->keyBy('id');

        // Ambil mapping kolom khusus untuk tabel biodata, data_akademik, perusahaan, atasan
        $pemetaan = QuestionMapping::whereIn('table_name', ['biodata', 'biodatas', 'alumnis', 'data_akademik', 'data_akademiks', 'perusahaan', 'companies', 'atasan', 'atasans'])
            ->whereIn('question_id', $kumpulanIdPertanyaan)
            ->get()
            ->keyBy('question_id');

        $dataUpdateBiodata = [];
        $dataUpdateAkademik = [];
        $dataUpdatePerusahaan = [];
        $dataUpdateAtasan = [];

        $kolomBiodata = $biodata->getFillable();
        $kolomAkademik = $biodata->dataAkademik ? $biodata->dataAkademik->getFillable() : (new DataAkademik)->getFillable();
        $kolomPerusahaan = (new Perusahaan)->getFillable();
        $kolomAtasan = (new Atasan)->getFillable();

        foreach ($jawabanMasuk as $idPertanyaan => $jawaban) {
            // Lewati key string _custom karena diproses langsung bersama ID pertanyaan induknya
            if (is_string($idPertanyaan) && str_ends_with($idPertanyaan, '_custom')) {
                continue;
            }

            if (! is_numeric($idPertanyaan)) {
                continue;
            }

            $subpertanyaan = $subpertanyaanModels[$idPertanyaan] ?? null;
            if (! $subpertanyaan) {
                continue;
            }

            $customText = isset($jawabanMasuk[$idPertanyaan.'_custom']) ? trim((string) $jawabanMasuk[$idPertanyaan.'_custom']) : null;

            $answerText = null;
            $answerJson = null;

            switch ($subpertanyaan->type) {
                case 'multiple_choice':
                case 'checkbox':
                    if (is_array($jawaban)) {
                        $processedArray = [];
                        foreach ($jawaban as $item) {
                            $textItem = (string) $item;
                            $isLainnya = str_contains(strtolower($textItem), 'lainnya')
                                || str_contains(strtolower($textItem), 'tuliskan')
                                || str_contains(strtolower($textItem), '...')
                                || str_starts_with($textItem, 'Lainnya: ');

                            if ($isLainnya && ! empty($customText)) {
                                $processedArray[] = 'Lainnya: '.$customText;
                            } else {
                                $processedArray[] = $textItem;
                            }
                        }
                        $answerJson = array_values(array_unique($processedArray));
                        $answerText = implode(', ', $answerJson);
                    }
                    break;

                case 'single_choice':
                case 'radio':
                case 'dropdown':
                case 'searchable_select':
                    if ($jawaban !== null && trim((string) $jawaban) !== '') {
                        $textVal = (string) $jawaban;
                        $isLainnya = str_contains(strtolower($textVal), 'lainnya')
                            || str_contains(strtolower($textVal), 'tuliskan')
                            || str_contains(strtolower($textVal), '...')
                            || str_starts_with($textVal, 'Lainnya: ');

                        if ($isLainnya && ! empty($customText)) {
                            $answerText = 'Lainnya: '.$customText;
                        } else {
                            $answerText = $textVal;
                        }
                    }
                    break;

                case 'radio_input':
                case 'radio_text':
                    if (is_array($jawaban)) {
                        $pilihan = $jawaban['pilihan'] ?? ($jawaban['selected'] ?? null);
                        $input = $jawaban['input'] ?? ($jawaban['text'] ?? null);
                        if (! empty($pilihan)) {
                            $answerText = ! empty($input) ? $pilihan.': '.$input : $pilihan;
                            $answerJson = $jawaban;
                        }
                    } elseif ($jawaban !== null && trim((string) $jawaban) !== '') {
                        $answerText = (string) $jawaban;
                    }
                    break;

                case 'matrix':
                case 'matrix_dual':
                case 'multiple_textbox':
                    if (is_array($jawaban)) {
                        $answerJson = $jawaban;
                        $answerText = json_encode($jawaban, JSON_UNESCAPED_UNICODE);
                    } elseif ($jawaban !== null && trim((string) $jawaban) !== '') {
                        $answerText = (string) $jawaban;
                    }
                    break;

                case 'multiple_number':
                    if (is_array($jawaban)) {
                        $total = 0;
                        $processed = [];
                        $textParts = [];
                        $optMap = $subpertanyaan->detils->keyBy(function ($d) {
                            return (string) ($d->kode_opsi ?: ($d->code ?: $d->id));
                        });

                        foreach ($jawaban as $optKey => $val) {
                            $numVal = is_numeric($val) ? (float) $val : 0;
                            // Jika nilai diinput ribuan (< 1.000.000 dan > 0), kalikan 1000
                            if ($numVal > 0 && $numVal < 1000000) {
                                $numVal = $numVal * 1000;
                            }
                            $processed[$optKey] = (int) $numVal;
                            $total += (int) $numVal;

                            $optLabel = $optMap[(string) $optKey]->option_text ?? ($subpertanyaan->detils->firstWhere('id', $optKey)?->option_text ?? $optKey);
                            $textParts[] = $optLabel.': Rp '.number_format($numVal, 0, ',', '.');
                        }
                        $processed['total'] = (int) $total;
                        $textParts[] = 'Total Pendapatan: Rp '.number_format($total, 0, ',', '.');

                        $answerJson = $processed;
                        $answerText = implode(', ', $textParts);
                    }
                    break;

                default:
                    if ($jawaban !== null && trim((string) $jawaban) !== '') {
                        $answerText = (string) $jawaban;
                    }
                    break;
            }

            if ($answerText === null && $answerJson === null) {
                Tracer::where('biodata_id', $biodata->id)
                    ->where('question_id', $subpertanyaan->id)
                    ->delete();

                continue;
            }

            Tracer::updateOrCreate(
                [
                    'biodata_id' => $biodata->id,
                    'question_id' => $subpertanyaan->id,
                ],
                [
                    'nim' => $biodata->nim,
                    'kelompok' => $subpertanyaan->kelompok,
                    'kode_pertanyaan' => $subpertanyaan->kode_pertanyaan,
                    'subpertanyaan' => $subpertanyaan->subpertanyaan,
                    'answer' => $answerText,
                    'answer_json' => $answerJson,
                    'keterangan' => $subpertanyaan->keterangan,
                    'tahun_lulus' => $biodata->tahun_lulus ?? $biodata->dataAkademik?->tahun_lulus,
                ]
            );

            // Jika ada relasi mapping yang resmi tercatat di database, sinkronisasi datanya ke tabel terkait
            if (isset($pemetaan[$idPertanyaan]) && $answerText !== null) {
                $kolom = $pemetaan[$idPertanyaan]->column_name;
                $tabel = $pemetaan[$idPertanyaan]->table_name;

                if (in_array($tabel, ['data_akademik', 'data_akademiks']) && in_array($kolom, $kolomAkademik)) {
                    $dataUpdateAkademik[$kolom] = $answerText;
                } elseif (in_array($tabel, ['biodata', 'biodatas', 'alumnis']) && in_array($kolom, $kolomBiodata)) {
                    $dataUpdateBiodata[$kolom] = $answerText;
                } elseif (in_array($tabel, ['perusahaan', 'companies']) && in_array($kolom, $kolomPerusahaan)) {
                    $dataUpdatePerusahaan[$kolom] = $answerText;
                } elseif (in_array($tabel, ['atasan', 'atasans']) && in_array($kolom, $kolomAtasan)) {
                    $dataUpdateAtasan[$kolom] = $answerText;
                }
            }
        }

        if (! empty($dataUpdateBiodata)) {
            $biodata->update($dataUpdateBiodata);
        }

        if (! empty($dataUpdateAkademik)) {
            DataAkademik::updateOrCreate(
                ['nim' => $biodata->nim],
                $dataUpdateAkademik
            );
        }

        if (! empty($dataUpdatePerusahaan)) {
            if ($biodata->perusahaan_id && $biodata->perusahaan) {
                $biodata->perusahaan->update($dataUpdatePerusahaan);
            } elseif (! empty($dataUpdatePerusahaan['nama_perusahaan'])) {
                $comp = Perusahaan::create($dataUpdatePerusahaan);
                $biodata->update(['perusahaan_id' => $comp->id]);
            }
        }

        if (! empty($dataUpdateAtasan)) {
            if ($biodata->atasan_id && $biodata->atasan) {
                $biodata->atasan->update($dataUpdateAtasan);
            } elseif (! empty($dataUpdateAtasan['nama']) || ! empty($dataUpdateAtasan['email'])) {
                $emailAtasan = $dataUpdateAtasan['email'] ?? ('atasan_'.$biodata->nim.'@tracerstudy.ukdw.ac.id');
                $atasan = Atasan::firstOrCreate(
                    ['email' => $emailAtasan],
                    [
                        'nama' => $dataUpdateAtasan['nama'] ?? 'Atasan',
                        'telepon' => $dataUpdateAtasan['telepon'] ?? null,
                    ]
                );
                $biodata->update(['atasan_id' => $atasan->id]);
            }
        }

        // Pastikan jawaban profil (F1..F2H) selalu tersinkronisasi di tabel tracer
        KuesionerSyncService::syncProfileResponses($biodata);

        return redirect()->back()->with('success', 'Jawaban berhasil disimpan.');
    }
}
