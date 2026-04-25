<?php

namespace Tests\Feature\Admin;

use App\Models\Module;
use App\Models\Permission;
use App\Models\PermissionAction;
use App\Models\Role;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class AccessManagementPagesTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        $this->withoutVite();
    }

    public function test_dashboard_shows_the_access_control_module(): void
    {
        $this->seed();
        $this->signInAsAdmin();

        $response = $this->get('/admin/dashboard');

        $response->assertOk()
            ->assertSee('Usuarios')
            ->assertSee('Roles')
            ->assertSee('Permisos')
            ->assertSee('Modulos')
            ->assertSee('Modulos activos');
    }

    public function test_access_management_pages_render_seeded_information(): void
    {
        $this->seed();
        $this->signInAsAdmin();

        $this->get('/admin/usuarios')
            ->assertOk()
            ->assertSee('Administrador Echo')
            ->assertSee('Super administrador');

        $this->get('/admin/roles')
            ->assertOk()
            ->assertSee('Super administrador')
            ->assertSee('Ver usuarios');

        $this->get('/admin/modulos')
            ->assertOk()
            ->assertSee('Usuarios')
            ->assertSee('view.others');

        $this->get('/admin/permisos')
            ->assertOk()
            ->assertSee('dashboard.view')
            ->assertSee('Configuracion')
            ->assertSee('Gestion completa');
    }

    public function test_user_helpers_resolve_roles_and_permissions(): void
    {
        $this->seed();

        $user = User::query()->where('email', 'admin@echoserp.test')->firstOrFail();

        $this->assertTrue($user->hasRole('super-admin'));
        $this->assertTrue($user->hasPermission('permissions.update'));
    }

    public function test_user_can_be_created_and_updated_with_role_assignments(): void
    {
        $this->seed();
        $this->signInAsAdmin();

        $adminRole = Role::query()->where('slug', 'admin')->firstOrFail();
        $viewerRole = Role::query()->where('slug', 'viewer')->firstOrFail();

        $this->post('/admin/usuarios', [
            'name' => 'Nuevo Usuario',
            'email' => 'nuevo@echoserp.test',
            'password' => 'secret123',
            'password_confirmation' => 'secret123',
            'email_verified' => '1',
            'role_ids' => [$adminRole->id, $viewerRole->id],
        ])->assertRedirect('/admin/usuarios');

        $user = User::query()->where('email', 'nuevo@echoserp.test')->firstOrFail();

        $this->assertEqualsCanonicalizing(
            [$adminRole->id, $viewerRole->id],
            $user->roles()->pluck('roles.id')->all()
        );

        $this->put('/admin/usuarios/'.$user->id, [
            'name' => 'Usuario Ajustado',
            'email' => 'nuevo@echoserp.test',
            'password' => '',
            'password_confirmation' => '',
            'role_ids' => [$viewerRole->id],
        ])->assertRedirect('/admin/usuarios');

        $user->refresh();

        $this->assertSame('Usuario Ajustado', $user->name);
        $this->assertNull($user->email_verified_at);
        $this->assertEquals([$viewerRole->id], $user->roles()->pluck('roles.id')->all());
    }

    public function test_user_can_be_deleted_from_the_admin_panel(): void
    {
        $this->seed();
        $this->signInAsAdmin();

        $viewerRole = Role::query()->where('slug', 'viewer')->firstOrFail();
        $user = User::query()->create([
            'name' => 'Temporal',
            'email' => 'temporal@echoserp.test',
            'password' => 'password',
        ]);
        $user->roles()->sync([$viewerRole->id]);

        $this->delete('/admin/usuarios/'.$user->id)
            ->assertRedirect('/admin/usuarios');

        $this->assertDatabaseMissing('users', ['id' => $user->id]);
        $this->assertDatabaseMissing('role_user', [
            'user_id' => $user->id,
            'role_id' => $viewerRole->id,
        ]);
    }

    public function test_authenticated_user_cannot_delete_their_own_account(): void
    {
        $this->seed();
        $this->signInAsAdmin();

        $admin = User::query()->where('email', 'admin@echoserp.test')->firstOrFail();

        $this->delete('/admin/usuarios/'.$admin->id)
            ->assertRedirect('/admin/usuarios')
            ->assertSessionHas('error');

        $this->assertDatabaseHas('users', ['id' => $admin->id]);
    }

    public function test_role_can_be_created_and_updated_with_permissions(): void
    {
        $this->seed();
        $this->signInAsAdmin();

        $viewUsers = Permission::query()->where('slug', 'users.view')->firstOrFail();
        $updateUsers = Permission::query()->where('slug', 'users.update')->firstOrFail();
        $viewPermissions = Permission::query()->where('slug', 'permissions.view')->firstOrFail();

        $this->post('/admin/roles', [
            'name' => 'Supervisor Regional',
            'slug' => '',
            'description' => 'Gestiona usuarios en su zona.',
            'permission_ids' => [$viewUsers->id, $updateUsers->id],
        ])->assertRedirect('/admin/roles');

        $role = Role::query()->where('slug', 'supervisor-regional')->firstOrFail();

        $this->assertEqualsCanonicalizing(
            [$viewUsers->id, $updateUsers->id],
            $role->permissions()->pluck('permissions.id')->all()
        );

        $this->put('/admin/roles/'.$role->id, [
            'name' => 'Supervisor Regional Senior',
            'slug' => 'supervisor-regional-senior',
            'description' => 'Gestion ampliada.',
            'is_system' => '1',
            'permission_ids' => [$viewPermissions->id],
        ])->assertRedirect('/admin/roles');

        $role->refresh();

        $this->assertSame('Supervisor Regional Senior', $role->name);
        $this->assertSame('supervisor-regional-senior', $role->slug);
        $this->assertTrue($role->is_system);
        $this->assertEquals([$viewPermissions->id], $role->permissions()->pluck('permissions.id')->all());
    }

    public function test_role_can_be_deleted_from_the_admin_panel(): void
    {
        $this->seed();
        $this->signInAsAdmin();

        $role = Role::query()->where('slug', 'viewer')->firstOrFail();
        $permission = Permission::query()->where('slug', 'dashboard.view')->firstOrFail();
        $user = User::query()->where('email', 'consulta@echoserp.test')->firstOrFail();

        $this->assertDatabaseHas('permission_role', [
            'role_id' => $role->id,
            'permission_id' => $permission->id,
        ]);
        $this->assertDatabaseHas('role_user', [
            'role_id' => $role->id,
            'user_id' => $user->id,
        ]);

        $this->delete('/admin/roles/'.$role->id)
            ->assertRedirect('/admin/roles');

        $this->assertDatabaseMissing('roles', ['id' => $role->id]);
        $this->assertDatabaseMissing('permission_role', [
            'role_id' => $role->id,
            'permission_id' => $permission->id,
        ]);
        $this->assertDatabaseMissing('role_user', [
            'role_id' => $role->id,
            'user_id' => $user->id,
        ]);
    }

    public function test_module_can_be_created_and_updated_with_action_assignments(): void
    {
        $this->seed();
        $this->signInAsAdmin();

        $viewAction = PermissionAction::query()->where('slug', 'view')->firstOrFail();
        $createAction = PermissionAction::query()->where('slug', 'create')->firstOrFail();
        $updateOthersAction = PermissionAction::query()->where('slug', 'update.others')->firstOrFail();

        $this->post('/admin/modulos', [
            'name' => 'Clientes',
            'slug' => '',
            'description' => 'Gestiona clientes y su seguimiento.',
            'action_ids' => [$viewAction->id, $createAction->id],
        ])->assertRedirect('/admin/modulos');

        $module = Module::query()->where('slug', 'clientes')->firstOrFail();

        $this->assertEqualsCanonicalizing(
            [$viewAction->id, $createAction->id],
            $module->actions()->pluck('permission_actions.id')->all()
        );

        $viewPermission = Permission::query()->create([
            'name' => 'Ver clientes',
            'module_id' => $module->id,
            'permission_action_id' => $viewAction->id,
            'slug' => 'clientes.view',
        ]);

        $createPermission = Permission::query()->create([
            'name' => 'Crear clientes',
            'module_id' => $module->id,
            'permission_action_id' => $createAction->id,
            'slug' => 'clientes.create',
        ]);

        $this->put('/admin/modulos/'.$module->id, [
            'name' => 'Clientes corporativos',
            'slug' => 'clientes-corporativos',
            'description' => 'Gestion ampliada.',
            'is_system' => '1',
            'action_ids' => [$viewAction->id, $updateOthersAction->id],
        ])->assertRedirect('/admin/modulos');

        $module->refresh();
        $viewPermission->refresh();

        $this->assertSame('Clientes corporativos', $module->name);
        $this->assertSame('clientes-corporativos', $module->slug);
        $this->assertTrue($module->is_system);
        $this->assertEqualsCanonicalizing(
            [$viewAction->id, $updateOthersAction->id],
            $module->actions()->pluck('permission_actions.id')->all()
        );
        $this->assertSame('clientes-corporativos.view', $viewPermission->slug);
        $this->assertDatabaseMissing('permissions', ['id' => $createPermission->id]);
    }

    public function test_module_can_be_deleted_from_the_admin_panel(): void
    {
        $this->seed();
        $this->signInAsAdmin();

        $module = Module::query()->where('slug', 'inventory')->firstOrFail();
        $permission = Permission::query()->where('slug', 'inventory.view')->firstOrFail();

        $this->delete('/admin/modulos/'.$module->id)
            ->assertRedirect('/admin/modulos');

        $this->assertDatabaseMissing('modules', ['id' => $module->id]);
        $this->assertDatabaseMissing('permissions', ['id' => $permission->id]);
    }

    public function test_permission_can_be_created_and_updated_from_module_and_action_selects(): void
    {
        $this->seed();
        $this->signInAsAdmin();

        $module = Module::query()->create([
            'name' => 'Clientes',
            'slug' => 'clientes',
            'description' => 'Gestion de clientes.',
        ]);

        $viewAction = PermissionAction::query()->where('slug', 'view')->firstOrFail();
        $updateOthersAction = PermissionAction::query()->where('slug', 'update.others')->firstOrFail();
        $module->actions()->sync([$viewAction->id, $updateOthersAction->id]);

        $this->post('/admin/permisos', [
            'name' => 'Ver clientes',
            'module_id' => $module->id,
            'permission_action_id' => $viewAction->id,
            'description' => 'Consulta el listado de clientes.',
        ])->assertRedirect('/admin/permisos');

        $permission = Permission::query()->where('slug', 'clientes.view')->firstOrFail();

        $this->assertSame($module->id, $permission->module_id);
        $this->assertSame($viewAction->id, $permission->permission_action_id);
        $this->assertSame('Ver clientes', $permission->name);

        $this->put('/admin/permisos/'.$permission->id, [
            'name' => 'Editar clientes de otros usuarios',
            'module_id' => $module->id,
            'permission_action_id' => $updateOthersAction->id,
            'description' => 'Gestiona registros ajenos.',
        ])->assertRedirect('/admin/permisos');

        $permission->refresh();

        $this->assertSame('clientes.update.others', $permission->slug);
        $this->assertSame('Editar clientes de otros usuarios', $permission->name);
        $this->assertSame('Gestiona registros ajenos.', $permission->description);
    }

    public function test_permission_rejects_actions_not_enabled_for_the_selected_module(): void
    {
        $this->seed();
        $this->signInAsAdmin();

        $module = Module::query()->create([
            'name' => 'Prospectos',
            'slug' => 'prospectos',
            'description' => 'Gestion comercial basica.',
        ]);

        $viewAction = PermissionAction::query()->where('slug', 'view')->firstOrFail();
        $deleteAction = PermissionAction::query()->where('slug', 'delete')->firstOrFail();
        $module->actions()->sync([$viewAction->id]);

        $this->from('/admin/permisos')
            ->post('/admin/permisos', [
                'name' => 'Eliminar prospectos',
                'module_id' => $module->id,
                'permission_action_id' => $deleteAction->id,
                'description' => 'No deberia pasar.',
            ])
            ->assertRedirect('/admin/permisos')
            ->assertSessionHasErrorsIn('permissionCreate', ['permission_action_id']);

        $this->assertDatabaseMissing('permissions', [
            'slug' => 'prospectos.delete',
        ]);
    }

    public function test_permission_can_be_deleted_from_the_admin_panel(): void
    {
        $this->seed();
        $this->signInAsAdmin();

        $permission = Permission::query()->where('slug', 'users.view')->firstOrFail();
        $role = Role::query()->where('slug', 'admin')->firstOrFail();

        $this->assertDatabaseHas('permission_role', [
            'permission_id' => $permission->id,
            'role_id' => $role->id,
        ]);

        $this->delete('/admin/permisos/'.$permission->id)
            ->assertRedirect('/admin/permisos');

        $this->assertDatabaseMissing('permissions', ['id' => $permission->id]);
        $this->assertDatabaseMissing('permission_role', [
            'permission_id' => $permission->id,
            'role_id' => $role->id,
        ]);
    }

    private function signInAsAdmin(): void
    {
        $user = User::query()->where('email', 'admin@echoserp.test')->firstOrFail();

        $this->actingAs($user);
    }
}
