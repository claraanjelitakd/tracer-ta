<?php

namespace App\Services\Export;

use App\Models\Biodata;
use App\Models\Kuesioner;
use App\Models\ProdiQuestionSection;
use App\Models\ProdiResponse;
use App\Models\Tracer;
use App\Services\Kuesioner\KelengkapanTracerService;
use App\Services\Kuesioner\KuesionerSyncService;
use Symfony\Component\HttpFoundation\StreamedResponse;

/**
 * AlumniTracerExcelExporter
 *
 * Fungsi: Menghasilkan berkas Excel (.xls) berformat HTML kaya gaya (UKDW Green Branding,
 * Identitas Alumni Lengkap, Kuesioner Universitas, Kuesioner Prodi) dengan proteksi format teks
 * (mso-number-format:'\@') agar seluruh data numerik panjang seperti NIK, NPWP, NIM, No KK,
 * No BPJS, NISN, dan No Telepon tidak terkonversi menjadi notasi eksponensial (scientific notation).
 */
class AlumniTracerExcelExporter
{
    /**
     * Download Berkas Excel (.xls) Berformat Lengkap
     *
     * @param  int|string  $id  ID Biodata Alumni
     */
    public static function download(int|string $id): StreamedResponse
    {
        $alumni = Biodata::with([
            'dataAkademik.yudisium',
            'dataAkademik.orangTua',
            'dataAkademik.propinsi',
            'dataAkademik.kabupaten',
            'yudisium',
            'orangTua',
            'propinsi',
            'kabupaten',
            'perusahaan.propinsi',
            'perusahaan.kabupaten',
            'atasan',
            'user',
            'prodi.fakultas',
        ])->findOrFail($id);

        KuesionerSyncService::syncProfileResponses($alumni);

        // Ambil status evaluasi tracer study
        $evaluasi = KelengkapanTracerService::evaluasiKelengkapanTotal($alumni);

        // Ambil seluruh jawaban kuesioner umum
        $savedResponses = Tracer::where('biodata_id', $alumni->id)
            ->get()
            ->keyBy('question_id');

        $kuesioner = Kuesioner::where('is_active', true)
            ->with(['sections' => function ($secQuery) {
                $secQuery->orderBy('order', 'asc')
                    ->with(['subpertanyaans' => function ($qQuery) {
                        $qQuery->orderBy('order', 'asc')
                            ->with('detils');
                    }]);
            }])
            ->first();

        // Kuesioner Prodi
        $savedProdiResponses = ProdiResponse::where('biodata_id', $alumni->id)
            ->get()
            ->keyBy('prodi_question_id');

        $prodiSections = $alumni->prodi_id
            ? ProdiQuestionSection::where('prodi_id', $alumni->prodi_id)
                ->with(['questions' => function ($qQuery) {
                    $qQuery->orderBy('order', 'asc')->with('options');
                }])
                ->orderBy('order', 'asc')
                ->get()
            : collect([]);

        $nim = preg_replace('/[^a-zA-Z0-9_-]/', '_', (string) $alumni->nim);
        $nama = preg_replace('/[^a-zA-Z0-9_-]/', '_', (string) ($alumni->nama ?? 'Alumni'));
        $filename = "Tracer_Study_{$nim}_{$nama}.xls";

        return response()->streamDownload(function () use ($alumni, $kuesioner, $savedResponses, $prodiSections, $savedProdiResponses) {
            $dataAkademik = $alumni->dataAkademik;
            $orangTua = $alumni->orangTua ?? $dataAkademik?->orangTua;
            $yudisium = $alumni->yudisium ?? $dataAkademik?->yudisium;
            $perusahaan = $alumni->perusahaan;
            $atasan = $alumni->atasan;

            // Identitas & Kontak
            $prodiNama = $alumni->prodi?->nama_prodi ?? ($dataAkademik?->program_studi ?? '-');
            $fakultasNama = $alumni->prodi?->fakultas?->nama_fakultas ?? ($dataAkademik?->fakultas ?? '-');
            $namaLengkap = $alumni->nama ?: ($dataAkademik?->nama ?: ($alumni->user?->name ?: '-'));
            $emailPribadi = $alumni->email_pribadi ?: ($alumni->email ?: ($dataAkademik?->email_pribadi ?: ($alumni->user?->email ?: '-')));
            $emailKampus = $dataAkademik?->email_students ?: ($alumni->email_students ?: '-');
            $telepon = $alumni->nomor_telepon ?: ($dataAkademik?->nomor_telepon ?: '-');
            $nik = $alumni->nik ?: ($dataAkademik?->nik ?: '-');
            $noKk = $alumni->no_kk ?: ($dataAkademik?->no_kk ?: '-');
            $noBpjs = $alumni->no_bpjs ?: ($dataAkademik?->no_bpjs ?: '-');
            $nisn = $dataAkademik?->nisn ?: ($alumni->nisn ?: '-');
            $npwp = $alumni->npwp ?: '-';

            // Alamat
            $alamat = $alumni->alamat ?: ($dataAkademik?->alamat_saat_ini ?: '-');
            $kelurahan = $alumni->kelurahan ?: ($dataAkademik?->kelurahan ?: '-');
            $kecamatan = $alumni->kecamatan ?: ($dataAkademik?->kecamatan ?: '-');
            $provinsi = $alumni->propinsi?->nama_provinsi ?: ($dataAkademik?->propinsi?->nama_provinsi ?: '-');
            $kabupaten = $alumni->kabupaten?->nama_kabupaten ?: ($dataAkademik?->kabupaten?->nama_kabupaten ?: '-');
            $kodePos = $alumni->kode_pos ?: ($dataAkademik?->kode_pos ?: '-');

            // Akademik & Kelulusan (Pastikan Tahun Lulus selalu terisi)
            $tahunLulus = $alumni->tahun_lulus
                ?: ($yudisium?->tahun_lulus
                ?: ($dataAkademik?->tahun_lulus
                ?: ($yudisium?->tahun_akademik_lulus
                ?: ($dataAkademik?->tahun_akademik_lulus ?: '-'))));

            $semesterLulus = $yudisium?->tahun_akademik_lulus ?: ($dataAkademik?->tahun_akademik_lulus ?: '-');
            $angkatanMasuk = $dataAkademik?->angkatan_masuk ?: '-';
            $ipk = $dataAkademik?->ip_kumulatif ?: '-';
            $totalSks = $dataAkademik?->total_sks ?: '-';
            $statusYudisium = $yudisium?->proses_yudisium ?: ($yudisium?->keterangan_hasil_yudisium ?: 'Lulus');
            $judulTa = $yudisium?->judul_ta ?: '-';
            $dospem = $yudisium?->dosen_pembimbing_1 ?: '-';

            // Karir & Perusahaan
            $namaPerusahaan = $perusahaan?->nama_perusahaan ?: '-';
            $posisiJabatan = $alumni->posisi_jabatan ?: '-';
            $skalaPerusahaan = $perusahaan?->skala ?: '-';
            $alamatPerusahaan = $perusahaan?->alamat ?: '-';
            $namaAtasan = $atasan?->nama ?: '-';
            $teleponAtasan = $atasan?->telepon ?: '-';
            $emailAtasan = $atasan?->email ?: '-';

            $waktuUnduh = date('d F Y, H:i').' WIB';

            echo '<html xmlns:o="urn:schemas-microsoft-com:office:office" xmlns:x="urn:schemas-microsoft-com:office:excel" xmlns="http://www.w3.org/TR/REC-html40">'."\n";
            echo "<head>\n";
            echo '<meta http-equiv="Content-Type" content="text/html; charset=UTF-8">'."\n";
            echo "<!--[if gte mso 9]><xml><x:ExcelWorkbook><x:ExcelWorksheets><x:ExcelWorksheet><x:Name>Tracer Study</x:Name><x:WorksheetOptions><x:DisplayGridlines/></x:WorksheetOptions></x:ExcelWorksheet></x:ExcelWorksheets></x:ExcelWorkbook></xml><![endif]-->\n";
            echo "<style>\n";
            echo "  body { font-family: Calibri, 'Segoe UI', Arial, sans-serif; font-size: 11pt; color: #1e293b; }\n";
            echo "  table { border-collapse: collapse; width: 100%; }\n";
            echo "  th, td { border: 1px solid #cbd5e1; padding: 6px 10px; vertical-align: middle; }\n";
            echo "  .header-main { font-size: 15pt; font-weight: bold; color: #0D542B; padding: 10px 0 4px 0; }\n";
            echo "  .header-sub { font-size: 9.5pt; color: #64748b; padding-bottom: 12px; }\n";
            echo "  .box-identitas-header { background-color: #0D542B; color: #ffffff; font-weight: bold; text-align: center; font-size: 11pt; padding: 8px; }\n";
            echo "  .box-identitas-subheader { background-color: #FDC700; color: #000000; font-weight: bold; text-align: left; font-size: 10.5pt; padding: 6px 10px; }\n";
            echo "  .box-identitas-label { background-color: #f8fafc; font-weight: bold; color: #334155; width: 16%; }\n";
            echo "  .box-identitas-val { background-color: #ffffff; width: 34%; }\n";
            echo "  .table-header { background-color: #f1f5f9; font-weight: bold; text-align: center; color: #1e293b; font-size: 10pt; }\n";
            echo "  .section-row-univ { background-color: #fef08a; font-weight: bold; color: #713f12; padding: 8px 10px; font-size: 10.5pt; }\n";
            echo "  .section-row-prodi { background-color: #fed7aa; font-weight: bold; color: #7c2d12; padding: 8px 10px; font-size: 10.5pt; }\n";
            echo "  .text-center { text-align: center; }\n";
            echo "  .text-left { text-align: left; }\n";
            echo "  .text-right { text-align: right; }\n";
            echo "  .text-code { font-family: Consolas, 'Courier New', monospace; font-weight: bold; text-align: center; mso-number-format: '\@'; }\n";
            echo "  .text-string { mso-number-format: '\@'; }\n";
            echo "  .status-terjawab { color: #15803d; font-weight: bold; text-align: center; background-color: #f0fdf4; }\n";
            echo "  .status-belum { color: #b91c1c; font-weight: bold; text-align: center; background-color: #fef2f2; }\n";
            echo "  .status-header { color: #64748b; font-style: italic; text-align: center; background-color: #fef9c3; }\n";
            echo "  .badge-wajib { color: #b91c1c; font-weight: bold; text-align: center; }\n";
            echo "  .badge-opsional { color: #475569; text-align: center; }\n";
            echo "  .bg-alt { background-color: #fafafa; }\n";
            echo "</style>\n";
            echo "</head>\n";
            echo "<body>\n";
            echo "<table>\n";

            // 1. Judul Header
            echo "  <tr><td colspan=\"8\" class=\"header-main\" style=\"border:none;\">LAPORAN LENGKAP KUESIONER TRACER STUDY ALUMNI</td></tr>\n";
            echo "  <tr><td colspan=\"8\" class=\"header-sub\" style=\"border:none;\">Universitas Kristen Duta Wacana (UKDW) | Diunduh pada: {$waktuUnduh}</td></tr>\n";
            echo "  <tr><td colspan=\"8\" style=\"border:none; height: 6px;\"></td></tr>\n";

            // 2. Blok Identitas Alumni Lengkap
            echo "  <tr><td colspan=\"8\" class=\"box-identitas-header\">IDENTITAS LENGKAP & REKAM JEJAK MAHASISWA / ALUMNI</td></tr>\n";

            // Sub 1: Identitas Diri & Kontak
            echo "  <tr><td colspan=\"8\" class=\"box-identitas-subheader\">1. Data Diri & Kontak Pribadi</td></tr>\n";
            echo "  <tr>\n";
            echo "    <td class=\"box-identitas-label\">NIM</td>\n";
            echo "    <td class=\"box-identitas-val text-string\">{$alumni->nim}</td>\n";
            echo "    <td class=\"box-identitas-label\">Nama Lengkap</td>\n";
            echo "    <td colspan=\"5\" class=\"box-identitas-val\">{$namaLengkap}</td>\n";
            echo "  </tr>\n";
            echo "  <tr>\n";
            echo "    <td class=\"box-identitas-label\">NIK (KTP)</td>\n";
            echo "    <td class=\"box-identitas-val text-string\">{$nik}</td>\n";
            echo "    <td class=\"box-identitas-label\">NPWP</td>\n";
            echo "    <td colspan=\"5\" class=\"box-identitas-val text-string\">{$npwp}</td>\n";
            echo "  </tr>\n";
            echo "  <tr>\n";
            echo "    <td class=\"box-identitas-label\">No. Kartu Keluarga</td>\n";
            echo "    <td class=\"box-identitas-val text-string\">{$noKk}</td>\n";
            echo "    <td class=\"box-identitas-label\">No. BPJS / Asuransi</td>\n";
            echo "    <td colspan=\"5\" class=\"box-identitas-val text-string\">{$noBpjs}</td>\n";
            echo "  </tr>\n";
            echo "  <tr>\n";
            echo "    <td class=\"box-identitas-label\">Nomor Telepon / WA</td>\n";
            echo "    <td class=\"box-identitas-val text-string\">{$telepon}</td>\n";
            echo "    <td class=\"box-identitas-label\">Email Pribadi</td>\n";
            echo "    <td colspan=\"5\" class=\"box-identitas-val\">{$emailPribadi}</td>\n";
            echo "  </tr>\n";
            echo "  <tr>\n";
            echo "    <td class=\"box-identitas-label\">Alamat Tinggal Saat Ini</td>\n";
            echo "    <td colspan=\"7\" class=\"box-identitas-val\">{$alamat}, Kel. {$kelurahan}, Kec. {$kecamatan}, {$kabupaten}, {$provinsi} ({$kodePos})</td>\n";
            echo "  </tr>\n";

            // Sub 2: Rekam Jejak Akademik & Kelulusan
            echo "  <tr><td colspan=\"8\" class=\"box-identitas-subheader\">2. Data Akademik, Kelulusan & Yudisium</td></tr>\n";
            echo "  <tr>\n";
            echo "    <td class=\"box-identitas-label\">Program Studi</td>\n";
            echo "    <td class=\"box-identitas-val\">{$prodiNama}</td>\n";
            echo "    <td class=\"box-identitas-label\">Fakultas</td>\n";
            echo "    <td colspan=\"5\" class=\"box-identitas-val\">{$fakultasNama}</td>\n";
            echo "  </tr>\n";
            echo "  <tr>\n";
            echo "    <td class=\"box-identitas-label\">Tahun Lulus</td>\n";
            echo "    <td class=\"box-identitas-val text-string\">{$tahunLulus}</td>\n";
            echo "    <td class=\"box-identitas-label\">Semester Kelulusan</td>\n";
            echo "    <td colspan=\"5\" class=\"box-identitas-val\">{$semesterLulus}</td>\n";
            echo "  </tr>\n";
            echo "  <tr>\n";
            echo "    <td class=\"box-identitas-label\">Angkatan Masuk</td>\n";
            echo "    <td class=\"box-identitas-val text-string\">{$angkatanMasuk}</td>\n";
            echo "    <td class=\"box-identitas-label\">IPK / Total SKS</td>\n";
            echo "    <td colspan=\"5\" class=\"box-identitas-val\">IPK: {$ipk} | SKS: {$totalSks}</td>\n";
            echo "  </tr>\n";
            echo "  <tr>\n";
            echo "    <td class=\"box-identitas-label\">Status Yudisium</td>\n";
            echo "    <td class=\"box-identitas-val\">{$statusYudisium}</td>\n";
            echo "    <td class=\"box-identitas-label\">Dosen Pembimbing TA</td>\n";
            echo "    <td colspan=\"5\" class=\"box-identitas-val\">{$dospem}</td>\n";
            echo "  </tr>\n";
            echo "  <tr>\n";
            echo "    <td class=\"box-identitas-label\">Judul Tugas Akhir</td>\n";
            echo "    <td colspan=\"7\" class=\"box-identitas-val\">{$judulTa}</td>\n";
            echo "  </tr>\n";

            // Sub 3: Informasi Pekerjaan & Karir
            echo "  <tr><td colspan=\"8\" class=\"box-identitas-subheader\">3. Informasi Karir, Perusahaan & Atasan</td></tr>\n";
            echo "  <tr>\n";
            echo "    <td class=\"box-identitas-label\">Nama Perusahaan / Kantor</td>\n";
            echo "    <td class=\"box-identitas-val\">{$namaPerusahaan}</td>\n";
            echo "    <td class=\"box-identitas-label\">Posisi / Jabatan</td>\n";
            echo "    <td colspan=\"5\" class=\"box-identitas-val\">{$posisiJabatan}</td>\n";
            echo "  </tr>\n";
            echo "  <tr>\n";
            echo "    <td class=\"box-identitas-label\">Skala Instansi / Usaha</td>\n";
            echo "    <td class=\"box-identitas-val\">{$skalaPerusahaan}</td>\n";
            echo "    <td class=\"box-identitas-label\">Nama Atasan Langsung</td>\n";
            echo "    <td colspan=\"5\" class=\"box-identitas-val\">{$namaAtasan}</td>\n";
            echo "  </tr>\n";
            echo "  <tr>\n";
            echo "    <td class=\"box-identitas-label\">Kontak Atasan</td>\n";
            echo "    <td colspan=\"7\" class=\"box-identitas-val text-string\">Telp: {$teleponAtasan} | Email: {$emailAtasan}</td>\n";
            echo "  </tr>\n";
            echo "  <tr><td colspan=\"8\" style=\"border:none; height: 12px;\"></td></tr>\n";

            // 3. Header Tabel Kuesioner
            echo "  <thead>\n";
            echo "    <tr class=\"table-header\">\n";
            echo "      <th style=\"width: 4%;\">No</th>\n";
            echo "      <th style=\"width: 18%;\">Bagian / Seksi</th>\n";
            echo "      <th style=\"width: 10%;\">Kode</th>\n";
            echo "      <th style=\"width: 32%;\">Pertanyaan Instrumen</th>\n";
            echo "      <th style=\"width: 8%;\">Tipe Input</th>\n";
            echo "      <th style=\"width: 6%;\">Sifat</th>\n";
            echo "      <th style=\"width: 8%;\">Status</th>\n";
            echo "      <th style=\"width: 14%;\">Respon / Jawaban Alumni</th>\n";
            echo "    </tr>\n";
            echo "  </thead>\n";
            echo "  <tbody>\n";

            // 4. Kuesioner Universitas
            $no = 1;
            if ($kuesioner) {
                foreach ($kuesioner->sections as $section) {
                    $sectionTitle = htmlspecialchars($section->title ?: ($section->section ?: 'Bagian Kuesioner'));
                    echo "    <tr><td colspan=\"8\" class=\"section-row-univ\">Seksi {$section->order}: {$sectionTitle} (Kuesioner Universitas)</td></tr>\n";

                    foreach ($section->subpertanyaans as $sub) {
                        $resp = $savedResponses->get($sub->id);
                        $isMandatory = KelengkapanTracerService::isMandatoryQuestion($sub->kode_pertanyaan);
                        $isHeader = in_array($sub->type, ['header', 'section_header']);

                        $answerText = '-';
                        $statusClass = 'status-belum';
                        $statusText = 'Belum Dijawab';

                        if ($isHeader) {
                            $answerText = '';
                            $statusClass = 'status-header';
                            $statusText = 'Header';
                        } elseif ($resp) {
                            if (! empty($resp->answer_text) && trim((string) $resp->answer_text) !== '') {
                                $answerText = htmlspecialchars((string) $resp->answer_text);
                                $statusClass = 'status-terjawab';
                                $statusText = 'Terjawab';
                            } elseif (is_array($resp->answer_json) && count($resp->answer_json) > 0) {
                                $jsonFormatted = [];
                                foreach ($resp->answer_json as $k => $v) {
                                    $jsonFormatted[] = is_numeric($k) ? (string) $v : "{$k}: {$v}";
                                }
                                $answerText = htmlspecialchars(implode(', ', $jsonFormatted));
                                $statusClass = 'status-terjawab';
                                $statusText = 'Terjawab';
                            }
                        }

                        $bgClass = ($no % 2 === 0) ? ' class="bg-alt"' : '';
                        $subPertanyaanText = htmlspecialchars($sub->subpertanyaan);
                        $tipeText = htmlspecialchars($sub->type ?: 'text');
                        $kodePertanyaan = htmlspecialchars($sub->kode_pertanyaan);
                        $sifatHtml = $isHeader ? '-' : ($isMandatory ? '<span class="badge-wajib">Wajib</span>' : '<span class="badge-opsional">Opsional</span>');

                        echo "    <tr{$bgClass}>\n";
                        echo "      <td class=\"text-center\">{$no}</td>\n";
                        echo "      <td>{$sectionTitle}</td>\n";
                        echo "      <td class=\"text-code\">{$kodePertanyaan}</td>\n";
                        echo "      <td>{$subPertanyaanText}</td>\n";
                        echo "      <td class=\"text-center\">{$tipeText}</td>\n";
                        echo "      <td class=\"text-center\">{$sifatHtml}</td>\n";
                        echo "      <td class=\"{$statusClass}\">{$statusText}</td>\n";
                        echo "      <td class=\"text-string\" style=\"font-weight: 500;\">{$answerText}</td>\n";
                        echo "    </tr>\n";

                        $no++;
                    }
                }
            }

            // 5. Kuesioner Khusus Program Studi
            if ($prodiSections->isNotEmpty()) {
                foreach ($prodiSections as $pSection) {
                    $pTitle = htmlspecialchars($pSection->title ?: 'Kuesioner Program Studi');
                    echo "    <tr><td colspan=\"8\" class=\"section-row-prodi\">Seksi {$pSection->order}: {$pTitle} (Kuesioner Program Studi: {$prodiNama})</td></tr>\n";

                    foreach ($pSection->questions as $pQ) {
                        $isHeader = in_array($pQ->type, ['header', 'section_header']);
                        $resp = $savedProdiResponses->get($pQ->id);
                        $isMandatory = (bool) $pQ->is_required;

                        $answerText = '-';
                        $statusClass = 'status-belum';
                        $statusText = 'Belum Dijawab';

                        if ($isHeader) {
                            $answerText = '';
                            $statusClass = 'status-header';
                            $statusText = 'Header';
                        } elseif ($resp) {
                            if (! empty($resp->answer_text) && trim((string) $resp->answer_text) !== '') {
                                $answerText = htmlspecialchars((string) $resp->answer_text);
                                $statusClass = 'status-terjawab';
                                $statusText = 'Terjawab';
                            } elseif (is_array($resp->answer_json) && count($resp->answer_json) > 0) {
                                $jsonFormatted = [];
                                foreach ($resp->answer_json as $k => $v) {
                                    $jsonFormatted[] = is_numeric($k) ? (string) $v : "{$k}: {$v}";
                                }
                                $answerText = htmlspecialchars(implode(', ', $jsonFormatted));
                                $statusClass = 'status-terjawab';
                                $statusText = 'Terjawab';
                            }
                        }

                        $bgClass = ($no % 2 === 0) ? ' class="bg-alt"' : '';
                        $pQuestionText = htmlspecialchars($pQ->question_text);
                        $tipeText = htmlspecialchars($pQ->type ?: 'text');
                        $kodePertanyaan = htmlspecialchars($pQ->code ?: "P{$pQ->id}");
                        $sifatHtml = $isHeader ? '-' : ($isMandatory ? '<span class="badge-wajib">Wajib</span>' : '<span class="badge-opsional">Opsional</span>');

                        echo "    <tr{$bgClass}>\n";
                        echo "      <td class=\"text-center\">{$no}</td>\n";
                        echo "      <td>{$pTitle}</td>\n";
                        echo "      <td class=\"text-code\">{$kodePertanyaan}</td>\n";
                        echo "      <td>{$pQuestionText}</td>\n";
                        echo "      <td class=\"text-center\">{$tipeText}</td>\n";
                        echo "      <td class=\"text-center\">{$sifatHtml}</td>\n";
                        echo "      <td class=\"{$statusClass}\">{$statusText}</td>\n";
                        echo "      <td class=\"text-string\" style=\"font-weight: 500;\">{$answerText}</td>\n";
                        echo "    </tr>\n";

                        $no++;
                    }
                }
            }

            echo "  </tbody>\n";
            echo "</table>\n";
            echo "</body>\n";
            echo "</html>\n";
        }, $filename, [
            'Content-Type' => 'application/vnd.ms-excel; charset=UTF-8',
            'Content-Disposition' => "attachment; filename=\"{$filename}\"",
            'Cache-Control' => 'max-age=0, no-cache, no-store, must-revalidate',
            'Pragma' => 'no-cache',
        ]);
    }
}
