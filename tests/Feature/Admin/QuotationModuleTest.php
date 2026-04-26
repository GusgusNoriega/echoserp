<?php

namespace Tests\Feature\Admin;

use App\Models\Currency;
use App\Models\Customer;
use App\Models\Quotation;
use App\Models\QuotationItem;
use App\Models\QuotationSetting;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

class QuotationModuleTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        $this->withoutVite();
        Storage::fake('quote_media');
    }

    public function test_quotation_pages_render_catalog_document_and_settings_views(): void
    {
        $this->seed();
        $this->signInAsAdmin();

        $currency = Currency::query()->create([
            'name' => 'Peso colombiano',
            'code' => 'COP',
            'symbol' => '$',
            'is_active' => true,
        ]);

        QuotationItem::query()->create([
            'type' => 'product',
            'name' => 'Impresora termica',
            'description' => 'Equipo para puntos de venta y comprobantes.',
            'unit_label' => 'unidad',
            'specifications' => ['Conexion USB', 'Corte automatico'],
            'price' => 350000,
            'currency_id' => $currency->id,
            'is_active' => true,
        ]);

        QuotationSetting::current()->update([
            'company_name' => 'Echo Systems',
            'company_document_number' => '900123123',
            'company_email' => 'ventas@echo.test',
            'default_currency_id' => $currency->id,
        ]);

        $this->get('/admin/cotizaciones')
            ->assertOk()
            ->assertSee('Cotizaciones comerciales')
            ->assertSee('Nueva cotizacion');

        $this->get('/admin/cotizaciones/catalogo')
            ->assertOk()
            ->assertSee('Catalogo comercial')
            ->assertSee('Impresora termica')
            ->assertSee('unidad');

        $this->get('/admin/cotizaciones/configuracion')
            ->assertOk()
            ->assertSee('Configuracion de cotizacion')
            ->assertSee('Logo para cotizaciones PDF')
            ->assertSee('Echo Systems')
            ->assertSee('ventas@echo.test');

        $this->get('/admin/cotizaciones/monedas')
            ->assertOk()
            ->assertSee('Monedas')
            ->assertSee('Peso colombiano')
            ->assertSee('COP');
    }

    public function test_quotation_settings_can_be_updated(): void
    {
        $this->seed();
        $this->signInAsAdmin();

        $currency = Currency::query()->create([
            'name' => 'Sol peruano',
            'code' => 'PEN',
            'symbol' => 'S/',
            'is_active' => true,
        ]);
        $logo = UploadedFile::fake()->image('logo-cotizaciones.png', 420, 160);

        $this->put('/admin/cotizaciones/configuracion', [
            'company_name' => 'Echos Peru SAC',
            'company_logo' => $logo,
            'company_document_label' => 'RUC',
            'company_document_number' => '20508768533',
            'company_email' => 'echo@empresa.test',
            'company_phone' => '+51 999999999',
            'company_website' => 'echosperu.test',
            'company_address' => 'Lima, Peru',
            'number_prefix' => 'COT',
            'default_validity_days' => 21,
            'default_tax_rate' => 18,
            'default_currency_id' => $currency->id,
            'default_notes' => 'Notas por defecto',
            'default_terms' => 'Terminos por defecto',
            'default_signer_name' => 'Gustavo Noriega',
            'default_signer_title' => 'Gerente general',
        ])->assertRedirect('/admin/cotizaciones/configuracion');

        $settings = QuotationSetting::current()->fresh();

        $this->assertSame('Echos Peru SAC', $settings->company_name);
        $this->assertSame('20508768533', $settings->company_document_number);
        $this->assertSame('echo@empresa.test', $settings->company_email);
        $this->assertSame(21, $settings->default_validity_days);
        $this->assertSame($currency->id, $settings->default_currency_id);
        $this->assertNotNull($settings->company_logo_path);
        Storage::disk('quote_media')->assertExists($settings->company_logo_path);
    }

    public function test_currency_can_be_created_updated_and_deleted(): void
    {
        $this->seed();
        $this->signInAsAdmin();

        $this->post('/admin/cotizaciones/monedas', [
            'name' => 'Dolar estadounidense',
            'code' => 'usd',
            'symbol' => 'US$',
            'is_active' => '1',
        ])->assertRedirect('/admin/cotizaciones/monedas');

        $currency = Currency::query()->where('code', 'USD')->firstOrFail();

        $this->assertSame('Dolar estadounidense', $currency->name);
        $this->assertTrue($currency->is_active);

        $this->put('/admin/cotizaciones/monedas/'.$currency->id, [
            'name' => 'Dolar americano',
            'code' => 'USD',
            'symbol' => '$',
        ])->assertRedirect('/admin/cotizaciones/monedas');

        $currency->refresh();

        $this->assertSame('Dolar americano', $currency->name);
        $this->assertSame('$', $currency->symbol);
        $this->assertFalse($currency->is_active);

        $this->delete('/admin/cotizaciones/monedas/'.$currency->id)
            ->assertRedirect('/admin/cotizaciones/monedas');

        $this->assertDatabaseMissing('currencies', ['id' => $currency->id]);
    }

    public function test_currency_in_use_cannot_be_deleted(): void
    {
        $this->seed();
        $this->signInAsAdmin();

        $currency = Currency::query()->create([
            'name' => 'Euro',
            'code' => 'EUR',
            'symbol' => 'EUR',
            'is_active' => true,
        ]);

        QuotationItem::query()->create([
            'type' => 'service',
            'name' => 'Implementacion',
            'description' => 'Servicio tecnico inicial.',
            'price' => 1200,
            'currency_id' => $currency->id,
            'is_active' => true,
        ]);

        $this->delete('/admin/cotizaciones/monedas/'.$currency->id)
            ->assertRedirect('/admin/cotizaciones/monedas')
            ->assertSessionHas('error');

        $this->assertDatabaseHas('currencies', ['id' => $currency->id]);
    }

    public function test_catalog_item_can_be_created_updated_and_deleted_with_image(): void
    {
        $this->seed();
        $this->signInAsAdmin();

        $currency = Currency::query()->create([
            'name' => 'Peso colombiano',
            'code' => 'COP',
            'symbol' => '$',
            'is_active' => true,
        ]);

        $firstImage = UploadedFile::fake()->image('catalogo.png');

        $this->post('/admin/cotizaciones/catalogo/items', [
            'type' => 'product',
            'name' => 'Terminal portatil',
            'description' => 'Equipo de captura para operacion en campo.',
            'unit_label' => 'unidad',
            'specifications_text' => "Pantalla tactil\nBateria extendida",
            'price' => '1500.50',
            'currency_id' => $currency->id,
            'image' => $firstImage,
            'is_active' => '1',
        ])->assertRedirect('/admin/cotizaciones/catalogo');

        $item = QuotationItem::query()->where('name', 'Terminal portatil')->firstOrFail();
        $originalPath = $item->image_path;

        $this->assertSame('unidad', $item->unit_label);
        $this->assertSame(['Pantalla tactil', 'Bateria extendida'], $item->specifications);
        $this->assertSame('1500.50', (string) $item->price);
        $this->assertNotNull($originalPath);
        Storage::disk('quote_media')->assertExists($originalPath);

        $replacementImage = UploadedFile::fake()->image('catalogo-2.png');

        $this->put('/admin/cotizaciones/catalogo/items/'.$item->id, [
            'type' => 'service',
            'name' => 'Terminal portatil avanzada',
            'description' => 'Servicio con suministro y configuracion.',
            'unit_label' => 'servicio',
            'specifications_text' => "Capacidad 64 GB\nGarantia 1 ano",
            'price' => '1899.90',
            'currency_id' => $currency->id,
            'image' => $replacementImage,
        ])->assertRedirect('/admin/cotizaciones/catalogo');

        $item->refresh();

        $this->assertSame('service', $item->type);
        $this->assertSame('Terminal portatil avanzada', $item->name);
        $this->assertSame('servicio', $item->unit_label);
        $this->assertSame(['Capacidad 64 GB', 'Garantia 1 ano'], $item->specifications);
        $this->assertSame('1899.90', (string) $item->price);
        $this->assertNotSame($originalPath, $item->image_path);
        Storage::disk('quote_media')->assertMissing($originalPath);
        Storage::disk('quote_media')->assertExists($item->image_path);

        $currentPath = $item->image_path;

        $this->delete('/admin/cotizaciones/catalogo/items/'.$item->id)
            ->assertRedirect('/admin/cotizaciones/catalogo');

        $this->assertDatabaseMissing('quotation_items', ['id' => $item->id]);
        Storage::disk('quote_media')->assertMissing($currentPath);
    }

    public function test_catalog_item_preserves_whole_number_price_when_created_and_updated(): void
    {
        $this->seed();
        $this->signInAsAdmin();

        $currency = Currency::query()->create([
            'name' => 'Sol peruano',
            'code' => 'PEN',
            'symbol' => 'S/',
            'is_active' => true,
        ]);

        $this->post('/admin/cotizaciones/catalogo/items', [
            'type' => 'product',
            'name' => 'Stand para feria',
            'description' => 'Stand modular para eventos comerciales.',
            'unit_label' => 'unidad',
            'specifications_text' => "Estructura modular\nPaneleria grafica",
            'price' => '200',
            'currency_id' => $currency->id,
            'is_active' => '1',
        ])->assertRedirect('/admin/cotizaciones/catalogo');

        $item = QuotationItem::query()->where('name', 'Stand para feria')->firstOrFail();

        $this->assertSame('200.00', (string) $item->price);

        $this->put('/admin/cotizaciones/catalogo/items/'.$item->id, [
            'type' => 'product',
            'name' => 'Stand para feria actualizado',
            'description' => 'Stand modular para eventos comerciales actualizado.',
            'unit_label' => 'unidad',
            'specifications_text' => "Estructura modular\nPaneleria grafica\nLuces incluidas",
            'price' => '200',
            'currency_id' => $currency->id,
            'is_active' => '1',
        ])->assertRedirect('/admin/cotizaciones/catalogo');

        $item->refresh();

        $this->assertSame('200.00', (string) $item->price);

        $this->get('/admin/cotizaciones/catalogo')
            ->assertOk()
            ->assertSee('S/ 200,00 PEN');
    }

    public function test_customer_records_can_be_created_updated_and_deleted(): void
    {
        $this->seed();
        $this->signInAsAdmin();

        $this->get('/admin/clientes')
            ->assertOk()
            ->assertSee('Clientes')
            ->assertSee('Nuevo cliente');

        $this->post('/admin/clientes', [
            'company_name' => 'Cliente Demo SAC',
            'document_label' => 'RUC',
            'document_number' => '20111111111',
            'contact_name' => 'Ana Torres',
            'email' => 'ana@cliente.test',
            'phone' => '+51 900111222',
            'address' => 'Lima',
            'notes' => 'Cliente recurrente',
            'is_active' => '1',
        ])->assertRedirect('/admin/clientes');

        $customer = Customer::query()->where('document_number', '20111111111')->firstOrFail();

        $this->assertSame('Cliente Demo SAC', $customer->company_name);
        $this->assertTrue($customer->is_active);

        $this->put('/admin/clientes/'.$customer->id, [
            'company_name' => 'Cliente Demo Renovado SAC',
            'document_label' => 'RUC',
            'document_number' => '20111111111',
            'contact_name' => 'Ana Torres',
            'email' => 'contacto@cliente.test',
            'phone' => '+51 900111222',
            'address' => 'Miraflores',
            'notes' => 'Ficha actualizada',
        ])->assertRedirect('/admin/clientes');

        $customer->refresh();

        $this->assertSame('Cliente Demo Renovado SAC', $customer->company_name);
        $this->assertSame('contacto@cliente.test', $customer->email);
        $this->assertFalse($customer->is_active);

        $this->delete('/admin/clientes/'.$customer->id)
            ->assertRedirect('/admin/clientes');

        $this->assertDatabaseMissing('customers', ['id' => $customer->id]);
    }

    public function test_quotation_can_be_linked_to_customer_without_requiring_the_relation(): void
    {
        $this->seed();
        $this->signInAsAdmin();

        $currency = Currency::query()->create([
            'name' => 'Sol peruano',
            'code' => 'PEN',
            'symbol' => 'S/',
            'is_active' => true,
        ]);

        QuotationSetting::current()->update([
            'company_name' => 'Echos Peru SAC',
            'default_currency_id' => $currency->id,
            'default_validity_days' => 15,
        ]);

        $customer = Customer::query()->create([
            'company_name' => 'Cliente Relacionado SAC',
            'document_label' => 'RUC',
            'document_number' => '20999999999',
            'email' => 'ventas@cliente.test',
            'phone' => '+51 900999888',
            'address' => 'Lima',
            'is_active' => true,
        ]);

        $this->get('/admin/cotizaciones/nueva')
            ->assertOk()
            ->assertSee('Cliente manual / sin registro')
            ->assertSee('Cliente Relacionado SAC');

        $basePayload = [
            'status' => 'draft',
            'issue_date' => '2026-04-24',
            'valid_until' => '2026-05-10',
            'title' => 'Cotizacion de prueba',
            'summary' => 'Documento comercial.',
            'client_company_name' => 'Cliente Relacionado SAC',
            'client_document_label' => 'RUC',
            'client_document_number' => '20999999999',
            'client_email' => 'ventas@cliente.test',
            'client_phone' => '+51 900999888',
            'client_address' => 'Lima',
            'currency_id' => $currency->id,
            'tax_rate' => '0.00',
            'line_items' => [
                [
                    'quotation_item_id' => '',
                    'catalog_lookup' => '',
                    'name' => 'Servicio mensual',
                    'description' => 'Soporte operativo.',
                    'quantity' => '1',
                    'unit_label' => 'mes',
                    'unit_price' => '500.00',
                    'discount_amount' => '0.00',
                ],
            ],
            'work_sections' => [],
        ];

        $this->post('/admin/cotizaciones', array_merge($basePayload, [
            'number' => 'COT-CLIENTE-0001',
            'customer_id' => $customer->id,
        ]))->assertRedirect('/admin/cotizaciones');

        $linkedQuotation = Quotation::query()->where('number', 'COT-CLIENTE-0001')->firstOrFail();

        $this->assertSame($customer->id, $linkedQuotation->customer_id);
        $this->assertSame('Cliente Relacionado SAC', $linkedQuotation->client_company_name);

        $this->post('/admin/cotizaciones', array_merge($basePayload, [
            'number' => 'COT-MANUAL-0001',
            'customer_id' => '',
            'client_company_name' => 'Cliente Manual SAS',
            'client_document_number' => '900111222',
        ]))->assertRedirect('/admin/cotizaciones');

        $manualQuotation = Quotation::query()->where('number', 'COT-MANUAL-0001')->firstOrFail();

        $this->assertNull($manualQuotation->customer_id);
        $this->assertSame('Cliente Manual SAS', $manualQuotation->client_company_name);
    }

    public function test_quotation_can_be_created_with_catalog_items_manual_items_and_work_plan(): void
    {
        $this->seed();
        $this->signInAsAdmin();

        $currency = Currency::query()->create([
            'name' => 'Sol peruano',
            'code' => 'PEN',
            'symbol' => 'S/',
            'is_active' => true,
        ]);

        QuotationSetting::current()->update([
            'company_name' => 'Echos Peru SAC',
            'company_document_label' => 'RUC',
            'company_document_number' => '20508768533',
            'company_email' => 'echo@empresa.test',
            'default_currency_id' => $currency->id,
            'default_validity_days' => 15,
        ]);

        $catalogImagePath = UploadedFile::fake()->image('crm-integral.png')->store('cotizaciones', 'quote_media');

        $catalogItem = QuotationItem::query()->create([
            'type' => 'service',
            'name' => 'CRM integral',
            'description' => 'Implementacion del modulo principal.',
            'unit_label' => 'modulo',
            'specifications' => ['Gestion de clientes', 'Pipeline comercial'],
            'price' => 1500,
            'currency_id' => $currency->id,
            'image_path' => $catalogImagePath,
            'is_active' => true,
        ]);

        $this->post('/admin/cotizaciones', [
            'number' => 'COT-20508768533-CRM-0001',
            'status' => 'draft',
            'issue_date' => '2026-04-24',
            'valid_until' => '2026-05-10',
            'title' => 'CRM integral para eventos - Echos Peru SAC',
            'summary' => 'Desarrollo de CRM para gestion comercial y operativa.',
            'client_company_name' => 'Echos Peru SAC',
            'client_document_label' => 'RUC',
            'client_document_number' => '20508768533',
            'client_email' => 'cliente@echos.test',
            'client_phone' => '+51 900000000',
            'client_address' => 'Lima',
            'currency_id' => $currency->id,
            'work_start_date' => '2026-04-25',
            'hide_work_plan' => '0',
            'work_end_date' => '2026-06-30',
            'estimated_hours' => '384',
            'estimated_days' => '48',
            'hours_per_day' => '8',
            'tax_rate' => '10.00',
            'notes' => 'Notas del proyecto',
            'terms_and_conditions' => 'Terminos comerciales',
            'line_items' => [
                [
                    'quotation_item_id' => $catalogItem->id,
                    'catalog_lookup' => '#'.$catalogItem->id.' - Servicio - CRM integral',
                    'name' => 'CRM integral',
                    'description' => 'Implementacion del modulo principal.',
                    'image_path' => $catalogImagePath,
                    'image_source' => 'catalog',
                    'image_url' => Storage::disk('quote_media')->url($catalogImagePath),
                    'remove_image' => '0',
                    'quantity' => '2',
                    'unit_label' => 'modulo',
                    'unit_price' => '1500.00',
                    'discount_amount' => '100.00',
                ],
                [
                    'quotation_item_id' => '',
                    'catalog_lookup' => '',
                    'name' => 'Capacitacion',
                    'description' => 'Transferencia operativa final.',
                    'image_path' => '',
                    'image_source' => '',
                    'image_url' => '',
                    'remove_image' => '0',
                    'quantity' => '1',
                    'unit_label' => 'fase',
                    'unit_price' => '200.00',
                    'discount_amount' => '0.00',
                    'image' => UploadedFile::fake()->image('capacitacion.png'),
                ],
            ],
            'work_sections' => [
                [
                    'title' => 'Modulo Cotizaciones',
                    'tasks' => [
                        [
                            'name' => 'Formulario de creacion y edicion',
                            'description' => 'Alta y actualizacion de cotizaciones con items.',
                            'duration_days' => '1',
                        ],
                        [
                            'name' => 'Configuracion de cotizaciones',
                            'description' => 'Numeracion, vigencia por defecto y terminos.',
                            'duration_days' => '1',
                        ],
                    ],
                ],
            ],
        ])->assertRedirect('/admin/cotizaciones');

        $quotation = Quotation::query()
            ->where('number', 'COT-20508768533-CRM-0001')
            ->with(['lineItems', 'workSections.tasks'])
            ->firstOrFail();

        $this->assertSame('Echos Peru SAC', $quotation->client_company_name);
        $this->assertSame('3410.00', (string) $quotation->total);
        $this->assertSame('3200.00', (string) $quotation->subtotal);
        $this->assertSame('100.00', (string) $quotation->discount_total);
        $this->assertSame('310.00', (string) $quotation->tax_total);
        $this->assertSame('16.00', (string) $quotation->estimated_hours);
        $this->assertSame('2.00', (string) $quotation->estimated_days);
        $this->assertSame('8.00', (string) $quotation->hours_per_day);
        $this->assertSame('Echos Peru SAC', $quotation->issuer_snapshot['company_name']);
        $this->assertCount(2, $quotation->lineItems);
        $this->assertSame('catalog', $quotation->lineItems[0]->source_type);
        $this->assertSame($catalogItem->id, $quotation->lineItems[0]->quotation_item_id);
        $this->assertSame('catalog', $quotation->lineItems[0]->image_source);
        $this->assertSame(['Gestion de clientes', 'Pipeline comercial'], $quotation->lineItems[0]->specifications);
        $this->assertNotNull($quotation->lineItems[0]->image_path);
        $this->assertNotSame($catalogImagePath, $quotation->lineItems[0]->image_path);
        Storage::disk('quote_media')->assertExists($quotation->lineItems[0]->image_path);
        $this->assertSame('uploaded', $quotation->lineItems[1]->image_source);
        $this->assertNotNull($quotation->lineItems[1]->image_path);
        Storage::disk('quote_media')->assertExists($quotation->lineItems[1]->image_path);
        $this->assertCount(1, $quotation->workSections);
        $this->assertCount(2, $quotation->workSections[0]->tasks);
    }

    public function test_simple_quotation_can_hide_work_plan_and_time_estimates(): void
    {
        $this->seed();
        $this->signInAsAdmin();

        $currency = Currency::query()->create([
            'name' => 'Sol peruano',
            'code' => 'PEN',
            'symbol' => 'S/',
            'is_active' => true,
        ]);

        QuotationSetting::current()->update([
            'company_name' => 'Echos Peru SAC',
            'default_currency_id' => $currency->id,
            'default_validity_days' => 15,
        ]);

        $this->get('/admin/cotizaciones/nueva')
            ->assertOk()
            ->assertSee('Desactivar plan de trabajo y tiempo estimado')
            ->assertSee('data-hide-work-plan-toggle checked', false);

        $this->post('/admin/cotizaciones', [
            'number' => 'COT-SIMPLE-0001',
            'status' => 'draft',
            'issue_date' => '2026-04-24',
            'valid_until' => '2026-05-10',
            'title' => 'Cotizacion simple',
            'summary' => 'Documento comercial sin plan detallado.',
            'client_company_name' => 'Cliente Simple SAC',
            'client_document_label' => 'RUC',
            'client_document_number' => '20111111111',
            'currency_id' => $currency->id,
            'work_start_date' => '2026-04-28',
            'hide_work_plan' => '1',
            'work_end_date' => '2026-05-30',
            'estimated_hours' => '999',
            'estimated_days' => '999',
            'hours_per_day' => '8',
            'tax_rate' => '0.00',
            'line_items' => [
                [
                    'quotation_item_id' => '',
                    'catalog_lookup' => '',
                    'name' => 'Servicio puntual',
                    'description' => 'Entrega sencilla.',
                    'quantity' => '1',
                    'unit_label' => 'servicio',
                    'unit_price' => '300.00',
                    'discount_amount' => '0.00',
                ],
            ],
            'work_sections' => [
                [
                    'title' => 'No debe guardarse',
                    'tasks' => [
                        [
                            'name' => 'Tarea oculta',
                            'description' => 'No aplica.',
                            'duration_days' => '4',
                        ],
                    ],
                ],
            ],
        ])->assertRedirect('/admin/cotizaciones');

        $quotation = Quotation::query()
            ->where('number', 'COT-SIMPLE-0001')
            ->with('workSections.tasks')
            ->firstOrFail();

        $this->assertTrue($quotation->hide_work_plan);
        $this->assertSame('2026-04-28', $quotation->work_start_date?->toDateString());
        $this->assertNull($quotation->work_end_date);
        $this->assertNull($quotation->estimated_hours);
        $this->assertNull($quotation->estimated_days);
        $this->assertNull($quotation->hours_per_day);
        $this->assertCount(0, $quotation->workSections);
    }

    public function test_quotation_update_keeps_existing_line_item_image_when_not_reuploaded(): void
    {
        $this->seed();
        $this->signInAsAdmin();

        $currency = Currency::query()->create([
            'name' => 'Sol peruano',
            'code' => 'PEN',
            'symbol' => 'S/',
            'is_active' => true,
        ]);

        QuotationSetting::current()->update([
            'company_name' => 'Echos Peru SAC',
            'company_document_label' => 'RUC',
            'company_document_number' => '20508768533',
            'company_email' => 'echo@empresa.test',
            'default_currency_id' => $currency->id,
            'default_validity_days' => 15,
        ]);

        $quotation = Quotation::query()->create([
            'number' => 'COT-20508768533-CRM-0002',
            'status' => 'draft',
            'issue_date' => '2026-04-24',
            'valid_until' => '2026-05-10',
            'title' => 'Cotizacion editable',
            'summary' => 'Version inicial.',
            'client_company_name' => 'Echos Peru SAC',
            'client_document_label' => 'RUC',
            'client_document_number' => '20508768533',
            'client_email' => 'cliente@echos.test',
            'client_phone' => '+51 900000000',
            'client_address' => 'Lima',
            'currency_id' => $currency->id,
            'subtotal' => 200,
            'discount_total' => 0,
            'tax_rate' => 10,
            'tax_total' => 20,
            'total' => 220,
            'issuer_snapshot' => QuotationSetting::current()->issuerSnapshot(),
        ]);

        $lineImagePath = UploadedFile::fake()->image('linea-existente.png')->store('cotizaciones/line-items', 'quote_media');

        $quotation->lineItems()->create([
            'sort_order' => 1,
            'source_type' => 'manual',
            'quotation_item_id' => null,
            'name' => 'Capacitacion',
            'description' => 'Transferencia operativa final.',
            'image_path' => $lineImagePath,
            'image_source' => 'uploaded',
            'quantity' => 1,
            'unit_label' => 'fase',
            'unit_price' => 200,
            'discount_amount' => 0,
            'line_total' => 200,
        ]);

        $this->put('/admin/cotizaciones/'.$quotation->id, [
            'number' => 'COT-20508768533-CRM-0002',
            'status' => 'sent',
            'issue_date' => '2026-04-24',
            'valid_until' => '2026-05-15',
            'title' => 'Cotizacion editable actualizada',
            'summary' => 'Version revisada.',
            'client_company_name' => 'Echos Peru SAC',
            'client_document_label' => 'RUC',
            'client_document_number' => '20508768533',
            'client_email' => 'cliente@echos.test',
            'client_phone' => '+51 900000000',
            'client_address' => 'Lima',
            'currency_id' => $currency->id,
            'hide_work_plan' => '0',
            'estimated_hours' => '999',
            'estimated_days' => '999',
            'hours_per_day' => '6',
            'tax_rate' => '10.00',
            'notes' => 'Notas actualizadas',
            'terms_and_conditions' => 'Terminos actualizados',
            'line_items' => [
                [
                    'quotation_item_id' => '',
                    'catalog_lookup' => '',
                    'name' => 'Capacitacion avanzada',
                    'description' => 'Transferencia operativa final con refuerzo.',
                    'image_path' => $lineImagePath,
                    'image_source' => 'uploaded',
                    'image_url' => Storage::disk('quote_media')->url($lineImagePath),
                    'remove_image' => '0',
                    'quantity' => '1',
                    'unit_label' => 'fase',
                    'unit_price' => '250.00',
                    'discount_amount' => '0.00',
                ],
            ],
            'work_sections' => [
                [
                    'title' => 'Plan actualizado',
                    'tasks' => [
                        [
                            'name' => 'Relevamiento',
                            'description' => 'Revision de alcance.',
                            'duration_days' => '2',
                        ],
                        [
                            'name' => 'Ejecucion',
                            'description' => 'Ajustes finales.',
                            'duration_days' => '3',
                        ],
                    ],
                ],
            ],
        ])->assertRedirect('/admin/cotizaciones');

        $quotation->refresh();
        $quotation->load(['lineItems', 'workSections.tasks']);

        $this->assertCount(1, $quotation->lineItems);
        $this->assertSame('Capacitacion avanzada', $quotation->lineItems[0]->name);
        $this->assertSame($lineImagePath, $quotation->lineItems[0]->image_path);
        $this->assertSame('uploaded', $quotation->lineItems[0]->image_source);
        $this->assertSame('30.00', (string) $quotation->estimated_hours);
        $this->assertSame('5.00', (string) $quotation->estimated_days);
        $this->assertSame('6.00', (string) $quotation->hours_per_day);
        $this->assertCount(2, $quotation->workSections[0]->tasks);
        Storage::disk('quote_media')->assertExists($lineImagePath);
    }

    public function test_quotation_pdf_can_be_downloaded_with_items_specs_and_images(): void
    {
        $this->seed();
        $this->signInAsAdmin();

        $currency = Currency::query()->create([
            'name' => 'Sol peruano',
            'code' => 'PEN',
            'symbol' => 'S/',
            'is_active' => true,
        ]);

        QuotationSetting::current()->update([
            'company_name' => 'Echos Peru SAC',
            'company_document_label' => 'RUC',
            'company_document_number' => '20508768533',
            'company_email' => 'echo@empresa.test',
            'company_phone' => '+51 999999999',
            'company_address' => 'Lima, Peru',
            'company_logo_path' => UploadedFile::fake()->image('logo-pdf.png', 420, 160)->store('cotizaciones/logos', 'quote_media'),
            'default_currency_id' => $currency->id,
            'default_signer_name' => 'Gustavo Noriega',
            'default_signer_title' => 'Gerente general',
        ]);

        $lineImagePath = UploadedFile::fake()->image('crm-pdf.png', 640, 360)->store('cotizaciones/line-items', 'quote_media');

        $quotation = Quotation::query()->create([
            'number' => 'COT-PDF-0001',
            'status' => 'sent',
            'issue_date' => '2026-04-24',
            'valid_until' => '2026-05-10',
            'title' => 'CRM integral para eventos',
            'summary' => 'Implementacion comercial y operativa.',
            'client_company_name' => 'Cliente Demo SAC',
            'client_document_label' => 'RUC',
            'client_document_number' => '20111111111',
            'client_email' => 'cliente@demo.test',
            'client_phone' => '+51 900000000',
            'client_address' => 'Lima',
            'currency_id' => $currency->id,
            'work_start_date' => '2026-04-25',
            'hide_work_plan' => false,
            'work_end_date' => '2026-05-30',
            'estimated_hours' => 80,
            'estimated_days' => 10,
            'hours_per_day' => 8,
            'subtotal' => 1500,
            'discount_total' => 100,
            'tax_rate' => 18,
            'tax_total' => 252,
            'total' => 1652,
            'notes' => 'Incluye acompanamiento inicial.',
            'terms_and_conditions' => 'Vigencia segun fecha indicada.',
            'issuer_snapshot' => QuotationSetting::current()->issuerSnapshot(),
        ]);

        $quotation->lineItems()->create([
            'sort_order' => 1,
            'source_type' => 'manual',
            'name' => 'CRM integral',
            'description' => 'Modulo principal para gestion comercial.',
            'specifications' => ['Clientes y oportunidades', 'Reportes comerciales'],
            'image_path' => $lineImagePath,
            'image_source' => 'uploaded',
            'quantity' => 1,
            'unit_label' => 'modulo',
            'unit_price' => 1500,
            'discount_amount' => 100,
            'line_total' => 1400,
        ]);

        $section = $quotation->workSections()->create([
            'sort_order' => 1,
            'title' => 'Implementacion',
        ]);

        $section->tasks()->create([
            'sort_order' => 1,
            'name' => 'Configuracion inicial',
            'description' => 'Parametrizacion y pruebas.',
            'duration_days' => 3,
        ]);

        $response = $this->get('/admin/cotizaciones/'.$quotation->id.'/pdf');

        $response->assertOk();
        $response->assertHeader('Content-Type', 'application/pdf');
        $this->assertStringContainsString('attachment;', $response->headers->get('Content-Disposition'));
        $this->assertStringContainsString('cotizacion-cot-pdf-0001.pdf', $response->headers->get('Content-Disposition'));
        $this->assertStringStartsWith('%PDF', $response->getContent());
    }

    private function signInAsAdmin(): void
    {
        $user = User::query()->where('email', 'admin@echoserp.test')->firstOrFail();

        $this->actingAs($user);
    }
}
