<?php

namespace Tests\Feature;

use App\Models\KelompokPertanyaan;
use App\Models\Kuesioner;
use App\Models\RefSubpertanyaan2021;
use App\Models\User;
use Illuminate\Foundation\Testing\DatabaseMigrations;
use Illuminate\Support\Facades\Hash;
use Tests\TestCase;

class BiroTigaKelolaPertanyaanTest extends TestCase
{
    use DatabaseMigrations;

    protected User $admin;

    protected KelompokPertanyaan $section;

    protected function setUp(): void
    {
        parent::setUp();

        $this->admin = User::create([
            'username' => 'superadmin',
            'name' => 'Super Administrator UKDW',
            'password' => Hash::make('password123'),
            'role' => 'superadmin',
            'must_change_password' => false,
        ]);

        $q = Kuesioner::create([
            'title' => 'Tracer Study',
            'year' => 2026,
            'is_active' => true,
        ]);

        $this->section = KelompokPertanyaan::create([
            'kuesioner_id' => $q->id,
            'title' => 'Bagian I',
            'order' => 1,
        ]);
    }

    /**
     * Memverifikasi halaman kelola pertanyaan memuat data pertanyaan & jump targets
     */
    public function test_can_render_kelola_pertanyaan_page(): void
    {
        RefSubpertanyaan2021::create([
            'kelompok_pertanyaan_id' => $this->section->id,
            'kode_pertanyaan' => 'F1',
            'subpertanyaan' => 'Nomor Induk Mahasiswa',
            'type' => 'text',
            'wajib' => true,
            'order' => 1,
        ]);

        $response = $this->actingAs($this->admin)->get('/superadmin/pertanyaan');
        $response->assertStatus(200);
        $response->assertInertia(fn ($page) => $page->component('SuperAdmin/Pertanyaan/Index')
            ->has('subpertanyaans')
            ->has('availableJumpTargets')
            ->has('targetQuestionMap')
        );
    }

    /**
     * Memverifikasi fitur pemindahan urutan pertanyaan (reorder) ala GForm
     */
    public function test_can_reorder_questions_direction(): void
    {
        $q1 = RefSubpertanyaan2021::create([
            'kelompok_pertanyaan_id' => $this->section->id,
            'kode_pertanyaan' => 'F1',
            'subpertanyaan' => 'Pertanyaan 1',
            'type' => 'text',
            'wajib' => true,
            'order' => 1,
        ]);

        $q2 = RefSubpertanyaan2021::create([
            'kelompok_pertanyaan_id' => $this->section->id,
            'kode_pertanyaan' => 'F2',
            'subpertanyaan' => 'Pertanyaan 2',
            'type' => 'text',
            'wajib' => true,
            'order' => 2,
        ]);

        // Pindahkan Q2 ke atas
        $response = $this->actingAs($this->admin)->post('/superadmin/pertanyaan/reorder', [
            'id' => $q2->id,
            'direction' => 'up',
        ]);

        $response->assertSessionHasNoErrors();
        $this->assertEquals(1, $q2->fresh()->order);
        $this->assertEquals(2, $q1->fresh()->order);
    }

    /**
     * Memverifikasi penambahan opsi tanpa input manual urutan
     */
    public function test_can_store_option_without_manual_order(): void
    {
        $q = RefSubpertanyaan2021::create([
            'kelompok_pertanyaan_id' => $this->section->id,
            'kode_pertanyaan' => 'F3',
            'subpertanyaan' => 'Kapan mulai mencari kerja?',
            'type' => 'single_choice',
            'wajib' => true,
            'order' => 1,
        ]);

        $response = $this->actingAs($this->admin)->post("/superadmin/pertanyaan/{$q->id}/options", [
            'option_text' => 'Sebelum lulus',
            'jump_to' => null,
        ]);

        $response->assertSessionHasNoErrors();
        $this->assertDatabaseHas('ref_subpertanyaan_detil', [
            'pertanyaan_id' => $q->id,
            'option_text' => 'Sebelum lulus',
            'order' => 1,
        ]);
    }

    /**
     * Memverifikasi pembuatan pertanyaan bertipe rating_5 tidak menyimpan opsi di tabel question_options (skala murni).
     */
    public function test_storing_rating_question_does_not_store_options(): void
    {
        $response = $this->actingAs($this->admin)->post('/superadmin/pertanyaan', [
            'kelompok_pertanyaan_id' => $this->section->id,
            'kode_pertanyaan' => 'F99',
            'subpertanyaan' => 'Penilaian Kualitas Fasilitas Belajar',
            'type' => 'rating_5',
            'wajib' => true,
        ]);

        $response->assertSessionHasNoErrors();
        $q = RefSubpertanyaan2021::where('kode_pertanyaan', 'F99')->first();
        $this->assertNotNull($q);
        $this->assertCount(0, $q->detils);
    }
}
