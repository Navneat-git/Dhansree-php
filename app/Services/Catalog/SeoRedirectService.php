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
}
