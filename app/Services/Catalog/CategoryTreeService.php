<?php

declare(strict_types=1);

namespace App\Services\Catalog;

use App\Models\Category;

class CategoryTreeService
{
    public const MAX_DEPTH = 3;

    /**
     * Ancestors of $id, nearest first. Stops safely if the data contains a cycle.
     *
     * @param array<int, int|null> $parentMap [id => parentId]
     * @return int[]
     */
    public function ancestorIds(int $id, array $parentMap): array
    {
        $result = [];
        $seen = [$id => true];
        $current = $parentMap[$id] ?? null;

        while ($current !== null && !isset($seen[$current])) {
            $result[] = $current;
            $seen[$current] = true;
            $current = $parentMap[$current] ?? null;
        }

        return $result;
    }

    /**
     * Depth level of the category: 1 for top-level root category.
     *
     * @param array<int, int|null> $parentMap
     */
    public function depthOf(int $id, array $parentMap): int
    {
        return count($this->ancestorIds($id, $parentMap)) + 1;
    }

    /**
     * True if setting $newParentId as parent of $id would create a loop/cycle.
     *
     * @param array<int, int|null> $parentMap
     */
    public function wouldCreateCycle(int $id, ?int $newParentId, array $parentMap): bool
    {
        if ($newParentId === null) {
            return false;
        }
        if ($newParentId === $id) {
            return true;
        }

        return in_array($id, $this->ancestorIds($newParentId, $parentMap), true);
    }

    /**
     * Number of levels existing below $id (0 for leaf category).
     *
     * @param array<int, int|null> $parentMap
     */
    public function subtreeHeight(int $id, array $parentMap): int
    {
        $children = [];
        foreach ($parentMap as $child => $parent) {
            if ($parent !== null) {
                $children[$parent][] = $child;
            }
        }

        $calcHeight = function (int $node, array &$guard) use (&$children, &$calcHeight): int {
            if (isset($guard[$node])) {
                return 0;
            }
            $guard[$node] = true;
            $kids = $children[$node] ?? [];
            if (empty($kids)) {
                return 0;
            }

            $maxKidHeight = 0;
            foreach ($kids as $kid) {
                $kidHeight = $calcHeight($kid, $guard);
                if ($kidHeight > $maxKidHeight) {
                    $maxKidHeight = $kidHeight;
                }
            }

            return 1 + $maxKidHeight;
        };

        $guard = [];
        return $calcHeight($id, $guard);
    }

    /**
     * Build category parent map from database.
     *
     * @return array<int, int|null>
     */
    public function getActiveParentMap(): array
    {
        return Category::whereNull('archived_at')
            ->pluck('parent_id', 'id')
            ->all();
    }

    /**
     * Flatten categories into picker options in tree order (parents before children, siblings by position)
     * with "Parent › Child" labels and depth indicators.
     *
     * @param iterable<Category>|null $categories
     * @return array<int, array{id: int, name: string, parent_id: int|null, depth: int, label: string, is_visible: bool}>
     */
    public function toCategoryOptions(?iterable $categories = null): array
    {
        if ($categories === null) {
            $categories = Category::whereNull('archived_at')
                ->orderBy('position')
                ->orderBy('id')
                ->get();
        }

        $items = [];
        $childrenMap = [];

        foreach ($categories as $cat) {
            $id = (int) $cat->id;
            $parentId = $cat->parent_id !== null ? (int) $cat->parent_id : null;
            $items[$id] = [
                'id' => $id,
                'name' => (string) $cat->name,
                'parent_id' => $parentId,
                'position' => (int) $cat->position,
                'is_visible' => (bool) $cat->is_visible,
            ];
        }

        foreach ($items as $id => $item) {
            $parentId = $item['parent_id'];
            if ($parentId !== null && !isset($items[$parentId])) {
                $parentId = null; // orphaned shown at root
            }
            $childrenMap[$parentId][] = $item;
        }

        foreach ($childrenMap as $pid => &$siblings) {
            usort($siblings, fn($a, $b) => $a['position'] <=> $b['position'] ?: $a['id'] <=> $b['id']);
        }
        unset($siblings);

        $options = [];
        $seen = [];

        $traverse = function (?int $parentId, array $trail, int $depth) use (&$traverse, &$options, &$childrenMap, &$seen) {
            $kids = $childrenMap[$parentId] ?? [];
            foreach ($kids as $kid) {
                if (isset($seen[$kid['id']])) {
                    continue;
                }
                $seen[$kid['id']] = true;
                $labels = array_merge($trail, [$kid['name']]);
                $options[] = [
                    'id' => $kid['id'],
                    'name' => $kid['name'],
                    'parent_id' => $kid['parent_id'],
                    'depth' => $depth,
                    'label' => implode(' › ', $labels),
                    'is_visible' => $kid['is_visible'],
                ];
                $traverse($kid['id'], $labels, $depth + 1);
            }
        };

        $traverse(null, [], 1);

        return $options;
    }

    /**
     * Builds a nested array representation of the category tree.
     */
    public function getTreeHierarchy(?bool $onlyActive = true): array
    {
        $query = Category::query();
        if ($onlyActive) {
            $query->whereNull('archived_at');
        }

        $categories = $query->withCount(['products', 'children'])
            ->orderBy('position')
            ->orderBy('id')
            ->get();

        $byParent = [];
        foreach ($categories as $cat) {
            $parentId = $cat->parent_id !== null ? (int) $cat->parent_id : 0;
            $byParent[$parentId][] = $cat;
        }

        $buildSubtree = function (int $parentId) use (&$buildSubtree, &$byParent): array {
            $branch = [];
            foreach ($byParent[$parentId] ?? [] as $category) {
                $children = $buildSubtree((int) $category->id);
                $category->setAttribute('tree_children', $children);
                $branch[] = $category;
            }
            return $branch;
        };

        return $buildSubtree(0);
    }
}
