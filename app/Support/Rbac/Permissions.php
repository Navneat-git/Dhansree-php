<?php

namespace App\Support\Rbac;

class Permissions
{
    public const PRODUCT_VIEW = 'PRODUCT_VIEW';
    public const PRODUCT_CREATE = 'PRODUCT_CREATE';
    public const PRODUCT_UPDATE = 'PRODUCT_UPDATE';
    public const PRODUCT_DELETE = 'PRODUCT_DELETE';
    public const PRODUCT_PUBLISH = 'PRODUCT_PUBLISH';

    public const CATEGORY_VIEW = 'CATEGORY_VIEW';
    public const CATEGORY_CREATE = 'CATEGORY_CREATE';
    public const CATEGORY_UPDATE = 'CATEGORY_UPDATE';
    public const CATEGORY_DELETE = 'CATEGORY_DELETE';

    public const INVENTORY_VIEW = 'INVENTORY_VIEW';
    public const INVENTORY_UPDATE = 'INVENTORY_UPDATE';
    public const INVENTORY_LOCATION_MANAGE = 'INVENTORY_LOCATION_MANAGE';

    public const ORDER_VIEW = 'ORDER_VIEW';
    public const ORDER_UPDATE = 'ORDER_UPDATE';
    public const PAYMENT_REFUND = 'PAYMENT_REFUND';

    public const SHIPPING_MANAGE = 'SHIPPING_MANAGE';
    public const SHIPPING_CANCEL = 'SHIPPING_CANCEL';

    public const CUSTOMER_VIEW = 'CUSTOMER_VIEW';
    public const CUSTOMER_UPDATE = 'CUSTOMER_UPDATE';

    public const COUPON_VIEW = 'COUPON_VIEW';
    public const COUPON_CREATE = 'COUPON_CREATE';
    public const COUPON_UPDATE = 'COUPON_UPDATE';
    public const COUPON_DELETE = 'COUPON_DELETE';

    public const REVIEW_VIEW = 'REVIEW_VIEW';
    public const REVIEW_MODERATE = 'REVIEW_MODERATE';

    public const SEO_VIEW = 'SEO_VIEW';
    public const SEO_UPDATE = 'SEO_UPDATE';

    public const BANNER_VIEW = 'BANNER_VIEW';
    public const BANNER_CREATE = 'BANNER_CREATE';
    public const BANNER_UPDATE = 'BANNER_UPDATE';
    public const BANNER_DELETE = 'BANNER_DELETE';

    public const SETTINGS_VIEW = 'SETTINGS_VIEW';
    public const SETTINGS_UPDATE = 'SETTINGS_UPDATE';
    public const SETTINGS_TAX_UPDATE = 'SETTINGS_TAX_UPDATE';

    public const ADMIN_USER_VIEW = 'ADMIN_USER_VIEW';
    public const ADMIN_USER_CREATE = 'ADMIN_USER_CREATE';
    public const ADMIN_USER_UPDATE = 'ADMIN_USER_UPDATE';
    public const ADMIN_USER_DELETE = 'ADMIN_USER_DELETE';
    public const AUDIT_LOG_VIEW = 'AUDIT_LOG_VIEW';

    public const DESCRIPTIONS = [
        self::PRODUCT_VIEW => 'View products, variants and images',
        self::PRODUCT_CREATE => 'Create products',
        self::PRODUCT_UPDATE => 'Edit products, pricing, images and SEO',
        self::PRODUCT_DELETE => 'Archive or delete products',
        self::PRODUCT_PUBLISH => 'Publish products to the storefront or take them offline',
        self::CATEGORY_VIEW => 'View categories',
        self::CATEGORY_CREATE => 'Create categories',
        self::CATEGORY_UPDATE => 'Edit, reorder, show or hide categories',
        self::CATEGORY_DELETE => 'Archive or delete categories',
        self::INVENTORY_VIEW => 'View stock levels, stock history and locations',
        self::INVENTORY_UPDATE => 'Adjust stock and set out-of-stock and low-stock rules',
        self::INVENTORY_LOCATION_MANAGE => 'Add and edit stock locations and choose the default',
        self::ORDER_VIEW => 'View orders',
        self::ORDER_UPDATE => 'Update order status, notes and shipments, and check payments with Razorpay',
        self::PAYMENT_REFUND => 'Refund online payments to customers',
        self::SHIPPING_MANAGE => 'Create courier shipments, assign AWBs, schedule pickups, print labels and check tracking',
        self::SHIPPING_CANCEL => 'Cancel courier shipments that have not been picked up yet',
        self::CUSTOMER_VIEW => 'View customers',
        self::CUSTOMER_UPDATE => 'Edit or deactivate customers',
        self::COUPON_VIEW => 'View coupons',
        self::COUPON_CREATE => 'Create coupons',
        self::COUPON_UPDATE => 'Edit coupons',
        self::COUPON_DELETE => 'Delete coupons',
        self::REVIEW_VIEW => 'View product reviews',
        self::REVIEW_MODERATE => 'Approve, reject or hide product reviews',
        self::SEO_VIEW => 'View SEO settings and redirects',
        self::SEO_UPDATE => 'Edit SEO settings and redirects',
        self::BANNER_VIEW => 'View banners and homepage sections',
        self::BANNER_CREATE => 'Create banners',
        self::BANNER_UPDATE => 'Edit banners and homepage sections',
        self::BANNER_DELETE => 'Delete banners',
        self::SETTINGS_VIEW => 'View store settings',
        self::SETTINGS_UPDATE => 'Change store details, contact, WhatsApp and order/inventory defaults',
        self::SETTINGS_TAX_UPDATE => 'Change the legal business details, GSTIN and GST settings',
        self::ADMIN_USER_VIEW => 'View admin users and roles',
        self::ADMIN_USER_CREATE => 'Invite or create admin users',
        self::ADMIN_USER_UPDATE => 'Edit admin users, roles and access',
        self::ADMIN_USER_DELETE => 'Disable and re-enable admin users',
        self::AUDIT_LOG_VIEW => 'View the security and activity audit log',
    ];

    public static function all(): array
    {
        return array_keys(self::DESCRIPTIONS);
    }

    public static function isValid(string $code): bool
    {
        return isset(self::DESCRIPTIONS[$code]);
    }
}
