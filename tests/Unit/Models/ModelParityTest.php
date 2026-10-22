<?php

namespace Tests\Unit\Models;

use App\Models\AdminPermission;
use App\Models\AdminRole;
use App\Models\AdminUser;
use App\Models\Category;
use App\Models\Coupon;
use App\Models\Customer;
use App\Models\InventoryItem;
use App\Models\InventoryLevel;
use App\Models\InventoryLocation;
use App\Models\Order;
use App\Models\OrderItem;
use App\Models\Payment;
use App\Models\Product;
use App\Models\ProductOption;
use App\Models\ProductOptionValue;
use App\Models\ProductVariant;
use App\Models\Shipment;
use App\Models\StoreSettings;
use Tests\TestCase;

class ModelParityTest extends TestCase
{
    public function test_all_models_can_be_instantiated(): void
    {
        $models = [
            \App\Models\AdminLoginChallenge::class,
            \App\Models\AdminPermission::class,
            \App\Models\AdminRecoveryCode::class,
            \App\Models\AdminRolePermission::class,
            \App\Models\AdminRole::class,
            \App\Models\AdminSession::class,
            \App\Models\AdminTotpCredential::class,
            \App\Models\AdminUserRole::class,
            \App\Models\AdminUser::class,
            \App\Models\AuditLog::class,
            \App\Models\BestSellerPin::class,
            \App\Models\Category::class,
            \App\Models\CategoryContent::class,
            \App\Models\CollectionCategory::class,
            \App\Models\Collection::class,
            \App\Models\ContentPageSection::class,
            \App\Models\ContentPage::class,
            \App\Models\CouponRedemption::class,
            \App\Models\Coupon::class,
            \App\Models\CustomerAddress::class,
            \App\Models\CustomerLoginChallenge::class,
            \App\Models\CustomerSession::class,
            \App\Models\Customer::class,
            \App\Models\DeliveryHoliday::class,
            \App\Models\EmailMessage::class,
            \App\Models\EmailTemplate::class,
            \App\Models\FaqEntry::class,
            \App\Models\FaqGroup::class,
            \App\Models\HomeBlockItemCategory::class,
            \App\Models\HomeBlockItem::class,
            \App\Models\HomeBlock::class,
            \App\Models\HomepageBanner::class,
            \App\Models\HomepageSectionItem::class,
            \App\Models\HomepageSection::class,
            \App\Models\InventoryItem::class,
            \App\Models\InventoryLevel::class,
            \App\Models\InventoryLocation::class,
            \App\Models\InventoryMovement::class,
            \App\Models\InventoryReservation::class,
            \App\Models\InvoiceSeries::class,
            \App\Models\MarketplaceTemplateColumn::class,
            \App\Models\MarketplaceTemplate::class,
            \App\Models\NewsletterSubscriber::class,
            \App\Models\OrderAddress::class,
            \App\Models\OrderItem::class,
            \App\Models\OrderNote::class,
            \App\Models\OrderStatusHistory::class,
            \App\Models\Order::class,
            \App\Models\PaymentEvent::class,
            \App\Models\PaymentRefund::class,
            \App\Models\Payment::class,
            \App\Models\ProductAttribute::class,
            \App\Models\ProductBadge::class,
            \App\Models\ProductContent::class,
            \App\Models\ProductImageOptionValue::class,
            \App\Models\ProductImageVariant::class,
            \App\Models\ProductImage::class,
            \App\Models\ProductMarketplaceValue::class,
            \App\Models\ProductOptionValue::class,
            \App\Models\ProductOption::class,
            \App\Models\ProductSalesEntry::class,
            \App\Models\ProductSalesStats::class,
            \App\Models\ProductVariantOptionValue::class,
            \App\Models\ProductVariant::class,
            \App\Models\Product::class,
            \App\Models\QuickLinkGroup::class,
            \App\Models\QuickLink::class,
            \App\Models\Review::class,
            \App\Models\SeoRedirect::class,
            \App\Models\ShipmentItem::class,
            \App\Models\ShipmentTrackingEvent::class,
            \App\Models\Shipment::class,
            \App\Models\ShippingWebhookEvent::class,
            \App\Models\SizeChartRow::class,
            \App\Models\SizeChart::class,
            \App\Models\StoreSettings::class,
            \App\Models\VariantMarketplaceValue::class,
            \App\Models\WishlistItem::class,
        ];

        $this->assertCount(78, $models);

        foreach ($models as $class) {
            $this->assertTrue(class_exists($class), "Model class {$class} must exist");
            $instance = new $class();
            $this->assertNotEmpty($instance->getTable(), "Model {$class} must define a table");
        }
    }

    public function test_admin_user_model_casts_and_auth(): void
    {
        $admin = new AdminUser([
            'email' => 'test@dhansree.com',
            'name' => 'Admin Tester',
            'status' => \App\Enums\AdminUserStatus::ACTIVE,
            'password_hash' => '$argon2id$v=19$m=19456,t=2,p=1$fakehash',
            'failed_login_attempts' => 0,
            'password_change_required' => false,
        ]);

        $this->assertEquals(\App\Enums\AdminUserStatus::ACTIVE, $admin->status);
        $this->assertFalse($admin->password_change_required);
        $this->assertEquals('$argon2id$v=19$m=19456,t=2,p=1$fakehash', $admin->getAuthPassword());
    }

    public function test_product_and_variant_money_and_scopes(): void
    {
        $product = new Product([
            'name' => 'Test Cotton Kurti',
            'slug' => 'test-cotton-kurti',
            'status' => \App\Enums\ProductStatus::ACTIVE,
            'gst_rate_bps' => 1200,
        ]);

        $this->assertEquals(\App\Enums\ProductStatus::ACTIVE, $product->status);
        $this->assertSame(1200, $product->gst_rate_bps);

        $variant = new ProductVariant([
            'sku' => 'TEST-KURTI-M',
            'price_paise' => 129900,
            'mrp_paise' => 159900,
            'weight_grams' => 350,
        ]);

        $this->assertSame(129900, $variant->price_paise);
        $this->assertSame(1299.0, $variant->price_rupees);
        $this->assertSame(1599.0, $variant->mrp_rupees);
        $this->assertSame(19, $variant->discount_percentage); // round((1599 - 1299)/1599 * 100) = 19%
    }

    public function test_order_money_and_statuses(): void
    {
        $order = new Order([
            'order_number' => 'DS-TEST1234',
            'status' => \App\Enums\OrderStatus::CONFIRMED,
            'payment_status' => \App\Enums\PaymentStatus::PAID,
            'payment_method' => \App\Enums\PaymentMethod::ONLINE,
            'subtotal_paise' => 200000,
            'discount_paise' => 20000,
            'shipping_paise' => 0,
            'taxable_paise' => 180000,
            'cgst_paise' => 10800,
            'sgst_paise' => 10800,
            'igst_paise' => 0,
            'tax_paise' => 21600,
            'total_paise' => 201600,
        ]);

        $this->assertEquals(\App\Enums\OrderStatus::CONFIRMED, $order->status);
        $this->assertEquals(\App\Enums\PaymentStatus::PAID, $order->payment_status);
        $this->assertSame(201600, $order->total_paise);
        $this->assertSame(2016.0, $order->total_rupees);
        $this->assertSame(200.0, $order->discount_rupees);
    }

    public function test_inventory_level_available_calculation(): void
    {
        $level = new InventoryLevel([
            'on_hand' => 25,
            'reserved' => 5,
        ]);

        $this->assertSame(20, $level->available);

        $levelUnderflow = new InventoryLevel([
            'on_hand' => 2,
            'reserved' => 5,
        ]);

        $this->assertSame(0, $levelUnderflow->available);
    }
}
