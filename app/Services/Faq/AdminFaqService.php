<?php

declare(strict_types=1);

namespace App\Services\Faq;

use App\Models\FaqEntry;
use App\Models\FaqGroup;
use Illuminate\Support\Collection;
use Illuminate\Support\Str;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;
use Symfony\Component\HttpKernel\Exception\UnprocessableEntityHttpException;

class AdminFaqService
{
    /**
     * Get all FAQ groups with their entries.
     */
    public function listGroupsWithEntries(): Collection
    {
        return FaqGroup::with(['faqEntries' => fn($q) => $q->orderBy('position')->orderBy('id')])
            ->orderBy('position')
            ->orderBy('id')
            ->get();
    }

    /**
     * Create a new FAQ group.
     */
    public function createGroup(array $data): FaqGroup
    {
        $name = trim((string) ($data['name'] ?? ''));
        if ($name === '') {
            throw new UnprocessableEntityHttpException('Group name is required.');
        }

        $slug = !empty($data['slug']) ? Str::slug($data['slug']) : Str::slug($name);
        if ($slug === '') {
            $slug = 'faq-group-' . uniqid();
        }

        return FaqGroup::create([
            'name' => $name,
            'slug' => $slug,
            'position' => isset($data['position']) ? max(0, (int) $data['position']) : 0,
            'is_visible' => isset($data['is_visible']) ? (bool) $data['is_visible'] : true,
            'show_on_faq_page' => isset($data['show_on_faq_page']) ? (bool) $data['show_on_faq_page'] : true,
            'show_on_listing' => isset($data['show_on_listing']) ? (bool) $data['show_on_listing'] : false,
            'show_on_product' => isset($data['show_on_product']) ? (bool) $data['show_on_product'] : false,
            'version' => 0,
        ]);
    }

    /**
     * Update an FAQ group.
     */
    public function updateGroup(int $id, array $data): FaqGroup
    {
        /** @var FaqGroup|null $group */
        $group = FaqGroup::find($id);
        if (!$group) {
            throw new NotFoundHttpException('FAQ group not found.');
        }

        $name = isset($data['name']) ? trim((string) $data['name']) : $group->name;
        $slug = !empty($data['slug']) ? Str::slug($data['slug']) : $group->slug;

        $group->update([
            'name' => $name,
            'slug' => $slug,
            'position' => isset($data['position']) ? max(0, (int) $data['position']) : $group->position,
            'is_visible' => isset($data['is_visible']) ? (bool) $data['is_visible'] : $group->is_visible,
            'show_on_faq_page' => isset($data['show_on_faq_page']) ? (bool) $data['show_on_faq_page'] : $group->show_on_faq_page,
            'show_on_listing' => isset($data['show_on_listing']) ? (bool) $data['show_on_listing'] : $group->show_on_listing,
            'show_on_product' => isset($data['show_on_product']) ? (bool) $data['show_on_product'] : $group->show_on_product,
            'version' => $group->version + 1,
        ]);

        return $group;
    }

    /**
     * Delete an FAQ group and all its entries.
     */
    public function deleteGroup(int $id): void
    {
        $group = FaqGroup::find($id);
        if (!$group) {
            throw new NotFoundHttpException('FAQ group not found.');
        }

        $group->faqEntries()->delete();
        $group->delete();
    }

    /**
     * Create an FAQ entry.
     */
    public function createEntry(int $groupId, array $data): FaqEntry
    {
        $group = FaqGroup::find($groupId);
        if (!$group) {
            throw new NotFoundHttpException('FAQ group not found.');
        }

        $question = trim((string) ($data['question'] ?? ''));
        $answer = trim((string) ($data['answer'] ?? ''));

        if ($question === '') {
            throw new UnprocessableEntityHttpException('FAQ question cannot be empty.');
        }
        if ($answer === '') {
            throw new UnprocessableEntityHttpException('FAQ answer cannot be empty.');
        }

        return FaqEntry::create([
            'group_id' => $groupId,
            'category_id' => !empty($data['category_id']) ? (int) $data['category_id'] : null,
            'product_id' => !empty($data['product_id']) ? (int) $data['product_id'] : null,
            'question' => $question,
            'answer' => $answer,
            'position' => isset($data['position']) ? max(0, (int) $data['position']) : 0,
            'is_visible' => isset($data['is_visible']) ? (bool) $data['is_visible'] : true,
            'version' => 0,
        ]);
    }

    /**
     * Update an FAQ entry.
     */
    public function updateEntry(int $id, array $data): FaqEntry
    {
        /** @var FaqEntry|null $entry */
        $entry = FaqEntry::find($id);
        if (!$entry) {
            throw new NotFoundHttpException('FAQ entry not found.');
        }

        $question = isset($data['question']) ? trim((string) $data['question']) : $entry->question;
        $answer = isset($data['answer']) ? trim((string) $data['answer']) : $entry->answer;

        if ($question === '') {
            throw new UnprocessableEntityHttpException('FAQ question cannot be empty.');
        }
        if ($answer === '') {
            throw new UnprocessableEntityHttpException('FAQ answer cannot be empty.');
        }

        $entry->update([
            'question' => $question,
            'answer' => $answer,
            'group_id' => array_key_exists('group_id', $data) ? ($data['group_id'] ? (int) $data['group_id'] : null) : $entry->group_id,
            'position' => isset($data['position']) ? max(0, (int) $data['position']) : $entry->position,
            'is_visible' => isset($data['is_visible']) ? (bool) $data['is_visible'] : $entry->is_visible,
            'category_id' => array_key_exists('category_id', $data) ? ($data['category_id'] ? (int) $data['category_id'] : null) : $entry->category_id,
            'product_id' => array_key_exists('product_id', $data) ? ($data['product_id'] ? (int) $data['product_id'] : null) : $entry->product_id,
            'version' => $entry->version + 1,
        ]);

        return $entry;
    }

    /**
     * Delete an FAQ entry.
     */
    public function deleteEntry(int $id): void
    {
        $entry = FaqEntry::find($id);
        if (!$entry) {
            throw new NotFoundHttpException('FAQ entry not found.');
        }

        $entry->delete();
    }
}
