<?php

declare(strict_types=1);

namespace Database\Seeders;

use App\Models\InstagramPost;
use App\Models\Product;
use Illuminate\Database\Seeder;

class InstagramFeedSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        if (InstagramPost::count() > 0) {
            return;
        }

        $items = [
            [
                'type' => 'reel',
                'caption' => "Festive drops are here! ✨ Pure Ajrakh handblock prints styled for intimate festive celebrations. Breathable pure cotton with matching mulmul dupatta. Tap to shop the full look! 🌸\n\n#dhansreetrends #jaipurikurti #anarkalisuits #purecotton #ethnicwear #festivecollection #jaipurfashion",
                'media_url' => 'products/2/6e2d12d1-ae7f-44aa-b575-7ebcf37b1b6e.webp',
                'instagram_url' => 'https://www.instagram.com/dhansreetrends',
                'likes_count' => 2480,
                'views_count' => '38.2K',
                'comments_count' => 54,
                'tagged_product_id' => 2,
                'position' => 1,
                'is_visible' => true,
            ],
            [
                'type' => 'reel',
                'caption' => "Effortless elegance in our Cotton Co-ord Kurta Shalwar Set with intricate border & motif detail 👗\n\nDesigned for graceful all-day comfort from morning office hours to festive family dinners. Available in sizes S to XXL! 🤍\n\n#coordset #jaipuri #festivestyle #ethniccomfort #dhansreestyle #madeinjaipur",
                'media_url' => 'products/4/002b52e4-b478-4103-8b69-69854668362c.webp',
                'instagram_url' => 'https://www.instagram.com/dhansreetrends',
                'likes_count' => 1890,
                'views_count' => '29.7K',
                'comments_count' => 41,
                'tagged_product_id' => 4,
                'position' => 2,
                'is_visible' => true,
            ],
            [
                'type' => 'reel',
                'caption' => "Pastel floral prints that feel like a gentle breeze 🌸 Our Off-White & Pink Floral Tunic with Trousers Co-ord is your go-to weekend look. Pure soft cotton blend and flattering cut.\n\nTap the shopping bag below to order with Free Express Delivery! 📦\n\n#dhansreetrends #floralcoords #pastellove #summerethnic #jaipurcotton #ootdindia",
                'media_url' => 'products/5/9ab0491b-95fd-4915-b10a-3d196c9f7c59.webp',
                'instagram_url' => 'https://www.instagram.com/dhansreetrends',
                'likes_count' => 3120,
                'views_count' => '44.8K',
                'comments_count' => 67,
                'tagged_product_id' => 5,
                'position' => 3,
                'is_visible' => true,
            ],
            [
                'type' => 'reel',
                'caption' => "Behind the craft: Slate Blue Pure Cotton Paisley Printed 3-Piece Suit Set 🧵✨\n\nAuthentic motifs, delicate lace accents on hem and sleeves, paired with straight trousers and lightweight printed dupatta. Made with love in Jaipur.\n\n#jaipurikurti #suitset #artisanmade #indiantradition #dhansree #festiveoutfit",
                'media_url' => 'products/6/df6cc412-9af9-48a5-a040-0005e84da902.webp',
                'instagram_url' => 'https://www.instagram.com/dhansreetrends',
                'likes_count' => 1650,
                'views_count' => '22.3K',
                'comments_count' => 38,
                'tagged_product_id' => 6,
                'position' => 4,
                'is_visible' => true,
            ],
            [
                'type' => 'post',
                'caption' => "Styling our bestselling Solid Cotton Straight Short Kurta in vibrant Crimson Red ❤️\n\nFeatures a flattering V-notch neckline and pure breathable cotton fabric. Pair it with white palazzos or denim for a chic everyday look.\n\n#shortkurti #redkurti #everydayethnic #collegelook #officeethnic #dhansreetrends",
                'media_url' => 'products/3/eafacebc-a5fc-4bdf-aac3-7099c1b29ba5.webp',
                'instagram_url' => 'https://www.instagram.com/dhansreetrends',
                'likes_count' => 1420,
                'views_count' => '19.1K',
                'comments_count' => 29,
                'tagged_product_id' => 3,
                'position' => 5,
                'is_visible' => true,
            ],
            [
                'type' => 'reel',
                'caption' => "Flared perfection 🤍 Women's Off-White Cotton Flex A-Line Kurta Set featuring intricate thread embroidery on sleeves and playful tassel accents.\n\nPure comfort for festive celebrations and pujas. Shop directly at dhansree.com! ✨\n\n#festivewear #alinekurta #tasseldetails #whiteethnic #jaipuri #festiveglow",
                'media_url' => 'products/9/db0d853c-db93-491c-b61b-ddb626099456.webp',
                'instagram_url' => 'https://www.instagram.com/dhansreetrends',
                'likes_count' => 2780,
                'views_count' => '36.5K',
                'comments_count' => 49,
                'tagged_product_id' => 9,
                'position' => 6,
                'is_visible' => true,
            ],
            [
                'type' => 'reel',
                'caption' => "Outfit wrinkle-free in 2 minutes! 💨 Our 2-in-1 Compact Travel Garment Steamer & Dry Iron is an ethnic wardrobe essential.\n\nFoldable, lightweight, and gentle on delicate silks, chiffons, and pure mulmul fabrics. Direct from our Smart Living Hub ⚡\n\n#smartliving #travelgadgets #steamer #ironinghacks #ethniccare #dhansree",
                'media_url' => 'products/7/e899c0e2-3164-45d7-945d-ae0dc956c3a6.webp',
                'instagram_url' => 'https://www.instagram.com/dhansreetrends',
                'likes_count' => 4210,
                'views_count' => '62.4K',
                'comments_count' => 83,
                'tagged_product_id' => 7,
                'position' => 7,
                'is_visible' => true,
            ],
            [
                'type' => 'post',
                'caption' => "Never get caught with low battery during festive roadtrips 📱⚡ 60W Multi-Functional Fast Charging Cable Kit with card ejector pin and multiple adapters in a compact pocket case.\n\nTested & shipped directly to you from Dhansree!\n\n#techaccessories #datacable #smartgadgets #everydaycarry #dhansreetrends",
                'media_url' => 'products/8/0ff4b314-f67a-47e4-8133-808587e2c52a.webp',
                'instagram_url' => 'https://www.instagram.com/dhansreetrends',
                'likes_count' => 980,
                'views_count' => '14.8K',
                'comments_count' => 19,
                'tagged_product_id' => 8,
                'position' => 8,
                'is_visible' => true,
            ],
        ];

        foreach ($items as $data) {
            // Verify tagged product exists or fallback to null
            if (!empty($data['tagged_product_id']) && !Product::where('id', $data['tagged_product_id'])->exists()) {
                $data['tagged_product_id'] = null;
            }
            InstagramPost::create($data);
        }
    }
}
