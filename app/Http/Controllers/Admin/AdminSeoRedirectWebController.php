<?php

declare(strict_types=1);

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\AdminUser;
use App\Services\Catalog\SeoRedirectService;
use App\Support\Rbac\Permissions;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\View\View;

class AdminSeoRedirectWebController extends Controller
{
    public function __construct(
        protected SeoRedirectService $seoService
    ) {}

    /**
     * List SEO redirects.
     */
    public function index(Request $request): View
    {
        /** @var AdminUser $admin */
        $admin = Auth::guard('admin')->user();
        if (!$admin->hasPermission(Permissions::SEO_VIEW) && !$admin->hasSuperAdmin()) {
            abort(403, 'You do not have permission to view SEO redirects.');
        }

        $filters = [
            'search' => $request->query('search'),
            'status_code' => $request->query('status_code'),
            'is_active' => $request->query('is_active'),
            'origin' => $request->query('origin'),
        ];
        $page = max(1, (int) $request->query('page', 1));

        $data = $this->seoService->list($filters, $page, 25);

        return view('admin.seo.index', [
            'redirects' => $data['items'],
            'paginator' => $data['paginator'],
            'stats' => $data['stats'],
            'filters' => $filters,
            'canUpdate' => $admin->hasPermission(Permissions::SEO_UPDATE) || $admin->hasSuperAdmin(),
        ]);
    }

    /**
     * Store manual redirect.
     */
    public function store(Request $request): RedirectResponse
    {
        /** @var AdminUser $admin */
        $admin = Auth::guard('admin')->user();
        if (!$admin->hasPermission(Permissions::SEO_UPDATE) && !$admin->hasSuperAdmin()) {
            abort(403, 'You do not have permission to manage SEO redirects.');
        }

        $validated = $request->validate([
            'source_path' => ['required', 'string', 'max:255', 'regex:/^\/[a-z0-9._~\/-]*$/'],
            'destination_path' => ['nullable', 'string', 'max:255', 'regex:/^\/[a-z0-9\/-]*$/'],
            'status_code' => 'required|in:301,410',
        ]);

        $this->seoService->createManual(
            $validated['source_path'],
            $validated['destination_path'] ?? null,
            (int) $validated['status_code'],
            $admin,
            $request
        );

        return redirect()->route('admin.seo.index')
            ->with('success', 'SEO redirect created successfully.');
    }

    /**
     * Toggle redirect active state.
     */
    public function toggle(Request $request, int $id): RedirectResponse
    {
        /** @var AdminUser $admin */
        $admin = Auth::guard('admin')->user();
        if (!$admin->hasPermission(Permissions::SEO_UPDATE) && !$admin->hasSuperAdmin()) {
            abort(403, 'You do not have permission to manage SEO redirects.');
        }

        $redirect = $this->seoService->toggle($id, $admin, $request);
        $stateText = $redirect->is_active ? 'activated' : 'deactivated';

        return back()->with('success', "Redirect for '{$redirect->source_path}' {$stateText}.");
    }

    /**
     * Delete redirect.
     */
    public function destroy(Request $request, int $id): RedirectResponse
    {
        /** @var AdminUser $admin */
        $admin = Auth::guard('admin')->user();
        if (!$admin->hasPermission(Permissions::SEO_UPDATE) && !$admin->hasSuperAdmin()) {
            abort(403, 'You do not have permission to manage SEO redirects.');
        }

        $this->seoService->delete($id, $admin, $request);

        return redirect()->route('admin.seo.index')
            ->with('success', 'Redirect rule deleted.');
    }
}
