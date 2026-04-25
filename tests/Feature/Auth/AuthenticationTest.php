<?php

namespace Tests\Feature\Auth;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;
use Tests\TestCase;

class AuthenticationTest extends TestCase
{
    use RefreshDatabase;

    public function test_login_page_is_available_to_guests(): void
    {
        $this->withoutVite();

        $this->get('/login')
            ->assertOk()
            ->assertSee('Entrar al panel')
            ->assertSee('Iniciar sesion');
    }

    public function test_authenticated_users_are_redirected_away_from_login(): void
    {
        $user = User::factory()->create();

        $this->actingAs($user)
            ->get('/login')
            ->assertRedirect('/admin/dashboard');
    }

    public function test_guests_are_redirected_to_login_when_accessing_the_admin_panel(): void
    {
        $this->get('/admin/dashboard')
            ->assertRedirect('/login');
    }

    public function test_users_can_log_in_with_valid_credentials(): void
    {
        $user = User::factory()->create([
            'email' => 'admin@echoserp.test',
            'password' => Hash::make('password'),
        ]);

        $this->post('/login', [
            'email' => 'admin@echoserp.test',
            'password' => 'password',
        ])->assertRedirect('/admin/dashboard');

        $this->assertAuthenticatedAs($user);
    }

    public function test_users_cannot_log_in_with_invalid_credentials(): void
    {
        User::factory()->create([
            'email' => 'admin@echoserp.test',
            'password' => Hash::make('password'),
        ]);

        $this->from('/login')
            ->post('/login', [
                'email' => 'admin@echoserp.test',
                'password' => 'incorrecta',
            ])
            ->assertRedirect('/login')
            ->assertSessionHasErrors('email');

        $this->assertGuest();
    }

    public function test_authenticated_users_can_log_out(): void
    {
        $user = User::factory()->create();

        $this->actingAs($user)
            ->post('/logout')
            ->assertRedirect('/login');

        $this->assertGuest();
    }
}
