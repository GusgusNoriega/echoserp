<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Customer;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;
use Illuminate\Validation\Rule;
use Illuminate\View\View;

class CustomerController extends Controller
{
    public function index(): View
    {
        $customers = Customer::query()
            ->withCount('quotations')
            ->orderByDesc('is_active')
            ->orderBy('company_name')
            ->get()
            ->map(static fn (Customer $customer): array => [
                'id' => $customer->id,
                'company_name' => $customer->company_name,
                'document_label' => $customer->document_label,
                'document_number' => $customer->document_number,
                'contact_name' => $customer->contact_name,
                'email' => $customer->email,
                'phone' => $customer->phone,
                'address' => $customer->address,
                'notes' => $customer->notes,
                'is_active' => $customer->is_active,
                'status_label' => $customer->is_active ? 'Activo' : 'Inactivo',
                'quotations_count' => $customer->quotations_count,
                'updated_at' => $customer->updated_at?->format('d/m/Y') ?? 'Sin fecha',
            ]);

        return view('admin.customers.index', [
            'eyebrow' => 'Operacion',
            'pageTitle' => 'Clientes',
            'pageDescription' => 'Administra clientes comerciales y reutiliza sus datos al crear o editar cotizaciones.',
            'metrics' => [
                [
                    'value' => str_pad((string) $customers->count(), 2, '0', STR_PAD_LEFT),
                    'label' => 'Clientes',
                    'detail' => 'Registros comerciales disponibles para cotizar.',
                ],
                [
                    'value' => str_pad((string) $customers->where('is_active', true)->count(), 2, '0', STR_PAD_LEFT),
                    'label' => 'Activos',
                    'detail' => 'Aparecen como opcion prioritaria para nuevas cotizaciones.',
                ],
                [
                    'value' => str_pad((string) $customers->whereNotNull('document_number')->count(), 2, '0', STR_PAD_LEFT),
                    'label' => 'Con documento',
                    'detail' => 'Clientes con identificacion fiscal o comercial.',
                ],
                [
                    'value' => str_pad((string) $customers->filter(static fn (array $customer): bool => $customer['quotations_count'] > 0)->count(), 2, '0', STR_PAD_LEFT),
                    'label' => 'Cotizados',
                    'detail' => 'Clientes relacionados con al menos una cotizacion.',
                ],
            ],
            'customers' => $customers,
            'recentCustomers' => $customers->sortByDesc('updated_at')->take(5)->values(),
        ]);
    }

    public function store(Request $request): RedirectResponse
    {
        $validator = $this->makeValidator($request);

        if ($validator->fails()) {
            return redirect()
                ->back()
                ->withErrors($validator, 'customerCreate')
                ->withInput()
                ->with('modal', 'customer-create-modal');
        }

        $validated = $validator->validated();

        Customer::query()->create($this->payload($validated, $request));

        return redirect()
            ->route('admin.customers.index')
            ->with('status', 'Cliente creado correctamente.');
    }

    public function update(Request $request, Customer $customer): RedirectResponse
    {
        $validator = $this->makeValidator($request, $customer);

        if ($validator->fails()) {
            return redirect()
                ->back()
                ->withErrors($validator, 'customerEdit')
                ->withInput()
                ->with('modal', 'customer-edit-modal-'.$customer->id);
        }

        $validated = $validator->validated();

        $customer->update($this->payload($validated, $request));

        return redirect()
            ->route('admin.customers.index')
            ->with('status', 'Cliente actualizado correctamente.');
    }

    public function destroy(Customer $customer): RedirectResponse
    {
        $customer->delete();

        return redirect()
            ->route('admin.customers.index')
            ->with('status', 'Cliente eliminado correctamente.');
    }

    private function makeValidator(Request $request, ?Customer $customer = null)
    {
        return Validator::make($request->all(), [
            'company_name' => ['required', 'string', 'max:255'],
            'document_label' => ['required', 'string', 'max:50'],
            'document_number' => ['nullable', 'string', 'max:50', Rule::unique(Customer::class, 'document_number')->ignore($customer?->id)],
            'contact_name' => ['nullable', 'string', 'max:255'],
            'email' => ['nullable', 'email', 'max:255'],
            'phone' => ['nullable', 'string', 'max:50'],
            'address' => ['nullable', 'string', 'max:500'],
            'notes' => ['nullable', 'string', 'max:10000'],
            'is_active' => ['nullable', 'boolean'],
        ]);
    }

    private function payload(array $validated, Request $request): array
    {
        return [
            'company_name' => $validated['company_name'],
            'document_label' => $validated['document_label'] ?: 'RUC',
            'document_number' => $validated['document_number'] ?? null,
            'contact_name' => $validated['contact_name'] ?? null,
            'email' => $validated['email'] ?? null,
            'phone' => $validated['phone'] ?? null,
            'address' => $validated['address'] ?? null,
            'notes' => $validated['notes'] ?? null,
            'is_active' => $request->boolean('is_active'),
        ];
    }
}
