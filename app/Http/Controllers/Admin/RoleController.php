<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Role;
use App\Support\Admin\AccessControlService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Str;
use Illuminate\View\View;

class RoleController extends Controller
{
    public function __construct(
        private readonly AccessControlService $accessControl,
    ) {
    }

    public function index(): View
    {
        return view('admin.roles.index', [
            'eyebrow' => 'Administracion',
            'pageTitle' => 'Roles',
            'pageDescription' => 'Perfiles reutilizables para controlar acceso por modulo sin tocar cada pantalla.',
            ...$this->accessControl->rolesData(),
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
                ->withErrors($validator, 'roleCreate')
                ->withInput()
                ->with('modal', 'role-create-modal');
        }

        $validated = $validator->validated();

        $role = Role::query()->create([
            'name' => $validated['name'],
            'slug' => $this->normalizeRoleSlug($validated['slug'] ?? $validated['name']),
            'description' => $validated['description'] ?? null,
            'is_system' => $request->boolean('is_system'),
        ]);

        $role->permissions()->sync($validated['permission_ids'] ?? []);

        return redirect()
            ->route('admin.roles.index')
            ->with('status', 'Rol creado correctamente.');
    }

    public function update(Request $request, string $role): RedirectResponse
    {
        if ($redirect = $this->ensureAccessControlIsReady()) {
            return $redirect;
        }

        $roleModel = Role::query()->findOrFail($role);
        $validator = $this->makeValidator($request, $roleModel);

        if ($validator->fails()) {
            return redirect()
                ->back()
                ->withErrors($validator, 'roleEdit')
                ->withInput()
                ->with('modal', 'role-edit-modal-'.$roleModel->id);
        }

        $validated = $validator->validated();

        $roleModel->update([
            'name' => $validated['name'],
            'slug' => $this->normalizeRoleSlug($validated['slug'] ?? $validated['name']),
            'description' => $validated['description'] ?? null,
            'is_system' => $request->boolean('is_system'),
        ]);

        $roleModel->permissions()->sync($validated['permission_ids'] ?? []);

        return redirect()
            ->route('admin.roles.index')
            ->with('status', 'Rol actualizado correctamente.');
    }

    public function destroy(string $role): RedirectResponse
    {
        if ($redirect = $this->ensureAccessControlIsReady()) {
            return $redirect;
        }

        $roleModel = Role::query()->findOrFail($role);
        $roleModel->delete();

        return redirect()
            ->route('admin.roles.index')
            ->with('status', 'Rol eliminado correctamente.');
    }

    private function makeValidator(Request $request, ?Role $role = null)
    {
        $validator = Validator::make($request->all(), [
            'name' => ['required', 'string', 'max:255'],
            'slug' => ['nullable', 'string', 'max:255', 'regex:/^[A-Za-z0-9\-_]+$/'],
            'description' => ['nullable', 'string', 'max:255'],
            'is_system' => ['nullable', 'boolean'],
            'permission_ids' => ['nullable', 'array'],
            'permission_ids.*' => ['integer', 'exists:permissions,id'],
        ]);

        $validator->after(function ($validator) use ($request, $role): void {
            $slugSource = $request->filled('slug')
                ? $request->input('slug')
                : $request->input('name');
            $slug = $this->normalizeRoleSlug($slugSource);

            if ($slug === '') {
                $validator->errors()->add('slug', 'El slug del rol no es valido.');

                return;
            }

            $query = Role::query()->where('slug', $slug);

            if ($role instanceof Role) {
                $query->whereKeyNot($role->id);
            }

            if ($query->exists()) {
                $validator->errors()->add('slug', 'Ya existe un rol con ese slug.');
            }
        });

        return $validator;
    }

    private function normalizeRoleSlug(?string $value): string
    {
        return Str::slug((string) $value);
    }

    private function ensureAccessControlIsReady(): ?RedirectResponse
    {
        if ($this->accessControl->isReady()) {
            return null;
        }

        return redirect()
            ->route('admin.roles.index')
            ->with('error', 'Ejecuta las migraciones del modulo antes de crear o editar roles.');
    }
}
