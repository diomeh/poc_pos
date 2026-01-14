<?php

namespace App\DataTransferObjects;

use App\Enums\DiscountType;

/**
 * Immutable Data Transfer Object for cart totals.
 * Encapsulates all order-level calculations.
 */
final readonly class CartTotalsData
{
    public function __construct(
        public float        $itemsTotal,
        public float        $itemDiscountsTotal,
        public float        $subtotal,
        public float        $taxRate,
        public float        $taxAmount,
        public float        $grossTotal,
        public float        $orderDiscount,
        public DiscountType $orderDiscountType,
        public float        $orderDiscountAmount,
        public float        $total,
        public int          $itemCount,
        public int          $totalQuantity,
    )
    {
    }

    /**
     * Create empty totals
     */
    public static function empty(float $taxRate = 0.10): self
    {
        return new self(
            itemsTotal: 0.0,
            itemDiscountsTotal: 0.0,
            subtotal: 0.0,
            taxRate: $taxRate,
            taxAmount: 0.0,
            grossTotal: 0.0,
            orderDiscount: 0.0,
            orderDiscountType: DiscountType::Fixed,
            orderDiscountAmount: 0.0,
            total: 0.0,
            itemCount: 0,
            totalQuantity: 0,
        );
    }

    /**
     * Calculate totals from cart items
     *
     * @param CartItemData[] $items
     */
    public static function calculate(
        array        $items,
        float        $taxRate,
        float        $orderDiscount,
        DiscountType $orderDiscountType,
    ): self
    {
        $itemsTotal         = 0.0;
        $itemDiscountsTotal = 0.0;
        $subtotal           = 0.0;
        $totalQuantity      = 0;

        foreach ($items as $item) {
            $itemsTotal         += $item->getLineTotal();
            $itemDiscountsTotal += $item->getDiscountAmount();
            $subtotal           += $item->getSubtotal();
            $totalQuantity      += $item->quantity;
        }

        // Calculate tax on subtotal (after item discounts)
        $taxAmount = round($subtotal * $taxRate, 2);

        // Gross total = subtotal + tax
        $grossTotal = round($subtotal + $taxAmount, 2);

        // Calculate order-level discount
        $orderDiscountAmount = match ($orderDiscountType) {
            DiscountType::Percentage => round(
                $grossTotal * (min(100, $orderDiscount) / 100),
                2
            ),
            DiscountType::Fixed      => round(
                min($grossTotal, max(0, $orderDiscount)),
                2
            ),
            default                  => 0.0,
        };

        // Final total
        $total = round(max(0, $grossTotal - $orderDiscountAmount), 2);

        return new self(
            itemsTotal: round($itemsTotal, 2),
            itemDiscountsTotal: round($itemDiscountsTotal, 2),
            subtotal: round($subtotal, 2),
            taxRate: $taxRate,
            taxAmount: $taxAmount,
            grossTotal: $grossTotal,
            orderDiscount: $orderDiscount,
            orderDiscountType: $orderDiscountType,
            orderDiscountAmount: $orderDiscountAmount,
            total: $total,
            itemCount: count($items),
            totalQuantity: $totalQuantity,
        );
    }

    /**
     * Check if there are any savings
     */
    public function hasSavings(): bool
    {
        return $this->getTotalSavings() > 0;
    }

    /**
     * Total savings (item discounts + order discount)
     */
    public function getTotalSavings(): float
    {
        return round($this->itemDiscountsTotal + $this->orderDiscountAmount, 2);
    }

    /**
     * Convert to array for views
     */
    public function toArray(): array
    {
        return [
            'items_total'           => $this->itemsTotal,
            'item_discounts_total'  => $this->itemDiscountsTotal,
            'subtotal'              => $this->subtotal,
            'tax_rate'              => $this->taxRate,
            'tax_rate_percent'      => $this->taxRate * 100,
            'tax_amount'            => $this->taxAmount,
            'gross_total'           => $this->grossTotal,
            'order_discount'        => $this->orderDiscount,
            'order_discount_type'   => $this->orderDiscountType->value,
            'order_discount_amount' => $this->orderDiscountAmount,
            'total'                 => $this->total,
            'total_savings'         => $this->getTotalSavings(),
            'item_count'            => $this->itemCount,
            'total_quantity'        => $this->totalQuantity,
        ];
    }
}
