<?php

declare(strict_types=1);

namespace App\Services\Banner;

use App\Enums\AuditActorType;
use App\Models\AdminUser;
use App\Models\HomepageBanner;
use App\Support\Audit\AuditLogger;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;
use Symfony\Component\HttpKernel\Exception\UnprocessableEntityHttpException;

class AdminBannersService
{
    /**
     * List banners ordered by position.
     */
    public function list(array $filters = [], int $page = 1, int $perPage = 20): array
    {
        $query = HomepageBanner::query()->with('createdByAdminUser:id,name');

        if (isset($filters['is_active']) && $filters['is_active'] !== '') {
            $query->where('is_active', (bool) $filters['is_active']);
        }

        if (!empty($filters['search'])) {
            $s = '%' . trim((string) $filters['search']) . '%';
            $query->where(function ($q) use ($s) {
                $q->where('title', 'like', $s)
                    ->orWhere('eyebrow', 'like', $s)
                    ->orWhere('subtitle', 'like', $s);
            });
        }

        $query->orderBy('position')->orderByDesc('id');
        $paginator = $query->paginate($perPage, ['*'], 'page', $page);

        return [
            'items' => collect($paginator->items()),
            'paginator' => $paginator,
            'stats' => [
                'total' => HomepageBanner::count(),
                'active' => HomepageBanner::where('is_active', true)->count(),
                'scheduled' => HomepageBanner::where('starts_at', '>', now())->count(),
                'expired' => HomepageBanner::where('ends_at', '<=', now())->count(),
            ],
        ];
    }

    /**
     * Get banner detail.
     */
    public function detail(int $id): HomepageBanner
    {
        /** @var HomepageBanner|null $banner */
        $banner = HomepageBanner::find($id);
        if (!$banner) {
            throw new NotFoundHttpException('Banner not found.');
        }

        return $banner;
    }

    /**
     * Create a new homepage banner.
     */
    public function create(
        array $data,
        ?UploadedFile $desktopImage = null,
        ?UploadedFile $mobileImage = null,
        AdminUser $admin,
        ?Request $request = null
    ): HomepageBanner {
        $clean = $this->validateAndPrepare($data, $desktopImage, $mobileImage);

        $clean['created_by_admin_user_id'] = $admin->id;
        $clean['version'] = 0;

        $banner = HomepageBanner::create($clean);

        AuditLogger::log(
            action: 'BANNER_CREATED',
            actorType: AuditActorType::ADMIN,
            actorAdminUserId: $admin->id,
            entityType: 'banner',
            entityId: $banner->id,
            metadata: [
                'title' => $banner->title,
                'link_path' => $banner->link_path,
                'position' => $banner->position,
            ],
            request: $request
        );

        return $banner;
    }

    /**
     * Update an existing homepage banner.
     */
    public function update(
        int $id,
        array $data,
        ?UploadedFile $desktopImage = null,
        ?UploadedFile $mobileImage = null,
        AdminUser $admin,
        ?Request $request = null
    ): HomepageBanner {
        $banner = $this->detail($id);

        $clean = $this->validateAndPrepare($data, $desktopImage, $mobileImage, $banner);
        $clean['version'] = $banner->version + 1;

        $banner->update($clean);

        AuditLogger::log(
            action: 'BANNER_UPDATED',
            actorType: AuditActorType::ADMIN,
            actorAdminUserId: $admin->id,
            entityType: 'banner',
            entityId: $banner->id,
            metadata: [
                'title' => $banner->title,
                'is_active' => $banner->is_active,
            ],
            request: $request
        );

        return $banner;
    }

    /**
     * Toggle banner active status.
     */
    public function toggleActive(int $id, AdminUser $admin, ?Request $request = null): HomepageBanner
    {
        $banner = $this->detail($id);

        $newActive = !$banner->is_active;
        $banner->update([
            'is_active' => $newActive,
            'version' => $banner->version + 1,
        ]);

        AuditLogger::log(
            action: 'BANNER_UPDATED',
            actorType: AuditActorType::ADMIN,
            actorAdminUserId: $admin->id,
            entityType: 'banner',
            entityId: $banner->id,
            metadata: [
                'action' => 'TOGGLE_ACTIVE',
                'is_active' => $newActive,
            ],
            request: $request
        );

        return $banner;
    }

    /**
     * Delete a banner and its stored images.
     */
    public function delete(int $id, AdminUser $admin, ?Request $request = null): void
    {
        $banner = $this->detail($id);

        // Remove media files if local
        if ($banner->image_storage_key && Storage::disk('public')->exists('media/' . $banner->image_storage_key)) {
            Storage::disk('public')->delete('media/' . $banner->image_storage_key);
        }
        if ($banner->mobile_storage_key && Storage::disk('public')->exists('media/' . $banner->mobile_storage_key)) {
            Storage::disk('public')->delete('media/' . $banner->mobile_storage_key);
        }

        $title = $banner->title;
        $banner->delete();

        AuditLogger::log(
            action: 'BANNER_DELETED',
            actorType: AuditActorType::ADMIN,
            actorAdminUserId: $admin->id,
            entityType: 'banner',
            entityId: $id,
            metadata: ['title' => $title],
            request: $request
        );
    }

    /**
     * Validate data and process images according to check constraints.
     */
    private function validateAndPrepare(
        array $data,
        ?UploadedFile $desktopImage,
        ?UploadedFile $mobileImage,
        ?HomepageBanner $existing = null
    ): array {
        $linkPath = !empty($data['link_path']) ? trim((string) $data['link_path']) : null;
        if ($linkPath !== null) {
            if (!str_starts_with($linkPath, '/') || str_starts_with($linkPath, '//') || str_contains($linkPath, ':') || str_contains($linkPath, '\\')) {
                throw new UnprocessableEntityHttpException('Link path must start with a single slash (e.g. /kurta-set) and contain no domain or colon.');
            }
        }

        $ctaLabel = !empty($data['cta_label']) ? trim((string) $data['cta_label']) : null;
        if ($ctaLabel !== null && $linkPath === null) {
            throw new UnprocessableEntityHttpException('A link path is required when providing a CTA button label.');
        }

        $wordMode = $data['word_mode'] ?? 'ON_PICTURE';
        if (!in_array($wordMode, ['ON_PICTURE', 'ON_LIGHTENED', 'LIGHT_ON_PICTURE'], true)) {
            $wordMode = 'ON_PICTURE';
        }

        $startsAt = !empty($data['starts_at']) ? Carbon::parse($data['starts_at']) : null;
        $endsAt = !empty($data['ends_at']) ? Carbon::parse($data['ends_at']) : null;

        if ($startsAt && $endsAt && $endsAt->lte($startsAt)) {
            throw new UnprocessableEntityHttpException('End date must be after the start date.');
        }

        $position = isset($data['position']) ? max(0, (int) $data['position']) : 0;
        $isActive = isset($data['is_active']) ? (bool) $data['is_active'] : true;

        // Process Desktop Image
        $imageStorageKey = $existing?->image_storage_key;
        $imageAltText = !empty($data['image_alt_text']) ? trim((string) $data['image_alt_text']) : $existing?->image_alt_text;
        $imageWidth = $existing?->image_width;
        $imageHeight = $existing?->image_height;

        if ($desktopImage) {
            $key = 'banners/' . Str::uuid() . '.' . ($desktopImage->getClientOriginalExtension() ?: 'webp');
            Storage::disk('public')->putFileAs('media/banners', $desktopImage, basename($key));

            $extraDir = config('store.media_local_directory') ?: (is_dir('/home/dhansree/files/ds-assets/ds-web') ? '/home/dhansree/files/ds-assets/ds-web' : null);
            if ($extraDir) {
                $targetFile = rtrim($extraDir, '/') . '/' . ltrim($key, '/');
                $targetDir = dirname($targetFile);
                if (!is_dir($targetDir)) {
                    @mkdir($targetDir, 0775, true);
                }
                @file_put_contents($targetFile, file_get_contents($desktopImage->getRealPath()));
            }
            
            $imageStorageKey = $key;
            $dimensions = @getimagesize($desktopImage->getRealPath());
            $imageWidth = $dimensions ? $dimensions[0] : 1920;
            $imageHeight = $dimensions ? $dimensions[1] : 600;
            if (!$imageAltText) {
                $imageAltText = $data['title'] ?? 'Dhansree Promotional Banner';
            }
        }

        // Enforce DB check: (image_storage_key is null) = (image_alt_text is null) = (width is null) = (height is null)
        if ($imageStorageKey === null) {
            $imageAltText = null;
            $imageWidth = null;
            $imageHeight = null;
        } elseif ($imageAltText === null || $imageWidth === null || $imageHeight === null) {
            $imageAltText = $imageAltText ?: ($data['title'] ?? 'Dhansree Banner');
            $imageWidth = $imageWidth ?: 1920;
            $imageHeight = $imageHeight ?: 600;
        }

        // Process Mobile Image
        $mobileStorageKey = $existing?->mobile_storage_key;
        $mobileAltText = !empty($data['mobile_alt_text']) ? trim((string) $data['mobile_alt_text']) : $existing?->mobile_alt_text;
        $mobileWidth = $existing?->mobile_width;
        $mobileHeight = $existing?->mobile_height;

        if ($mobileImage) {
            $mKey = 'banners/mobile_' . Str::uuid() . '.' . ($mobileImage->getClientOriginalExtension() ?: 'webp');
            Storage::disk('public')->putFileAs('media/banners', $mobileImage, basename($mKey));

            $extraDir = config('store.media_local_directory') ?: (is_dir('/home/dhansree/files/ds-assets/ds-web') ? '/home/dhansree/files/ds-assets/ds-web' : null);
            if ($extraDir) {
                $targetFile = rtrim($extraDir, '/') . '/' . ltrim($mKey, '/');
                $targetDir = dirname($targetFile);
                if (!is_dir($targetDir)) {
                    @mkdir($targetDir, 0775, true);
                }
                @file_put_contents($targetFile, file_get_contents($mobileImage->getRealPath()));
            }

            $mobileStorageKey = $mKey;
            $mDimensions = @getimagesize($mobileImage->getRealPath());
            $mobileWidth = $mDimensions ? $mDimensions[0] : 750;
            $mobileHeight = $mDimensions ? $mDimensions[1] : 750;
            if (!$mobileAltText) {
                $mobileAltText = $data['title'] ?? 'Dhansree Mobile Banner';
            }
        }

        // Enforce DB check: (mobile_storage_key is null) = (mobile_alt_text is null) = (width is null) = (height is null)
        if ($mobileStorageKey === null) {
            $mobileAltText = null;
            $mobileWidth = null;
            $mobileHeight = null;
        } elseif ($mobileAltText === null || $mobileWidth === null || $mobileHeight === null) {
            $mobileAltText = $mobileAltText ?: ($data['title'] ?? 'Dhansree Mobile Banner');
            $mobileWidth = $mobileWidth ?: 750;
            $mobileHeight = $mobileHeight ?: 750;
        }

        return [
            'title' => !empty($data['title']) ? trim((string) $data['title']) : null,
            'eyebrow' => !empty($data['eyebrow']) ? trim((string) $data['eyebrow']) : null,
            'subtitle' => !empty($data['subtitle']) ? trim((string) $data['subtitle']) : null,
            'caption' => !empty($data['caption']) ? trim((string) $data['caption']) : null,
            'image_storage_key' => $imageStorageKey,
            'image_alt_text' => $imageAltText,
            'image_width' => $imageWidth,
            'image_height' => $imageHeight,
            'mobile_storage_key' => $mobileStorageKey,
            'mobile_alt_text' => $mobileAltText,
            'mobile_width' => $mobileWidth,
            'mobile_height' => $mobileHeight,
            'link_path' => $linkPath,
            'cta_label' => $ctaLabel,
            'word_mode' => $wordMode,
            'starts_at' => $startsAt,
            'ends_at' => $endsAt,
            'is_active' => $isActive,
            'position' => $position,
        ];
    }
}
