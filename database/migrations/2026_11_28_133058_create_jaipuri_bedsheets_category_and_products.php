<?php

declare(strict_types=1);

use App\Enums\ProductStatus;
use App\Models\Category;
use App\Models\InventoryItem;
use App\Models\InventoryLevel;
use App\Models\Product;
use App\Models\ProductImage;
use App\Models\ProductOption;
use App\Models\ProductOptionValue;
use App\Models\ProductVariant;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        // 1. Create or update the Jaipuri Bedsheets Category
        $category = Category::firstOrCreate(
            ['slug' => 'jaipuri-bedsheets'],
            [
                'name' => 'Jaipuri Bedsheets',
                'description' => 'Authentic 100% pure cotton Jaipuri bedsheets handcrafted in Jaipur, Rajasthan. Featuring traditional Sanganeri floral block prints, royal Bagru motifs, and breathable 210+ thread count cotton in King and Queen double bed sizes with matching pillow covers.',
                'position' => 4,
                'is_visible' => true,
                'show_in_browse' => true,
                'seo_title' => 'Buy Jaipuri Bedsheets Online | 100% Pure Cotton Handblock Prints — Dhansree Trends',
                'seo_description' => 'Shop authentic Jaipuri pure cotton bedsheets online at Dhansree Trends. Discover handcrafted Sanganeri and Bagru block prints, 210 TC softness, king and queen bedsheets with pillow covers. Free shipping & COD.',
                'image_storage_key' => 'bedsheets/banner.webp',
                'card_shape' => 'PORTRAIT',
            ]
        );

        $category->update([
            'is_visible' => true,
            'show_in_browse' => true,
            'image_storage_key' => 'bedsheets/banner.webp',
        ]);

        // 2. Product definitions
        $productsData = [
            [
                'name' => 'Jaipuri Sanganeri Indigo Floral 100% Pure Cotton Bedsheet with 2 Pillow Covers',
                'slug' => 'jaipuri-sanganeri-indigo-floral-cotton-bedsheet',
                'brand' => 'Dhanshree Trends',
                'status' => ProductStatus::ACTIVE,
                'ranking' => 1,
                'short_description' => 'Authentic Jaipur Sanganeri handblock floral print in royal indigo blue on crisp white 100% pure combed cotton. Fast colors, breathable 210 TC weave, includes 2 matching pillow covers.',
                'description' => "Transform your bedroom into a tranquil royal retreat with this authentic Sanganeri Indigo Floral Bedsheet from Dhanshree Trends, Jaipur.\n\nCrafted by master artisans using traditional hand-block printing techniques, this bedsheet features intricate floral vines and ornate borders printed on premium 100% long-staple cotton.\n\n• **Material:** 100% Pure Combed Cotton (210 Thread Count)\n• **Print:** Traditional Sanganeri Handblock Floral Jaal\n• **Sizes Available:** King Size (108\" × 108\" / 274 × 274 cm) & Queen Size (90\" × 100\" / 228 × 254 cm)\n• **Inclusions:** 1 Double Bedsheet + 2 Matching Flapped Pillow Covers (18\" × 27\" / 46 × 69 cm)\n• **Fast Colors:** Azo-free skin-friendly dyes, pre-washed for shrink resistance\n• **Care Instructions:** Gentle machine wash or hand wash in cold water. Line dry in shade.",
                'hsn_code' => '6304',
                'gst_rate_bps' => 500,
                'seo_title' => 'Jaipuri Sanganeri Indigo Floral Pure Cotton Bedsheet | Dhansree Trends',
                'seo_description' => 'Buy Jaipuri Sanganeri Indigo Floral 100% pure cotton king size bedsheet with 2 pillow covers. Breathable 210 TC, fast color guarantee, free shipping.',
                'image' => 'bedsheets/sanganeri_indigo.webp',
                'alt_text' => 'Jaipuri Sanganeri Indigo Floral 100% Pure Cotton Bedsheet with Pillow Covers',
                'sku_prefix' => 'DS-JBS-IND',
            ],
            [
                'name' => 'Jaipuri Bagru Crimson Maroon & Beige Boota Pure Cotton Bedsheet with 2 Pillow Covers',
                'slug' => 'jaipuri-bagru-crimson-maroon-cotton-bedsheet',
                'brand' => 'Dhanshree Trends',
                'status' => ProductStatus::ACTIVE,
                'ranking' => 2,
                'short_description' => 'Royal Rajasthani Bagru handblock motif in crimson maroon and earthy beige. 100% breathable cotton, 210 TC, complete with 2 matching pillow covers.',
                'description' => "Experience regal comfort with our Bagru Crimson Maroon Pure Cotton Bedsheet. Rooted in century-old Rajasthani block printing traditions, this piece showcases classic floral boota motifs and wide ornate borders.\n\n• **Material:** 100% Premium Cotton (210 TC)\n• **Art Form:** Authentic Bagru Handblock Print\n• **Sizes Available:** King Size (108\" × 108\" / 274 × 274 cm) & Queen Size (90\" × 100\" / 228 × 254 cm)\n• **Inclusions:** 1 Double Bedsheet + 2 Matching Pillow Covers (18\" × 27\")\n• **Features:** Breathable, skin-friendly, fast colors guaranteed\n• **Care Instructions:** Cold machine wash, dry inside out in shade.",
                'hsn_code' => '6304',
                'gst_rate_bps' => 500,
                'seo_title' => 'Jaipuri Bagru Crimson Maroon Pure Cotton Bedsheet | Dhansree Trends',
                'seo_description' => 'Shop authentic Jaipuri Bagru crimson maroon pure cotton double bedsheet with 2 pillow covers. 100% soft cotton, handcrafted elegance, cash on delivery.',
                'image' => 'bedsheets/bagru_maroon.webp',
                'alt_text' => 'Jaipuri Bagru Crimson Maroon & Beige Pure Cotton Bedsheet with Pillow Covers',
                'sku_prefix' => 'DS-JBS-BGR',
            ],
            [
                'name' => 'Royal Mughal Mustard Gold & Sage Green Pure Cotton Bedsheet with 2 Pillow Covers',
                'slug' => 'royal-mughal-mustard-gold-cotton-bedsheet',
                'brand' => 'Dhanshree Trends',
                'status' => ProductStatus::ACTIVE,
                'ranking' => 3,
                'short_description' => 'Opulent Mughal floral boota in mustard gold and sage olive green. 100% pure cotton, 210 TC, includes 2 matching pillow covers.',
                'description' => "Infuse your home with royal heritage. This Mughal Boota Bedsheet combines festive turmeric gold and calming sage olive green on unbleached natural cotton.\n\n• **Material:** 100% Pure Combed Cotton (210 TC)\n• **Art Form:** Mughal Garden Boota Block Print\n• **Sizes Available:** King Size (108\" × 108\" / 274 × 274 cm) & Queen Size (90\" × 100\" / 228 × 254 cm)\n• **Inclusions:** 1 Double Bedsheet + 2 Matching Pillow Covers (18\" × 27\")\n• **Features:** All-season breathability, gentle on sensitive skin\n• **Care Instructions:** Gentle wash, mild detergent, shade dry.",
                'hsn_code' => '6304',
                'gst_rate_bps' => 500,
                'seo_title' => 'Royal Mughal Mustard Gold Pure Cotton Bedsheet | Dhansree Trends',
                'seo_description' => 'Buy Royal Mughal Mustard Gold 100% pure cotton king size bedsheet with pillow covers. Authentic Jaipur block print, breathable comfort, free delivery.',
                'image' => 'bedsheets/mughal_gold.webp',
                'alt_text' => 'Royal Mughal Mustard Gold & Sage Green Pure Cotton Bedsheet with Pillow Covers',
                'sku_prefix' => 'DS-JBS-MGH',
            ],
            [
                'name' => 'Jaipur Gulab Pink & Mint Sanganeri Jaal Pure Cotton Bedsheet with 2 Pillow Covers',
                'slug' => 'jaipur-gulab-pink-mint-sanganeri-bedsheet',
                'brand' => 'Dhanshree Trends',
                'status' => ProductStatus::ACTIVE,
                'ranking' => 4,
                'short_description' => 'Delicate Jaipur rose floral jaal in blush pink and mint green. Handblock printed on 100% breathable cotton with 2 matching pillow covers.',
                'description' => "Celebrate the Pink City's timeless charm. Delicate pink rose vines and mint leaf borders bring freshness, romance, and serene comfort to your master bedroom.\n\n• **Material:** 100% Long-Staple Cotton (210 TC)\n• **Art Form:** Sanganeri Gulab Jaal Handblock Print\n• **Sizes Available:** King Size (108\" × 108\" / 274 × 274 cm) & Queen Size (90\" × 100\" / 228 × 254 cm)\n• **Inclusions:** 1 Double Bedsheet + 2 Matching Pillow Covers (18\" × 27\")\n• **Features:** Ultra-soft handfeel, hypoallergenic, fast colors\n• **Care Instructions:** Machine wash cold, tumble dry low or shade dry.",
                'hsn_code' => '6304',
                'gst_rate_bps' => 500,
                'seo_title' => 'Jaipur Gulab Pink & Mint Sanganeri Pure Cotton Bedsheet | Dhansree Trends',
                'seo_description' => 'Shop Jaipur Gulab Pink & Mint 100% cotton double bedsheet with 2 pillow covers. Delicate floral handblock print, breathable comfort, fast shipping.',
                'image' => 'bedsheets/jaipur_pink.webp',
                'alt_text' => 'Jaipur Gulab Pink & Mint Sanganeri Pure Cotton Bedsheet with Pillow Covers',
                'sku_prefix' => 'DS-JBS-PNK',
            ],
        ];

        foreach ($productsData as $p) {
            $product = Product::firstOrCreate(
                ['slug' => $p['slug']],
                [
                    'category_id' => $category->id,
                    'name' => $p['name'],
                    'brand' => $p['brand'],
                    'status' => $p['status'],
                    'ranking' => $p['ranking'],
                    'short_description' => $p['short_description'],
                    'description' => $p['description'],
                    'hsn_code' => $p['hsn_code'],
                    'gst_rate_bps' => $p['gst_rate_bps'],
                    'seo_title' => $p['seo_title'],
                    'seo_description' => $p['seo_description'],
                    'published_at' => now(),
                ]
            );

            // Create Option: Size
            $sizeOption = ProductOption::firstOrCreate(
                [
                    'product_id' => $product->id,
                    'name' => 'Size',
                ],
                [
                    'position' => 0,
                ]
            );

            $kingVal = ProductOptionValue::firstOrCreate(
                [
                    'option_id' => $sizeOption->id,
                    'value' => 'King Size (108" × 108")',
                ],
                [
                    'position' => 0,
                ]
            );

            $queenVal = ProductOptionValue::firstOrCreate(
                [
                    'option_id' => $sizeOption->id,
                    'value' => 'Queen Size (90" × 100")',
                ],
                [
                    'position' => 1,
                ]
            );

            // King Variant
            $kingVariant = ProductVariant::firstOrCreate(
                [
                    'product_id' => $product->id,
                    'sku' => $p['sku_prefix'] . '-KNG',
                ],
                [
                    'price_paise' => 79900, // ₹799
                    'mrp_paise' => 159900,  // ₹1,599
                    'cost_paise' => 38000,
                    'option_signature' => (string) $kingVal->id,
                    'position' => 0,
                    'weight_grams' => 1100,
                ]
            );

            DB::table('product_variant_option_values')->updateOrInsert(
                ['variant_id' => $kingVariant->id, 'option_id' => $sizeOption->id],
                ['option_value_id' => $kingVal->id]
            );

            InventoryItem::firstOrCreate(
                ['variant_id' => $kingVariant->id],
                ['backorder_policy' => 'STOP_SALE', 'version' => 0]
            );

            InventoryLevel::firstOrCreate(
                ['variant_id' => $kingVariant->id, 'location_id' => 1],
                ['on_hand' => 25, 'reserved' => 0]
            );

            // Queen Variant
            $queenVariant = ProductVariant::firstOrCreate(
                [
                    'product_id' => $product->id,
                    'sku' => $p['sku_prefix'] . '-QEN',
                ],
                [
                    'price_paise' => 69900, // ₹699
                    'mrp_paise' => 139900,  // ₹1,399
                    'cost_paise' => 33000,
                    'option_signature' => (string) $queenVal->id,
                    'position' => 1,
                    'weight_grams' => 950,
                ]
            );

            DB::table('product_variant_option_values')->updateOrInsert(
                ['variant_id' => $queenVariant->id, 'option_id' => $sizeOption->id],
                ['option_value_id' => $queenVal->id]
            );

            InventoryItem::firstOrCreate(
                ['variant_id' => $queenVariant->id],
                ['backorder_policy' => 'STOP_SALE', 'version' => 0]
            );

            InventoryLevel::firstOrCreate(
                ['variant_id' => $queenVariant->id, 'location_id' => 1],
                ['on_hand' => 25, 'reserved' => 0]
            );

            // Primary Image
            ProductImage::firstOrCreate(
                [
                    'product_id' => $product->id,
                    'position' => 0,
                ],
                [
                    'storage_key' => $p['image'],
                    'alt_text' => $p['alt_text'],
                    'is_actual_product_image' => true,
                    'width' => 1200,
                    'height' => 900,
                    'byte_size' => 250000,
                ]
            );
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        $category = Category::where('slug', 'jaipuri-bedsheets')->first();
        if ($category) {
            $productIds = Product::where('category_id', $category->id)->pluck('id');
            $variantIds = ProductVariant::whereIn('product_id', $productIds)->pluck('id');

            DB::table('product_variant_option_values')->whereIn('variant_id', $variantIds)->delete();
            InventoryLevel::whereIn('variant_id', $variantIds)->delete();
            InventoryItem::whereIn('variant_id', $variantIds)->delete();
            ProductVariant::whereIn('id', $variantIds)->delete();
            ProductOptionValue::whereIn('option_id', ProductOption::whereIn('product_id', $productIds)->pluck('id'))->delete();
            ProductOption::whereIn('product_id', $productIds)->delete();
            ProductImage::whereIn('product_id', $productIds)->delete();
            Product::whereIn('id', $productIds)->delete();
            $category->delete();
        }
    }
};
