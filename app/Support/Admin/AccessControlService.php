<?php

namespace App\Support\Admin;

use App\Models\Module;
use App\Models\Permission;
use App\Models\PermissionAction;
use App\Models\Role;
use App\Models\User;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Schema;

class AccessControlService
{
    public function isReady(): bool
    {
        return collect([
            'users',
            'modules',
            'permission_actions',
            'module_permission_action',
            'roles',
            'permissions',
            'role_user',
            'permission_role',
        ])->every(static fn (string $table): bool => Schema::hasTable($table));
    }

    public function dashboardData(): array
    {
        if (! $this->isReady()) {
            return [
                'isReady' => false,
                'stats' => [
                    ['value' => '00', 'label' => 'Usuarios activos', 'detail' => 'Ejecuta la migracion para activar el modulo.'],
                    ['value' => '00', 'label' => 'Roles activos', 'detail' => 'La estructura RBAC ya esta integrada en codigo.'],
                    ['value' => '00', 'label' => 'Permisos definidos', 'detail' => 'Puedes centralizar nuevas vistas desde un solo lugar.'],
                    ['value' => '00', 'label' => 'Modulos activos', 'detail' => 'El catalogo nuevo organiza acciones y slugs.'],
                ],
                'modules' => $this->moduleLinks(),
                'roadmap' => $this->roadmap(),
                'highlights' => [
                    ['label' => 'Modelo RBAC', 'value' => 'Listo'],
                    ['label' => 'Catalogo modular', 'value' => 'Pendiente'],
                    ['label' => 'Selects conectados', 'value' => 'Pendiente'],
                    ['label' => 'Navegacion', 'value' => 'Centralizada'],
                ],
            ];
        }

        $users = User::query()->with('roles:id,name,slug')->get();
        $roles = Role::query()->withCount('permissions')->get();
        $permissions = Permission::query()->count();
        $modules = Module::query()->count();

        $verifiedUsers = $users->filter(static fn (User $user): bool => $user->email_verified_at !== null)->count();
        $usersWithRoles = $users->filter(static fn (User $user): bool => $user->roles->isNotEmpty())->count();
        $rolesWithPermissions = $roles->filter(static fn (Role $role): bool => $role->permissions_count > 0)->count();
        $coverage = $roles->isEmpty()
            ? 0
            : (int) round(($rolesWithPermissions / $roles->count()) * 100);

        $topRole = $roles
            ->sortByDesc('permissions_count')
            ->first();

        return [
            'isReady' => true,
            'stats' => [
                [
                    'value' => $this->formatInteger($users->count()),
                    'label' => 'Usuarios activos',
                    'detail' => $verifiedUsers.' con correo verificado.',
                ],
                [
                    'value' => $this->formatInteger($roles->count()),
                    'label' => 'Roles activos',
                    'detail' => $rolesWithPermissions.' con permisos asignados.',
                ],
                [
                    'value' => $this->formatInteger($permissions),
                    'label' => 'Permisos definidos',
                    'detail' => $modules.' modulos cubiertos.',
                ],
                [
                    'value' => $this->formatInteger($modules),
                    'label' => 'Modulos activos',
                    'detail' => 'Catalogo listo para acciones por select.',
                ],
            ],
            'modules' => $this->moduleLinks($users->count(), $roles->count(), $permissions, $modules),
            'roadmap' => $this->roadmap(),
            'highlights' => [
                ['label' => 'Usuarios con rol', 'value' => $usersWithRoles.'/'.$users->count()],
                ['label' => 'Rol mas amplio', 'value' => $topRole?->name ?? 'Sin datos'],
                ['label' => 'Modulos cubiertos', 'value' => (string) $modules],
                ['label' => 'Estado', 'value' => 'Operativo'],
            ],
        ];
    }

    public function usersData(): array
    {
        if (! $this->isReady()) {
            return [
                'isReady' => false,
                'metrics' => $this->emptyMetrics(
                    'Usuarios',
                    'Roles por usuario',
                    'Roles en uso'
                ),
                'users' => collect(),
                'roleOptions' => collect(),
                'roleDistribution' => collect(),
                'nextSteps' => $this->userNextSteps(),
            ];
        }

        $roleOptions = Role::query()
            ->orderByDesc('is_system')
            ->orderBy('name')
            ->get()
            ->map(static fn (Role $role): array => [
                'id' => $role->id,
                'name' => $role->name,
                'slug' => $role->slug,
                'description' => $role->description,
                'is_system' => $role->is_system,
            ]);

        $users = User::query()
            ->with(['roles.permissions.module'])
            ->orderBy('name')
            ->get()
            ->map(function (User $user): array {
                $roles = $user->roles
                    ->sortByDesc('is_system')
                    ->values();

                $permissions = $roles
                    ->flatMap(static fn (Role $role) => $role->permissions)
                    ->unique('id')
                    ->values();

                return [
                    'id' => $user->id,
                    'name' => $user->name,
                    'email' => $user->email,
                    'email_verified' => $user->email_verified_at !== null,
                    'status_label' => $user->email_verified_at ? 'Verificado' : 'Pendiente',
                    'status_key' => $user->email_verified_at ? 'success' : 'warning',
                    'roles' => $roles->map(static fn (Role $role): array => [
                        'id' => $role->id,
                        'name' => $role->name,
                        'slug' => $role->slug,
                        'is_system' => $role->is_system,
                    ])->values(),
                    'role_ids' => $roles->pluck('id')->values()->all(),
                    'permissions_count' => $permissions->count(),
                    'permission_modules_count' => $permissions->pluck('module_id')->filter()->unique()->count(),
                    'created_at' => $user->created_at?->format('d/m/Y') ?? 'Sin fecha',
                ];
            });

        $roles = Role::query()->withCount('users')->orderByDesc('users_count')->orderBy('name')->get();

        $verifiedUsers = $users->where('status_key', 'success')->count();
        $pendingUsers = $users->count() - $verifiedUsers;
        $averageRoles = $users->isEmpty()
            ? 0
            : round($users->avg(static fn (array $user): float => (float) count($user['roles'])), 1);
        $multiRoleUsers = $users->filter(static fn (array $user): bool => count($user['roles']) > 1)->count();

        return [
            'isReady' => true,
            'metrics' => [
                [
                    'value' => $this->formatInteger($users->count()),
                    'label' => 'Usuarios',
                    'detail' => $verifiedUsers.' verificados y '.$pendingUsers.' pendientes.',
                ],
                [
                    'value' => number_format($averageRoles, 1),
                    'label' => 'Roles por usuario',
                    'detail' => $multiRoleUsers.' cuentas con multiples perfiles.',
                ],
                [
                    'value' => $this->formatInteger($roles->where('users_count', '>', 0)->count()),
                    'label' => 'Roles en uso',
                    'detail' => $roles->count().' roles configurados en total.',
                ],
            ],
            'users' => $users,
            'roleOptions' => $roleOptions,
            'roleDistribution' => $roles
                ->filter(static fn (Role $role): bool => $role->users_count > 0)
                ->map(static fn (Role $role): array => [
                    'name' => $role->name,
                    'description' => $role->description,
                    'users_count' => $role->users_count,
                ])
                ->values(),
            'nextSteps' => $this->userNextSteps(),
        ];
    }

    public function rolesData(): array
    {
        if (! $this->isReady()) {
            return [
                'isReady' => false,
                'metrics' => $this->emptyMetrics(
                    'Roles',
                    'Roles del sistema',
                    'Permisos por rol'
                ),
                'roles' => collect(),
                'permissionOptions' => collect(),
                'moduleCoverage' => collect(),
                'nextSteps' => $this->roleNextSteps(),
            ];
        }

        $permissionOptions = $this->permissionOptions();

        $roles = Role::query()
            ->withCount(['users', 'permissions'])
            ->with(['permissions.module:id,name,slug'])
            ->orderByDesc('is_system')
            ->orderBy('name')
            ->get()
            ->map(function (Role $role): array {
                $permissions = $role->permissions
                    ->sortBy(static fn (Permission $permission): string => ($permission->module?->name ?? '').'-'.$permission->name)
                    ->values();

                return [
                    'id' => $role->id,
                    'name' => $role->name,
                    'slug' => $role->slug,
                    'description' => $role->description,
                    'is_system' => $role->is_system,
                    'users_count' => $role->users_count,
                    'permissions_count' => $role->permissions_count,
                    'module_count' => $permissions->pluck('module_id')->filter()->unique()->count(),
                    'permission_ids' => $permissions->pluck('id')->values()->all(),
                    'preview_permissions' => $permissions
                        ->take(4)
                        ->map(static fn (Permission $permission): array => [
                            'name' => $permission->name,
                            'slug' => $permission->slug,
                        ])
                        ->values(),
                    'remaining_permissions' => max($permissions->count() - 4, 0),
                ];
            });

        $permissionGroups = Permission::query()
            ->with(['roles:id,name', 'module:id,name,slug'])
            ->orderBy('module_id')
            ->get()
            ->groupBy(static fn (Permission $permission): string => (string) $permission->module_id);

        return [
            'isReady' => true,
            'metrics' => [
                [
                    'value' => $this->formatInteger($roles->count()),
                    'label' => 'Roles',
                    'detail' => $roles->where('users_count', '>', 0)->count().' con usuarios asignados.',
                ],
                [
                    'value' => $this->formatInteger($roles->where('is_system', true)->count()),
                    'label' => 'Roles del sistema',
                    'detail' => 'Pensados para arrancar sin rehacer permisos.',
                ],
                [
                    'value' => number_format($roles->avg('permissions_count') ?? 0, 1),
                    'label' => 'Permisos por rol',
                    'detail' => 'Promedio actual de cobertura por perfil.',
                ],
            ],
            'roles' => $roles,
            'permissionOptions' => $permissionOptions,
            'moduleCoverage' => $permissionGroups
                ->map(function (Collection $permissions): array {
                    $module = $permissions->first()?->module;

                    return [
                        'label' => $module?->name ?? 'Sin modulo',
                        'permissions_count' => $permissions->count(),
                        'roles_count' => $permissions
                            ->flatMap(static fn (Permission $permission) => $permission->roles)
                            ->unique('id')
                            ->count(),
                    ];
                })
                ->sortByDesc('roles_count')
                ->values(),
            'nextSteps' => $this->roleNextSteps(),
        ];
    }

    public function modulesData(): array
    {
        if (! $this->isReady()) {
            return [
                'isReady' => false,
                'metrics' => $this->emptyMetrics(
                    'Modulos',
                    'Acciones por modulo',
                    'Permisos vinculados'
                ),
                'modules' => collect(),
                'actionOptions' => collect(),
                'nextSteps' => $this->moduleNextSteps(),
            ];
        }

        $actionOptions = $this->actionOptions();

        $modules = Module::query()
            ->withCount(['permissions', 'actions'])
            ->with('actions:id,name,slug,is_system')
            ->orderByDesc('is_system')
            ->orderBy('name')
            ->get()
            ->map(function (Module $module): array {
                $actions = $module->actions
                    ->sortBy('name')
                    ->values();

                return [
                    'id' => $module->id,
                    'name' => $module->name,
                    'slug' => $module->slug,
                    'description' => $module->description,
                    'is_system' => $module->is_system,
                    'permissions_count' => $module->permissions_count,
                    'actions_count' => $module->actions_count,
                    'action_ids' => $actions->pluck('id')->map(static fn (int $id): int => $id)->all(),
                    'actions' => $actions->map(static fn (PermissionAction $action): array => [
                        'id' => $action->id,
                        'name' => $action->name,
                        'slug' => $action->slug,
                        'is_system' => $action->is_system,
                    ])->values(),
                ];
            });

        $averageActions = $modules->isEmpty()
            ? 0
            : round($modules->avg(static fn (array $module): float => (float) $module['actions_count']), 1);

        return [
            'isReady' => true,
            'metrics' => [
                [
                    'value' => $this->formatInteger($modules->count()),
                    'label' => 'Modulos',
                    'detail' => $modules->where('is_system', false)->count().' personalizados.',
                ],
                [
                    'value' => number_format($averageActions, 1),
                    'label' => 'Acciones por modulo',
                    'detail' => $actionOptions->count().' acciones disponibles en catalogo.',
                ],
                [
                    'value' => $this->formatInteger((int) $modules->sum('permissions_count')),
                    'label' => 'Permisos vinculados',
                    'detail' => $modules->filter(static fn (array $module): bool => $module['permissions_count'] > 0)->count().' modulos ya utilizados.',
                ],
            ],
            'modules' => $modules,
            'actionOptions' => $actionOptions,
            'nextSteps' => $this->moduleNextSteps(),
        ];
    }

    public function permissionsData(): array
    {
        if (! $this->isReady()) {
            return [
                'isReady' => false,
                'metrics' => $this->emptyMetrics(
                    'Permisos',
                    'Modulos cubiertos',
                    'Permisos sin rol'
                ),
                'permissionGroups' => collect(),
                'moduleOptions' => collect(),
                'actionOptions' => collect(),
                'nextSteps' => $this->permissionNextSteps(),
            ];
        }

        $permissions = Permission::query()
            ->with(['roles:id,name,slug,is_system', 'module:id,name,slug', 'action:id,name,slug'])
            ->orderBy('module_id')
            ->orderBy('permission_action_id')
            ->orderBy('name')
            ->get();

        $moduleOptions = $this->moduleOptions();

        return [
            'isReady' => true,
            'metrics' => [
                [
                    'value' => $this->formatInteger($permissions->count()),
                    'label' => 'Permisos',
                    'detail' => 'Catalogo central para nuevas vistas y acciones.',
                ],
                [
                    'value' => $this->formatInteger($moduleOptions->count()),
                    'label' => 'Modulos cubiertos',
                    'detail' => 'Agrupados para crecer sin duplicar reglas.',
                ],
                [
                    'value' => $this->formatInteger(
                        $permissions->filter(static fn (Permission $permission): bool => $permission->roles->isEmpty())->count()
                    ),
                    'label' => 'Permisos sin rol',
                    'detail' => 'Sirven para detectar huecos antes de abrir nuevas vistas.',
                ],
            ],
            'permissionGroups' => $permissions
                ->groupBy(static fn (Permission $permission): string => (string) $permission->module_id)
                ->map(function (Collection $modulePermissions): array {
                    $module = $modulePermissions->first()?->module;

                    return [
                        'id' => $module?->id,
                        'label' => $module?->name ?? 'Sin modulo',
                        'key' => $module?->slug ?? 'sin-modulo',
                        'count' => $modulePermissions->count(),
                        'roles_count' => $modulePermissions
                            ->flatMap(static fn (Permission $permission) => $permission->roles)
                            ->unique('id')
                            ->count(),
                        'permissions' => $modulePermissions
                            ->map(static fn (Permission $permission): array => [
                                'id' => $permission->id,
                                'name' => $permission->name,
                                'slug' => $permission->slug,
                                'module_id' => $permission->module_id,
                                'module' => $permission->module?->slug,
                                'module_name' => $permission->module?->name,
                                'action_id' => $permission->permission_action_id,
                                'action' => $permission->action?->slug,
                                'action_name' => $permission->action?->name,
                                'description' => $permission->description,
                                'roles' => $permission->roles
                                    ->sortByDesc('is_system')
                                    ->map(static fn (Role $role): array => [
                                        'name' => $role->name,
                                        'slug' => $role->slug,
                                    ])
                                    ->values(),
                            ])
                            ->values(),
                    ];
                })
                ->values(),
            'moduleOptions' => $moduleOptions,
            'actionOptions' => $this->actionOptions(),
            'nextSteps' => $this->permissionNextSteps(),
        ];
    }

    protected function moduleLinks(
        ?int $users = null,
        ?int $roles = null,
        ?int $permissions = null,
        ?int $modules = null,
    ): array {
        return [
            [
                'title' => 'Usuarios',
                'description' => filled($users)
                    ? 'Gestiona '.$users.' cuentas y sus perfiles de acceso.'
                    : 'Gestion de cuentas, estado y perfiles de acceso.',
                'route' => 'admin.users.index',
            ],
            [
                'title' => 'Roles',
                'description' => filled($roles)
                    ? 'Define '.$roles.' perfiles reutilizables por modulo.'
                    : 'Perfiles reutilizables para cada area del panel.',
                'route' => 'admin.roles.index',
            ],
            [
                'title' => 'Permisos',
                'description' => filled($permissions)
                    ? 'Centraliza '.$permissions.' permisos para nuevas vistas.'
                    : 'Acciones y vistas listas para crecer sin duplicar reglas.',
                'route' => 'admin.permissions.index',
            ],
            [
                'title' => 'Modulos',
                'description' => filled($modules)
                    ? 'Organiza '.$modules.' modulos con acciones disponibles.'
                    : 'Agrupa acciones y slugs desde un catalogo normalizado.',
                'route' => 'admin.modules.index',
            ],
            [
                'title' => 'Cotizaciones',
                'description' => 'Administra cotizaciones, catalogo comercial y configuracion del modulo.',
                'route' => 'admin.quotations.index',
            ],
        ];
    }

    protected function roadmap(): array
    {
        return [
            'Crear modulos y conectar sus acciones disponibles desde un solo formulario.',
            'Generar permisos usando select de modulo y select de accion para evitar slugs inconsistentes.',
            'Aplicar visibilidad real del menu segun rol o permiso cuando cierres la capa de acceso.',
        ];
    }

    protected function userNextSteps(): array
    {
        return [
            'Agregar alta y edicion de usuarios con asignacion de roles desde un formulario.',
            'Proteger vistas futuras usando hasRole() o hasPermission() en el usuario autenticado.',
            'Extender el listado con filtros por estado, sucursal o ultima actividad.',
        ];
    }

    protected function roleNextSteps(): array
    {
        return [
            'Separar roles de sistema y roles personalizados cuando abras el CRUD completo.',
            'Agregar clonacion de roles para acelerar nuevas configuraciones operativas.',
            'Usar estos perfiles para ocultar secciones del menu y acciones sensibles.',
        ];
    }

    protected function moduleNextSteps(): array
    {
        return [
            'Selecciona las acciones que aplican a cada modulo antes de crear permisos concretos.',
            'Mantener slugs cortos y consistentes hara mas limpia la capa de middleware futura.',
            'Si un modulo cambia de alcance, actualiza aqui primero y luego ajusta roles o permisos.',
        ];
    }

    protected function permissionNextSteps(): array
    {
        return [
            'Registrar cada nueva vista del panel con un modulo y una accion del catalogo.',
            'Usar selects evita slugs duplicados y ayuda a mantener acciones coherentes.',
            'Conecta middleware o policies cuando cierres reglas por rol y permiso.',
        ];
    }

    protected function emptyMetrics(string $first, string $second, string $third): array
    {
        return [
            ['value' => '00', 'label' => $first, 'detail' => 'Pendiente de migrar la base de acceso.'],
            ['value' => '0.0', 'label' => $second, 'detail' => 'La estructura ya quedo preparada en codigo.'],
            ['value' => '00', 'label' => $third, 'detail' => 'Ejecuta migrate para activar datos persistidos.'],
        ];
    }

    protected function formatInteger(int $value): string
    {
        return str_pad((string) $value, 2, '0', STR_PAD_LEFT);
    }

    protected function moduleOptions(): Collection
    {
        return Module::query()
            ->withCount(['permissions', 'actions'])
            ->with('actions:id,name,slug,is_system')
            ->orderByDesc('is_system')
            ->orderBy('name')
            ->get()
            ->map(function (Module $module): array {
                $actions = $module->actions
                    ->sortBy('name')
                    ->values();

                return [
                    'id' => $module->id,
                    'name' => $module->name,
                    'slug' => $module->slug,
                    'description' => $module->description,
                    'is_system' => $module->is_system,
                    'permissions_count' => $module->permissions_count,
                    'actions_count' => $module->actions_count,
                    'action_ids' => $actions->pluck('id')->map(static fn (int $id): int => $id)->all(),
                    'actions' => $actions->map(static fn (PermissionAction $action): array => [
                        'id' => $action->id,
                        'name' => $action->name,
                        'slug' => $action->slug,
                        'is_system' => $action->is_system,
                    ])->values(),
                ];
            });
    }

    protected function actionOptions(): Collection
    {
        return PermissionAction::query()
            ->orderByDesc('is_system')
            ->orderBy('name')
            ->get()
            ->map(static fn (PermissionAction $action): array => [
                'id' => $action->id,
                'name' => $action->name,
                'slug' => $action->slug,
                'description' => $action->description,
                'is_system' => $action->is_system,
            ]);
    }

    protected function permissionOptions(): Collection
    {
        return Permission::query()
            ->with('module:id,name,slug')
            ->orderBy('module_id')
            ->orderBy('name')
            ->get()
            ->groupBy(static fn (Permission $permission): string => (string) $permission->module_id)
            ->map(function (Collection $permissions): array {
                $module = $permissions->first()?->module;

                return [
                    'key' => $module?->slug ?? 'sin-modulo',
                    'label' => $module?->name ?? 'Sin modulo',
                    'permissions' => $permissions->map(static fn (Permission $permission): array => [
                        'id' => $permission->id,
                        'name' => $permission->name,
                        'slug' => $permission->slug,
                        'description' => $permission->description,
                    ])->values(),
                ];
            })
            ->values();
    }
}
