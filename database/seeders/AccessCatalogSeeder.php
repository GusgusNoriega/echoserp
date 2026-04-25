<?php

namespace Database\Seeders;

use App\Models\Module;
use App\Models\Permission;
use App\Models\PermissionAction;
use Illuminate\Database\Seeder;

class AccessCatalogSeeder extends Seeder
{
    /**
     * Seed the access catalog for modules, actions, and permissions.
     */
    public function run(): void
    {
        $actions = collect([
            ['slug' => 'view', 'name' => 'Ver', 'description' => 'Consulta registros o vistas.', 'is_system' => true],
            ['slug' => 'create', 'name' => 'Crear', 'description' => 'Registra nuevos elementos.', 'is_system' => true],
            ['slug' => 'update', 'name' => 'Editar', 'description' => 'Actualiza informacion existente.', 'is_system' => true],
            ['slug' => 'delete', 'name' => 'Eliminar', 'description' => 'Elimina registros existentes.', 'is_system' => true],
            ['slug' => 'view.others', 'name' => 'Ver de otros usuarios', 'description' => 'Consulta registros ajenos.', 'is_system' => true],
            ['slug' => 'update.others', 'name' => 'Editar de otros usuarios', 'description' => 'Edita registros ajenos.', 'is_system' => true],
            ['slug' => 'delete.others', 'name' => 'Eliminar de otros usuarios', 'description' => 'Elimina registros ajenos.', 'is_system' => true],
            ['slug' => 'assign', 'name' => 'Asignar', 'description' => 'Asigna relaciones, estados o responsables.', 'is_system' => true],
            ['slug' => 'export', 'name' => 'Exportar', 'description' => 'Exporta informacion del modulo.', 'is_system' => true],
            ['slug' => 'manage', 'name' => 'Gestion completa', 'description' => 'Control amplio sobre el modulo.', 'is_system' => true],
        ])->mapWithKeys(function (array $data): array {
            $action = PermissionAction::query()->updateOrCreate(
                ['slug' => $data['slug']],
                [
                    'name' => $data['name'],
                    'description' => $data['description'],
                    'is_system' => $data['is_system'],
                ]
            );

            return [$data['slug'] => $action];
        });

        $modules = collect([
            'dashboard' => [
                'name' => 'Dashboard',
                'description' => 'Panel principal del sistema.',
                'is_system' => true,
                'actions' => ['view'],
            ],
            'users' => [
                'name' => 'Usuarios',
                'description' => 'Gestion de cuentas, acceso y seguridad operativa.',
                'is_system' => true,
                'actions' => ['view', 'create', 'update', 'delete', 'view.others', 'update.others', 'delete.others', 'assign'],
            ],
            'roles' => [
                'name' => 'Roles',
                'description' => 'Perfiles reutilizables por modulo.',
                'is_system' => true,
                'actions' => ['view', 'create', 'update', 'delete'],
            ],
            'permissions' => [
                'name' => 'Permisos',
                'description' => 'Catalogo de acciones y cobertura del panel.',
                'is_system' => true,
                'actions' => ['view', 'create', 'update', 'delete'],
            ],
            'modules' => [
                'name' => 'Modulos',
                'description' => 'Catalogo estructural para agrupar permisos.',
                'is_system' => true,
                'actions' => ['view', 'create', 'update', 'delete'],
            ],
            'branches' => [
                'name' => 'Sucursales',
                'description' => 'Sedes, responsables y cobertura operativa.',
                'is_system' => true,
                'actions' => ['view', 'create', 'update', 'delete'],
            ],
            'inventory' => [
                'name' => 'Inventario',
                'description' => 'Productos, stock y alertas operativas.',
                'is_system' => true,
                'actions' => ['view', 'create', 'update', 'delete', 'export'],
            ],
            'sales' => [
                'name' => 'Ventas',
                'description' => 'Pedidos, estados y gestion comercial.',
                'is_system' => true,
                'actions' => ['view', 'create', 'update', 'delete', 'export'],
            ],
            'customers' => [
                'name' => 'Clientes',
                'description' => 'Datos comerciales para reutilizar en cotizaciones y ventas.',
                'is_system' => true,
                'actions' => ['view', 'create', 'update', 'delete', 'export'],
            ],
            'quotations' => [
                'name' => 'Cotizaciones',
                'description' => 'Cotizaciones comerciales, catalogo reutilizable y configuracion documental.',
                'is_system' => true,
                'actions' => ['view', 'create', 'update', 'delete'],
            ],
            'reports' => [
                'name' => 'Reportes',
                'description' => 'Analitica, comparativos y exportaciones.',
                'is_system' => true,
                'actions' => ['view', 'export'],
            ],
            'settings' => [
                'name' => 'Configuracion',
                'description' => 'Parametros globales del sistema y la interfaz.',
                'is_system' => true,
                'actions' => ['view', 'update', 'manage'],
            ],
        ])->mapWithKeys(function (array $data, string $slug) use ($actions): array {
            $module = Module::query()->updateOrCreate(
                ['slug' => $slug],
                [
                    'name' => $data['name'],
                    'description' => $data['description'],
                    'is_system' => $data['is_system'],
                ]
            );

            $module->actions()->sync(
                $actions
                    ->only($data['actions'])
                    ->pluck('id')
                    ->all()
            );

            return [$slug => $module];
        });

        $permissionBlueprints = [
            'dashboard.view' => ['name' => 'Ver dashboard', 'description' => 'Acceso al panel principal.'],
            'users.view' => ['name' => 'Ver usuarios', 'description' => 'Consulta el listado de usuarios.'],
            'users.create' => ['name' => 'Crear usuarios', 'description' => 'Registra nuevas cuentas de acceso.'],
            'users.update' => ['name' => 'Editar usuarios', 'description' => 'Actualiza datos o roles del usuario.'],
            'users.delete' => ['name' => 'Eliminar usuarios', 'description' => 'Elimina cuentas del sistema.'],
            'users.view.others' => ['name' => 'Ver usuarios de otros responsables', 'description' => 'Consulta cuentas o datos ajenos.'],
            'users.update.others' => ['name' => 'Editar usuarios de otros responsables', 'description' => 'Modifica cuentas ajenas.'],
            'users.delete.others' => ['name' => 'Eliminar usuarios de otros responsables', 'description' => 'Elimina cuentas ajenas.'],
            'users.assign' => ['name' => 'Asignar roles a usuarios', 'description' => 'Relaciona perfiles y acceso.'],
            'roles.view' => ['name' => 'Ver roles', 'description' => 'Consulta perfiles de acceso.'],
            'roles.create' => ['name' => 'Crear roles', 'description' => 'Define nuevos perfiles reutilizables.'],
            'roles.update' => ['name' => 'Editar roles', 'description' => 'Ajusta permisos y descripciones del rol.'],
            'roles.delete' => ['name' => 'Eliminar roles', 'description' => 'Elimina perfiles del catalogo.'],
            'permissions.view' => ['name' => 'Ver permisos', 'description' => 'Consulta el catalogo de permisos.'],
            'permissions.create' => ['name' => 'Crear permisos', 'description' => 'Registra permisos para nuevas vistas o acciones.'],
            'permissions.update' => ['name' => 'Editar permisos', 'description' => 'Actualiza slugs, nombres o cobertura.'],
            'permissions.delete' => ['name' => 'Eliminar permisos', 'description' => 'Quita permisos del catalogo.'],
            'modules.view' => ['name' => 'Ver modulos', 'description' => 'Consulta el catalogo de modulos.'],
            'modules.create' => ['name' => 'Crear modulos', 'description' => 'Registra nuevos modulos para permisos.'],
            'modules.update' => ['name' => 'Editar modulos', 'description' => 'Actualiza modulo, slug y acciones disponibles.'],
            'modules.delete' => ['name' => 'Eliminar modulos', 'description' => 'Elimina modulos del catalogo.'],
            'settings.view' => ['name' => 'Ver configuracion', 'description' => 'Consulta ajustes generales y de apariencia.'],
            'settings.update' => ['name' => 'Editar configuracion', 'description' => 'Actualiza parametros del sistema.'],
            'branches.view' => ['name' => 'Ver sucursales', 'description' => 'Consulta sedes y responsables.'],
            'branches.create' => ['name' => 'Crear sucursales', 'description' => 'Registra nuevas sedes.'],
            'branches.update' => ['name' => 'Editar sucursales', 'description' => 'Actualiza datos operativos por sede.'],
            'branches.delete' => ['name' => 'Eliminar sucursales', 'description' => 'Elimina sedes del catalogo.'],
            'inventory.view' => ['name' => 'Ver inventario', 'description' => 'Consulta productos y stock.'],
            'inventory.create' => ['name' => 'Crear inventario', 'description' => 'Registra productos o entradas.'],
            'inventory.update' => ['name' => 'Editar inventario', 'description' => 'Actualiza productos y movimientos.'],
            'inventory.delete' => ['name' => 'Eliminar inventario', 'description' => 'Elimina productos o movimientos.'],
            'inventory.export' => ['name' => 'Exportar inventario', 'description' => 'Descarga reportes de stock.'],
            'sales.view' => ['name' => 'Ver ventas', 'description' => 'Consulta pedidos y estados comerciales.'],
            'sales.create' => ['name' => 'Crear ventas', 'description' => 'Registra nuevos pedidos o ventas.'],
            'sales.update' => ['name' => 'Editar ventas', 'description' => 'Actualiza pedidos y estados.'],
            'sales.delete' => ['name' => 'Eliminar ventas', 'description' => 'Elimina ventas o pedidos.'],
            'sales.export' => ['name' => 'Exportar ventas', 'description' => 'Descarga reportes comerciales.'],
            'customers.view' => ['name' => 'Ver clientes', 'description' => 'Consulta clientes comerciales.'],
            'customers.create' => ['name' => 'Crear clientes', 'description' => 'Registra nuevos clientes comerciales.'],
            'customers.update' => ['name' => 'Editar clientes', 'description' => 'Actualiza datos de clientes.'],
            'customers.delete' => ['name' => 'Eliminar clientes', 'description' => 'Elimina clientes del catalogo comercial.'],
            'customers.export' => ['name' => 'Exportar clientes', 'description' => 'Descarga reportes de clientes.'],
            'quotations.view' => ['name' => 'Ver cotizaciones', 'description' => 'Consulta cotizaciones, catalogo y configuracion del modulo.'],
            'quotations.create' => ['name' => 'Crear cotizaciones', 'description' => 'Registra nuevas cotizaciones o elementos reutilizables del modulo.'],
            'quotations.update' => ['name' => 'Editar cotizaciones', 'description' => 'Actualiza documentos, terminos, catalogo o configuracion asociada.'],
            'quotations.delete' => ['name' => 'Eliminar cotizaciones', 'description' => 'Elimina cotizaciones o registros auxiliares del modulo.'],
            'reports.view' => ['name' => 'Ver reportes', 'description' => 'Consulta analitica y reportes.'],
            'reports.export' => ['name' => 'Exportar reportes', 'description' => 'Descarga reportes consolidados.'],
        ];

        collect($permissionBlueprints)->each(function (array $data, string $slug) use ($modules, $actions): void {
            [$moduleSlug, $actionSlug] = explode('.', $slug, 2);

            $module = $modules->get($moduleSlug);
            $action = $actions->get($actionSlug);

            if (! $module instanceof Module || ! $action instanceof PermissionAction) {
                return;
            }

            Permission::query()->updateOrCreate(
                ['slug' => $slug],
                [
                    'name' => $data['name'],
                    'module_id' => $module->id,
                    'permission_action_id' => $action->id,
                    'description' => $data['description'] ?? null,
                ]
            );
        });
    }
}
