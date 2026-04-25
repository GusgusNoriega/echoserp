<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Support\Admin\AccessControlService;
use Illuminate\View\View;

class DashboardController extends Controller
{
    public function __construct(
        private readonly AccessControlService $accessControl,
    ) {
    }

    public function __invoke(): View
    {
        return view('admin.dashboard', [
            'eyebrow' => 'Panel inicial',
            'pageTitle' => 'Dashboard administrativo',
            'pageDescription' => 'Base real para gestionar usuarios, roles y permisos sin rehacer la navegacion del panel.',
            ...$this->accessControl->dashboardData(),
        ]);
    }
}
