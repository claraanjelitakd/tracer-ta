<?php

namespace Tests\Feature;

use App\Models\KelompokPertanyaan;
use App\Models\Kuesioner;
use App\Models\RefSubpertanyaan2021;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;
use Tests\TestCase;

class SuperAdminKelolaPertanyaanTest extends TestCase
{
    use RefreshDatabase;

    protected User $superadmin;

    protected Kuesioner $kuesioner;

    protected KelompokPertanyaan $section;

    protected function setUp(): void
    {
        parent::setUp();

        $this->superadmin = User::create([
            'username' => 'superadmin_pertanyaan_test',
            'name' => 'Super Administrator Test',
            'password' => Hash::make('password123'),
            'role' => 'superadmin',
            'must_change_password' => false,
        ]);

        $this->kuesioner = Kuesioner::create([
            'title' => 'Tracer Study 2026',
            'year' => 2026,
            'is_active' => true,
        ]);

        $this->section = KelompokPertanyaan::create([
            'kuesioner_id' => $this->kuesioner->id,
            'title' => 'Bagian Identitas & Pekerjaan',
            'order' => 1,
        ]);
    }

    /**
     * Test superadmin dapat melihat halaman daftar pertanyaan.
     */
    public function test_superadmin_can_view_questions_page(): void
    {
        RefSubpertanyaan2021::create([
            'kelompok' => 'F1',
            'kelompok_pertanyaan_id' => $this->section->id,
            'kode_pertanyaan' => 'F301_TEST',
            'subpertanyaan' => 'Kapan Anda mulai mencari pekerjaan?',
            'type' => 'single_choice',
            'wajib' => true,
            'tampil_di' => 'kuesioner',
            'order' => 1,
        ]);

        $response = $this->actingAs($this->superadmin)
            ->get(route('superadmin.pertanyaan.index'));

        $response->assertStatus(200);
    }

    /**
     * Test superadmin dapat menambahkan butir pertanyaan baru dengan konfigurasi tampil_di.
     */
    public function test_superadmin_can_create_new_question_with_tampil_di(): void
    {
        $response = $this->actingAs($this->superadmin)
            ->post(route('superadmin.pertanyaan.store'), [
                'kelompok_pertanyaan_id' => $this->section->id,
                'kode_pertanyaan' => 'F302_TEST',
                'subpertanyaan' => 'Berapa banyak perusahaan yang Anda lamar?',
                'type' => 'number',
                'wajib' => true,
                'tampil_di' => 'kuesioner',
                'order' => 1,
            ]);

        $response->assertRedirect();
        $this->assertDatabaseHas('ref_subpertanyaan2021', [
            'kode_pertanyaan' => 'F302_TEST',
            'subpertanyaan' => 'Berapa banyak perusahaan yang Anda lamar?',
            'tampil_di' => 'kuesioner',
        ]);
    }

    /**
     * Test superadmin dapat memperbarui lokasi tampil pertanyaan (kuesioner -> profile).
     */
    public function test_superadmin_can_update_question_target(): void
    {
        $question = RefSubpertanyaan2021::create([
            'kelompok' => 'F1',
            'kelompok_pertanyaan_id' => $this->section->id,
            'kode_pertanyaan' => 'F303_TEST',
            'subpertanyaan' => 'Pertanyaan Awal',
            'type' => 'text',
            'wajib' => true,
            'tampil_di' => 'kuesioner',
            'order' => 1,
        ]);

        $response = $this->actingAs($this->superadmin)
            ->put(route('superadmin.pertanyaan.update', $question->id), [
                'kelompok_pertanyaan_id' => $this->section->id,
                'kode_pertanyaan' => 'F303_TEST',
                'subpertanyaan' => 'Pertanyaan Diperbarui',
                'type' => 'text',
                'wajib' => true,
                'tampil_di' => 'profile',
                'order' => 1,
            ]);

        $response->assertRedirect();
        $this->assertDatabaseHas('ref_subpertanyaan2021', [
            'id' => $question->id,
            'tampil_di' => 'profile',
            'subpertanyaan' => 'Pertanyaan Diperbarui',
        ]);
    }

    /**
     * Test superadmin dapat mengubah urutan pertanyaan naik dan turun.
     */
    public function test_superadmin_can_reorder_questions(): void
    {
        $q1 = RefSubpertanyaan2021::create([
            'kelompok' => 'F1',
            'kelompok_pertanyaan_id' => $this->section->id,
            'kode_pertanyaan' => 'Q1_TEST',
            'subpertanyaan' => 'Soal 1',
            'type' => 'text',
            'wajib' => true,
            'tampil_di' => 'kuesioner',
            'order' => 1,
        ]);

        $q2 = RefSubpertanyaan2021::create([
            'kelompok' => 'F1',
            'kelompok_pertanyaan_id' => $this->section->id,
            'kode_pertanyaan' => 'Q2_TEST',
            'subpertanyaan' => 'Soal 2',
            'type' => 'text',
            'wajib' => true,
            'tampil_di' => 'kuesioner',
            'order' => 2,
        ]);

        $response = $this->actingAs($this->superadmin)
            ->post(route('superadmin.pertanyaan.reorder'), [
                'id' => $q2->id,
                'direction' => 'up',
            ]);

        $response->assertRedirect();
        $this->assertEquals(1, $q2->fresh()->order);
        $this->assertEquals(2, $q1->fresh()->order);
    }
}
