<?php

namespace Tests\Feature;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;
use Tests\TestCase;

class BiroTigaDashboardTest extends TestCase
{
    use RefreshDatabase;

    /**
     * Tamu tanpa autentikasi dialihkan ke halaman login saat akses dashboard Biro 3.
     */
    public function test_guest_is_redirected_to_login(): void
    {
        $response = $this->get('/biro3/dashboard');
        $response->assertRedirect('/login');
    }

    /**
     * Login sebagai admin_biro3 mengarahkan ke /biro3/dashboard (bukan langsung ke menu alumni).
     */
    public function test_admin_biro3_login_redirects_to_dashboard(): void
    {
        User::create([
            'username' => 'admin_biro3',
            'name' => 'Admin Biro 3 UKDW',
            'password' => Hash::make('password123'),
            'role' => 'admin_biro3',
            'must_change_password' => false,
        ]);

        $response = $this->post('/login', [
            'username' => 'admin_biro3',
            'password' => 'password123',
        ]);

        $response->assertRedirect('/biro3/dashboard');
    }

    /**
     * Admin Biro 3 dapat mengakses halaman dashboard Biro 3 dengan sukses.
     */
    public function test_admin_biro3_can_render_dashboard(): void
    {
        $admin = User::create([
            'username' => 'admin_biro3',
            'name' => 'Admin Biro 3 UKDW',
            'password' => Hash::make('password123'),
            'role' => 'admin_biro3',
            'must_change_password' => false,
        ]);

        $response = $this->actingAs($admin)->get('/biro3/dashboard');
        $response->assertStatus(200);
        $response->assertInertia(fn ($page) => $page->component('AdminBiroTiga/Dashboard')
            ->has('stats')
            ->has('prodiSummaries')
        );
    }
}
