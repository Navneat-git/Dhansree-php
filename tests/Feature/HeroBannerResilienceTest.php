<?php

declare(strict_types=1);

namespace Tests\Feature;

use App\Models\HomepageBanner;
use App\Services\Storefront\CatalogBrowsingService;
use Tests\TestCase;

class HeroBannerResilienceTest extends TestCase
{
    public function test_hero_banners_still_load_even_if_schedule_end_date_has_elapsed(): void
    {
        // Simulate banner where ends_at is in the past
        HomepageBanner::where('id', '>', 0)->update([
            'is_active' => true,
            'starts_at' => now()->subDays(10),
            'ends_at' => now()->subMinutes(5), // expired 5 minutes ago
        ]);

        $service = app(CatalogBrowsingService::class);
        $data = $service->getWomenFashionHomeData();

        $this->assertNotEmpty($data['banners']);
        $this->assertTrue($data['banners']->isNotEmpty());

        $firstBanner = $data['banners']->first();
        $this->assertNotNull($firstBanner->image_url);
    }

    public function test_hero_banners_fallback_to_default_brand_banners_if_table_empty(): void
    {
        // Force empty active banners
        HomepageBanner::query()->update(['is_active' => false]);

        $service = app(CatalogBrowsingService::class);
        $data = $service->getWomenFashionHomeData();

        $this->assertNotEmpty($data['banners']);
        $this->assertGreaterThanOrEqual(2, $data['banners']->count());

        $first = $data['banners']->first();
        $this->assertStringContainsString('hero_banner_festive_women', $first->image_storage_key);
    }
}
