<?php

declare(strict_types=1);

namespace App\Http\Controllers\Admin\SocialCommerce;

use App\Http\Controllers\Controller;
use App\Jobs\PublishSocialContentJob;
use App\Models\SocialPublishingAccount;
use App\Models\SocialPublishingQueue;
use App\Services\SocialCommerce\MetaPublishingService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class SocialPublishingQueueController extends Controller
{
    /**
     * List all scheduled and queued publishing items.
     */
    public function index(Request $request): View
    {
        $query = SocialPublishingQueue::with(['reel', 'mediaAsset', 'creator', 'logs'])
            ->latest('id');

        if ($request->filled('status')) {
            $query->where('status', $request->input('status'));
        }

        $queueItems = $query->paginate(20)->withQueryString();
        $connectedAccounts = SocialPublishingAccount::where('is_connected', true)->get();

        return view('admin.social-commerce.queue.index', [
            'queueItems' => $queueItems,
            'connectedAccounts' => $connectedAccounts,
            'filters' => $request->all(),
        ]);
    }

    /**
     * Approve a queued item for publishing (safety control).
     */
    public function approve(SocialPublishingQueue $item): RedirectResponse
    {
        $item->update([
            'status' => 'APPROVED',
        ]);

        return redirect()->back()->with('success', "Item #{$item->id} approved for publishing.");
    }

    /**
     * Immediately publish an approved item to social platforms.
     */
    public function publishNow(SocialPublishingQueue $item, MetaPublishingService $publishingService): RedirectResponse
    {
        if (!in_array($item->status, ['APPROVED', 'SCHEDULED', 'FAILED', 'PARTIALLY_PUBLISHED'], true)) {
            return redirect()->back()->with('error', "Item #{$item->id} must be Approved before publishing.");
        }

        PublishSocialContentJob::dispatch($item->id);

        return redirect()->back()->with('success', "Publishing job dispatched for item #{$item->id}. Status will update shortly.");
    }

    /**
     * Update queue item captions, platforms, or scheduled time.
     */
    public function update(Request $request, SocialPublishingQueue $item): RedirectResponse
    {
        $request->validate([
            'caption_instagram' => 'nullable|string',
            'caption_facebook' => 'nullable|string',
            'platforms' => 'required|array|min:1',
            'platforms.*' => 'in:INSTAGRAM,FACEBOOK',
            'scheduled_at' => 'nullable|date',
        ]);

        $item->update([
            'caption_instagram' => $request->input('caption_instagram'),
            'caption_facebook' => $request->input('caption_facebook'),
            'platforms' => $request->input('platforms'),
            'scheduled_at' => $request->input('scheduled_at'),
            'status' => $request->filled('scheduled_at') ? 'SCHEDULED' : ($item->status === 'SCHEDULED' ? 'APPROVED' : $item->status),
        ]);

        return redirect()->back()->with('success', "Queue item #{$item->id} updated.");
    }

    /**
     * Retry a failed publishing item.
     */
    public function retry(SocialPublishingQueue $item): RedirectResponse
    {
        $item->update([
            'status' => 'APPROVED',
            'error_message' => null,
        ]);

        return redirect()->back()->with('success', "Queue item #{$item->id} reset to Approved for retry.");
    }

    /**
     * Cancel / delete a queue item.
     */
    public function destroy(SocialPublishingQueue $item): RedirectResponse
    {
        $item->delete();
        return redirect()->back()->with('success', "Queue item #{$item->id} removed.");
    }
}
