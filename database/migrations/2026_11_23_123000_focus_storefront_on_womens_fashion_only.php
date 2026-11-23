<?php

declare(strict_types=1);

use App\Models\Category;
use App\Models\ContentPage;
use App\Models\ContentPageSection;
use App\Models\QuickLink;
use Illuminate\Database\Migrations\Migration;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        // 1. Update ContentPage 'ABOUT' to focus exclusively on Women's Fashion, Kurtis, and Suit Sets
        $aboutPage = ContentPage::where('key', 'ABOUT')->first();
        if ($aboutPage) {
            $aboutPage->update([
                'title' => 'About Us',
                'intro' => "Dhansree is a women’s ethnic fashion brand operated by Dhanshree Trends, rooted in the heritage city of Jaipur, Rajasthan. We specialize in handcrafted women’s kurtis, elegant suit sets with dupattas, contemporary two-piece co-ord sets, and festive ethnic wear.\n\nOur mission is to celebrate authentic Indian craftsmanship, premium breathable cotton and festive fabrics, flattering silhouettes, and transparent direct-to-consumer pricing. At Dhansree, we bring Jaipur's timeless textile artistry directly to modern women across India.",
                'seo_title' => "About Us | Dhansree Trends — Handcrafted Women's Kurtis & Suit Sets",
                'seo_description' => "Learn about Dhansree Trends, Jaipur's premier women's ethnic fashion brand. Explore handcrafted pure cotton kurties, designer salwar suit sets, and chic co-ord sets.",
                'is_visible' => true,
            ]);

            // Replace existing sections for ABOUT page
            ContentPageSection::where('page_id', $aboutPage->id)->delete();

            $sections = [
                [
                    'position' => 0,
                    'heading' => 'Our Story & Heritage',
                    'body' => "Dhansree began in 2026, inspired by the rich textile heritage, vibrant block prints, and royal elegance of Jaipur, Rajasthan. Founded by Beena Sharma, Dhansree is built with love, passion, and a clear vision: to bring genuine artisanal ethnic wear to women seeking comfort, grace, and authentic styling.\n\nThe name Dhansree holds a deeply personal meaning for our family — named after our beloved god-gifted daughter, who continues to be our greatest blessing and inspiration behind this journey.\n\nWhat started as a shared family dream has blossomed into a curated destination for women's ethnic fashion. We set out to bridge the gap between traditional Rajasthani craftsmanship and contemporary everyday wear. Every kurti, salwar suit, and co-ord set in our collection is thoughtfully designed to make you feel confident, comfortable, and beautifully radiant — whether you are heading to work, attending a family celebration, or enjoying a festive occasion.\n\nBased in Jaipur, Dhanshree Trends currently serves patrons across India through our official online store at dhansree.com, delivering authentic handcrafted elegance straight from our workshop to your wardrobe.",
                ],
                [
                    'position' => 1,
                    'heading' => 'Our Collections & Craft',
                    'body' => "We take immense pride in curating women's ethnic wear that celebrates Indian culture while embracing modern convenience:\n\n• **Handcrafted Kurta Sets:** Breathable pure cotton, chanderi, and silk-blend kurta sets featuring delicate gota-patti accents, floral prints, and comfortable matching trousers.\n\n• **Salwar Suit Sets with Dupatta:** Classic three-piece ensembles pairing beautifully tailored kurtas with coordinated bottoms and airy mulmul, organza, or chiffon dupattas.\n\n• **Festive Anarkali Ensembles:** Royal flared silhouettes with intricate yoke embroidery and graceful drape, perfect for weddings, pujas, and celebratory gatherings.\n\n• **Contemporary Co-ord Sets:** Two-piece matching ethnic sets that effortlessly balance modern chic styling with all-day comfort for work, travel, and casual outings.\n\n• **Everyday Pure Cotton Wear:** Feather-light, skin-friendly cottons designed for the Indian climate, keeping you cool, poised, and stylish all day long.",
                ],
                [
                    'position' => 2,
                    'heading' => 'Fabric Quality & Design Philosophy',
                    'body' => "At Dhansree, our approach to fashion is defined by quality, comfort, and authenticity:\n\n• **Natural & Breathable Fabrics:** We prioritize high-grade combed cotton, premium modal, and artisanal weaves that feel gentle on the skin and endure through seasons.\n\n• **Flattering Fits (Sizes S to XXL):** Every garment is tailored with careful attention to Indian body proportions, ensuring ease of movement and flattering drape.\n\n• **Jaipur Artisanal Aesthetics:** From traditional sanganeri motifs and bagru-inspired prints to subtle sequin and zari embroidery, our designs honor Jaipur's world-renowned artisan legacy.\n\n• **Direct-to-Consumer Value:** By designing, crafting, and shipping directly to you without multiple middlemen markups, we deliver luxury aesthetics and fine craftsmanship at fair, honest prices.",
                ],
                [
                    'position' => 3,
                    'heading' => 'Where to Find Us & Customer Care',
                    'body' => "Dhansree is proudly headquartered in the Pink City of Jaipur, Rajasthan, India, and delivers to pin codes across the country.\n\nYou can explore our entire catalog of women's ethnic fashion anytime from the comfort of your home.\n\n**Website:** https://dhansree.com\n**Business Legal Name:** Dhanshree Trends\n**Headquarters:** Jaipur, Rajasthan, India\n**Customer Care Email:** info@dhansree.com\n**WhatsApp Support:** +91 9887776777\n**Operating Hours:** Monday – Saturday, 10:00 AM – 7:00 PM IST\n\nThank you for being part of our story and supporting authentic homegrown ethnic fashion. Every order supports Indian artisans and brings us one step closer to our dream.",
                ],
            ];

            foreach ($sections as $sec) {
                ContentPageSection::create([
                    'page_id' => $aboutPage->id,
                    'position' => $sec['position'],
                    'heading' => $sec['heading'],
                    'body' => $sec['body'],
                ]);
            }
        }

        // 2. Hide Non-Women's Fashion categories from public browse & navigation
        $nonFashionSlugs = [
            'mobile-accessories',
            'smart-gadgets',
            'home-and-lifestyle',
            'car-accessories',
            'home-essentials',
            'kitchen-essentials',
        ];

        Category::whereIn('slug', $nonFashionSlugs)->update([
            'is_visible' => false,
            'show_in_browse' => false,
        ]);

        // Ensure Women's Fashion categories are visible
        $fashionSlugs = [
            'women-fashion',
            'kurta-set',
            'women-two-piece-sets-co-ord',
            'women-suit-set',
            'anarkali-kurta-sets',
        ];

        Category::whereIn('slug', $fashionSlugs)->update([
            'is_visible' => true,
            'show_in_browse' => true,
        ]);

        // 3. Update quick links so Home & Lifestyle is replaced by Kurta Sets
        $quickLinkHome = QuickLink::where('path', 'like', '%home-and-lifestyle%')->first();
        if ($quickLinkHome) {
            $quickLinkHome->update([
                'label' => 'Kurta Sets',
                'path' => '/kurta-set',
            ]);
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        // No-op rollback to maintain data safety
    }
};
