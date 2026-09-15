<?php

namespace Tests\Feature;

use App\Models\Biodata;
use App\Models\DataAkademik;
use App\Models\KelompokPertanyaan;
use App\Models\Kuesioner;
use App\Models\Prodi;
use App\Models\RefSubpertanyaan2021;
use App\Models\RefSubpertanyaanDetil;
use App\Models\Tracer;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;
use Tests\TestCase;

class AlumniKuesionerMultipleNumberTest extends TestCase
{
    use RefreshDatabase;

    protected User $user;

    protected Biodata $alumni;

    protected Kuesioner $kuesioner;

    protected KelompokPertanyaan $section;

    protected RefSubpertanyaan2021 $questionF13;

    protected RefSubpertanyaanDetil $opt1;

    protected RefSubpertanyaanDetil $opt2;

    protected RefSubpertanyaanDetil $opt3;

    protected function setUp(): void
    {
        parent::setUp();

        $this->user = User::create([
            'username' => '71190001',
            'name' => 'Alumni Test',
            'email' => 'alumni@example.com',
            'password' => Hash::make('password123'),
            'role' => 'alumni',
            'must_change_password' => false,
        ]);

        $prodi = Prodi::create([
            'kode_prodi' => '71',
            'nama_prodi' => 'Informatika',
            'jenjang' => 'S1',
        ]);

        DataAkademik::create([
            'nim' => '71190001',
            'nama' => 'Alumni Test',
            'status_mahasiswa' => 'AR',
        ]);

        $this->alumni = Biodata::create([
            'user_id' => $this->user->id,
            'prodi_id' => $prodi->id,
            'nim' => '71190001',
            'nama' => 'Alumni Test',
            'expert' => 'Web Development',
            'minat' => 'Cloud Computing',
        ]);

        $this->kuesioner = Kuesioner::create([
            'title' => 'Tracer Study Test',
            'year' => 2026,
            'is_active' => true,
        ]);

        $this->section = KelompokPertanyaan::create([
            'kuesioner_id' => $this->kuesioner->id,
            'title' => 'Karakteristik Pekerjaan Saat Ini',
            'order' => 7,
        ]);

        $this->questionF13 = RefSubpertanyaan2021::create([
            'kelompok_pertanyaan_id' => $this->section->id,
            'kode_pertanyaan' => 'F13',
            'subpertanyaan' => 'Kira-kira berapa pendapatan anda setiap bulannya?',
            'type' => 'multiple_number',
            'wajib' => true,
            'order' => 1,
        ]);

        $this->opt1 = RefSubpertanyaanDetil::create([
            'pertanyaan_id' => $this->questionF13->id,
            'kode_pertanyaan' => 'F13',
            'kode_opsi' => 'F13-01',
            'option_text' => 'Dari Pekerjaan Utama',
            'order' => 1,
        ]);

        $this->opt2 = RefSubpertanyaanDetil::create([
            'pertanyaan_id' => $this->questionF13->id,
            'kode_pertanyaan' => 'F13',
            'kode_opsi' => 'F13-02',
            'option_text' => 'Dari Lembur dan Tips',
            'order' => 2,
        ]);

        $this->opt3 = RefSubpertanyaanDetil::create([
            'pertanyaan_id' => $this->questionF13->id,
            'kode_pertanyaan' => 'F13',
            'kode_opsi' => 'F13-03',
            'option_text' => 'Dari Pekerjaan Lainnya',
            'order' => 3,
        ]);
    }

    /**
     * Memverifikasi penyimpanan jawaban multiple_number dengan input ribuan:
     * - Nilai input ribuan dikonversi menjadi Rupiah penuh (x 1000).
     * - Total salary dihitung akumulatif dan disimpan pada answer_json['total'].
     * - answer_text menyertakan rincian opsi dan Total Pendapatan.
     */
    public function test_can_store_multiple_number_with_total_and_thousands_conversion(): void
    {
        $payload = [
            'answers' => [
                $this->questionF13->id => [
                    'F13-01' => 6500, // 6.500.000
                    'F13-02' => 750,  // 750.000
                    'F13-03' => 0,    // 0
                ],
            ],
        ];

        $response = $this->actingAs($this->user)->post('/alumni/kuesioner', $payload);
        $response->assertStatus(302);

        $saved = Tracer::where('biodata_id', $this->alumni->id)
            ->where('question_id', $this->questionF13->id)
            ->first();

        $this->assertNotNull($saved);
        $this->assertEquals(6500000, $saved->answer_json['F13-01']);
        $this->assertEquals(750000, $saved->answer_json['F13-02']);
        $this->assertEquals(0, $saved->answer_json['F13-03']);
        $this->assertEquals(7250000, $saved->answer_json['total']);

        $this->assertStringContainsString('Dari Pekerjaan Utama: Rp 6.500.000', $saved->answer_text);
        $this->assertStringContainsString('Dari Lembur dan Tips: Rp 750.000', $saved->answer_text);
        $this->assertStringContainsString('Total Pendapatan: Rp 7.250.000', $saved->answer_text);
    }

    /**
     * Memverifikasi jika alumni mengubah nilai gaji, total salary di database ikut terupdate.
     */
    public function test_updating_multiple_number_recalculates_total(): void
    {
        // Simpan jawaban awal
        $this->actingAs($this->user)->post('/alumni/kuesioner', [
            'answers' => [
                $this->questionF13->id => [
                    'F13-01' => 5000,
                    'F13-02' => 500,
                    'F13-03' => 0,
                ],
            ],
        ]);

        $saved1 = Tracer::where('biodata_id', $this->alumni->id)
            ->where('question_id', $this->questionF13->id)
            ->first();
        $this->assertEquals(5500000, $saved1->answer_json['total']);

        // Update nominal F13-01 naik menjadi 7000 (Rp 7.000.000) dan F13-03 menjadi 1000 (Rp 1.000.000)
        $this->actingAs($this->user)->post('/alumni/kuesioner', [
            'answers' => [
                $this->questionF13->id => [
                    'F13-01' => 7000,
                    'F13-02' => 500,
                    'F13-03' => 1000,
                ],
            ],
        ]);

        $saved2 = Tracer::where('biodata_id', $this->alumni->id)
            ->where('question_id', $this->questionF13->id)
            ->first();

        $this->assertEquals(8500000, $saved2->answer_json['total']);
        $this->assertStringContainsString('Total Pendapatan: Rp 8.500.000', $saved2->answer_text);
    }

    /**
     * Memverifikasi kuesioner mengirimkan nilai dalam satuan ribuan ke frontend saat dimuat kembali.
     */
    public function test_loading_kuesioner_normalizes_multiple_number_to_thousands(): void
    {
        // Simpan jawaban ke DB dengan nominal penuh
        Tracer::create([
            'biodata_id' => $this->alumni->id,
            'question_id' => $this->questionF13->id,
            'answer' => 'Dari Pekerjaan Utama: Rp 6.000.000, Dari Lembur dan Tips: Rp 500.000, Dari Pekerjaan Lainnya: Rp 0, Total Pendapatan: Rp 6.500.000',
            'answer_json' => [
                'F13-01' => 6000000,
                'F13-02' => 500000,
                'F13-03' => 0,
                'total' => 6500000,
            ],
        ]);

        $response = $this->actingAs($this->user)->get('/alumni/kuesioner');
        $response->assertStatus(200);
        $response->assertInertia(function ($page) {
            $initialAnswers = $page->toArray()['props']['initialAnswers'];
            $qAnswers = $initialAnswers[$this->questionF13->id];

            $this->assertEquals(6000, $qAnswers['F13-01']);
            $this->assertEquals(500, $qAnswers['F13-02']);
            $this->assertEquals(0, $qAnswers['F13-03']);
        });
    }

    /**
     * Memverifikasi proteksi jika alumni menginput angka nominal penuh (>= 1.000.000)
     * sistem tidak mengalikan ulang dengan 1000 (mencegah kesalahan menjadi miliaran).
     */
    public function test_full_nominal_input_is_protected_from_double_multiplication(): void
    {
        $payload = [
            'answers' => [
                $this->questionF13->id => [
                    'F13-01' => 5000000, // Alumni mengetik 5.000.000 langsung
                    'F13-02' => 0,
                    'F13-03' => 0,
                ],
            ],
        ];

        $response = $this->actingAs($this->user)->post('/alumni/kuesioner', $payload);
        $response->assertStatus(302);

        $saved = Tracer::where('biodata_id', $this->alumni->id)
            ->where('question_id', $this->questionF13->id)
            ->first();

        $this->assertEquals(5000000, $saved->answer_json['F13-01']);
        $this->assertEquals(5000000, $saved->answer_json['total']);
    }
}
