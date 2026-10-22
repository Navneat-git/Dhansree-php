<?php

declare(strict_types=1);

namespace App\Services\Catalog;

use App\Enums\AuditActorType;
use App\Enums\SeoRedirectOrigin;
use App\Models\SeoRedirect;
use App\Support\Audit\AuditLogger;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class SeoRedirectService
{
    /**
     * Stop redirects starting from $path so visitors aren't sent away from live content.
     */
    public function releaseRedirectSource(string $path): void
    {
        SeoRedirect::where('source_path', $path)
            ->where('is_active', true)
            ->update(['is_active' => false]);
    }

    /**
     * Record a permanent (301) redirect from $from to $to.
     * Prevents chains (A -> B -> C becomes A -> C) and loops (A -> B -> A).
     */
    public function recordAddressChange(
        string $from,
        string $to,
        ?int $adminUserId = null,
        ?Request $request = null
    ): void {
        $from = '/' . ltrim($from, '/');
        $to = '/' . ltrim($to, '/');

        if ($from === $to) {
            return;
        }

        DB::transaction(function () use ($from, $to, $adminUserId, $request) {
            // 1. Release any active redirects whose source is the destination
            $this->releaseRedirectSource($to);

            // 2. Collapse chains: Any redirect that pointed to the old path now points directly to the new path
            SeoRedirect::where('destination_path', $from)
                ->where('source_path', '!=', $to)
                ->update(['destination_path' => $to]);

            // 3. Upsert the redirect row
            $redirect = SeoRedirect::where('source_path', $from)->first();

            if ($redirect) {
                $redirect->update([
                    'destination_path' => $to,
                    'status_code' => 301,
                    'origin' => SeoRedirectOrigin::AUTOMATIC,
                    'is_active' => true,
                ]);
            } else {
                $redirect = SeoRedirect::create([
                    'source_path' => $from,
                    'destination_path' => $to,
                    'status_code' => 301,
                    'origin' => SeoRedirectOrigin::AUTOMATIC,
                    'is_active' => true,
                    'created_by_admin_user_id' => $adminUserId,
                ]);
            }

            // 4. Audit log
            if ($adminUserId) {
                AuditLogger::log(
                    'catalog.seo_redirect.created',
                    AuditActorType::ADMIN,
                    actorAdminUserId: $adminUserId,
                    entityType: 'seo_redirect',
                    entityId: $redirect->id,
                    metadata: [
                        'origin' => 'AUTOMATIC',
                        'source_path' => $from,
                        'destination_path' => $to,
                        'status_code' => 301,
                    ],
                    request: $request
                );
            }
        });
    }

    /**
     * Find active redirect for request path.
     */
    public function findRedirect(string $path): ?SeoRedirect
    {
        $normalized = '/' . ltrim($path, '/');

        return SeoRedirect::where('source_path', $normalized)
            ->where('is_active', true)
            ->first();
    }

    /**
     * List SEO redirects with pagination and stats.
     */
    public function list(array $filters = [], int $page = 1, int $perPage = 25): array
    {
        $query = SeoRedirect::query()->with('createdByAdminUser:id,name');

        if (!empty($filters['search'])) {
            $search = '%' . trim((string) $filters['search']) . '%';
            $query->where(function ($q) use ($search) {
                $q->where('source_path', 'like', $search)
                    ->orWhere('destination_path', 'like', $search);
            });
        }

        if (!empty($filters['status_code'])) {
            $query->where('status_code', (int) $filters['status_code']);
        }

        if (isset($filters['is_active']) && $filters['is_active'] !== '') {
            $query->where('is_active', (bool) $filters['is_active']);
        }

        if (!empty($filters['origin'])) {
            $query->where('origin', $filters['origin']);
        }

        $query->orderByDesc('id');
        $paginator = $query->paginate($perPage, ['*'], 'page', $page);

        return [
            'items' => collect($paginator->items()),
            'paginator' => $paginator,
            'stats' => [
                'total' => SeoRedirect::count(),
                'active' => SeoRedirect::where('is_active', true)->count(),
                'code_301' => SeoRedirect::where('status_code', 301)->count(),
                'code_410' => SeoRedirect::where('status_code', 410)->count(),
            ],
        ];
    }

    /**
     * Create or update manual redirect.
     */
    public function createManual(
        string $from,
        ?string $to,
        int $statusCode,
        \App\Models\AdminUser $admin,
        ?Request $request = null
    ): SeoRedirect {
        $from = strtolower('/' . ltrim($from, '/'));
        if ($to !== null && trim($to) !== '') {
            $to = strtolower('/' . ltrim(trim($to), '/'));
        } else {
            $to = null;
        }

        if (!in_array($statusCode, [301, 410], true)) {
            throw new \Symfony\Component\HttpKernel\Exception\UnprocessableEntityHttpException('Status code must be 301 (Moved Permanently) or 410 (Gone).');
        }

        if ($statusCode === 301 && empty($to)) {
            throw new \Symfony\Component\HttpKernel\Exception\UnprocessableEntityHttpException('Destination path is required for 301 permanent redirects.');
        }

        if ($statusCode === 410) {
            $to = null;
        }

        if ($from === $to) {
            throw new \Symfony\Component\HttpKernel\Exception\UnprocessableEntityHttpException('Source and destination cannot be identical.');
        }

        // Validate regex patterns matching DB constraint
        if (!preg_match('/^\/[a-z0-9._~\/-]*$/', $from) || str_starts_with($from, '//')) {
            throw new \Symfony\Component\HttpKernel\Exception\UnprocessableEntityHttpException('Source path must be a valid relative URL path starting with a single slash.');
        }

        if ($to !== null && (!preg_match('/^\/[a-z0-9\/-]*$/', $to) || str_starts_with($to, '//'))) {
            throw new \Symfony\Component\HttpKernel\Exception\UnprocessableEntityHttpException('Destination path must be a valid relative URL path starting with a single slash.');
        }

        return DB::transaction(function () use ($from, $to, $statusCode, $admin, $request) {
            if ($to !== null) {
                $this->releaseRedirectSource($to);

                // Collapse chains
                SeoRedirect::where('destination_path', $from)
                    ->where('source_path', '!=', $to)
                    ->update(['destination_path' => $to]);
            }

            $redirect = SeoRedirect::where('source_path', $from)->first();

            if ($redirect) {
                $redirect->update([
                    'destination_path' => $to,
                    'status_code' => $statusCode,
                    'origin' => SeoRedirectOrigin::MANUAL,
                    'is_active' => true,
                    'created_by_admin_user_id' => $admin->id,
                ]);
            } else {
                $redirect = SeoRedirect::create([
                    'source_path' => $from,
                    'destination_path' => $to,
                    'status_code' => $statusCode,
                    'origin' => SeoRedirectOrigin::MANUAL,
                    'is_active' => true,
                    'created_by_admin_user_id' => $admin->id,
                ]);
            }

            AuditLogger::log(
                action: 'SEO_REDIRECT_CREATED',
                actorType: AuditActorType::ADMIN,
                actorAdminUserId: $admin->id,
                entityType: 'seo_redirect',
                entityId: $redirect->id,
                metadata: [
                    'source_path' => $from,
                    'destination_path' => $to,
                    'status_code' => $statusCode,
                ],
                request: $request
            );

            return $redirect;
        });
    }

    /**
     * Toggle active state.
     */
    public function toggle(int $id, \App\Models\AdminUser $admin, ?Request $request = null): SeoRedirect
    {
        /** @var SeoRedirect|null $redirect */
        $redirect = SeoRedirect::find($id);
        if (!$redirect) {
            throw new \Symfony\Component\HttpKernel\Exception\NotFoundHttpException('Redirect not found.');
        }

        $newActive = !$redirect->is_active;
        $redirect->update(['is_active' => $newActive]);

        AuditLogger::log(
            action: 'SEO_REDIRECT_UPDATED',
            actorType: AuditActorType::ADMIN,
            actorAdminUserId: $admin->id,
            entityType: 'seo_redirect',
            entityId: $redirect->id,
            metadata: ['is_active' => $newActive],
            request: $request
        );

        return $redirect;
    }

    /**
     * Delete redirect.
     */
    public function delete(int $id, \App\Models\AdminUser $admin, ?Request $request = null): void
    {
        $redirect = SeoRedirect::find($id);
        if (!$redirect) {
            throw new \Symfony\Component\HttpKernel\Exception\NotFoundHttpException('Redirect not found.');
        }

        $path = $redirect->source_path;
        $redirect->delete();

        AuditLogger::log(
            action: 'SEO_REDIRECT_DELETED',
            actorType: AuditActorType::ADMIN,
            actorAdminUserId: $admin->id,
            entityType: 'seo_redirect',
            entityId: $id,
            metadata: ['source_path' => $path],
            request: $request
        );
    }
}
