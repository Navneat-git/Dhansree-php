<?php

declare(strict_types=1);

namespace App\Services\Catalog;

use App\Enums\AuditActorType;
use App\Models\Category;
use App\Support\Audit\AuditLogger;
use Carbon\Carbon;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\Request;
use Illuminate\Http\UploadedFile;
use Illuminate\Pagination\LengthAwarePaginator;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use InvalidArgumentException;
use RuntimeException;

class CategoryService
{
    public function __construct(
        private readonly CategoryTreeService $treeService,
        private readonly SlugService $slugService,
        private readonly SeoRedirectService $redirectService,
        private readonly ImageProcessor $imageProcessor
    ) {}

    /**
     * List categories with filtering and pagination.
     */
    public function list(array $filters = [], int $perPage = 50): LengthAwarePaginator
    {
        $query = Category::query()->withCount([
            'children as child_count' => fn(Builder $q) => $q->whereNull('archived_at'),
            'products as product_count' => fn(Builder $q) => $q->where('status', '!=', 'ARCHIVED'),
        ]);

        $status = $filters['status'] ?? 'active';
        if ($status === 'archived') {
            $query->whereNotNull('archived_at');
        } else {
            $query->whereNull('archived_at');
            if ($status === 'visible') {
                $query->where('is_visible', true);
            } elseif ($status === 'hidden') {
                $query->where('is_visible', false);
            }
        }

        if (!empty($filters['search'])) {
            $search = '%' . $filters['search'] . '%';
            $query->where(function (Builder $q) use ($search) {
                $q->where('name', 'like', $search)
                  ->orWhere('slug', 'like', $search);
            });
            $query->orderBy('name')->orderBy('id');
        } elseif ($status === 'archived') {
            $query->orderBy('name')->orderBy('id');
        } else {
            if (array_key_exists('parent_id', $filters)) {
                $query->where('parent_id', $filters['parent_id']);
            }
            $query->orderBy('position')->orderBy('id');
        }

        return $query->paginate($perPage);
    }

    /**
     * Get details for category including lineage and tree properties.
     */
    public function detail(int $id): Category
    {
        $category = Category::withCount([
            'children as child_count' => fn(Builder $q) => $q->whereNull('archived_at'),
            'products as product_count' => fn(Builder $q) => $q->where('status', '!=', 'ARCHIVED'),
        ])->findOrFail($id);

        $parentMap = $this->treeService->getActiveParentMap();
        $ancestorIds = array_reverse($this->treeService->ancestorIds($id, $parentMap));

        $ancestors = empty($ancestorIds) ? collect([]) : Category::whereIn('id', $ancestorIds)
            ->select('id', 'name', 'slug')
            ->get()
            ->sortBy(fn($cat) => array_search($cat->id, $ancestorIds));

        $category->setAttribute('ancestors', $ancestors->values());
        $category->setAttribute('depth', $this->treeService->depthOf($id, $parentMap));
        $category->setAttribute('subtree_height', $this->treeService->subtreeHeight($id, $parentMap));

        return $category;
    }

    /**
     * Create a category with depth validation and slug uniqueness.
     */
    public function create(array $data, ?int $adminUserId = null, ?Request $request = null): Category
    {
        return DB::transaction(function () use ($data, $adminUserId, $request) {
            $parentId = !empty($data['parent_id']) ? (int) $data['parent_id'] : null;

            if ($parentId !== null) {
                $parent = Category::lockForUpdate()->find($parentId);
                if (!$parent || $parent->archived_at !== null) {
                    throw new InvalidArgumentException('Chosen parent category does not exist or is archived.');
                }

                $parentMap = $this->treeService->getActiveParentMap();
                if ($this->treeService->depthOf($parentId, $parentMap) + 1 > CategoryTreeService::MAX_DEPTH) {
                    throw new InvalidArgumentException(
                        sprintf('Categories can be at most %d levels deep.', CategoryTreeService::MAX_DEPTH)
                    );
                }
            }

            // Slug generation / check
            $name = trim($data['name']);
            if (!empty($data['slug'])) {
                $slug = SlugService::slugify($data['slug']);
                if (in_array($slug, SlugService::RESERVED_CATEGORY_SLUGS, true)) {
                    throw new InvalidArgumentException("The URL slug '{$slug}' is reserved by the system.");
                }
                if (Category::where('slug', $slug)->exists()) {
                    throw new InvalidArgumentException("The category slug '{$slug}' is already taken.");
                }
            } else {
                $slug = SlugService::generateUnique(
                    $name,
                    fn(string $cand) => Category::where('slug', $cand)->exists(),
                    SlugService::RESERVED_CATEGORY_SLUGS
                );
            }

            // Next position among siblings
            $maxPos = Category::where('parent_id', $parentId)
                ->whereNull('archived_at')
                ->max('position');
            $position = $maxPos !== null ? $maxPos + 1 : 0;

            $category = Category::create([
                'name' => $name,
                'slug' => $slug,
                'parent_id' => $parentId,
                'description' => $data['description'] ?? null,
                'position' => $position,
                'is_visible' => (bool) ($data['is_visible'] ?? true),
                'show_in_browse' => (bool) ($data['show_in_browse'] ?? true),
                'card_shape' => $data['card_shape'] ?? null,
                'seo_title' => $data['seo_title'] ?? null,
                'seo_description' => $data['seo_description'] ?? null,
                'version' => 0,
            ]);

            // Release any redirect that led from this new category's URL
            $this->redirectService->releaseRedirectSource('/' . $slug);

            if ($adminUserId) {
                AuditLogger::log(
                    'catalog.category.created',
                    AuditActorType::ADMIN,
                    actorAdminUserId: $adminUserId,
                    entityType: 'category',
                    entityId: $category->id,
                    metadata: [
                        'name' => $category->name,
                        'slug' => $category->slug,
                        'parent_id' => $category->parent_id,
                        'is_visible' => $category->is_visible,
                    ],
                    request: $request
                );
            }

            return $category;
        });
    }

    /**
     * Update category details, parent, slug, and settings.
     */
    public function update(Category $category, array $data, ?int $adminUserId = null, ?Request $request = null): Category
    {
        return DB::transaction(function () use ($category, $data, $adminUserId, $request) {
            // Re-fetch under lock
            $current = Category::lockForUpdate()->findOrFail($category->id);

            if ($current->archived_at !== null) {
                throw new InvalidArgumentException('Archived categories cannot be edited. Restore it first.');
            }

            if (isset($data['version']) && (int) $data['version'] !== (int) $current->version) {
                throw new RuntimeException('This category was modified by another administrator. Please refresh.');
            }

            $parentMap = $this->treeService->getActiveParentMap();
            $newParentId = array_key_exists('parent_id', $data)
                ? (!empty($data['parent_id']) ? (int) $data['parent_id'] : null)
                : $current->parent_id;

            $moved = $newParentId !== $current->parent_id;
            if ($moved && $newParentId !== null) {
                if ($this->treeService->wouldCreateCycle($current->id, $newParentId, $parentMap)) {
                    throw new InvalidArgumentException('A category cannot be moved inside itself or one of its descendants.');
                }

                $subtreeHeight = $this->treeService->subtreeHeight($current->id, $parentMap);
                $newParentDepth = $this->treeService->depthOf($newParentId, $parentMap);

                if ($newParentDepth + 1 + $subtreeHeight > CategoryTreeService::MAX_DEPTH) {
                    throw new InvalidArgumentException(
                        sprintf('Categories can be at most %d levels deep. Choose a higher-level parent.', CategoryTreeService::MAX_DEPTH)
                    );
                }
            }

            $newSlug = !empty($data['slug']) ? SlugService::slugify($data['slug']) : $current->slug;
            if ($newSlug !== $current->slug) {
                if (in_array($newSlug, SlugService::RESERVED_CATEGORY_SLUGS, true)) {
                    throw new InvalidArgumentException("The URL slug '{$newSlug}' is reserved by the system.");
                }
                $exists = Category::where('slug', $newSlug)->where('id', '!=', $current->id)->exists();
                if ($exists) {
                    throw new InvalidArgumentException("The URL slug '{$newSlug}' is already in use by another category.");
                }

                // Record 301 redirect
                $this->redirectService->recordAddressChange('/' . $current->slug, '/' . $newSlug, $adminUserId, $request);
            }

            $updates = [
                'name' => trim($data['name'] ?? $current->name),
                'slug' => $newSlug,
                'parent_id' => $newParentId,
                'description' => array_key_exists('description', $data) ? $data['description'] : $current->description,
                'is_visible' => array_key_exists('is_visible', $data) ? (bool) $data['is_visible'] : $current->is_visible,
                'show_in_browse' => array_key_exists('show_in_browse', $data) ? (bool) $data['show_in_browse'] : $current->show_in_browse,
                'card_shape' => array_key_exists('card_shape', $data) ? $data['card_shape'] : $current->card_shape,
                'seo_title' => array_key_exists('seo_title', $data) ? $data['seo_title'] : $current->seo_title,
                'seo_description' => array_key_exists('seo_description', $data) ? $data['seo_description'] : $current->seo_description,
                'version' => $current->version + 1,
            ];

            if ($moved) {
                $maxPos = Category::where('parent_id', $newParentId)
                    ->whereNull('archived_at')
                    ->max('position');
                $updates['position'] = $maxPos !== null ? $maxPos + 1 : 0;
            }

            $current->update($updates);

            if ($adminUserId) {
                AuditLogger::log(
                    'catalog.category.updated',
                    AuditActorType::ADMIN,
                    actorAdminUserId: $adminUserId,
                    entityType: 'category',
                    entityId: $current->id,
                    metadata: ['updated_fields' => array_keys($data)],
                    request: $request
                );
            }

            return $current;
        });
    }

    /**
     * Quick toggle category visibility.
     */
    public function setVisibility(Category $category, bool $isVisible, ?int $adminUserId = null, ?Request $request = null): Category
    {
        if ($category->archived_at !== null) {
            throw new InvalidArgumentException('Restore this category before changing its visibility.');
        }

        if ($category->is_visible === $isVisible) {
            return $category;
        }

        $category->update([
            'is_visible' => $isVisible,
            'version' => $category->version + 1,
        ]);

        if ($adminUserId) {
            AuditLogger::log(
                'catalog.category.visibility_changed',
                AuditActorType::ADMIN,
                actorAdminUserId: $adminUserId,
                entityType: 'category',
                entityId: $category->id,
                metadata: ['is_visible' => $isVisible],
                request: $request
            );
        }

        return $category;
    }

    /**
     * Reorder sibling categories.
     *
     * @param int[] $orderedIds
     */
    public function reorder(?int $parentId, array $orderedIds, ?int $adminUserId = null, ?Request $request = null): void
    {
        DB::transaction(function () use ($parentId, $orderedIds, $adminUserId, $request) {
            foreach ($orderedIds as $index => $catId) {
                Category::where('id', $catId)
                    ->where('parent_id', $parentId)
                    ->whereNull('archived_at')
                    ->update(['position' => $index]);
            }

            if ($adminUserId) {
                AuditLogger::log(
                    'catalog.category.reordered',
                    AuditActorType::ADMIN,
                    actorAdminUserId: $adminUserId,
                    entityType: 'category',
                    entityId: $parentId,
                    metadata: ['parent_id' => $parentId, 'order' => $orderedIds],
                    request: $request
                );
            }
        });
    }

    /**
     * Archive category (refuses if active children or products exist).
     */
    public function archive(Category $category, ?int $adminUserId = null, ?Request $request = null): Category
    {
        return DB::transaction(function () use ($category, $adminUserId, $request) {
            $cat = Category::lockForUpdate()->findOrFail($category->id);

            if ($cat->archived_at !== null) {
                throw new InvalidArgumentException('This category is already archived.');
            }

            $childCount = Category::where('parent_id', $cat->id)
                ->whereNull('archived_at')
                ->count();
            if ($childCount > 0) {
                throw new InvalidArgumentException("Cannot archive: Move or archive its {$childCount} subcategories first.");
            }

            $productCount = $cat->products()
                ->where('status', '!=', 'ARCHIVED')
                ->count();
            if ($productCount > 0) {
                throw new InvalidArgumentException("Cannot archive: Move its {$productCount} products to another category first, or archive them.");
            }

            $cat->update([
                'archived_at' => Carbon::now('UTC'),
                'version' => $cat->version + 1,
            ]);

            if ($adminUserId) {
                AuditLogger::log(
                    'catalog.category.archived',
                    AuditActorType::ADMIN,
                    actorAdminUserId: $adminUserId,
                    entityType: 'category',
                    entityId: $cat->id,
                    request: $request
                );
            }

            return $cat;
        });
    }

    /**
     * Restore an archived category.
     */
    public function restore(Category $category, ?int $adminUserId = null, ?Request $request = null): Category
    {
        return DB::transaction(function () use ($category, $adminUserId, $request) {
            $cat = Category::lockForUpdate()->findOrFail($category->id);

            if ($cat->archived_at === null) {
                throw new InvalidArgumentException('This category is not archived.');
            }

            if ($cat->parent_id !== null) {
                $parent = Category::find($cat->parent_id);
                if (!$parent || $parent->archived_at !== null) {
                    throw new InvalidArgumentException('Restore the parent category first before restoring this subcategory.');
                }

                $parentMap = $this->treeService->getActiveParentMap();
                if ($this->treeService->depthOf($cat->parent_id, $parentMap) + 1 > CategoryTreeService::MAX_DEPTH) {
                    throw new InvalidArgumentException('Categories can be at most 3 levels deep.');
                }
            }

            $maxPos = Category::where('parent_id', $cat->parent_id)
                ->whereNull('archived_at')
                ->max('position');
            $position = $maxPos !== null ? $maxPos + 1 : 0;

            $cat->update([
                'archived_at' => null,
                'position' => $position,
                'version' => $cat->version + 1,
            ]);

            if ($adminUserId) {
                AuditLogger::log(
                    'catalog.category.restored',
                    AuditActorType::ADMIN,
                    actorAdminUserId: $adminUserId,
                    entityType: 'category',
                    entityId: $cat->id,
                    request: $request
                );
            }

            return $cat;
        });
    }

    /**
     * Upload/set category picture.
     */
    public function setImage(
        Category $category,
        UploadedFile $file,
        string $altText,
        ?int $adminUserId = null,
        ?Request $request = null
    ): Category {
        if ($category->archived_at !== null) {
            throw new InvalidArgumentException('Restore this category before updating its picture.');
        }

        $processed = $this->imageProcessor->process($file);
        $storageKey = sprintf('categories/%d/%s.webp', $category->id, (string) Str::uuid());

        // Store file to disk (public disk)
        Storage::disk('public')->put('media/' . $storageKey, $processed['data']);

        $oldKey = $category->image_storage_key;

        $category->update([
            'image_storage_key' => $storageKey,
            'image_alt_text' => Str::limit(trim($altText), 160),
            'image_width' => $processed['width'],
            'image_height' => $processed['height'],
        ]);

        if ($oldKey && $oldKey !== $storageKey) {
            Storage::disk('public')->delete('media/' . $oldKey);
        }

        if ($adminUserId) {
            AuditLogger::log(
                'catalog.category.image_changed',
                AuditActorType::ADMIN,
                actorAdminUserId: $adminUserId,
                entityType: 'category',
                entityId: $category->id,
                metadata: [
                    'width' => $processed['width'],
                    'height' => $processed['height'],
                    'byte_size' => $processed['byteSize'],
                ],
                request: $request
            );
        }

        return $category;
    }

    /**
     * Remove category picture.
     */
    public function removeImage(Category $category, ?int $adminUserId = null, ?Request $request = null): Category
    {
        $oldKey = $category->image_storage_key;
        if (!$oldKey) {
            return $category;
        }

        $category->update([
            'image_storage_key' => null,
            'image_alt_text' => null,
            'image_width' => null,
            'image_height' => null,
        ]);

        Storage::disk('public')->delete('media/' . $oldKey);

        if ($adminUserId) {
            AuditLogger::log(
                'catalog.category.image_removed',
                AuditActorType::ADMIN,
                actorAdminUserId: $adminUserId,
                entityType: 'category',
                entityId: $category->id,
                request: $request
            );
        }

        return $category;
    }
}
