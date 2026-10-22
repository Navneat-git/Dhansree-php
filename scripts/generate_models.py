import os, sys, re

with open('/tmp/dhansree_prod_columns.tsv') as f:
    cols_data = [line.strip().split('\t') for line in f if line.strip()]

with open('/tmp/dhansree_prod_fks.tsv') as f:
    fks_data = [line.strip().split('\t') for line in f if line.strip()]

table_to_model = {
    'admin_login_challenges': 'AdminLoginChallenge',
    'admin_permissions': 'AdminPermission',
    'admin_recovery_codes': 'AdminRecoveryCode',
    'admin_role_permissions': 'AdminRolePermission',
    'admin_roles': 'AdminRole',
    'admin_sessions': 'AdminSession',
    'admin_totp_credentials': 'AdminTotpCredential',
    'admin_user_roles': 'AdminUserRole',
    'admin_users': 'AdminUser',
    'audit_logs': 'AuditLog',
    'best_seller_pins': 'BestSellerPin',
    'categories': 'Category',
    'category_content': 'CategoryContent',
    'collection_categories': 'CollectionCategory',
    'collections': 'Collection',
    'content_page_sections': 'ContentPageSection',
    'content_pages': 'ContentPage',
    'coupon_redemptions': 'CouponRedemption',
    'coupons': 'Coupon',
    'customer_addresses': 'CustomerAddress',
    'customer_login_challenges': 'CustomerLoginChallenge',
    'customer_sessions': 'CustomerSession',
    'customers': 'Customer',
    'delivery_holidays': 'DeliveryHoliday',
    'email_messages': 'EmailMessage',
    'email_templates': 'EmailTemplate',
    'faq_entries': 'FaqEntry',
    'faq_groups': 'FaqGroup',
    'home_block_item_categories': 'HomeBlockItemCategory',
    'home_block_items': 'HomeBlockItem',
    'home_blocks': 'HomeBlock',
    'homepage_banners': 'HomepageBanner',
    'homepage_section_items': 'HomepageSectionItem',
    'homepage_sections': 'HomepageSection',
    'inventory_items': 'InventoryItem',
    'inventory_levels': 'InventoryLevel',
    'inventory_locations': 'InventoryLocation',
    'inventory_movements': 'InventoryMovement',
    'inventory_reservations': 'InventoryReservation',
    'invoice_series': 'InvoiceSeries',
    'marketplace_template_columns': 'MarketplaceTemplateColumn',
    'marketplace_templates': 'MarketplaceTemplate',
    'newsletter_subscribers': 'NewsletterSubscriber',
    'order_addresses': 'OrderAddress',
    'order_items': 'OrderItem',
    'order_notes': 'OrderNote',
    'order_status_history': 'OrderStatusHistory',
    'orders': 'Order',
    'payment_events': 'PaymentEvent',
    'payment_refunds': 'PaymentRefund',
    'payments': 'Payment',
    'product_attributes': 'ProductAttribute',
    'product_badges': 'ProductBadge',
    'product_content': 'ProductContent',
    'product_image_option_values': 'ProductImageOptionValue',
    'product_image_variants': 'ProductImageVariant',
    'product_images': 'ProductImage',
    'product_marketplace_values': 'ProductMarketplaceValue',
    'product_option_values': 'ProductOptionValue',
    'product_options': 'ProductOption',
    'product_sales_entries': 'ProductSalesEntry',
    'product_sales_stats': 'ProductSalesStats',
    'product_variant_option_values': 'ProductVariantOptionValue',
    'product_variants': 'ProductVariant',
    'products': 'Product',
    'quick_link_groups': 'QuickLinkGroup',
    'quick_links': 'QuickLink',
    'reviews': 'Review',
    'seo_redirects': 'SeoRedirect',
    'shipment_items': 'ShipmentItem',
    'shipment_tracking_events': 'ShipmentTrackingEvent',
    'shipments': 'Shipment',
    'shipping_webhook_events': 'ShippingWebhookEvent',
    'size_chart_rows': 'SizeChartRow',
    'size_charts': 'SizeChart',
    'store_settings': 'StoreSettings',
    'variant_marketplace_values': 'VariantMarketplaceValue',
    'wishlist_items': 'WishlistItem'
}

enum_map = {
    'admin_sessions.revoke_reason': 'AdminSessionRevokeReason',
    'admin_users.status': 'AdminUserStatus',
    'audit_logs.actor_type': 'AuditActorType',
    'content_pages.key': 'ContentPageKey',
    'coupons.type': 'CouponType',
    'customer_addresses.label': 'AddressLabel',
    'customer_sessions.revoke_reason': 'CustomerSessionRevokeReason',
    'customers.status': 'CustomerStatus',
    'email_messages.type': 'EmailType',
    'email_messages.status': 'EmailStatus',
    'email_templates.type': 'EmailType',
    'home_blocks.type': 'HomeBlockType',
    'homepage_sections.key': 'HomepageSectionKey',
    'inventory_items.backorder_policy': 'BackorderPolicy',
    'inventory_movements.type': 'InventoryMovementType',
    'inventory_movements.reason': 'StockAdjustmentReason',
    'inventory_movements.actor_type': 'AuditActorType',
    'inventory_reservations.status': 'InventoryReservationStatus',
    'order_addresses.type': 'OrderAddressType',
    'order_status_history.from_status': 'OrderStatus',
    'order_status_history.to_status': 'OrderStatus',
    'order_status_history.actor_type': 'OrderActorType',
    'orders.status': 'OrderStatus',
    'orders.payment_status': 'PaymentStatus',
    'orders.payment_method': 'PaymentMethod',
    'orders.gst_registration_type': 'GstRegistrationType',
    'orders.supply_type': 'GstSupplyType',
    'orders.shipping_rate_source': 'ShippingRateSource',
    'orders.coupon_type': 'CouponType',
    'payment_events.status': 'PaymentEventStatus',
    'payment_refunds.status': 'RefundStatus',
    'payment_refunds.origin': 'RefundOrigin',
    'payments.method': 'PaymentMethod',
    'payments.status': 'PaymentStatus',
    'payments.confirmed_via': 'PaymentConfirmationSource',
    'products.status': 'ProductStatus',
    'reviews.status': 'ReviewStatus',
    'seo_redirects.origin': 'SeoRedirectOrigin',
    'shipment_tracking_events.source': 'ShipmentEventSource',
    'shipment_tracking_events.mapped_status': 'ShipmentStatus',
    'shipments.status': 'ShipmentStatus',
    'shipments.payment_mode': 'ShipmentPaymentMode',
    'shipping_webhook_events.status': 'ShippingWebhookStatus',
    'store_settings.gst_registration_type': 'GstRegistrationType',
    'store_settings.shipping_mode': 'ShippingMode',
    'store_settings.shipping_rate_rule': 'ShippingRateRule'
}

tables = {}
for row in cols_data:
    t_name = row[0]
    if t_name not in table_to_model:
        continue
    if t_name not in tables:
        tables[t_name] = {'columns': [], 'pks': [], 'fks': [], 'inv_fks': []}
    tables[t_name]['columns'].append(row)
    if len(row) > 5 and row[5] == 'PRI':
        tables[t_name]['pks'].append(row[1])

for row in fks_data:
    t_name = row[0]
    ref_table = row[2]
    if t_name in tables and ref_table in tables:
        tables[t_name]['fks'].append({'col': row[1], 'ref_table': ref_table, 'ref_col': row[3]})
        tables[ref_table]['inv_fks'].append({'from_table': t_name, 'from_col': row[1], 'ref_col': row[3]})

def snake_to_camel(s):
    parts = s.split('_')
    return parts[0] + ''.join(p.capitalize() for p in parts[1:])

def relation_name(col, ref_table):
    if col == 'parent_id':
        return 'parent'
    if col.endswith('_id'):
        base = col[:-3]
        return snake_to_camel(base)
    return snake_to_camel(ref_table)

def inv_relation_name(from_table, from_col):
    if from_table == 'categories' and from_col == 'parent_id':
        return 'children'
    name = snake_to_camel(from_table)
    if not name.endswith('s'):
        name += 's'
    return name

out_dir = '/home/administrator/nodegny/Dhansree-php/app/Models'
os.makedirs(out_dir, exist_ok=True)

for t_name, data in tables.items():
    model_name = table_to_model[t_name]
    pks = data['pks']
    col_names = [c[1] for c in data['columns']]
    has_created = 'created_at' in col_names
    has_updated = 'updated_at' in col_names
    
    casts = {}
    used_enums = set()
    
    for c in data['columns']:
        c_name = c[1]
        c_type = c[2]
        c_full = c[3]
        
        full_key = f"{t_name}.{c_name}"
        if full_key in enum_map:
            enum_cls = enum_map[full_key]
            casts[c_name] = f"\\App\\Enums\\{enum_cls}::class"
            used_enums.add(enum_cls)
            continue
            
        if c_type == 'json':
            casts[c_name] = "'array'"
        elif c_full == 'tinyint(1)':
            casts[c_name] = "'boolean'"
        elif c_name.endswith('_paise') or c_name.endswith('_bps') or c_name.endswith('_grams'):
            casts[c_name] = "'integer'"
        elif c_type in ('datetime', 'timestamp'):
            casts[c_name] = "'datetime'"
        elif c_type in ('int', 'smallint', 'tinyint', 'mediumint', 'bigint') and not c_name.endswith('_id') and c_name not in pks:
            if c_name in ('on_hand', 'reserved', 'quantity', 'failed_login_attempts', 'attempts', 'deliveries', 'version', 'position', 'rating', 'width', 'height', 'byte_size', 'status_code', 'times_redeemed', 'usage_limit', 'per_customer_limit', 'package_length_cm', 'package_breadth_cm', 'package_height_cm', 'packaging_weight_grams', 'item_count'):
                casts[c_name] = "'integer'"

    pk_str = "protected $primaryKey = 'id';"
    inc_str = ""
    key_type_str = ""
    
    if len(pks) == 1:
        pk = pks[0]
        if pk != 'id':
            pk_str = f"protected $primaryKey = '{pk}';"
            pk_col = next(c for c in data['columns'] if c[1] == pk)
            if 'int' not in pk_col[2]:
                key_type_str = "\n    protected $keyType = 'string';\n    public $incrementing = false;"
    elif len(pks) > 1:
        joined_pks = "', '".join(pks)
        pk_str = f"protected $primaryKey = ['{joined_pks}'];"
        inc_str = "\n    public $incrementing = false;"

    if has_created and has_updated:
        ts_str = "public $timestamps = true;"
    else:
        ts_str = "public $timestamps = false;"

    casts_items = [f"        '{k}' => {v}," for k, v in casts.items()]
    if casts_items:
        casts_str = "    protected $casts = [\n" + "\n".join(casts_items) + "\n    ];"
    else:
        casts_str = "    protected $casts = [];"

    methods = []
    used_relation_names = set()

    for fk in data['fks']:
        r_name = relation_name(fk['col'], fk['ref_table'])
        if r_name in used_relation_names or r_name in col_names:
            r_name = r_name + 'Relation'
        used_relation_names.add(r_name)
        ref_model = table_to_model[fk['ref_table']]
        method_code = f"""    public function {r_name}(): BelongsTo
    {{
        return $this->belongsTo({ref_model}::class, '{fk['col']}', '{fk['ref_col']}');
    }}"""
        methods.append(method_code)

    if len(pks) == 1 and pks[0] == 'id':
        for ifk in data['inv_fks']:
            from_t = ifk['from_table']
            from_m = table_to_model[from_t]
            r_name = inv_relation_name(from_t, ifk['from_col'])
            if r_name in used_relation_names or r_name in col_names:
                r_name = r_name + '_' + snake_to_camel(ifk['from_col'])
            used_relation_names.add(r_name)
            
            method_code = f"""    public function {r_name}(): HasMany
    {{
        return $this->hasMany({from_m}::class, '{ifk['from_col']}', '{ifk['ref_col']}');
    }}"""
            methods.append(method_code)

    methods_str = "\n\n".join(methods)
    if methods_str:
        methods_str = "\n\n" + methods_str

    code = f"""<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;

class {model_name} extends Model
{{
    protected $table = '{t_name}';
    {pk_str}{inc_str}{key_type_str}
    {ts_str}

    protected $guarded = [];

{casts_str}{methods_str}
}}
"""
    with open(f"{out_dir}/{model_name}.php", "w") as mf:
        mf.write(code)

print(f"Generated {len(tables)} models in {out_dir}")
