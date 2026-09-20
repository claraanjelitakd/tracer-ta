<?php

namespace App\Services\Alumni;

use App\Models\Atasan;
use App\Models\Biodata;
use App\Models\DataOrangTua;
use App\Models\Perusahaan;
use App\Models\RefSubpertanyaanDetil;
use App\Models\Yudisium;
use App\Services\Kuesioner\KuesionerSyncService;

/**
 * AdminAlumniProfileService
 *
 * Layanan terpadu (Single Source of Truth) untuk:
 * 1. Menyusun array formData lengkap untuk dikirim ke frontend Vue (buildFormData).
 * 2. Menyimpan seluruh entitas profil alumni secara atomik (updateProfile).
 *
 * Digunakan oleh seluruh role administrator:
 * - Super Admin: DetailAlumniSuperAdminController
 * - Biro 3   : DetailAlumniController (AdminBiroTiga)
 * - Fakultas  : DetailAlumniFakultasController
 * - Prodi     : DaftarAlumniProdiController
 *
 * Alasan sentralisasi:
 * Sebelumnya logika ini tersebar di 4 controller yang berbeda-beda,
 * menyebabkan data profil tidak tersimpan konsisten, gaji berlipat ganda,
 * dan bug eksklusivitas posisi jabatan (F5C terisi walau alumni bukan wiraswasta).
 */
class AdminAlumniProfileService
{
    /**
     * Susun array formData lengkap untuk dikirim ke frontend Vue.
     *
     * Mendukung multi-level fallback untuk setiap field:
     *   biodata → dataAkademik → yudisium → perusahaan → atasan → orangTua
     *
     * Array yang dihasilkan digunakan sebagai prop `formData` di:
     * - SuperAdmin/Alumni/Show.vue
     * - AdminBiroTiga/AlumniShow.vue
     * - AdminFakultas/Alumni/Show.vue
     * - AdminProdi/Alumni/Show.vue
     *
     * @param  Biodata  $alumni  Model biodata dengan relasi di-eager load.
     * @return array<string, mixed> Array flat key-value siap dikirim ke Inertia.
     */
    public static function buildFormData(Biodata $alumni): array
    {
        $dataAkademik = $alumni->dataAkademik;
        $orangTua = $alumni->orangTua ?? $dataAkademik?->orangTua;
        $yudisium = $alumni->yudisium ?? $dataAkademik?->yudisium;
        $atasan = $alumni->atasan;
        $perusahaan = $alumni->perusahaan;

        return [
            // 1. Identitas Pribadi
            'nim' => $alumni->nim ?? ($dataAkademik?->nim ?? ''),
            'nama' => $alumni->nama ?? ($dataAkademik?->nama ?? ''),
            'tempat_lahir' => $alumni->tempat_lahir ?? ($dataAkademik?->tempat_lahir ?? ''),
            'tanggal_lahir' => $alumni->tanggal_lahir ?? ($dataAkademik?->tanggal_lahir ?? ''),
            'agama' => $alumni->agama ?? ($dataAkademik?->agama ?? ''),
            'jenis_kelamin' => $alumni->jenis_kelamin ?? ($dataAkademik?->jenis_kelamin ?? ''),
            'golongan_darah' => $alumni->golongan_darah ?? ($dataAkademik?->golongan_darah ?? ''),
            'warga_negara' => $alumni->warga_negara ?? ($dataAkademik?->warga_negara ?? 'WNI'),
            'nik' => $alumni->nik ?? ($dataAkademik?->nik ?? ''),
            'no_kk' => $alumni->no_kk ?? ($dataAkademik?->no_kk ?? ''),
            'nisn' => $alumni->nisn ?? ($dataAkademik?->nisn ?? ''),
            'no_bpjs' => $alumni->no_bpjs ?? ($dataAkademik?->no_bpjs ?? ''),
            'npwp' => $alumni->npwp ?? '',

            // Kontak & Alamat Pribadi
            'alamat_saat_ini' => $alumni->alamat ?? ($dataAkademik?->alamat_saat_ini ?? ''),
            'alamat' => $alumni->alamat ?? ($dataAkademik?->alamat_saat_ini ?? ''),
            'kelurahan' => $alumni->kelurahan ?? ($dataAkademik?->kelurahan ?? ''),
            'kecamatan' => $alumni->kecamatan ?? ($dataAkademik?->kecamatan ?? ''),
            'kabupaten_id' => $alumni->kabupaten_id ?? ($dataAkademik?->kabupaten_id ?? ''),
            'propinsi_id' => $alumni->propinsi_id ?? ($dataAkademik?->propinsi_id ?? ''),
            'provinsi_id' => $alumni->propinsi_id ?? ($dataAkademik?->propinsi_id ?? ''),
            'kode_pos' => $alumni->kode_pos ?? ($dataAkademik?->kode_pos ?? ''),
            'nomor_telepon' => $alumni->nomor_telepon ?? ($dataAkademik?->nomor_telepon ?? ''),
            'email_pribadi' => $alumni->email_pribadi ?? ($alumni->email ?? ($dataAkademik?->email_pribadi ?? '')),
            'email' => $alumni->email ?? ($dataAkademik?->email_students ?? ''),
            'email_students' => $dataAkademik?->email_students ?? '',

            // 2. Data Akademik
            'prodi_id' => $alumni->prodi_id ?? ($dataAkademik?->prodi_id ?? ''),
            'kode_prodi' => $alumni->prodi?->kode_prodi ?? ($dataAkademik?->program_studi ?? ''),
            'program_studi' => $alumni->prodi?->nama_prodi ?? ($dataAkademik?->program_studi ?? ''),
            'fakultas' => $alumni->prodi?->fakultas?->nama_fakultas ?? ($dataAkademik?->fakultas ?? ''),
            'angkatan_masuk' => $dataAkademik?->angkatan_masuk ?? '',
            'status_mahasiswa' => $dataAkademik?->status_mahasiswa ?? 'AR',
            'tahun_akademik_lulus' => $yudisium?->tahun_akademik_lulus ?? ($dataAkademik?->tahun_akademik_lulus ?? ''),
            'tahun_lulus' => $alumni->tahun_lulus ?? ($yudisium?->tahun_lulus ?? ($dataAkademik?->tahun_lulus ?? '')),
            'ipk' => $dataAkademik?->ip_kumulatif ?? '',
            'ip_kumulatif' => $dataAkademik?->ip_kumulatif ?? '',
            'strata' => $dataAkademik?->strata ?? 'S1',
            'no_ijazah' => $dataAkademik?->no_ijazah ?? '',
            'total_sks' => $dataAkademik?->total_sks ?? '',
            'total_angka_kualitas' => $dataAkademik?->total_angka_kualitas ?? '',
            'asal_sekolah' => $dataAkademik?->asal_sekolah ?? '',

            // Yudisium
            'judul_ta' => $yudisium?->judul_ta ?? '',
            'judul_ta_inggris' => $yudisium?->judul_ta_inggris ?? '',
            'dosen_pembimbing_1' => $yudisium?->dosen_pembimbing_1 ?? '',
            'dosen_pembimbing_2' => $yudisium?->dosen_pembimbing_2 ?? '',
            'dosen_penguji_1' => $yudisium?->dosen_penguji_1 ?? '',
            'dosen_penguji_2' => $yudisium?->dosen_penguji_2 ?? '',
            'url_publikasi' => $yudisium?->url_publikasi ?? '',
            'jenis_publikasi' => $yudisium?->jenis_publikasi ?? '',
            'status_publikasi' => $yudisium?->status_publikasi ?? '',
            'status_yudisium' => $yudisium?->status_yudisium ?? ($yudisium?->proses_yudisium ?? 'Lulus'),
            'keterangan_hasil_yudisium' => $yudisium?->keterangan_hasil_yudisium ?? '',
            'proses_yudisium' => $yudisium?->proses_yudisium ?? '',

            // 3. Data Orang Tua
            'nama_orang_tua' => $orangTua?->nama_orang_tua ?? '',
            'pekerjaan_orang_tua' => $orangTua?->pekerjaan ?? '',
            'alamat_orang_tua' => $orangTua?->alamat ?? '',
            'kota_orang_tua' => $orangTua?->kota ?? '',
            'kabupaten_id_orang_tua' => $orangTua?->kabupaten_id ?? '',
            'propinsi_id_orang_tua' => $orangTua?->propinsi_id ?? '',
            'provinsi_id_orang_tua' => $orangTua?->propinsi_id ?? '',
            'kode_pos_orang_tua' => $orangTua?->kode_pos ?? '',
            'nomor_telepon_orang_tua' => $orangTua?->nomor_telepon ?? '',

            // 4. Karier, Media Sosial, Keahlian & Penghasilan
            'instagram_url' => $alumni->instagram_url ?? '',
            'facebook_url' => $alumni->facebook_url ?? '',
            'linkedin_url' => $alumni->linkedin_url ?? '',
            'linkedin_username' => $alumni->linkedin_username ?? '',
            'expert' => $alumni->expert ?? '',
            'minat' => $alumni->minat ?? '',
            'kategori_pekerjaan' => $alumni->kategori_pekerjaan ?? '',
            'posisi_jabatan' => $alumni->posisi_jabatan ?? '',
            'posisi_wiraswasta' => $alumni->posisi_wiraswasta ?? '',
            'pendidikan_tingkat' => $alumni->pendidikan_tingkat ?? '',
            'perguruan_tinggi' => $alumni->perguruan_tinggi ?? '',
            'pendidikan_prodi' => $alumni->pendidikan_prodi ?? '',
            'gaji' => $alumni->gaji !== null ? (int) $alumni->gaji : '',
            'jenis_pekerjaan' => $alumni->jenis_pekerjaan ?? '',
            'zipcode' => $alumni->zipcode ?? '',

            // Perusahaan
            'nama_perusahaan' => $perusahaan?->nama_perusahaan ?? '',
            'perusahaan_alamat' => $perusahaan?->alamat ?? '',
            'perusahaan_skala' => $perusahaan?->skala ?? '',
            'perusahaan_jenis_perusahaan' => $perusahaan?->jenis_perusahaan ?? '',
            'perusahaan_jenis_perusahaan_lainnya' => $perusahaan?->jenis_perusahaan_lainnya ?? '',
            'perusahaan_jenis_lokasi' => $perusahaan?->jenis_lokasi ?? 'Dalam Negeri',
            'perusahaan_negara' => $perusahaan?->negara ?? 'Indonesia',
            'perusahaan_propinsi_id' => $perusahaan?->propinsi_id ?? '',
            'perusahaan_kabupaten_id' => $perusahaan?->kabupaten_id ?? '',
            'perusahaan_status_verifikasi' => $perusahaan?->status_verifikasi ?? '',

            // Alias Company untuk kompatibilitas frontend
            'company_alamat' => $perusahaan?->alamat ?? '',
            'company_skala' => $perusahaan?->skala ?? '',
            'company_jenis_perusahaan' => $perusahaan?->jenis_perusahaan ?? '',
            'company_jenis_perusahaan_lainnya' => $perusahaan?->jenis_perusahaan_lainnya ?? '',
            'company_jenis_lokasi' => $perusahaan?->jenis_lokasi ?? 'Dalam Negeri',
            'company_negara' => $perusahaan?->negara ?? 'Indonesia',
            'company_province_id' => $perusahaan?->propinsi_id ?? '',
            'company_kabupaten_id' => $perusahaan?->kabupaten_id ?? '',
            'company_status_verifikasi' => $perusahaan?->status_verifikasi ?? '',

            // Atasan
            'nama_atasan' => $atasan?->nama ?? '',
            'email_atasan' => $atasan?->email ?? '',
            'telepon_atasan' => $atasan?->telepon ?? '',
        ];
    }

    /**
     * Ambil opsi referensi kuesioner resmi dari database.
     *
     * Mengambil daftar pilihan dari `ref_subpertanyaan_detil` untuk kode:
     * - F2G  : Posisi jabatan struktural (Pekerja)
     * - F2H  : Jenis pekerjaan / bidang usaha
     * - F5D  : Skala instansi/perusahaan
     * - F11  : Sumber biaya kuliah
     * - F8   : Status/kategori pekerjaan saat ini
     *
     * Hasil dikembalikan sebagai array ter-group by kode pertanyaan (uppercase)
     * untuk kemudahan akses di frontend Vue (misal: `refOptions['F2G']`).
     *
     * @return array<string, array<int, array<string, mixed>>>
     */
    public static function getRefOptions(): array
    {
        return RefSubpertanyaanDetil::whereIn('kode_pertanyaan', ['F2G', 'F2H', 'F5D', 'F11', 'F8', 'f2g', 'f2h', 'f5d', 'f11', 'f8'])
            ->orderBy('order')
            ->get(['id', 'kode_pertanyaan', 'kode_opsi', 'option_text', 'order'])
            ->groupBy(fn ($item) => strtoupper($item->kode_pertanyaan))
            ->toArray();
    }

    /**
     * Simpan seluruh pembaruan profil alumni oleh Admin secara atomik.
     *
     * Proses terdiri dari 7 langkah berurutan:
     * 1. DataOrangTua  — updateOrCreate by NIM
     * 2. Yudisium      — updateOrCreate by NIM
     * 3. Perusahaan    — firstOrCreate by nama_perusahaan, lalu update atribut
     * 4. Atasan        — update jika sudah ada (by atasan_id), atau create baru
     * 5. Sanitasi Gaji — strip separator ribuan + desimal, round ke integer
     * 6. Biodata       — update() dengan eksklusivitas posisi by kategori_pekerjaan
     * 7. Sinkronisasi  — KuesionerSyncService::syncProfileResponses()
     *
     * Aturan eksklusivitas posisi (mencegah bug F5C terisi saat bukan wiraswasta):
     * - kategori_pekerjaan = 'Pekerja'              → posisi_wiraswasta = null
     * - kategori_pekerjaan = 'Wiraswasta'           → posisi_jabatan = null
     * - kategori_pekerjaan = 'Melanjutkan Pendidikan' → keduanya null
     *
     * @param  Biodata  $biodata  Model biodata yang akan diperbarui.
     * @param  array<string, mixed>  $data  Data request dari form frontend.
     */
    public static function updateProfile(Biodata $biodata, array $data): void
    {
        // 1. Tangani Data Orang Tua
        $orangTuaData = [
            'nama_orang_tua' => ! empty($data['nama_orang_tua']) ? $data['nama_orang_tua'] : null,
            'pekerjaan' => ! empty($data['pekerjaan_orang_tua']) ? $data['pekerjaan_orang_tua'] : null,
            'alamat' => ! empty($data['alamat_orang_tua']) ? $data['alamat_orang_tua'] : null,
            'kota' => ! empty($data['kota_orang_tua']) ? $data['kota_orang_tua'] : null,
            'kabupaten_id' => ! empty($data['kabupaten_id_orang_tua']) ? $data['kabupaten_id_orang_tua'] : null,
            'propinsi_id' => ! empty($data['propinsi_id_orang_tua']) ? $data['propinsi_id_orang_tua'] : (! empty($data['provinsi_id_orang_tua']) ? $data['provinsi_id_orang_tua'] : null),
            'kode_pos' => ! empty($data['kode_pos_orang_tua']) ? $data['kode_pos_orang_tua'] : null,
            'nomor_telepon' => ! empty($data['nomor_telepon_orang_tua']) ? $data['nomor_telepon_orang_tua'] : null,
        ];
        $orangTua = DataOrangTua::updateOrCreate(['nim' => $biodata->nim], $orangTuaData);

        // 2. Tangani Data Yudisium
        $yudisiumData = [
            'judul_ta' => ! empty($data['judul_ta']) ? $data['judul_ta'] : null,
            'judul_ta_inggris' => ! empty($data['judul_ta_inggris']) ? $data['judul_ta_inggris'] : null,
            'dosen_pembimbing_1' => ! empty($data['dosen_pembimbing_1']) ? $data['dosen_pembimbing_1'] : null,
            'dosen_pembimbing_2' => ! empty($data['dosen_pembimbing_2']) ? $data['dosen_pembimbing_2'] : null,
            'dosen_penguji_1' => ! empty($data['dosen_penguji_1']) ? $data['dosen_penguji_1'] : null,
            'dosen_penguji_2' => ! empty($data['dosen_penguji_2']) ? $data['dosen_penguji_2'] : null,
            'url_publikasi' => ! empty($data['url_publikasi']) ? $data['url_publikasi'] : null,
            'jenis_publikasi' => ! empty($data['jenis_publikasi']) ? $data['jenis_publikasi'] : null,
            'status_publikasi' => ! empty($data['status_publikasi']) ? $data['status_publikasi'] : null,
            'tahun_lulus' => ! empty($data['tahun_lulus']) ? $data['tahun_lulus'] : null,
            'tahun_akademik_lulus' => ! empty($data['tahun_akademik_lulus']) ? $data['tahun_akademik_lulus'] : null,
            'status_yudisium' => ! empty($data['status_yudisium']) ? $data['status_yudisium'] : 'Lulus',
            'proses_yudisium' => ! empty($data['proses_yudisium']) ? $data['proses_yudisium'] : 'Lulus',
            'keterangan_hasil_yudisium' => ! empty($data['keterangan_hasil_yudisium']) ? $data['keterangan_hasil_yudisium'] : null,
        ];
        $yudisium = Yudisium::updateOrCreate(['nim' => $biodata->nim], $yudisiumData);

        // 3. Tangani Data Perusahaan
        $perusahaanId = $biodata->perusahaan_id;
        $namaPerusahaan = ! empty($data['nama_perusahaan']) ? trim($data['nama_perusahaan']) : null;
        if ($namaPerusahaan) {
            $jenisLokasi = ! empty($data['company_jenis_lokasi']) ? $data['company_jenis_lokasi'] : (! empty($data['perusahaan_jenis_lokasi']) ? $data['perusahaan_jenis_lokasi'] : 'Dalam Negeri');
            $negara = ($jenisLokasi === 'Luar Negeri') ? (! empty($data['company_negara']) ? $data['company_negara'] : (! empty($data['perusahaan_negara']) ? $data['perusahaan_negara'] : '')) : 'Indonesia';
            $provId = ($jenisLokasi === 'Luar Negeri') ? null : (! empty($data['company_province_id']) ? $data['company_province_id'] : (! empty($data['perusahaan_propinsi_id']) ? $data['perusahaan_propinsi_id'] : null));
            $kabId = ($jenisLokasi === 'Luar Negeri') ? null : (! empty($data['company_kabupaten_id']) ? $data['company_kabupaten_id'] : (! empty($data['perusahaan_kabupaten_id']) ? $data['perusahaan_kabupaten_id'] : null));
            $alamat = ! empty($data['company_alamat']) ? $data['company_alamat'] : (! empty($data['perusahaan_alamat']) ? $data['perusahaan_alamat'] : null);
            $skala = ! empty($data['company_skala']) ? $data['company_skala'] : (! empty($data['perusahaan_skala']) ? $data['perusahaan_skala'] : null);
            $jenisPerusahaan = ! empty($data['company_jenis_perusahaan']) ? $data['company_jenis_perusahaan'] : (! empty($data['perusahaan_jenis_perusahaan']) ? $data['perusahaan_jenis_perusahaan'] : null);
            $jenisPerusahaanLainnya = ! empty($data['company_jenis_perusahaan_lainnya']) ? $data['company_jenis_perusahaan_lainnya'] : (! empty($data['perusahaan_jenis_perusahaan_lainnya']) ? $data['perusahaan_jenis_perusahaan_lainnya'] : null);

            $perusahaan = Perusahaan::firstOrCreate(
                ['nama_perusahaan' => $namaPerusahaan],
                [
                    'jenis_lokasi' => $jenisLokasi,
                    'negara' => $negara,
                    'propinsi_id' => $provId,
                    'kabupaten_id' => $kabId,
                    'alamat' => $alamat,
                    'skala' => $skala,
                    'jenis_perusahaan' => $jenisPerusahaan,
                    'jenis_perusahaan_lainnya' => $jenisPerusahaanLainnya,
                    'status_verifikasi' => 'Terverifikasi',
                ]
            );

            $perusahaan->update([
                'jenis_lokasi' => $jenisLokasi,
                'negara' => $negara,
                'propinsi_id' => $provId,
                'kabupaten_id' => $kabId,
                'alamat' => $alamat ?: $perusahaan->alamat,
                'skala' => $skala ?: $perusahaan->skala,
                'jenis_perusahaan' => $jenisPerusahaan ?: $perusahaan->jenis_perusahaan,
                'jenis_perusahaan_lainnya' => $jenisPerusahaanLainnya ?: $perusahaan->jenis_perusahaan_lainnya,
            ]);

            $perusahaanId = $perusahaan->id;
        }

        // 4. Tangani Data Atasan & Peran
        $kategoriPekerjaan = ! empty($data['kategori_pekerjaan']) ? $data['kategori_pekerjaan'] : null;
        $posisiJabatan = ! empty($data['posisi_jabatan']) ? $data['posisi_jabatan'] : null;
        $posisiWiraswasta = ! empty($data['posisi_wiraswasta']) ? $data['posisi_wiraswasta'] : null;
        if ($posisiWiraswasta === 'Lainnya' && ! empty($data['posisi_wiraswasta_lainnya'])) {
            $posisiWiraswasta = $data['posisi_wiraswasta_lainnya'];
        }

        if ($kategoriPekerjaan === 'Pekerja') {
            $posisiWiraswasta = null;
        } elseif ($kategoriPekerjaan === 'Wiraswasta') {
            $posisiJabatan = null;
        } elseif ($kategoriPekerjaan === 'Melanjutkan Pendidikan') {
            $posisiJabatan = null;
            $posisiWiraswasta = null;
        }

        $atasanId = $biodata->atasan_id;
        $namaAtasan = ! empty($data['nama_atasan']) ? $data['nama_atasan'] : null;
        $emailAtasan = ! empty($data['email_atasan']) ? $data['email_atasan'] : null;
        $teleponAtasan = ! empty($data['telepon_atasan']) ? $data['telepon_atasan'] : null;

        if ($namaAtasan || $emailAtasan || $teleponAtasan) {
            $atasan = ! empty($biodata->atasan_id) ? Atasan::find($biodata->atasan_id) : null;
            if ($atasan) {
                $atasan->update([
                    'nama' => $namaAtasan ?: $atasan->nama,
                    'email' => $emailAtasan,
                    'telepon' => $teleponAtasan,
                ]);
            } else {
                $atasan = Atasan::create([
                    'nama' => $namaAtasan ?: 'Atasan',
                    'email' => $emailAtasan,
                    'telepon' => $teleponAtasan,
                ]);
            }
            $atasanId = $atasan->id;
        }

        // 5. Sanitasi Gaji / Penghasilan
        // Normalisasi input gaji agar tidak berlipat ganda setiap kali disimpan.
        // Kasus yang ditangani:
        //   - Format desimal bersih (misal "5000000.00") → round ke integer
        //   - Format ribuan dengan separator (misal "5.000.000" atau "5,000,000") →
        //     hapus pola desimal 2 digit di akhir terlebih dahulu, lalu strip semua non-digit
        $gajiNominal = null;
        if (isset($data['gaji']) && $data['gaji'] !== '' && $data['gaji'] !== null) {
            $rawGaji = (string) $data['gaji'];
            if (preg_match('/^\d+(\.\d{1,2})?$/', $rawGaji)) {
                // Angka desimal murni (misal dari DB atau input numerik langsung)
                $gajiInt = (int) round((float) $rawGaji);
            } else {
                // Format ribuan: buang dulu pola "xxx.00" atau "xxx,00" di akhir,
                // kemudian strip semua karakter non-numerik
                $rawGaji = preg_replace('/[.,]\d{2}$/', '', $rawGaji);
                $cleanGaji = preg_replace('/[^0-9]/', '', $rawGaji);
                $gajiInt = (int) $cleanGaji;
            }
            $gajiNominal = $gajiInt > 0 ? $gajiInt : null;
        }

        // 6. Update Model Biodata
        $biodata->update([
            'nama' => ! empty($data['nama']) ? $data['nama'] : $biodata->nama,
            'tempat_lahir' => ! empty($data['tempat_lahir']) ? $data['tempat_lahir'] : null,
            'tanggal_lahir' => ! empty($data['tanggal_lahir']) ? $data['tanggal_lahir'] : null,
            'jenis_kelamin' => ! empty($data['jenis_kelamin']) ? $data['jenis_kelamin'] : null,
            'golongan_darah' => ! empty($data['golongan_darah']) ? $data['golongan_darah'] : null,
            'warga_negara' => ! empty($data['warga_negara']) ? $data['warga_negara'] : ($biodata->warga_negara ?: 'WNI'),
            'nomor_telepon' => ! empty($data['nomor_telepon']) ? $data['nomor_telepon'] : null,
            'email' => ! empty($data['email']) ? $data['email'] : (! empty($data['email_pribadi']) ? $data['email_pribadi'] : null),
            'email_pribadi' => ! empty($data['email_pribadi']) ? $data['email_pribadi'] : null,
            'alamat' => ! empty($data['alamat_saat_ini']) ? $data['alamat_saat_ini'] : (! empty($data['alamat']) ? $data['alamat'] : null),
            'kelurahan' => ! empty($data['kelurahan']) ? $data['kelurahan'] : null,
            'kecamatan' => ! empty($data['kecamatan']) ? $data['kecamatan'] : null,
            'kabupaten_id' => ! empty($data['kabupaten_id']) ? $data['kabupaten_id'] : null,
            'propinsi_id' => ! empty($data['propinsi_id']) ? $data['propinsi_id'] : (! empty($data['provinsi_id']) ? $data['provinsi_id'] : null),
            'kode_pos' => ! empty($data['kode_pos']) ? $data['kode_pos'] : null,
            'agama' => ! empty($data['agama']) ? $data['agama'] : null,
            'nik' => ! empty($biodata->nik) ? $biodata->nik : (! empty($data['nik']) ? $data['nik'] : null),
            'no_kk' => ! empty($data['no_kk']) ? $data['no_kk'] : null,
            'no_bpjs' => ! empty($data['no_bpjs']) ? $data['no_bpjs'] : null,
            'nisn' => ! empty($data['nisn']) ? $data['nisn'] : null,
            'npwp' => ! empty($data['npwp']) ? $data['npwp'] : null,
            'instagram_url' => ! empty($data['instagram_url']) ? $data['instagram_url'] : null,
            'facebook_url' => ! empty($data['facebook_url']) ? $data['facebook_url'] : null,
            'linkedin_url' => ! empty($data['linkedin_url']) ? $data['linkedin_url'] : null,
            'linkedin_username' => ! empty($data['linkedin_username']) ? $data['linkedin_username'] : null,
            'expert' => ! empty($data['expert']) ? $data['expert'] : null,
            'minat' => ! empty($data['minat']) ? $data['minat'] : null,
            'kategori_pekerjaan' => $kategoriPekerjaan,
            'posisi_jabatan' => $posisiJabatan,
            'posisi_wiraswasta' => $posisiWiraswasta,
            'pendidikan_tingkat' => ! empty($data['pendidikan_tingkat']) ? $data['pendidikan_tingkat'] : null,
            'perguruan_tinggi' => ! empty($data['perguruan_tinggi']) ? $data['perguruan_tinggi'] : null,
            'pendidikan_prodi' => ! empty($data['pendidikan_prodi']) ? $data['pendidikan_prodi'] : null,
            'gaji' => $gajiNominal !== null ? $gajiNominal : null,
            'jenis_pekerjaan' => ! empty($data['jenis_pekerjaan']) ? $data['jenis_pekerjaan'] : (! empty($data['company_jenis_perusahaan']) ? $data['company_jenis_perusahaan'] : (! empty($data['perusahaan_jenis_perusahaan']) ? $data['perusahaan_jenis_perusahaan'] : null)),
            'zipcode' => ! empty($data['zipcode']) ? $data['zipcode'] : null,
            'perusahaan_id' => $perusahaanId,
            'atasan_id' => $atasanId,
            'orang_tua_id' => $orangTua->id ?? $biodata->orang_tua_id,
            'yudisium_id' => $yudisium->id ?? $biodata->yudisium_id,
        ]);

        $biodata->refresh();

        // 7. Sinkronisasi Otomatis ke Jawaban Kuesioner Tracer
        KuesionerSyncService::syncProfileResponses($biodata);
    }
}
