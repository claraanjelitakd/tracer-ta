<?php

namespace Tests\Feature;

use App\Models\Biodata;
use App\Models\DataAkademik;
use App\Models\Prodi;
use App\Models\ProdiQuestion;
use App\Models\ProdiQuestionSection;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;
use Tests\TestCase;

class AdminProdiKuesionerTest extends TestCase
{
    use RefreshDatabase;

    protected Prodi $prodiSI;

    protected Prodi $prodiTI;

    protected User $adminSI;

    protected User $adminTI;

    protected User $alumniUser;

    protected Biodata $alumni;

    protected ProdiQuestionSection $sectionSI;

    protected function setUp(): void
    {
        parent::setUp();

        $this->prodiSI = Prodi::create(['kode_prodi' => '72', 'nama_prodi' => 'Sistem Informasi']);
        $this->prodiTI = Prodi::create(['kode_prodi' => '71', 'nama_prodi' => 'Informatika']);

        $this->adminSI = User::create([
            'username' => 'admin_si',
            'name' => 'Admin Prodi SI',
            'password' => Hash::make('password123'),
            'role' => 'admin_prodi',
            'prodi_id' => $this->prodiSI->id,
            'must_change_password' => false,
        ]);

        $this->adminTI = User::create([
            'username' => 'admin_ti',
            'name' => 'Admin Prodi TI',
            'password' => Hash::make('password123'),
            'role' => 'admin_prodi',
            'prodi_id' => $this->prodiTI->id,
            'must_change_password' => false,
        ]);

        $this->alumniUser = User::create([
            'username' => '72200001',
            'name' => 'Alumni SI',
            'password' => Hash::make('15082001'),
            'role' => 'alumni',
            'prodi_id' => $this->prodiSI->id,
            'must_change_password' => false,
        ]);

        DataAkademik::create([
            'nim' => '72200001',
            'nama' => 'Alumni SI',
            'email_pribadi' => 'alumni.si@gmail.com',
            'tempat_lahir' => 'Yogyakarta',
            'tanggal_lahir' => '2001-08-15',
            'agama' => 'Kristen',
            'jenis_kelamin' => 'Laki-laki',
            'nomor_telepon' => '081234567890',
            'alamat_saat_ini' => 'Jl. Dr. Wahidin',
            'nik' => '3471012345670002',
            'ip_kumulatif' => 3.80,
            'tahun_akademik_lulus' => 'Genap 2024/2025',
        ]);

        $this->alumni = Biodata::create([
            'user_id' => $this->alumniUser->id,
            'nim' => '72200001',
            'nama' => 'Alumni SI',
            'prodi_id' => $this->prodiSI->id,
        ]);

        $this->sectionSI = ProdiQuestionSection::create([
            'prodi_id' => $this->prodiSI->id,
            'title' => 'Evaluasi Kurikulum Prodi SI',
            'description' => 'Umpan balik relevansi materi dan kompetensi lulusan',
            'order' => 1,
        ]);
    }

    public function test_admin_prodi_can_access_dashboard_and_pertanyaan(): void
    {
        $response = $this->actingAs($this->adminSI)->get('/prodi/dashboard');
        $response->assertStatus(200);

        $responsePertanyaan = $this->actingAs($this->adminSI)->get('/prodi/pertanyaan');
        $responsePertanyaan->assertStatus(200);

        $responseAlumni = $this->actingAs($this->adminSI)->get('/prodi/alumni');
        $responseAlumni->assertStatus(200);
    }

    public function test_admin_prodi_can_create_section_for_own_prodi(): void
    {
        $response = $this->actingAs($this->adminSI)->post('/prodi/section', [
            'title' => 'Fasilitas & Lab Komputer SI',
            'description' => 'Evaluasi kelayakan fasilitas laboratorium',
            'order' => 2,
        ]);

        $response->assertRedirect();
        $this->assertDatabaseHas('prodi_question_section', [
            'title' => 'Fasilitas & Lab Komputer SI',
            'prodi_id' => $this->prodiSI->id,
        ]);
    }

    public function test_admin_prodi_can_create_question_and_options_for_own_prodi(): void
    {
        $response = $this->actingAs($this->adminSI)->post('/prodi/pertanyaan', [
            'prodi_question_section_id' => $this->sectionSI->id,
            'code' => 'PSI-01',
            'question_text' => 'Bagaimana kepuasan terhadap kurikulum SI?',
            'type' => 'single_choice',
            'is_required' => true,
            'options' => ['Sangat Puas', 'Cukup Puas', 'Kurang Puas'],
        ]);

        $response->assertRedirect();
        $this->assertDatabaseHas('prodi_question', [
            'code' => 'PSI-01',
            'prodi_id' => $this->prodiSI->id,
            'prodi_question_section_id' => $this->sectionSI->id,
        ]);

        $this->assertDatabaseHas('prodi_question_option', [
            'option_text' => 'Sangat Puas',
        ]);
    }

    public function test_admin_prodi_cannot_modify_other_prodi_question(): void
    {
        $tiSection = ProdiQuestionSection::create([
            'prodi_id' => $this->prodiTI->id,
            'title' => 'Section TI',
            'order' => 1,
        ]);

        $tiQuestion = ProdiQuestion::create([
            'prodi_id' => $this->prodiTI->id,
            'prodi_question_section_id' => $tiSection->id,
            'code' => 'PTI-01',
            'question_text' => 'Pertanyaan Khusus TI',
            'type' => 'text',
            'is_required' => false,
            'order' => 1,
        ]);

        $response = $this->actingAs($this->adminSI)->put("/prodi/pertanyaan/{$tiQuestion->id}", [
            'code' => 'PTI-01',
            'question_text' => 'Mencoba meretas pertanyaan TI',
            'type' => 'text',
            'is_required' => false,
        ]);

        $response->assertStatus(404);
    }

    public function test_alumni_can_view_and_submit_prodi_questionnaire(): void
    {
        $qSI = ProdiQuestion::create([
            'prodi_id' => $this->prodiSI->id,
            'prodi_question_section_id' => $this->sectionSI->id,
            'code' => 'PSI-TEST',
            'question_text' => 'Relevansi materi SI',
            'type' => 'single_choice',
            'is_required' => true,
            'order' => 1,
        ]);

        $response = $this->actingAs($this->alumniUser)->get('/alumni/kuesioner-prodi');
        $response->assertStatus(200);

        $submitResponse = $this->actingAs($this->alumniUser)->post('/alumni/kuesioner-prodi', [
            'answers' => [
                $qSI->id => 'Sangat Relevan',
            ],
        ]);

        $submitResponse->assertRedirect('/alumni/dashboard');
        $this->assertDatabaseHas('prodi_response', [
            'biodata_id' => $this->alumni->id,
            'prodi_question_id' => $qSI->id,
            'answer_text' => 'Sangat Relevan',
        ]);
    }

    public function test_admin_prodi_can_view_sections_and_reorder(): void
    {
        $sec2 = ProdiQuestionSection::create([
            'prodi_id' => $this->prodiSI->id,
            'title' => 'Section 2 SI',
            'order' => 2,
        ]);

        $response = $this->actingAs($this->adminSI)->get('/prodi/sections');
        $response->assertStatus(200);

        $reorderResponse = $this->actingAs($this->adminSI)->post('/prodi/sections/reorder', [
            'id' => $sec2->id,
            'direction' => 'up',
        ]);

        $reorderResponse->assertRedirect();
        $this->assertEquals(1, $sec2->fresh()->order);
        $this->assertEquals(2, $this->sectionSI->fresh()->order);
    }

    public function test_admin_prodi_can_view_alumni_detail_with_both_questionnaires(): void
    {
        $response = $this->actingAs($this->adminSI)->get("/prodi/alumni/{$this->alumni->id}");
        $response->assertStatus(200);
        $response->assertInertia(fn ($page) => $page->component('AdminProdi/Alumni/Show')
            ->has('alumni')
            ->has('evaluasi')
            ->has('univSections')
            ->has('prodiSections')
            ->has('prodiEvaluasi')
            ->has('formData')
        );
    }

    public function test_admin_prodi_cannot_view_other_prodi_alumni_detail(): void
    {
        // Admin TI cannot view alumni SI
        $response = $this->actingAs($this->adminTI)->get("/prodi/alumni/{$this->alumni->id}");
        $response->assertStatus(404);
    }

    public function test_superadmin_can_view_alumni_detail_with_prodi_questionnaire(): void
    {
        $superadmin = User::create([
            'username' => 'superadmin_test',
            'name' => 'Super Admin Test',
            'password' => Hash::make('password123'),
            'role' => 'superadmin',
            'must_change_password' => false,
        ]);

        $response = $this->actingAs($superadmin)->get("/superadmin/alumni/{$this->alumni->id}");
        $response->assertStatus(200);
        $response->assertInertia(fn ($page) => $page->component('SuperAdmin/Alumni/Show')
            ->has('prodiSections')
            ->has('prodiEvaluasi')
        );
    }

    public function test_academic_questions_are_automatically_synced_for_prodi_questionnaire(): void
    {
        $qNama = ProdiQuestion::create([
            'prodi_id' => $this->prodiSI->id,
            'prodi_question_section_id' => $this->sectionSI->id,
            'code' => 'PSI-1-01',
            'question_text' => 'Nama',
            'type' => 'text',
            'is_required' => true,
            'order' => 1,
        ]);

        $qNim = ProdiQuestion::create([
            'prodi_id' => $this->prodiSI->id,
            'prodi_question_section_id' => $this->sectionSI->id,
            'code' => 'PSI-1-02',
            'question_text' => 'NIM',
            'type' => 'text',
            'is_required' => true,
            'order' => 2,
        ]);

        // Access alumni detail from Admin Prodi, which triggers KuesionerSyncService
        $response = $this->actingAs($this->adminSI)->get("/prodi/alumni/{$this->alumni->id}");
        $response->assertStatus(200);

        $this->assertDatabaseHas('prodi_response', [
            'biodata_id' => $this->alumni->id,
            'prodi_question_id' => $qNama->id,
            'answer_text' => $this->alumni->dataAkademik->nama,
        ]);

        $this->assertDatabaseHas('prodi_response', [
            'biodata_id' => $this->alumni->id,
            'prodi_question_id' => $qNim->id,
            'answer_text' => $this->alumni->nim,
        ]);
    }
}
