<?php

namespace Database\Seeders;

use App\Models\Permission;
use App\Models\Role;
use App\Models\User;
use Illuminate\Database\Seeder;
use Illuminate\Support\Arr;
use Illuminate\Support\Facades\Hash;

class AccessControlSeeder extends Seeder
{
    /**
     * Seed the application's database.
     */
    public function run(): void
    {
        $permissionIds = Permission::query()
            ->get()
            ->mapWithKeys(static fn (Permission $permission): array => [$permission->slug => $permission->id]);

        $roles = collect([
            'super-admin' => [
                'name' => 'Super administrador',
                'description' => 'Control total del panel y de la configuracion base.',
                'is_system' => true,
                'permissions' => $permissionIds->keys()->all(),
            ],
            'admin' => [
                'name' => 'Administrador',
                'description' => 'Gestion operativa de usuarios, roles y ajustes generales.',
                'is_system' => true,
                'permissions' => [
                    'dashboard.view',
                    'users.view',
                    'users.create',
                    'users.update',
                    'users.assign',
                    'roles.view',
                    'roles.update',
                    'permissions.view',
                    'modules.view',
                    'modules.update',
                    'settings.view',
                    'settings.update',
                    'customers.view',
                    'customers.create',
                    'customers.update',
                    'customers.delete',
                    'quotations.view',
                    'quotations.create',
                    'quotations.update',
                    'quotations.delete',
                ],
            ],
            'editor' => [
                'name' => 'Editor',
                'description' => 'Mantiene datos operativos sin administrar la seguridad completa.',
                'is_system' => false,
                'permissions' => [
                    'dashboard.view',
                    'users.view',
                    'roles.view',
                    'permissions.view',
                    'modules.view',
                    'customers.view',
                    'customers.update',
                    'quotations.view',
                    'quotations.update',
                ],
            ],
            'viewer' => [
                'name' => 'Consulta',
                'description' => 'Solo lectura para reportes y revision del panel.',
                'is_system' => false,
                'permissions' => [
                    'dashboard.view',
                    'permissions.view',
                    'reports.view',
                    'customers.view',
                    'quotations.view',
                ],
            ],
        ])->mapWithKeys(function (array $data, string $slug) use ($permissionIds): array {
            $role = Role::query()->updateOrCreate(
                ['slug' => $slug],
                Arr::except($data, ['permissions'])
            );

            $role->permissions()->sync(
                collect($data['permissions'])
                    ->map(static fn (string $permissionSlug): ?int => $permissionIds->get($permissionSlug))
                    ->filter()
                    ->values()
                    ->all()
            );

            return [$slug => $role];
        });

        $existingUsers = User::query()
            ->orderBy('id')
            ->get();

        if ($existingUsers->isEmpty()) {
            $existingUsers = collect([
                ['name' => 'Administrador Echo', 'email' => 'admin@echoserp.test', 'role' => 'super-admin', 'verified' => true],
                ['name' => 'Coordinacion Operativa', 'email' => 'operaciones@echoserp.test', 'role' => 'admin', 'verified' => true],
                ['name' => 'Supervisor de Sede', 'email' => 'supervisor@echoserp.test', 'role' => 'editor', 'verified' => true],
                ['name' => 'Analista Consulta', 'email' => 'consulta@echoserp.test', 'role' => 'viewer', 'verified' => false],
            ])->map(function (array $data): User {
                return User::query()->updateOrCreate(
                    ['email' => $data['email']],
                    [
                        'name' => $data['name'],
                        'password' => Hash::make('password'),
                        'email_verified_at' => $data['verified'] ? now() : null,
                    ]
                );
            });
        }

        $defaultRoles = ['super-admin', 'admin', 'editor', 'viewer'];

        $existingUsers->values()->each(function (User $user, int $index) use ($roles, $defaultRoles): void {
            if ($user->roles()->exists()) {
                return;
            }

            $fallbackRole = $roles->get($defaultRoles[$index % count($defaultRoles)]);

            if ($fallbackRole instanceof Role) {
                $user->roles()->syncWithoutDetaching([$fallbackRole->id]);
            }
        });
    }
}
