<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\User;
use App\Support\Admin\AccessControlService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;
use Illuminate\Validation\Rule;
use Illuminate\View\View;

class UserController extends Controller
{
    public function __construct(
        private readonly AccessControlService $accessControl,
    ) {
    }

    public function index(): View
    {
        return view('admin.users.index', [
            'eyebrow' => 'Administracion',
            'pageTitle' => 'Usuarios',
            'pageDescription' => 'Listado base para asignar roles, revisar acceso y extender nuevas vistas del panel.',
            ...$this->accessControl->usersData(),
        ]);
    }

    public function store(Request $request): RedirectResponse
    {
        if ($redirect = $this->ensureAccessControlIsReady()) {
            return $redirect;
        }

        $validator = Validator::make($request->all(), [
            'name' => ['required', 'string', 'max:255'],
            'email' => ['required', 'string', 'email', 'max:255', Rule::unique(User::class, 'email')],
            'password' => ['required', 'string', 'min:8', 'confirmed'],
            'role_ids' => ['nullable', 'array'],
            'role_ids.*' => ['integer', 'exists:roles,id'],
            'email_verified' => ['nullable', 'boolean'],
        ]);

        if ($validator->fails()) {
            return redirect()
                ->back()
                ->withErrors($validator, 'userCreate')
                ->withInput($request->except(['password', 'password_confirmation']))
                ->with('modal', 'user-create-modal');
        }

        $validated = $validator->validated();

        $user = User::query()->create([
            'name' => $validated['name'],
            'email' => $validated['email'],
            'password' => $validated['password'],
            'email_verified_at' => $request->boolean('email_verified') ? now() : null,
        ]);

        $user->roles()->sync($validated['role_ids'] ?? []);

        return redirect()
            ->route('admin.users.index')
            ->with('status', 'Usuario creado correctamente.');
    }

    public function update(Request $request, User $user): RedirectResponse
    {
        if ($redirect = $this->ensureAccessControlIsReady()) {
            return $redirect;
        }

        $validator = Validator::make($request->all(), [
            'name' => ['required', 'string', 'max:255'],
            'email' => ['required', 'string', 'email', 'max:255', Rule::unique(User::class, 'email')->ignore($user->id)],
            'password' => ['nullable', 'string', 'min:8', 'confirmed'],
            'role_ids' => ['nullable', 'array'],
            'role_ids.*' => ['integer', 'exists:roles,id'],
            'email_verified' => ['nullable', 'boolean'],
        ]);

        if ($validator->fails()) {
            return redirect()
                ->back()
                ->withErrors($validator, 'userEdit')
                ->withInput($request->except(['password', 'password_confirmation']))
                ->with('modal', 'user-edit-modal-'.$user->id);
        }

        $validated = $validator->validated();

        $user->name = $validated['name'];
        $user->email = $validated['email'];
        $user->email_verified_at = $request->boolean('email_verified')
            ? ($user->email_verified_at ?? now())
            : null;

        if (filled($validated['password'] ?? null)) {
            $user->password = $validated['password'];
        }

        $user->save();
        $user->roles()->sync($validated['role_ids'] ?? []);

        return redirect()
            ->route('admin.users.index')
            ->with('status', 'Usuario actualizado correctamente.');
    }

    public function destroy(User $user): RedirectResponse
    {
        if ($redirect = $this->ensureAccessControlIsReady()) {
            return $redirect;
        }

        if (auth()->id() === $user->id) {
            return redirect()
                ->route('admin.users.index')
                ->with('error', 'No puedes eliminar la cuenta con la que tienes la sesion activa.');
        }

        $user->delete();

        return redirect()
            ->route('admin.users.index')
            ->with('status', 'Usuario eliminado correctamente.');
    }

    private function ensureAccessControlIsReady(): ?RedirectResponse
    {
        if ($this->accessControl->isReady()) {
            return null;
        }

        return redirect()
            ->route('admin.users.index')
            ->with('error', 'Ejecuta las migraciones del modulo antes de crear o editar usuarios.');
    }
}
