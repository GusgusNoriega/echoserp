<?php

use App\Http\Controllers\Admin\CurrencyController;
use App\Http\Controllers\Admin\CustomerController;
use App\Http\Controllers\Admin\DashboardController;
use App\Http\Controllers\Admin\ModuleController;
use App\Http\Controllers\Admin\PermissionController;
use App\Http\Controllers\Admin\QuotationController;
use App\Http\Controllers\Admin\QuotationItemController;
use App\Http\Controllers\Admin\QuotationSettingsController;
use App\Http\Controllers\Admin\RoleController;
use App\Http\Controllers\Admin\UserController;
use App\Http\Controllers\Auth\AuthenticatedSessionController;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Route;

Route::get('/', function () {
    return redirect()->route(Auth::check() ? 'admin.dashboard' : 'login');
});

Route::middleware('guest')->group(function (): void {
    Route::get('/login', [AuthenticatedSessionController::class, 'create'])->name('login');
    Route::post('/login', [AuthenticatedSessionController::class, 'store'])->name('login.store');
});

Route::post('/logout', [AuthenticatedSessionController::class, 'destroy'])
    ->middleware('auth')
    ->name('logout');

Route::middleware('auth')->prefix('admin')->name('admin.')->group(function (): void {
    Route::redirect('/', '/admin/dashboard');
    Route::get('/dashboard', DashboardController::class)->name('dashboard');
    Route::get('/usuarios', [UserController::class, 'index'])->name('users.index');
    Route::post('/usuarios', [UserController::class, 'store'])->name('users.store');
    Route::put('/usuarios/{user}', [UserController::class, 'update'])->name('users.update');
    Route::delete('/usuarios/{user}', [UserController::class, 'destroy'])->name('users.destroy');
    Route::get('/roles', [RoleController::class, 'index'])->name('roles.index');
    Route::post('/roles', [RoleController::class, 'store'])->name('roles.store');
    Route::put('/roles/{role}', [RoleController::class, 'update'])->name('roles.update');
    Route::delete('/roles/{role}', [RoleController::class, 'destroy'])->name('roles.destroy');
    Route::get('/modulos', [ModuleController::class, 'index'])->name('modules.index');
    Route::post('/modulos', [ModuleController::class, 'store'])->name('modules.store');
    Route::put('/modulos/{module}', [ModuleController::class, 'update'])->name('modules.update');
    Route::delete('/modulos/{module}', [ModuleController::class, 'destroy'])->name('modules.destroy');
    Route::get('/permisos', [PermissionController::class, 'index'])->name('permissions.index');
    Route::post('/permisos', [PermissionController::class, 'store'])->name('permissions.store');
    Route::put('/permisos/{permission}', [PermissionController::class, 'update'])->name('permissions.update');
    Route::delete('/permisos/{permission}', [PermissionController::class, 'destroy'])->name('permissions.destroy');

    Route::view('/sucursales', 'admin.placeholder', [
        'eyebrow' => 'Administracion',
        'pageTitle' => 'Sucursales',
        'pageDescription' => 'Vista base para sedes, responsables, horarios y datos operativos.',
        'summary' => 'Puedes montar aqui tarjetas por sede, un listado maestro o un mapa de cobertura.',
        'metrics' => [
            ['value' => 'Mapa', 'label' => 'Vista opcional', 'detail' => 'Ubicacion y cobertura de sedes.'],
            ['value' => 'CRUD', 'label' => 'Acciones esperadas', 'detail' => 'Alta, edicion y estados.'],
            ['value' => 'Multi', 'label' => 'Escala futura', 'detail' => 'Datos por sucursal y por equipo.'],
        ],
        'checklist' => [
            'Crear ficha con datos generales y contactos.',
            'Agregar horarios, cajas y responsables por sede.',
            'Preparar relacion con usuarios, ventas o inventario.',
        ],
        'notes' => [
            'El sidebar deja esta seccion lista para crecer sin mover rutas principales.',
            'Las tarjetas del dashboard ya sirven como base visual para cards por sucursal.',
            'El modo oscuro y las paletas mantienen contraste suficiente para datos densos.',
        ],
    ])->name('branches.index');

    Route::view('/inventario', 'admin.placeholder', [
        'eyebrow' => 'Operacion',
        'pageTitle' => 'Inventario',
        'pageDescription' => 'Base para productos, categorias, stock y alertas operativas.',
        'summary' => 'Este espacio acepta bien tablas grandes, resumenes por KPI y paneles laterales.',
        'metrics' => [
            ['value' => 'Stock', 'label' => 'Indicador clave', 'detail' => 'Disponibilidad y reposicion.'],
            ['value' => 'SKU', 'label' => 'Escala prevista', 'detail' => 'Catalogos amplios por categoria.'],
            ['value' => 'Alertas', 'label' => 'Bloque extra', 'detail' => 'Minimos, quiebres y rotacion.'],
        ],
        'checklist' => [
            'Agregar tabla de productos con filtros persistentes.',
            'Crear widgets de stock bajo, entradas y salidas.',
            'Preparar detalle de producto con tabs o panel lateral.',
        ],
        'notes' => [
            'La rejilla principal ya funciona para KPI y tablas en escritorio.',
            'En telefono, las tarjetas se apilan para no romper lectura.',
            'Puedes reutilizar el mismo patron visual para compras o proveedores.',
        ],
    ])->name('inventory.index');

    Route::view('/ventas', 'admin.placeholder', [
        'eyebrow' => 'Operacion',
        'pageTitle' => 'Ventas',
        'pageDescription' => 'Plantilla para pedidos, estados comerciales y seguimiento diario.',
        'summary' => 'Aqui puedes colocar un tablero por estado, una tabla maestra o un flujo comercial.',
        'metrics' => [
            ['value' => 'Kanban', 'label' => 'Patron util', 'detail' => 'Embudo por estado de venta.'],
            ['value' => 'Caja', 'label' => 'Relacion futura', 'detail' => 'Pagos, cierres y conciliacion.'],
            ['value' => 'Alertas', 'label' => 'Apoyo operativo', 'detail' => 'Retrasos, pedidos y pendientes.'],
        ],
        'checklist' => [
            'Definir resumen por dia, sede o vendedor.',
            'Agregar tabla con filtros, fechas y exportacion.',
            'Preparar detalle de pedido con timeline de cambios.',
        ],
        'notes' => [
            'El layout soporta bien dashboards comerciales con bloques mixtos.',
            'La navegacion acordeon permite crecer por submodulos sin saturar el sidebar.',
            'Puedes agregar accesos rapidos al header si una vista lo necesita.',
        ],
    ])->name('sales.index');

    Route::get('/clientes', [CustomerController::class, 'index'])->name('customers.index');
    Route::post('/clientes', [CustomerController::class, 'store'])->name('customers.store');
    Route::put('/clientes/{customer}', [CustomerController::class, 'update'])->name('customers.update');
    Route::delete('/clientes/{customer}', [CustomerController::class, 'destroy'])->name('customers.destroy');

    Route::prefix('cotizaciones')->name('quotations.')->group(function (): void {
        Route::get('/', [QuotationController::class, 'index'])->name('index');
        Route::get('/nueva', [QuotationController::class, 'create'])->name('create');

        Route::prefix('catalogo')->name('catalog.')->group(function (): void {
            Route::get('/', [QuotationItemController::class, 'index'])->name('index');
            Route::post('/items', [QuotationItemController::class, 'store'])->name('items.store');
            Route::put('/items/{quotationItem}', [QuotationItemController::class, 'update'])->name('items.update');
            Route::delete('/items/{quotationItem}', [QuotationItemController::class, 'destroy'])->name('items.destroy');
        });

        Route::get('/configuracion', [QuotationSettingsController::class, 'index'])->name('settings.index');
        Route::put('/configuracion', [QuotationSettingsController::class, 'update'])->name('settings.update');

        Route::get('/monedas', [CurrencyController::class, 'index'])->name('currencies.index');
        Route::post('/monedas', [CurrencyController::class, 'store'])->name('currencies.store');
        Route::put('/monedas/{currency}', [CurrencyController::class, 'update'])->name('currencies.update');
        Route::delete('/monedas/{currency}', [CurrencyController::class, 'destroy'])->name('currencies.destroy');

        Route::post('/', [QuotationController::class, 'store'])->name('store');
        Route::get('/{quotation}/pdf', [QuotationController::class, 'downloadPdf'])->name('pdf');
        Route::get('/{quotation}/editar', [QuotationController::class, 'edit'])->name('edit');
        Route::put('/{quotation}', [QuotationController::class, 'update'])->name('update');
        Route::delete('/{quotation}', [QuotationController::class, 'destroy'])->name('destroy');
    });

    Route::view('/reportes', 'admin.placeholder', [
        'eyebrow' => 'Operacion',
        'pageTitle' => 'Reportes',
        'pageDescription' => 'Base para analitica, exportaciones y vistas comparativas.',
        'summary' => 'El panel ya esta preparado para agregar graficos, filtros avanzados y tarjetas resumen.',
        'metrics' => [
            ['value' => 'CSV', 'label' => 'Salida esperada', 'detail' => 'Exportaciones y descargas.'],
            ['value' => 'KPI', 'label' => 'Bloque nativo', 'detail' => 'Metricas principales y resumen.'],
            ['value' => 'Trend', 'label' => 'Lectura visual', 'detail' => 'Comparativos por periodo.'],
        ],
        'checklist' => [
            'Definir filtros por fecha, sede y categoria.',
            'Agregar tarjetas KPI y contenedores para graficos.',
            'Preparar exportacion y estados de consulta vacia.',
        ],
        'notes' => [
            'La composicion del dashboard ya funciona como punto de partida.',
            'Puedes separar reportes por dominio sin tocar el resto del layout.',
            'Conviene extraer filtros globales a un componente Blade reutilizable.',
        ],
    ])->name('reports.index');

    Route::view('/configuracion/general', 'admin.placeholder', [
        'eyebrow' => 'Configuracion',
        'pageTitle' => 'General',
        'pageDescription' => 'Espacio para datos de empresa, parametros del sistema y reglas globales.',
        'summary' => 'Este modulo admite formularios largos, secciones plegables y ayudas contextuales.',
        'metrics' => [
            ['value' => 'Forms', 'label' => 'Patron esperado', 'detail' => 'Bloques por categoria de ajuste.'],
            ['value' => 'Media', 'label' => 'Extensible', 'detail' => 'Logo, archivos y datos de marca.'],
            ['value' => 'Audit', 'label' => 'Recomendado', 'detail' => 'Registro de cambios criticos.'],
        ],
        'checklist' => [
            'Separar empresa, regionalizacion y preferencias del sistema.',
            'Agregar validaciones y mensajes de ayuda por bloque.',
            'Preparar guardado parcial por seccion si el formulario crece.',
        ],
        'notes' => [
            'Las tarjetas del layout ya funcionan bien como fieldsets o paneles de ajustes.',
            'La version movil conserva espacio suficiente para formularios largos.',
            'Puedes reusar el mismo patron para impuestos, monedas o integraciones.',
        ],
    ])->name('settings.general');

    Route::view('/configuracion/apariencia', 'admin.placeholder', [
        'eyebrow' => 'Configuracion',
        'pageTitle' => 'Apariencia',
        'pageDescription' => 'Vista para controlar el lenguaje visual del panel y futuros ajustes de interfaz.',
        'summary' => 'Ya tienes selector de modo y paletas en el header; aqui puede vivir configuracion mas avanzada.',
        'metrics' => [
            ['value' => 'Tema', 'label' => 'Activo', 'detail' => 'Modo claro u oscuro persistente.'],
            ['value' => 'Color', 'label' => 'Paletas', 'detail' => 'Aurora, Laguna, Cobre y Bosque.'],
            ['value' => 'UX', 'label' => 'Escalable', 'detail' => 'Listo para densidad, tipografia y layout.'],
        ],
        'checklist' => [
            'Guardar preferencias visuales por usuario cuando agregues autenticacion.',
            'Agregar opciones futuras para densidad, esquinas o tamano de texto.',
            'Crear vista previa de componentes si deseas un mini design system interno.',
        ],
        'notes' => [
            'La logica actual de temas vive en resources/js/app.js.',
            'Los tokens visuales estan concentrados en resources/css/app.css.',
            'La lista de paletas queda centralizada en config/admin.php.',
        ],
    ])->name('settings.appearance');
});
