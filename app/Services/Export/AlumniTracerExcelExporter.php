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
            // ==========================================
            // EXTRACT SELURUH ENTITAS PROFIL LENGKAP 4 SUB-TAB
            // ==========================================
            $dataAkademik = $alumni->dataAkademik;
            $orangTua = $alumni->orangTua ?? $dataAkademik?->orangTua;
            $yudisium = $alumni->yudisium ?? $dataAkademik?->yudisium;
            $perusahaan = $alumni->perusahaan;
            $atasan = $alumni->atasan;

            // 1. Data Pribadi & Kontak
            $namaLengkap = $alumni->nama ?: ($dataAkademik?->nama ?: ($alumni->user?->name ?: '-'));
            $nik = $alumni->nik ?: ($dataAkademik?->nik ?: '-');
            $npwp = $alumni->npwp ?: '-';
            $noKk = $alumni->no_kk ?: ($dataAkademik?->no_kk ?: '-');
            $noBpjs = $alumni->no_bpjs ?: ($dataAkademik?->no_bpjs ?: '-');
            $nisn = $dataAkademik?->nisn ?: ($alumni->nisn ?: '-');
            $jenisKelamin = $alumni->jenis_kelamin ?: ($dataAkademik?->jenis_kelamin ?: '-');
            $tempatLahir = $alumni->tempat_lahir ?: ($dataAkademik?->tempat_lahir ?: '-');
            $tanggalLahir = $alumni->tanggal_lahir ?: ($dataAkademik?->tanggal_lahir ?: '-');
            $agama = $alumni->agama ?: ($dataAkademik?->agama ?: '-');
            $golDarah = $alumni->golongan_darah ?: ($dataAkademik?->golongan_darah ?: '-');
            $wargaNegara = $alumni->warga_negara ?: ($dataAkademik?->warga_negara ?: 'WNI');
            $telepon = $alumni->nomor_telepon ?: ($dataAkademik?->nomor_telepon ?: '-');
            $emailPribadi = $alumni->email_pribadi ?: ($alumni->email ?: ($dataAkademik?->email_pribadi ?: ($alumni->user?->email ?: '-')));
            $emailKampus = $dataAkademik?->email_students ?: ($alumni->email_students ?: '-');
            $linkedin = $alumni->linkedin_url ?: ($alumni->linkedin_username ?: '-');
            $instagram = $alumni->instagram_url ?: '-';
            $facebook = $alumni->facebook_url ?: '-';
            $medsos = trim(($instagram !== '-' ? "IG: {$instagram}" : '').($facebook !== '-' ? " | FB: {$facebook}" : '')) ?: '-';
            $expert = $alumni->expert ?: '-';
            $minat = $alumni->minat ?: '-';
            $alamat = $alumni->alamat ?: ($dataAkademik?->alamat_saat_ini ?: '-');
            $kelurahan = $alumni->kelurahan ?: ($dataAkademik?->kelurahan ?: '-');
            $kecamatan = $alumni->kecamatan ?: ($dataAkademik?->kecamatan ?: '-');
            $provinsi = $alumni->propinsi?->nama_provinsi ?: ($dataAkademik?->propinsi?->nama_provinsi ?: '-');
            $kabupaten = $alumni->kabupaten?->nama_kabupaten ?: ($dataAkademik?->kabupaten?->nama_kabupaten ?: '-');
            $kodePos = $alumni->kode_pos ?: ($dataAkademik?->kode_pos ?: '-');
            $alamatDomisiliLengkap = "{$alamat}, Kel. {$kelurahan}, Kec. {$kecamatan}, {$kabupaten}, {$provinsi} ({$kodePos})";

            // 2. Data Akademik & Kelulusan
            $prodiNama = $alumni->prodi?->nama_prodi ?? ($dataAkademik?->program_studi ?? '-');
            $fakultasNama = $alumni->prodi?->fakultas?->nama_fakultas ?? ($dataAkademik?->fakultas ?? '-');
            $angkatanMasuk = $dataAkademik?->angkatan_masuk ?: '-';
            $statusMahasiswa = $dataAkademik?->status_mahasiswa ?: 'Alumni';
            $tahunLulus = $alumni->tahun_lulus
                ?: ($yudisium?->tahun_lulus
                ?: ($dataAkademik?->tahun_lulus
                ?: ($yudisium?->tahun_akademik_lulus
                ?: ($dataAkademik?->tahun_akademik_lulus ?: '-'))));
            $semesterLulus = $yudisium?->tahun_akademik_lulus ?: ($dataAkademik?->tahun_akademik_lulus ?: '-');
            $ipk = $dataAkademik?->ip_kumulatif ?: '-';
            $totalSks = $dataAkademik?->total_sks ?: '-';
            $totalAngkaKualitas = $dataAkademik?->total_angka_kualitas ?: '-';
            $asalSekolah = $dataAkademik?->asal_sekolah ?: '-';
            $jurusanSekolah = $dataAkademik?->jurusan_asal_sekolah ?: '-';
            $alamatSekolah = $dataAkademik?->alamat_asal_sekolah ?: '-';
            $wilayahSekolah = trim(($dataAkademik?->kota_kabupaten_asal_sekolah ? $dataAkademik->kota_kabupaten_asal_sekolah : '').($dataAkademik?->provinsi_asal_sekolah ? ', '.$dataAkademik->provinsi_asal_sekolah : '')) ?: '-';

            // 3. Data Orang Tua / Wali
            $namaOrtu = $orangTua?->nama_orang_tua ?: '-';
            $pekerjaanOrtu = $orangTua?->pekerjaan ?: '-';
            $teleponOrtu = $orangTua?->nomor_telepon ?: '-';
            $kotaOrtu = $orangTua?->kota ?: ($orangTua?->kabupaten?->nama_kabupaten ?: '-');
            $alamatOrtu = $orangTua?->alamat ?: '-';
            $kodePosOrtu = $orangTua?->kode_pos ?: '-';

            // 4. Data Karier, Perusahaan & Atasan
            $kategoriPekerjaan = $alumni->kategori_pekerjaan ?: '-';
            $posisiJabatan = $alumni->posisi_jabatan ?: ($alumni->posisi_wiraswasta ?: '-');
            $posisiWiraswasta = $alumni->posisi_wiraswasta ?: '-';
            $gaji = $alumni->gaji ? 'Rp '.number_format((float) $alumni->gaji, 0, ',', '.') : '-';
            $jenisPekerjaan = $alumni->jenis_pekerjaan ?: ($perusahaan?->jenis_perusahaan ?: ($alumni->kategori_pekerjaan ?: '-'));
            $namaPerusahaan = $perusahaan?->nama_perusahaan ?: '-';
            $sektorPerusahaan = $perusahaan?->sektor ?: '-';
            $skalaPerusahaan = $perusahaan?->skala ?: '-';
            $jenisPerusahaan = $perusahaan?->jenis_perusahaan ?: ($alumni->jenis_pekerjaan ?: '-');
            $lokasiPerusahaan = $perusahaan?->jenis_lokasi ?: '-';
            $negaraPerusahaan = $perusahaan?->negara ?: 'Indonesia';
            $alamatPerusahaan = $perusahaan?->alamat ?: '-';
            $kodePosPerusahaan = $perusahaan?->kode_pos ?: ($alumni->zipcode ?: '-');
            $namaAtasan = $atasan?->nama ?: '-';
            $teleponAtasan = $atasan?->telepon ?: '-';
            $emailAtasan = $atasan?->email ?: '-';
            $tingkatPendidikanLanjut = $alumni->pendidikan_tingkat ?: '-';
            $kampusLanjut = $alumni->perguruan_tinggi ?: '-';
            $prodiLanjut = $alumni->pendidikan_prodi ?: '-';

            $waktuUnduh = date('d F Y, H:i').' WIB';

            echo '<html xmlns:o="urn:schemas-microsoft-com:office:office" xmlns:x="urn:schemas-microsoft-com:office:excel" xmlns="http://www.w3.org/TR/REC-html40">'."\n";
            echo "<head>\n";
            echo '<meta http-equiv="Content-Type" content="text/html; charset=UTF-8">'."\n";
            echo "<!--[if gte mso 9]><xml><x:ExcelWorkbook><x:ExcelWorksheets><x:ExcelWorksheet><x:Name>Tracer Study</x:Name><x:WorksheetOptions><x:DisplayGridlines/></x:WorksheetOptions></x:ExcelWorksheet></x:ExcelWorksheets></x:ExcelWorkbook></xml><![endif]-->\n";
            echo "<style>\n";
            echo "  body { font-family: Calibri, 'Segoe UI', Arial, sans-serif; font-size: 11pt; color: #1e293b; }\n";
            echo "  table { border-collapse: collapse; width: 100%; }\n";
            echo "  th, td { border: 1px solid #cbd5e1; padding: 6px 10px; vertical-align: middle; mso-number-format: '\@'; }\n";
            echo "  .header-main { font-size: 15pt; font-weight: bold; color: #0D542B; padding: 10px 0 4px 0; }\n";
            echo "  .header-sub { font-size: 9.5pt; color: #64748b; padding-bottom: 12px; }\n";
            echo "  .box-identitas-header { background-color: #0D542B; color: #ffffff; font-weight: bold; text-align: center; font-size: 11pt; padding: 8px; }\n";
            echo "  .box-identitas-subheader { background-color: #FDC700; color: #000000; font-weight: bold; text-align: left; font-size: 10.5pt; padding: 6px 10px; }\n";
            echo "  .box-identitas-label { background-color: #f8fafc; font-weight: bold; color: #334155; width: 18%; }\n";
            echo "  .box-identitas-val { background-color: #ffffff; width: 32%; mso-number-format: '\@'; }\n";
            echo "  .table-header { background-color: #f1f5f9; font-weight: bold; text-align: center; color: #1e293b; font-size: 10pt; }\n";
            echo "  .section-row-univ { background-color: #fef08a; font-weight: bold; color: #713f12; padding: 8px 10px; font-size: 10.5pt; }\n";
            echo "  .section-row-prodi { background-color: #fed7aa; font-weight: bold; color: #7c2d12; padding: 8px 10px; font-size: 10.5pt; }\n";
            echo "  .text-center { text-align: center; }\n";
            echo "  .text-left { text-align: left; }\n";
            echo "  .text-right { text-align: right; }\n";
            echo "  .text-code { font-family: Consolas, 'Courier New', monospace; font-weight: bold; text-align: center; mso-number-format: '\@'; }\n";
            echo "  .status-v { color: #15803d; font-weight: bold; text-align: center; font-size: 12pt; background-color: #f0fdf4; mso-number-format: '\@'; }\n";
            echo "  .status-x { color: #b91c1c; font-weight: bold; text-align: center; font-size: 12pt; background-color: #fef2f2; mso-number-format: '\@'; }\n";
            echo "  .status-header { color: #64748b; font-style: italic; text-align: center; background-color: #fef9c3; mso-number-format: '\@'; }\n";
            echo "  .badge-wajib { color: #b91c1c; font-weight: bold; text-align: center; }\n";
            echo "  .badge-opsional { color: #475569; text-align: center; }\n";
            echo "  .bg-alt { background-color: #fafafa; }\n";
            echo "</style>\n";
            echo "</head>\n";
            echo "<body>\n";
            echo "<table>\n";

            // 1. Judul Header Laporan
            echo "  <tr><td colspan=\"8\" class=\"header-main\" style=\"border:none;\">LAPORAN LENGKAP KUESIONER TRACER STUDY ALUMNI</td></tr>\n";
            echo "  <tr><td colspan=\"8\" class=\"header-sub\" style=\"border:none;\">Universitas Kristen Duta Wacana (UKDW) | Diunduh pada: {$waktuUnduh}</td></tr>\n";
            echo "  <tr><td colspan=\"8\" style=\"border:none; height: 6px;\"></td></tr>\n";

            // 2. Blok Identitas Alumni Lengkap (4 Sub-Bagian Lengkap)
            echo "  <tr><td colspan=\"8\" class=\"box-identitas-header\">IDENTITAS LENGKAP & REKAM JEJAK MAHASISWA / ALUMNI</td></tr>\n";

            // -------------------------------------------------------------
            // SUB-BAGIAN 1: DATA PRIBADI & KONTAK
            // -------------------------------------------------------------
            echo "  <tr><td colspan=\"8\" class=\"box-identitas-subheader\">1. Data Diri & Kontak Pribadi</td></tr>\n";
            echo "  <tr>\n";
            echo "    <td class=\"box-identitas-label\">NIM</td>\n";
            echo "    <td class=\"box-identitas-val\" style=\"mso-number-format:'\@';\">{$alumni->nim}</td>\n";
            echo "    <td class=\"box-identitas-label\">Nama Lengkap</td>\n";
            echo "    <td colspan=\"5\" class=\"box-identitas-val\" style=\"mso-number-format:'\@';\">{$namaLengkap}</td>\n";
            echo "  </tr>\n";
            echo "  <tr>\n";
            echo "    <td class=\"box-identitas-label\">NIK (KTP)</td>\n";
            echo "    <td class=\"box-identitas-val\" style=\"mso-number-format:'\@';\">{$nik}</td>\n";
            echo "    <td class=\"box-identitas-label\">NPWP</td>\n";
            echo "    <td colspan=\"5\" class=\"box-identitas-val\" style=\"mso-number-format:'\@';\">{$npwp}</td>\n";
            echo "  </tr>\n";
            echo "  <tr>\n";
            echo "    <td class=\"box-identitas-label\">No. Kartu Keluarga (KK)</td>\n";
            echo "    <td class=\"box-identitas-val\" style=\"mso-number-format:'\@';\">{$noKk}</td>\n";
            echo "    <td class=\"box-identitas-label\">No. BPJS / Asuransi</td>\n";
            echo "    <td colspan=\"5\" class=\"box-identitas-val\" style=\"mso-number-format:'\@';\">{$noBpjs}</td>\n";
            echo "  </tr>\n";
            echo "  <tr>\n";
            echo "    <td class=\"box-identitas-label\">NISN</td>\n";
            echo "    <td class=\"box-identitas-val\" style=\"mso-number-format:'\@';\">{$nisn}</td>\n";
            echo "    <td class=\"box-identitas-label\">Jenis Kelamin</td>\n";
            echo "    <td colspan=\"5\" class=\"box-identitas-val\" style=\"mso-number-format:'\@';\">{$jenisKelamin}</td>\n";
            echo "  </tr>\n";
            echo "  <tr>\n";
            echo "    <td class=\"box-identitas-label\">Tempat Lahir</td>\n";
            echo "    <td class=\"box-identitas-val\" style=\"mso-number-format:'\@';\">{$tempatLahir}</td>\n";
            echo "    <td class=\"box-identitas-label\">Tanggal Lahir</td>\n";
            echo "    <td colspan=\"5\" class=\"box-identitas-val\" style=\"mso-number-format:'\@';\">{$tanggalLahir}</td>\n";
            echo "  </tr>\n";
            echo "  <tr>\n";
            echo "    <td class=\"box-identitas-label\">Agama</td>\n";
            echo "    <td class=\"box-identitas-val\" style=\"mso-number-format:'\@';\">{$agama}</td>\n";
            echo "    <td class=\"box-identitas-label\">Golongan Darah</td>\n";
            echo "    <td colspan=\"5\" class=\"box-identitas-val\" style=\"mso-number-format:'\@';\">{$golDarah}</td>\n";
            echo "  </tr>\n";
            echo "  <tr>\n";
            echo "    <td class=\"box-identitas-label\">Kewarganegaraan</td>\n";
            echo "    <td class=\"box-identitas-val\" style=\"mso-number-format:'\@';\">{$wargaNegara}</td>\n";
            echo "    <td class=\"box-identitas-label\">Nomor Telepon / WA</td>\n";
            echo "    <td colspan=\"5\" class=\"box-identitas-val\" style=\"mso-number-format:'\@';\">{$telepon}</td>\n";
            echo "  </tr>\n";
            echo "  <tr>\n";
            echo "    <td class=\"box-identitas-label\">Email Pribadi</td>\n";
            echo "    <td class=\"box-identitas-val\" style=\"mso-number-format:'\@';\">{$emailPribadi}</td>\n";
            echo "    <td class=\"box-identitas-label\">Email Kampus / Mahasiswa</td>\n";
            echo "    <td colspan=\"5\" class=\"box-identitas-val\" style=\"mso-number-format:'\@';\">{$emailKampus}</td>\n";
            echo "  </tr>\n";
            echo "  <tr>\n";
            echo "    <td class=\"box-identitas-label\">LinkedIn (URL / Username)</td>\n";
            echo "    <td class=\"box-identitas-val\" style=\"mso-number-format:'\@';\">{$linkedin}</td>\n";
            echo "    <td class=\"box-identitas-label\">Media Sosial Lainnya</td>\n";
            echo "    <td colspan=\"5\" class=\"box-identitas-val\" style=\"mso-number-format:'\@';\">{$medsos}</td>\n";
            echo "  </tr>\n";
            echo "  <tr>\n";
            echo "    <td class=\"box-identitas-label\">Bidang Keahlian (Expert)</td>\n";
            echo "    <td class=\"box-identitas-val\" style=\"mso-number-format:'\@';\">{$expert}</td>\n";
            echo "    <td class=\"box-identitas-label\">Minat / Peminatan</td>\n";
            echo "    <td colspan=\"5\" class=\"box-identitas-val\" style=\"mso-number-format:'\@';\">{$minat}</td>\n";
            echo "  </tr>\n";
            echo "  <tr>\n";
            echo "    <td class=\"box-identitas-label\">Alamat Domisili Lengkap</td>\n";
            echo "    <td colspan=\"7\" class=\"box-identitas-val\" style=\"mso-number-format:'\@';\">{$alamatDomisiliLengkap}</td>\n";
            echo "  </tr>\n";

            // -------------------------------------------------------------
            // SUB-BAGIAN 2: DATA AKADEMIK & KELULUSAN
            // -------------------------------------------------------------
            echo "  <tr><td colspan=\"8\" class=\"box-identitas-subheader\">2. Data Akademik & Kelulusan</td></tr>\n";
            echo "  <tr>\n";
            echo "    <td class=\"box-identitas-label\">Program Studi</td>\n";
            echo "    <td class=\"box-identitas-val\" style=\"mso-number-format:'\@';\">{$prodiNama}</td>\n";
            echo "    <td class=\"box-identitas-label\">Fakultas</td>\n";
            echo "    <td colspan=\"5\" class=\"box-identitas-val\" style=\"mso-number-format:'\@';\">{$fakultasNama}</td>\n";
            echo "  </tr>\n";
            echo "  <tr>\n";
            echo "    <td class=\"box-identitas-label\">Angkatan Masuk</td>\n";
            echo "    <td class=\"box-identitas-val\" style=\"mso-number-format:'\@';\">{$angkatanMasuk}</td>\n";
            echo "    <td class=\"box-identitas-label\">Status Mahasiswa</td>\n";
            echo "    <td colspan=\"5\" class=\"box-identitas-val\" style=\"mso-number-format:'\@';\">{$statusMahasiswa}</td>\n";
            echo "  </tr>\n";
            echo "  <tr>\n";
            echo "    <td class=\"box-identitas-label\">Tahun Lulus</td>\n";
            echo "    <td class=\"box-identitas-val\" style=\"mso-number-format:'\@';\">{$tahunLulus}</td>\n";
            echo "    <td class=\"box-identitas-label\">Semester Kelulusan</td>\n";
            echo "    <td colspan=\"5\" class=\"box-identitas-val\" style=\"mso-number-format:'\@';\">{$semesterLulus}</td>\n";
            echo "  </tr>\n";
            echo "  <tr>\n";
            echo "    <td class=\"box-identitas-label\">IPK Kumulatif</td>\n";
            echo "    <td class=\"box-identitas-val\" style=\"mso-number-format:'\@';\">{$ipk}</td>\n";
            echo "    <td class=\"box-identitas-label\">Total SKS & Angka Kualitas</td>\n";
            echo "    <td colspan=\"5\" class=\"box-identitas-val\" style=\"mso-number-format:'\@';\">SKS: {$totalSks} | Angka Kualitas: {$totalAngkaKualitas}</td>\n";
            echo "  </tr>\n";
            echo "  <tr>\n";
            echo "    <td class=\"box-identitas-label\">Asal Sekolah (SMA/SMK)</td>\n";
            echo "    <td class=\"box-identitas-val\" style=\"mso-number-format:'\@';\">{$asalSekolah}</td>\n";
            echo "    <td class=\"box-identitas-label\">Jurusan Asal Sekolah</td>\n";
            echo "    <td colspan=\"5\" class=\"box-identitas-val\" style=\"mso-number-format:'\@';\">{$jurusanSekolah}</td>\n";
            echo "  </tr>\n";
            echo "  <tr>\n";
            echo "    <td class=\"box-identitas-label\">Alamat Asal Sekolah</td>\n";
            echo "    <td colspan=\"7\" class=\"box-identitas-val\" style=\"mso-number-format:'\@';\">{$alamatSekolah} ({$wilayahSekolah})</td>\n";
            echo "  </tr>\n";

            // -------------------------------------------------------------
            // SUB-BAGIAN 3: DATA ORANG TUA / WALI
            // -------------------------------------------------------------
            echo "  <tr><td colspan=\"8\" class=\"box-identitas-subheader\">3. Data Orang Tua / Wali</td></tr>\n";
            echo "  <tr>\n";
            echo "    <td class=\"box-identitas-label\">Nama Lengkap Orang Tua / Wali</td>\n";
            echo "    <td class=\"box-identitas-val\" style=\"mso-number-format:'\@';\">{$namaOrtu}</td>\n";
            echo "    <td class=\"box-identitas-label\">Pekerjaan Orang Tua / Wali</td>\n";
            echo "    <td colspan=\"5\" class=\"box-identitas-val\" style=\"mso-number-format:'\@';\">{$pekerjaanOrtu}</td>\n";
            echo "  </tr>\n";
            echo "  <tr>\n";
            echo "    <td class=\"box-identitas-label\">Nomor Telepon / WA Orang Tua</td>\n";
            echo "    <td class=\"box-identitas-val\" style=\"mso-number-format:'\@';\">{$teleponOrtu}</td>\n";
            echo "    <td class=\"box-identitas-label\">Kota Asal Orang Tua</td>\n";
            echo "    <td colspan=\"5\" class=\"box-identitas-val\" style=\"mso-number-format:'\@';\">{$kotaOrtu}</td>\n";
            echo "  </tr>\n";
            echo "  <tr>\n";
            echo "    <td class=\"box-identitas-label\">Alamat Lengkap Orang Tua</td>\n";
            echo "    <td colspan=\"7\" class=\"box-identitas-val\" style=\"mso-number-format:'\@';\">{$alamatOrtu} (Kode Pos: {$kodePosOrtu})</td>\n";
            echo "  </tr>\n";

            // -------------------------------------------------------------
            // SUB-BAGIAN 4: DATA KARIER, PERUSAHAAN & ATASAN
            // -------------------------------------------------------------
            echo "  <tr><td colspan=\"8\" class=\"box-identitas-subheader\">4. Data Karier, Perusahaan & Atasan</td></tr>\n";
            echo "  <tr>\n";
            echo "    <td class=\"box-identitas-label\">Status / Kategori Pekerjaan</td>\n";
            echo "    <td class=\"box-identitas-val\" style=\"mso-number-format:'\@';\">{$kategoriPekerjaan}</td>\n";
            echo "    <td class=\"box-identitas-label\">Posisi / Jabatan Pekerjaan</td>\n";
            echo "    <td colspan=\"5\" class=\"box-identitas-val\" style=\"mso-number-format:'\@';\">{$posisiJabatan}</td>\n";
            echo "  </tr>\n";
            echo "  <tr>\n";
            echo "    <td class=\"box-identitas-label\">Posisi Usaha / Wiraswasta</td>\n";
            echo "    <td class=\"box-identitas-val\" style=\"mso-number-format:'\@';\">{$posisiWiraswasta}</td>\n";
            echo "    <td class=\"box-identitas-label\">Estimasi Gaji (Take Home Pay)</td>\n";
            echo "    <td colspan=\"5\" class=\"box-identitas-val\" style=\"mso-number-format:'\@';\">{$gaji}</td>\n";
            echo "  </tr>\n";
            echo "  <tr>\n";
            echo "    <td class=\"box-identitas-label\">Jenis / Bidang Pekerjaan</td>\n";
            echo "    <td class=\"box-identitas-val\" style=\"mso-number-format:'\@';\">{$jenisPekerjaan}</td>\n";
            echo "    <td class=\"box-identitas-label\">Nama Perusahaan / Kantor</td>\n";
            echo "    <td colspan=\"5\" class=\"box-identitas-val\" style=\"mso-number-format:'\@';\">{$namaPerusahaan}</td>\n";
            echo "  </tr>\n";
            echo "  <tr>\n";
            echo "    <td class=\"box-identitas-label\">Sektor Perusahaan</td>\n";
            echo "    <td class=\"box-identitas-val\" style=\"mso-number-format:'\@';\">{$sektorPerusahaan}</td>\n";
            echo "    <td class=\"box-identitas-label\">Skala Instansi / Perusahaan</td>\n";
            echo "    <td colspan=\"5\" class=\"box-identitas-val\" style=\"mso-number-format:'\@';\">{$skalaPerusahaan}</td>\n";
            echo "  </tr>\n";
            echo "  <tr>\n";
            echo "    <td class=\"box-identitas-label\">Jenis Usaha Perusahaan</td>\n";
            echo "    <td class=\"box-identitas-val\" style=\"mso-number-format:'\@';\">{$jenisPerusahaan}</td>\n";
            echo "    <td class=\"box-identitas-label\">Jenis Lokasi & Negara</td>\n";
            echo "    <td colspan=\"5\" class=\"box-identitas-val\" style=\"mso-number-format:'\@';\">{$lokasiPerusahaan} ({$negaraPerusahaan})</td>\n";
            echo "  </tr>\n";
            echo "  <tr>\n";
            echo "    <td class=\"box-identitas-label\">Alamat Lengkap Perusahaan</td>\n";
            echo "    <td colspan=\"7\" class=\"box-identitas-val\" style=\"mso-number-format:'\@';\">{$alamatPerusahaan} (Kode Pos: {$kodePosPerusahaan})</td>\n";
            echo "  </tr>\n";
            echo "  <tr>\n";
            echo "    <td class=\"box-identitas-label\">Nama Atasan Langsung</td>\n";
            echo "    <td class=\"box-identitas-val\" style=\"mso-number-format:'\@';\">{$namaAtasan}</td>\n";
            echo "    <td class=\"box-identitas-label\">Kontak Atasan Langsung</td>\n";
            echo "    <td colspan=\"5\" class=\"box-identitas-val\" style=\"mso-number-format:'\@';\">Telp: {$teleponAtasan} | Email: {$emailAtasan}</td>\n";
            echo "  </tr>\n";
            echo "  <tr>\n";
            echo "    <td class=\"box-identitas-label\">Pendidikan Lanjut (Studi S2/S3)</td>\n";
            echo "    <td colspan=\"7\" class=\"box-identitas-val\" style=\"mso-number-format:'\@';\">Tingkat: {$tingkatPendidikanLanjut} | Kampus: {$kampusLanjut} | Program Studi: {$prodiLanjut}</td>\n";
            echo "  </tr>\n";
            echo "  <tr><td colspan=\"8\" style=\"border:none; height: 12px;\"></td></tr>\n";

            // 3. Header Tabel Kuesioner
            echo "  <thead>\n";
            echo "    <tr class=\"table-header\">\n";
            echo "      <th style=\"width: 4%;\">No</th>\n";
            echo "      <th style=\"width: 18%;\">Bagian / Seksi</th>\n";
            echo "      <th style=\"width: 10%;\">Kode</th>\n";
            echo "      <th style=\"width: 34%;\">Pertanyaan Instrumen</th>\n";
            echo "      <th style=\"width: 8%;\">Tipe Input</th>\n";
            echo "      <th style=\"width: 6%;\">Sifat</th>\n";
            echo "      <th style=\"width: 6%;\">Status</th>\n";
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
                        $statusClass = 'status-x';
                        $statusText = 'x';

                        if ($isHeader) {
                            $answerText = '';
                            $statusClass = 'status-header';
                            $statusText = '-';
                        } elseif ($resp) {
                            $plainAnswer = ! empty($resp->answer_text) && trim((string) $resp->answer_text) !== ''
                                ? (string) $resp->answer_text
                                : (! empty($resp->answer) && trim((string) $resp->answer) !== '' ? (string) $resp->answer : null);

                            if ($plainAnswer !== null) {
                                $answerText = htmlspecialchars($plainAnswer);
                                $statusClass = 'status-v';
                                $statusText = 'v';
                            } elseif (is_array($resp->answer_json) && count($resp->answer_json) > 0) {
                                $jsonFormatted = [];
                                foreach ($resp->answer_json as $k => $v) {
                                    $jsonFormatted[] = is_numeric($k) ? (string) $v : "{$k}: {$v}";
                                }
                                $answerText = htmlspecialchars(implode(', ', $jsonFormatted));
                                $statusClass = 'status-v';
                                $statusText = 'v';
                            }
                        }

                        $bgClass = ($no % 2 === 0) ? ' class="bg-alt"' : '';
                        $subPertanyaanText = htmlspecialchars($sub->subpertanyaan);
                        $tipeText = htmlspecialchars($sub->type ?: 'text');
                        $kodePertanyaan = htmlspecialchars($sub->kode_pertanyaan);
                        $sifatHtml = $isHeader ? '-' : ($isMandatory ? '<span class=\"badge-wajib\">Wajib</span>' : '<span class=\"badge-opsional\">Opsional</span>');

                        echo "    <tr{$bgClass}>\n";
                        echo "      <td class=\"text-center\" style=\"mso-number-format:'\@';\">{$no}</td>\n";
                        echo "      <td style=\"mso-number-format:'\@';\">{$sectionTitle}</td>\n";
                        echo "      <td class=\"text-code\" style=\"mso-number-format:'\@';\">{$kodePertanyaan}</td>\n";
                        echo "      <td style=\"mso-number-format:'\@';\">{$subPertanyaanText}</td>\n";
                        echo "      <td class=\"text-center\" style=\"mso-number-format:'\@';\">{$tipeText}</td>\n";
                        echo "      <td class=\"text-center\" style=\"mso-number-format:'\@';\">{$sifatHtml}</td>\n";
                        echo "      <td class=\"{$statusClass}\" style=\"mso-number-format:'\@';\">{$statusText}</td>\n";
                        echo "      <td style=\"font-weight: 500; mso-number-format:'\@';\">{$answerText}</td>\n";
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
                        $statusClass = 'status-x';
                        $statusText = 'x';

                        if ($isHeader) {
                            $answerText = '';
                            $statusClass = 'status-header';
                            $statusText = '-';
                        } elseif ($resp) {
                            if (! empty($resp->answer_text) && trim((string) $resp->answer_text) !== '') {
                                $answerText = htmlspecialchars((string) $resp->answer_text);
                                $statusClass = 'status-v';
                                $statusText = 'v';
                            } elseif (is_array($resp->answer_json) && count($resp->answer_json) > 0) {
                                $jsonFormatted = [];
                                foreach ($resp->answer_json as $k => $v) {
                                    $jsonFormatted[] = is_numeric($k) ? (string) $v : "{$k}: {$v}";
                                }
                                $answerText = htmlspecialchars(implode(', ', $jsonFormatted));
                                $statusClass = 'status-v';
                                $statusText = 'v';
                            }
                        }

                        $bgClass = ($no % 2 === 0) ? ' class="bg-alt"' : '';
                        $questionText = htmlspecialchars($pQ->question_text);
                        $tipeText = htmlspecialchars($pQ->type ?: 'text');
                        $codeText = htmlspecialchars($pQ->code);
                        $sifatHtml = $isHeader ? '-' : ($isMandatory ? '<span class=\"badge-wajib\">Wajib</span>' : '<span class=\"badge-opsional\">Opsional</span>');

                        echo "    <tr{$bgClass}>\n";
                        echo "      <td class=\"text-center\" style=\"mso-number-format:'\@';\">{$no}</td>\n";
                        echo "      <td style=\"mso-number-format:'\@';\">{$pTitle}</td>\n";
                        echo "      <td class=\"text-code\" style=\"mso-number-format:'\@';\">{$codeText}</td>\n";
                        echo "      <td style=\"mso-number-format:'\@';\">{$questionText}</td>\n";
                        echo "      <td class=\"text-center\" style=\"mso-number-format:'\@';\">{$tipeText}</td>\n";
                        echo "      <td class=\"text-center\" style=\"mso-number-format:'\@';\">{$sifatHtml}</td>\n";
                        echo "      <td class=\"{$statusClass}\" style=\"mso-number-format:'\@';\">{$statusText}</td>\n";
                        echo "      <td style=\"font-weight: 500; mso-number-format:'\@';\">{$answerText}</td>\n";
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
            'Expires' => '0',
        ]);
    }
}
