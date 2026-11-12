<?php

declare(strict_types=1);

namespace App\Http\Controllers\Admin\SocialCommerce;

use App\Http\Controllers\Controller;
use App\Services\SocialCommerce\SocialCommerceDashboardService;
use Illuminate\Http\Request;
use Illuminate\View\View;

class SocialCommerceDashboardController extends Controller
{
    public function __construct(
        protected SocialCommerceDashboardService $dashboardService
    ) {}

    public function index(Request $request): View
    {
        $data = $this->dashboardService->getDashboardMetrics();

        return view('admin.social-commerce.dashboard', $data);
    }
}
