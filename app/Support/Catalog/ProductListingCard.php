<?php

declare(strict_types=1);

namespace App\Support\Catalog;

use App\Models\Product;
use App\Models\ProductVariant;
use Illuminate\Contracts\Support\Arrayable;
use Illuminate\Support\Collection;
use JsonSerializable;

class ProductListingCard implements Arrayable, JsonSerializable
{
    /**
     * @param Collection<int, ProductVariant> $variants
     */
    public function __construct(
        public Product $product,
        public string $name,
        public string $slug,
        public ?string $brand,
        public ?string $colorName,
        public ?string $colorCode,
        public ?string $colorHex,
        public ?string $primaryImageUrl,
        public string $url,
        public string $formattedPrice,
        public ?string $formattedMrp,
        public ?int $discountPercentage,
        public ?ProductVariant $firstVariant,
        public Collection $variants,
        public bool $hasMultipleVariants,
        public bool $inStock
    ) {}

    public function __get(string $key): mixed
    {
        return match ($key) {
            'id', 'product_id' => $this->product->id,
            'name' => $this->name,
            'slug' => $this->slug,
            'brand' => $this->brand,
            'color_name' => $this->colorName,
            'color_code' => $this->colorCode,
            'color_hex' => $this->colorHex,
            'primary_image_url' => $this->primaryImageUrl,
            'url' => $this->url,
            'formatted_price' => $this->formattedPrice,
            'formatted_mrp' => $this->formattedMrp,
            'discount_percentage' => $this->discountPercentage,
            'first_variant', 'firstVariant' => $this->firstVariant,
            'variants' => $this->variants,
            'has_multiple_variants', 'hasMultipleVariants' => $this->hasMultipleVariants,
            'in_stock', 'inStock' => $this->inStock,
            'approved_video', 'approvedVideo' => $this->product->approved_video,
            default => $this->product->getAttribute($key),
        };
    }

    public function __isset(string $key): bool
    {
        return in_array($key, [
            'id', 'product_id', 'name', 'slug', 'brand',
            'color_name', 'color_code', 'color_hex',
            'primary_image_url', 'url', 'formatted_price', 'formatted_mrp',
            'discount_percentage', 'first_variant', 'firstVariant',
            'variants', 'has_multiple_variants', 'hasMultipleVariants', 'in_stock', 'inStock',
            'approved_video', 'approvedVideo',
        ], true) || isset($this->product->{$key});
    }

    public function toArray(): array
    {
        return [
            'id' => $this->product->id,
            'product_id' => $this->product->id,
            'name' => $this->name,
            'slug' => $this->slug,
            'brand' => $this->brand,
            'color_name' => $this->colorName,
            'color_code' => $this->colorCode,
            'color_hex' => $this->colorHex,
            'primary_image_url' => $this->primaryImageUrl,
            'url' => $this->url,
            'formatted_price' => $this->formattedPrice,
            'formatted_mrp' => $this->formattedMrp,
            'discount_percentage' => $this->discountPercentage,
            'first_variant_id' => $this->firstVariant?->id,
            'in_stock' => $this->inStock,
        ];
    }

    public function jsonSerialize(): array
    {
        return $this->toArray();
    }
}
