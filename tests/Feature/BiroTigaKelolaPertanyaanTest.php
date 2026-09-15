<?php

namespace Tests\Feature;

use App\Models\Question;
use App\Models\Questionnaire;
use App\Models\QuestionSection;
use App\Models\User;
use Illuminate\Foundation\Testing\DatabaseMigrations;
use Illuminate\Support\Facades\Hash;
use Tests\TestCase;

class BiroTigaKelolaPertanyaanTest extends TestCase
{
    use DatabaseMigrations;

    protected User $admin;

    protected QuestionSection $section;

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

        $q = Questionnaire::create([
            'title' => 'Tracer Study',
            'year' => 2026,
            'is_active' => true,
        ]);

        $this->section = QuestionSection::create([
            'questionnaire_id' => $q->id,
            'title' => 'Bagian I',
            'order' => 1,
        ]);
    }

    /**
     * Memverifikasi halaman kelola pertanyaan memuat data pertanyaan & jump targets
     */
    public function test_can_render_kelola_pertanyaan_page(): void
    {
        Question::create([
            'question_section_id' => $this->section->id,
            'code' => 'F1',
            'question_text' => 'Nomor Induk Mahasiswa',
            'type' => 'text',
            'is_required' => true,
            'order' => 1,
        ]);

        $response = $this->actingAs($this->admin)->get('/superadmin/pertanyaan');
        $response->assertStatus(200);
        $response->assertInertia(fn ($page) => $page->component('SuperAdmin/Pertanyaan/Index')
            ->has('questions')
            ->has('availableJumpTargets')
            ->has('targetQuestionMap')
        );
    }

    /**
     * Memverifikasi fitur pemindahan urutan pertanyaan (reorder) ala GForm
     */
    public function test_can_reorder_questions_direction(): void
    {
        $q1 = Question::create([
            'question_section_id' => $this->section->id,
            'code' => 'F1',
            'question_text' => 'Pertanyaan 1',
            'type' => 'text',
            'is_required' => true,
            'order' => 1,
        ]);

        $q2 = Question::create([
            'question_section_id' => $this->section->id,
            'code' => 'F2',
            'question_text' => 'Pertanyaan 2',
            'type' => 'text',
            'is_required' => true,
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
        $q = Question::create([
            'question_section_id' => $this->section->id,
            'code' => 'F3',
            'question_text' => 'Kapan mulai mencari kerja?',
            'type' => 'single_choice',
            'is_required' => true,
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
            'question_section_id' => $this->section->id,
            'code' => 'F99',
            'question_text' => 'Penilaian Kualitas Fasilitas Belajar',
            'type' => 'rating_5',
            'is_required' => true,
        ]);

        $response->assertSessionHasNoErrors();
        $q = Question::where('code', 'F99')->first();
        $this->assertNotNull($q);
        $this->assertCount(0, $q->options);
    }
}
