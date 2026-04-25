<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Module;
use App\Models\Permission;
use App\Models\PermissionAction;
use App\Support\Admin\AccessControlService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;
use Illuminate\View\View;

class PermissionController extends Controller
{
    public function __construct(
        private readonly AccessControlService $accessControl,
    ) {
    }

    public function index(): View
    {
        return view('admin.permissions.index', [
            'eyebrow' => 'Administracion',
            'pageTitle' => 'Permisos',
            'pageDescription' => 'Catalogo central de acciones para que el panel siga creciendo con reglas claras.',
            ...$this->accessControl->permissionsData(),
        ]);
    }

    public function store(Request $request): RedirectResponse
    {
        if ($redirect = $this->ensureAccessControlIsReady()) {
            return $redirect;
        }

        $validator = $this->makeValidator($request);

        if ($validator->fails()) {
            return redirect()
                ->back()
                ->withErrors($validator, 'permissionCreate')
                ->withInput()
                ->with('modal', 'permission-create-modal');
        }

        $validated = $validator->validated();
        $module = Module::query()->findOrFail($validated['module_id']);
        $action = PermissionAction::query()->findOrFail($validated['permission_action_id']);

        Permission::query()->create([
            'name' => $validated['name'],
            'module_id' => $module->id,
            'permission_action_id' => $action->id,
            'slug' => $this->composePermissionSlug($module->slug, $action->slug),
            'description' => $validated['description'] ?? null,
        ]);

        return redirect()
            ->route('admin.permissions.index')
            ->with('status', 'Permiso creado correctamente.');
    }

    public function update(Request $request, string $permission): RedirectResponse
    {
        if ($redirect = $this->ensureAccessControlIsReady()) {
            return $redirect;
        }

        $permissionModel = Permission::query()->findOrFail($permission);
        $validator = $this->makeValidator($request, $permissionModel);

        if ($validator->fails()) {
            return redirect()
                ->back()
                ->withErrors($validator, 'permissionEdit')
                ->withInput()
                ->with('modal', 'permission-edit-modal-'.$permissionModel->id);
        }

        $validated = $validator->validated();
        $moduleModel = Module::query()->findOrFail($validated['module_id']);
        $actionModel = PermissionAction::query()->findOrFail($validated['permission_action_id']);

        $permissionModel->update([
            'name' => $validated['name'],
            'module_id' => $moduleModel->id,
            'permission_action_id' => $actionModel->id,
            'slug' => $this->composePermissionSlug($moduleModel->slug, $actionModel->slug),
            'description' => $validated['description'] ?? null,
        ]);

        return redirect()
            ->route('admin.permissions.index')
            ->with('status', 'Permiso actualizado correctamente.');
    }

    public function destroy(string $permission): RedirectResponse
    {
        if ($redirect = $this->ensureAccessControlIsReady()) {
            return $redirect;
        }

        $permissionModel = Permission::query()->findOrFail($permission);
        $permissionModel->delete();

        return redirect()
            ->route('admin.permissions.index')
            ->with('status', 'Permiso eliminado correctamente.');
    }

    private function makeValidator(Request $request, ?Permission $permission = null)
    {
        $validator = Validator::make($request->all(), [
            'name' => ['required', 'string', 'max:255'],
            'module_id' => ['required', 'integer', 'exists:modules,id'],
            'permission_action_id' => ['required', 'integer', 'exists:permission_actions,id'],
            'description' => ['nullable', 'string', 'max:255'],
        ]);

        $validator->after(function ($validator) use ($request, $permission): void {
            $module = Module::query()
                ->with('actions:id')
                ->find($request->input('module_id'));
            $action = PermissionAction::query()->find($request->input('permission_action_id'));

            if (! $module instanceof Module || ! $action instanceof PermissionAction) {
                $validator->errors()->add('permission_action_id', 'Debes definir modulo y accion validos.');

                return;
            }

            if (! $module->actions->contains('id', $action->id)) {
                $validator->errors()->add('permission_action_id', 'La accion elegida no esta disponible para el modulo seleccionado.');

                return;
            }

            $slug = $this->composePermissionSlug($module->slug, $action->slug);
            $query = Permission::query()->where('slug', $slug);

            if ($permission instanceof Permission) {
                $query->whereKeyNot($permission->id);
            }

            if ($query->exists()) {
                $validator->errors()->add('permission_action_id', 'Ya existe un permiso con ese modulo y accion.');
            }
        });

        return $validator;
    }

    private function composePermissionSlug(string $moduleSlug, string $actionSlug): string
    {
        return trim($moduleSlug.'.'.$actionSlug, '.');
    }

    private function ensureAccessControlIsReady(): ?RedirectResponse
    {
        if ($this->accessControl->isReady()) {
            return null;
        }

        return redirect()
            ->route('admin.permissions.index')
            ->with('error', 'Ejecuta las migraciones del modulo antes de crear o editar permisos.');
    }
}
