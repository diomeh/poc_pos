<?php

namespace App\DataTransferObjects;

use App\Enums\DiscountType;
use App\Models\Product;
use InvalidArgumentException;

/**
 * Immutable Data Transfer Object for cart items.
 * Provides type safety and encapsulates discount calculations.
 */
final readonly class CartItemData
{
    public function __construct(
        public int          $productId,
        public string       $name,
        public string       $sku,
        public float        $unitPrice,
        public int          $quantity,
        public float        $discount,
        public DiscountType $discountType,
        public int          $maxStock,
        public ?string      $imageUrl = null,
    )
    {
        if ($quantity < 0) {
            throw new InvalidArgumentException('Quantity cannot be negative');
        }
        if ($unitPrice < 0) {
            throw new InvalidArgumentException('Unit price cannot be negative');
        }
        if ($discount < 0) {
            throw new InvalidArgumentException('Discount cannot be negative');
        }
    }

    /**
     * Create from a Product model
     */
    public static function fromProduct(Product $product, int $quantity = 1): self
    {
        return new self(
            productId: $product->id,
            name: $product->name,
            sku: $product->sku,
            unitPrice: (float)$product->price,
            quantity: $quantity,
            discount: 0.0,
            discountType: DiscountType::Fixed,
            maxStock: $product->stock_qtty,
            imageUrl: $product->image_url ?? null,
        );
    }

    /**
     * Create from array (for Livewire state hydration)
     */
    public static function fromArray(array $data): self
    {
        return new self(
            productId: (int)$data['product_id'],
            name: (string)$data['name'],
            sku: (string)$data['sku'],
            unitPrice: (float)$data['unit_price'],
            quantity: (int)$data['quantity'],
            discount: (float)($data['discount'] ?? 0),
            discountType: $data['discount_type'] instanceof DiscountType
                ? $data['discount_type']
                : DiscountType::tryFrom((int)$data['discount_type']) ?? DiscountType::Fixed,
            maxStock: (int)($data['max_stock'] ?? 0),
            imageUrl: $data['image_url'] ?? null,
        );
    }

    /**
     * Convert to array for Livewire state
     */
    public function toArray(): array
    {
        return [
            'product_id'      => $this->productId,
            'name'            => $this->name,
            'sku'             => $this->sku,
            'unit_price'      => $this->unitPrice,
            'quantity'        => $this->quantity,
            'discount'        => $this->discount,
            'discount_type'   => $this->discountType->value,
            'max_stock'       => $this->maxStock,
            'image_url'       => $this->imageUrl,
            // Computed values for easy access in views
            'line_total'      => $this->getLineTotal(),
            'discount_amount' => $this->getDiscountAmount(),
            'subtotal'        => $this->getSubtotal(),
        ];
    }

    /**
     * Line total before discount (unit_price × quantity)
     */
    public function getLineTotal(): float
    {
        return round($this->unitPrice * $this->quantity, 2);
    }

    /**
     * Calculate discount amount based on type
     */
    public function getDiscountAmount(): float
    {
        $lineTotal = $this->getLineTotal();

        if ($lineTotal <= 0 || $this->discount <= 0) {
            return 0.0;
        }

        return match ($this->discountType) {
            DiscountType::Percentage => round(
                $lineTotal * (min(100, $this->discount) / 100),
                2
            ),
            DiscountType::Fixed      => round(
                min($lineTotal, $this->discount),
                2
            ),
            default                  => 0.0,
        };
    }

    /**
     * Subtotal after discount
     */
    public function getSubtotal(): float
    {
        return round($this->getLineTotal() - $this->getDiscountAmount(), 2);
    }

    /**
     * Create a new instance with updated quantity
     */
    public function withQuantity(int $quantity): self
    {
        return new self(
            productId: $this->productId,
            name: $this->name,
            sku: $this->sku,
            unitPrice: $this->unitPrice,
            quantity: min($quantity, $this->maxStock),
            discount: $this->discount,
            discountType: $this->discountType,
            maxStock: $this->maxStock,
            imageUrl: $this->imageUrl,
        );
    }

    /**
     * Create a new instance with updated discount
     */
    public function withDiscount(float $discount, ?DiscountType $type = null): self
    {
        return new self(
            productId: $this->productId,
            name: $this->name,
            sku: $this->sku,
            unitPrice: $this->unitPrice,
            quantity: $this->quantity,
            discount: max(0, $discount),
            discountType: $type ?? $this->discountType,
            maxStock: $this->maxStock,
            imageUrl: $this->imageUrl,
        );
    }

    /**
     * Check if item can be incremented
     */
    public function canIncrement(): bool
    {
        return $this->quantity < $this->maxStock;
    }

    /**
     * Check if item can be decremented
     */
    public function canDecrement(): bool
    {
        return $this->quantity > 1;
    }
}
