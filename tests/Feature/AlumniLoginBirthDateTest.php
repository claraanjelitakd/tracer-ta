<?php

namespace Tests\Feature;

use App\Models\DataAkademik;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;
use Tests\TestCase;

class AlumniLoginBirthDateTest extends TestCase
{
    use RefreshDatabase;

    public function test_alumni_can_login_with_default_birth_date_password_and_is_forced_to_change_password(): void
    {
        // 1. Buat data akademik dengan tanggal lahir 15 Agustus 2001 (15082001)
        DataAkademik::create([
            'nim' => '72210001',
            'nama' => 'Test Alumni',
            'tanggal_lahir' => '2001-08-15',
            'strata' => 'S1',
            'status_mahasiswa' => 'AR',
            'ip_kumulatif' => 3.50,
        ]);

        // 2. Buat akun user alumni dengan password acak/belum diatur
        $user = User::create([
            'username' => '72210001',
            'name' => 'Test Alumni',
            'email' => 'test.alumni@students.ukdw.ac.id',
            'password' => Hash::make('unrelated_old_password'),
            'role' => 'alumni',
            'must_change_password' => true,
        ]);

        // 3. Login dengan default password tanggal lahir ddmmyyyy (15082001)
        $response = $this->post('/login', [
            'username' => '72210001',
            'password' => '15082001',
        ]);

        $this->assertAuthenticatedAs($user);
        $response->assertRedirect('/change-password');
        $this->assertTrue($user->fresh()->must_change_password);

        // 4. Ubah kata sandi di halaman change-password
        $changePasswordResponse = $this->post('/change-password', [
            'password' => 'NewSecretPass123!',
            'password_confirmation' => 'NewSecretPass123!',
        ]);

        $this->assertFalse($user->fresh()->must_change_password);
        $changePasswordResponse->assertRedirect('/alumni/dashboard');

        // 5. Logout
        $this->post('/logout');
        $this->assertGuest();

        // 6. Login kembali dengan password baru
        $loginAgainResponse = $this->post('/login', [
            'username' => '72210001',
            'password' => 'NewSecretPass123!',
        ]);

        $this->assertAuthenticatedAs($user);
        // Karena must_change_password sudah false, diarahkan langsung ke dashboard alumni
        $loginAgainResponse->assertRedirect('/alumni/dashboard');
    }
}
