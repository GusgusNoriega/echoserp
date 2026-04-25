<?php

return [
    'brand' => [
        'name' => 'EchoPanel',
        'subtitle' => 'Backoffice adaptable',
        'logo' => 'branding/logo.png',
        'logo_alt' => 'Logo de ECHOS Technology',
    ],

    'palettes' => [
        ['key' => 'aurora', 'label' => 'Aurora', 'color' => '#5b5bd6'],
        ['key' => 'lagoon', 'label' => 'Laguna', 'color' => '#118ab2'],
        ['key' => 'ember', 'label' => 'Cobre', 'color' => '#d97706'],
        ['key' => 'forest', 'label' => 'Bosque', 'color' => '#1f8f5f'],
    ],

    'navigation' => [
        [
            'type' => 'link',
            'label' => 'Dashboard',
            'route' => 'admin.dashboard',
            'icon' => 'home',
            'description' => 'Resumen general del panel.',
            'badge' => 'Base',
        ],
        [
            'type' => 'group',
            'label' => 'Administracion',
            'icon' => 'shield',
            'description' => 'Equipo, accesos y estructura.',
            'children' => [
                [
                    'label' => 'Usuarios',
                    'route' => 'admin.users.index',
                    'description' => 'Altas, perfiles y acceso.',
                ],
                [
                    'label' => 'Roles',
                    'route' => 'admin.roles.index',
                    'description' => 'Perfiles reutilizables por modulo.',
                ],
                [
                    'label' => 'Permisos',
                    'route' => 'admin.permissions.index',
                    'description' => 'Acciones y vistas del panel.',
                ],
                [
                    'label' => 'Modulos',
                    'route' => 'admin.modules.index',
                    'description' => 'Catalogo base para agrupar permisos.',
                ],
                [
                    'label' => 'Sucursales',
                    'route' => 'admin.branches.index',
                    'description' => 'Sedes, responsables y alcance.',
                ],
            ],
        ],
        [
            'type' => 'group',
            'label' => 'Operacion',
            'icon' => 'layers',
            'description' => 'Areas que puedes ampliar por modulo.',
            'children' => [
                [
                    'label' => 'Inventario',
                    'route' => 'admin.inventory.index',
                    'description' => 'Productos, stock y alertas.',
                ],
                [
                    'label' => 'Ventas',
                    'route' => 'admin.sales.index',
                    'description' => 'Pedidos, estados y seguimiento.',
                ],
                [
                    'label' => 'Clientes',
                    'route' => 'admin.customers.index',
                    'description' => 'Datos comerciales reutilizables.',
                ],
                [
                    'label' => 'Cotizaciones',
                    'route' => 'admin.quotations.index',
                    'description' => 'Documentos comerciales con cliente, items y terminos.',
                ],
                [
                    'label' => 'Catalogo comercial',
                    'route' => 'admin.quotations.catalog.index',
                    'description' => 'Productos y servicios reutilizables para cotizar.',
                ],
                [
                    'label' => 'Monedas',
                    'route' => 'admin.quotations.currencies.index',
                    'description' => 'Tipos de moneda para precios de cotizacion.',
                ],
                [
                    'label' => 'Config. cotizacion',
                    'route' => 'admin.quotations.settings.index',
                    'description' => 'Datos corporativos y valores por defecto.',
                ],
                [
                    'label' => 'Reportes',
                    'route' => 'admin.reports.index',
                    'description' => 'Analitica, exportaciones y comparativos.',
                ],
            ],
        ],
        [
            'type' => 'group',
            'label' => 'Configuracion',
            'icon' => 'settings',
            'description' => 'Ajustes base del panel y la empresa.',
            'children' => [
                [
                    'label' => 'General',
                    'route' => 'admin.settings.general',
                    'description' => 'Empresa, parametros y reglas globales.',
                ],
                [
                    'label' => 'Apariencia',
                    'route' => 'admin.settings.appearance',
                    'description' => 'Modo, paletas y lenguaje visual.',
                ],
            ],
        ],
    ],
];
