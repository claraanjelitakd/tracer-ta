<?php

namespace Tests\Feature;

use App\Models\Questionnaire;
use App\Models\QuestionSection;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;
use Tests\TestCase;

class SuperAdminKelolaSectionTest extends TestCase
{
    use RefreshDatabase;

    protected User $superadmin;

    protected Questionnaire $questionnaire;

    protected function setUp(): void
    {
        parent::setUp();

        $this->superadmin = User::create([
            'username' => 'superadmin_test',
            'name' => 'Super Administrator Test',
            'password' => Hash::make('password123'),
            'role' => 'superadmin',
            'must_change_password' => false,
        ]);

        $this->questionnaire = Questionnaire::create([
            'title' => 'Tracer Study 2026',
            'year' => 2026,
            'is_active' => true,
        ]);
    }

    /**
     * Test superadmin dapat melihat halaman daftar section.
     */
    public function test_superadmin_can_view_sections_page(): void
    {
        QuestionSection::create([
            'questionnaire_id' => $this->questionnaire->id,
            'title' => 'Bagian Identitas Diri',
            'order' => 1,
        ]);

        $response = $this->actingAs($this->superadmin)
            ->get(route('superadmin.sections.index'));

        $response->assertStatus(200);
    }

    /**
     * Test superadmin dapat menambahkan section baru.
     */
    public function test_superadmin_can_create_new_section(): void
    {
        $response = $this->actingAs($this->superadmin)
            ->post(route('superadmin.sections.store'), [
                'questionnaire_id' => $this->questionnaire->id,
                'title' => 'Bagian Riwayat Pekerjaan',
                'order' => 1,
            ]);

        $response->assertRedirect();
        $this->assertDatabaseHas('kelompok_pertanyaans', [
            'kuesioner_id' => $this->questionnaire->id,
            'title' => 'Bagian Riwayat Pekerjaan',
            'order' => 1,
        ]);
    }

    /**
     * Test superadmin dapat memperbarui section yang sudah ada.
     */
    public function test_superadmin_can_update_section(): void
    {
        $section = QuestionSection::create([
            'questionnaire_id' => $this->questionnaire->id,
            'title' => 'Judul Lama',
            'order' => 1,
        ]);

        $response = $this->actingAs($this->superadmin)
            ->put(route('superadmin.sections.update', $section->id), [
                'questionnaire_id' => $this->questionnaire->id,
                'title' => 'Judul Baru yang Diperbarui',
                'order' => 1,
            ]);

        $response->assertRedirect();
        $this->assertDatabaseHas('kelompok_pertanyaans', [
            'id' => $section->id,
            'title' => 'Judul Baru yang Diperbarui',
        ]);
    }

    /**
     * Test superadmin dapat menghapus section.
     */
    public function test_superadmin_can_delete_section(): void
    {
        $section = QuestionSection::create([
            'questionnaire_id' => $this->questionnaire->id,
            'title' => 'Bagian untuk Dihapus',
            'order' => 1,
        ]);

        $response = $this->actingAs($this->superadmin)
            ->delete(route('superadmin.sections.destroy', $section->id));

        $response->assertRedirect();
        $this->assertDatabaseMissing('kelompok_pertanyaans', [
            'id' => $section->id,
        ]);
    }

    /**
     * Test superadmin dapat menukar urutan section (up / down).
     */
    public function test_superadmin_can_reorder_sections_directionally(): void
    {
        $section1 = QuestionSection::create([
            'questionnaire_id' => $this->questionnaire->id,
            'title' => 'Section 1',
            'order' => 1,
        ]);

        $section2 = QuestionSection::create([
            'questionnaire_id' => $this->questionnaire->id,
            'title' => 'Section 2',
            'order' => 2,
        ]);

        // Pindahkan section 2 ke atas (direction = up)
        $response = $this->actingAs($this->superadmin)
            ->post(route('superadmin.sections.reorder'), [
                'id' => $section2->id,
                'direction' => 'up',
            ]);

        $response->assertRedirect();

        $this->assertEquals(1, $section2->fresh()->order);
        $this->assertEquals(2, $section1->fresh()->order);
    }

    /**
     * Test superadmin dapat mengubah urutan section secara massal.
     */
    public function test_superadmin_can_bulk_reorder_sections(): void
    {
        $secA = QuestionSection::create([
            'questionnaire_id' => $this->questionnaire->id,
            'title' => 'Section A',
            'order' => 1,
        ]);

        $secB = QuestionSection::create([
            'questionnaire_id' => $this->questionnaire->id,
            'title' => 'Section B',
            'order' => 2,
        ]);

        $response = $this->actingAs($this->superadmin)
            ->post(route('superadmin.sections.reorder'), [
                'orders' => [
                    ['id' => $secA->id, 'order' => 5],
                    ['id' => $secB->id, 'order' => 6],
                ],
            ]);

        $response->assertRedirect();

        $this->assertEquals(5, $secA->fresh()->order);
        $this->assertEquals(6, $secB->fresh()->order);
    }
}
