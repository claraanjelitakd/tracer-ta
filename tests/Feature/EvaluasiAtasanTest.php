<?php

namespace Tests\Feature;

use App\Models\Biodata;
use App\Models\DataAkademik;
use App\Models\EvaluasiAtasan;
use App\Models\PertanyaanEvaluasiAtasan;
use App\Models\Perusahaan;
use App\Models\Prodi;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Str;
use Tests\TestCase;

class EvaluasiAtasanTest extends TestCase
{
    use RefreshDatabase;

    public function test_public_can_view_evaluasi_atasan_form_with_valid_token(): void
    {
        $prodi = Prodi::create(['kode_prodi' => '72', 'nama_prodi' => 'Sistem Informasi']);
        $user = User::create([
            'username' => '72200001',
            'name' => 'Budi Alumni',
            'email' => 'budi@example.com',
            'password' => bcrypt('password'),
            'role' => 'alumni',
        ]);
        DataAkademik::create(['nim' => '72200001', 'nama' => 'Budi Alumni']);
        $biodata = Biodata::where('nim', '72200001')->first();
        if (! $biodata) {
            $biodata = Biodata::create(['user_id' => $user->id, 'nim' => '72200001', 'nama' => 'Budi Alumni', 'prodi_id' => $prodi->id]);
        } else {
            $biodata->update(['prodi_id' => $prodi->id, 'nama' => 'Budi Alumni']);
        }

        $perusahaan = Perusahaan::create([
            'nama_perusahaan' => 'PT Teknologi Indonesia',
            'alamat' => 'Jl. Jenderal Sudirman No. 10',
            'homepage' => 'https://tekno.co.id',
        ]);

        $evaluasi = EvaluasiAtasan::create([
            'biodata_id' => $biodata->id,
            'perusahaan_id' => $perusahaan->id,
            'token' => Str::random(40),
            'nama_perusahaan' => 'PT Teknologi Indonesia',
            'alamat_lengkap' => 'Jl. Jenderal Sudirman No. 10',
        ]);

        $response = $this->get("/evaluasi-atasan/{$evaluasi->token}");

        $response->assertStatus(200);
        $response->assertInertia(fn ($page) => $page
            ->component('Public/EvaluasiAtasanForm')
            ->where('token', $evaluasi->token)
            ->where('alumniInfo.nama', 'Budi Alumni')
        );
    }

    public function test_invalid_token_returns_not_found_component(): void
    {
        $response = $this->get('/evaluasi-atasan/invalid-token-123456');

        $response->assertStatus(200);
        $response->assertInertia(fn ($page) => $page
            ->component('Public/EvaluasiAtasanNotFound')
        );
    }

    public function test_atasan_can_submit_evaluasi_form(): void
    {
        $prodi = Prodi::create(['kode_prodi' => '72', 'nama_prodi' => 'Sistem Informasi']);
        $user = User::create([
            'username' => '72200002',
            'name' => 'Alumni Test',
            'email' => 'alumni@example.com',
            'password' => bcrypt('password'),
            'role' => 'alumni',
        ]);
        DataAkademik::create(['nim' => '72200002', 'nama' => 'Alumni Test']);
        $biodata = Biodata::where('nim', '72200002')->first();
        if (! $biodata) {
            $biodata = Biodata::create(['user_id' => $user->id, 'nim' => '72200002', 'prodi_id' => $prodi->id]);
        } else {
            $biodata->update(['prodi_id' => $prodi->id]);
        }

        $perusahaan = Perusahaan::create([
            'nama_perusahaan' => 'PT Makmur Sentosa',
            'alamat' => 'Jl. Magelang KM 5, Yogyakarta',
        ]);

        $evaluasi = EvaluasiAtasan::create([
            'biodata_id' => $biodata->id,
            'perusahaan_id' => $perusahaan->id,
            'token' => Str::random(40),
        ]);

        PertanyaanEvaluasiAtasan::create([
            'kode' => 'integritas',
            'aspek' => 'Integritas',
            'order' => 1,
        ]);

        $postData = [
            'nama_perusahaan' => 'PT Makmur Sentosa',
            'alamat_lengkap' => 'Jl. Magelang KM 5, Yogyakarta',
            'no_telp_fax' => '0274-123456',
            'homepage' => 'https://makmursentosa.com',
            'nama_atasan' => 'Bapak Atasan',
            'email_atasan' => 'atasan@makmursentosa.com',
            'telepon_atasan' => '08123456789',
            'bentuk_perusahaan' => 'Perusahaan Terbatas',
            'skala_perusahaan' => 'Nasional',
            'jumlah_pegawai' => '51 - 100 Orang',
            'jumlah_alumni_ukdw' => '< 5 Orang',
            'standar_gaji_pertama' => '3.000.000 – 4.000.000',
            'tingkat_kesiapan_kerja' => 'Sangat siap',
            'jawaban' => [
                'integritas' => 'Sangat Tinggi',
                'keahlian_ilmu' => 'Tinggi',
            ],
            'catatan_lainnya' => 'Kinerja alumni sangat memuaskan.',
        ];

        $response = $this->post("/evaluasi-atasan/{$evaluasi->token}", $postData);

        $response->assertStatus(302);
        $this->assertDatabaseHas('evaluasi_atasan', [
            'id' => $evaluasi->id,
            'is_submitted' => true,
            'jumlah_alumni_ukdw' => '< 5 Orang',
        ]);

        $this->assertDatabaseHas('perusahaan', [
            'id' => $perusahaan->id,
            'nama_perusahaan' => 'PT Makmur Sentosa',
            'alamat' => 'Jl. Magelang KM 5, Yogyakarta',
            'skala' => 'Nasional',
            'bentuk_perusahaan' => 'Perusahaan Terbatas',
            'jumlah_pegawai' => '51 - 100 Orang',
            'homepage' => 'https://makmursentosa.com',
            'no_telp_fax' => '0274-123456',
        ]);

        $this->assertDatabaseHas('respon_evaluasi_atasan', [
            'evaluasi_atasan_id' => $evaluasi->id,
            'nilai' => 'Sangat Tinggi',
        ]);
    }
}
