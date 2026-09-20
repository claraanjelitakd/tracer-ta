<?php

namespace Database\Seeders;

use App\Models\DataAkademik;
use App\Models\DataOrangTua;
use App\Models\Kabupaten;
use Illuminate\Database\Seeder;

/**
 * Seeder DataOrangTua
 *
 * Mengisi data kontak dan identitas orang tua mahasiswa/alumni.
 */
class DataOrangTuaSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $akademiks = DataAkademik::where('nim', '!=', '72220001')->get();

        $pekerjaanList = [
            'Pegawai Negeri Sipil (PNS)',
            'Wiraswasta',
            'Karyawan BUMN',
            'Dokter / Tenaga Medis',
            'Dosen / Pendidik',
            'Manajer Perusahaan Swasta',
            'Wirausaha Kuliner',
            'Arsitek Profesional',
            'Konsultan Pajak & Keuangan',
            'Purnawirawan / Pensiunan',
        ];

        foreach ($akademiks as $index => $akademik) {
            $namaBelakang = explode(' ', $akademik->nama);
            $family = end($namaBelakang);

            DataOrangTua::updateOrCreate(
                ['nim' => $akademik->nim],
                [
                    'nama_orang_tua' => 'Ir. Hendra '.$family.', M.M.',
                    'pekerjaan' => $pekerjaanList[$index % count($pekerjaanList)],
                    'alamat' => $akademik->alamat_saat_ini,
                    'kota' => $akademik->kabupaten_id ? (Kabupaten::find($akademik->kabupaten_id)?->nama_kabupaten ?? 'Sleman') : 'Sleman',
                    'kabupaten_id' => $akademik->kabupaten_id,
                    'propinsi_id' => $akademik->propinsi_id,
                    'kode_pos' => $akademik->kode_pos ?? '55281',
                    'nomor_telepon' => '0813'.rand(1000, 9999).str_pad((string) $index, 4, '0', STR_PAD_LEFT),
                ]
            );
        }
    }
}
