<?php

namespace App\Http\Controllers;

use App\Interface\DashboardInterface;
use Illuminate\Http\Request;
use Inertia\Inertia;

class DashboardController extends Controller
{
    private DashboardInterface $dashboardService;
    public function __construct(DashboardInterface $dashboardService) {
        $this->dashboardService = $dashboardService;
    }

    public function index(){
        $dashboard = $this->dashboardService->getDashboardData();

        return Inertia::render('Dashboard', [
            'dashboardData' => $dashboard
        ]);
    }
    

    
    
}
