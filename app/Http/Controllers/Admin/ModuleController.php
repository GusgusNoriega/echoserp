<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Module;
use App\Models\Permission;
use App\Support\Admin\AccessControlService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Str;
use Illuminate\View\View;

class ModuleController extends Controller
{
    public function __construct(
        private readonly AccessControlService $accessControl,
    ) {
    }

    public function index(): View
    {
        return view('admin.modules.index', [
            'eyebrow' => 'Administracion',
            'pageTitle' => 'Modulos',
            'pageDescription' => 'Catalogo estructural para agrupar permisos y limitar acciones disponibles por modulo.',
            ...$this->accessControl->modulesData(),
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
                ->withErrors($validator, 'moduleCreate')
                ->withInput()
                ->with('modal', 'module-create-modal');
        }

        $validated = $validator->validated();

        DB::transaction(function () use ($request, $validated): void {
            $module = Module::query()->create([
                'name' => $validated['name'],
                'slug' => $this->normalizeModuleSlug($validated['slug'] ?? $validated['name']),
                'description' => $validated['description'] ?? null,
                'is_system' => $request->boolean('is_system'),
            ]);

            $module->actions()->sync($validated['action_ids'] ?? []);
        });

        return redirect()
            ->route('admin.modules.index')
            ->with('status', 'Modulo creado correctamente.');
    }

    public function update(Request $request, string $module): RedirectResponse
    {
        if ($redirect = $this->ensureAccessControlIsReady()) {
            return $redirect;
        }

        $moduleModel = Module::query()->with('permissions.action:id,slug')->findOrFail($module);
        $validator = $this->makeValidator($request, $moduleModel);

        if ($validator->fails()) {
            return redirect()
                ->back()
                ->withErrors($validator, 'moduleEdit')
                ->withInput()
                ->with('modal', 'module-edit-modal-'.$moduleModel->id);
        }

        $validated = $validator->validated();
        $nextSlug = $this->normalizeModuleSlug($validated['slug'] ?? $validated['name']);
        $selectedActionIds = collect($validated['action_ids'] ?? [])->map(static fn (mixed $id): int => (int) $id)->all();

        DB::transaction(function () use ($request, $validated, $moduleModel, $nextSlug, $selectedActionIds): void {
            $moduleModel->update([
                'name' => $validated['name'],
                'slug' => $nextSlug,
                'description' => $validated['description'] ?? null,
                'is_system' => $request->boolean('is_system'),
            ]);

            $moduleModel->actions()->sync($selectedActionIds);

            if ($selectedActionIds === []) {
                $moduleModel->permissions()->delete();

                return;
            }

            $moduleModel->permissions()
                ->whereNotIn('permission_action_id', $selectedActionIds)
                ->delete();

            $moduleModel->load('permissions.action:id,slug');

            $moduleModel->permissions->each(function (Permission $permission) use ($nextSlug): void {
                $actionSlug = $permission->action?->slug;

                if (! filled($actionSlug)) {
                    return;
                }

                $permission->update([
                    'slug' => $this->composePermissionSlug($nextSlug, $actionSlug),
                ]);
            });
        });

        return redirect()
            ->route('admin.modules.index')
            ->with('status', 'Modulo actualizado correctamente.');
    }

    public function destroy(string $module): RedirectResponse
    {
        if ($redirect = $this->ensureAccessControlIsReady()) {
            return $redirect;
        }

        $moduleModel = Module::query()->findOrFail($module);
        $moduleModel->delete();

        return redirect()
            ->route('admin.modules.index')
            ->with('status', 'Modulo eliminado correctamente.');
    }

    private function makeValidator(Request $request, ?Module $module = null)
    {
        $validator = Validator::make($request->all(), [
            'name' => ['required', 'string', 'max:255'],
            'slug' => ['nullable', 'string', 'max:255', 'regex:/^[A-Za-z0-9\-_]+$/'],
            'description' => ['nullable', 'string', 'max:255'],
            'is_system' => ['nullable', 'boolean'],
            'action_ids' => ['nullable', 'array'],
            'action_ids.*' => ['integer', 'exists:permission_actions,id'],
        ]);

        $validator->after(function ($validator) use ($request, $module): void {
            $slugSource = $request->filled('slug')
                ? $request->input('slug')
                : $request->input('name');
            $slug = $this->normalizeModuleSlug($slugSource);

            if ($slug === '') {
                $validator->errors()->add('slug', 'El slug del modulo no es valido.');

                return;
            }

            $query = Module::query()->where('slug', $slug);

            if ($module instanceof Module) {
                $query->whereKeyNot($module->id);
            }

            if ($query->exists()) {
                $validator->errors()->add('slug', 'Ya existe un modulo con ese slug.');
            }
        });

        return $validator;
    }

    private function normalizeModuleSlug(?string $value): string
    {
        return Str::slug((string) $value);
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
            ->route('admin.modules.index')
            ->with('error', 'Ejecuta las migraciones del modulo antes de crear o editar modulos.');
    }
}
