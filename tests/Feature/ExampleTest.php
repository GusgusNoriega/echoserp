<?php

namespace Tests\Feature;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ExampleTest extends TestCase
{
    use RefreshDatabase;

    /**
     * The root entrypoint should send guests to the login screen.
     */
    public function test_the_root_route_redirects_guests_to_login(): void
    {
        $response = $this->get('/');

        $response->assertRedirect('/login');
    }

    /**
     * Authenticated users should still land in the admin dashboard.
     */
    public function test_the_root_route_redirects_authenticated_users_to_the_admin_dashboard(): void
    {
        $user = User::factory()->create();

        $response = $this->actingAs($user)->get('/');

        $response->assertRedirect('/admin/dashboard');
    }

    /**
     * The admin dashboard should render the reusable panel layout.
     */
    public function test_the_admin_dashboard_renders_the_layout_shell_for_authenticated_users(): void
    {
        $this->withoutVite();

        $user = User::factory()->create();

        $response = $this->actingAs($user)->get('/admin/dashboard');

        $response
            ->assertOk()
            ->assertSee('Dashboard administrativo')
            ->assertSee('Administracion')
            ->assertSee('Cambiar modo del panel');
    }
}
