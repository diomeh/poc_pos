<?php

namespace Database\Factories\Traits;

use App\Enums\DiscountType;
use Faker\Generator;

trait CalculatesDiscount
{
    /**
     * Get a random discount for a given subtotal.
     *
     * Returns an array with the following shape:
     * - amount (float): discount amount. For Percentage this is 0-100, for Fixed it's a monetary value up to the subtotal. Can be 0.
     * - subtotal (float): the subtotal after applying the discount.
     * - type (DiscountType): the discount type.
     *
     * @param float $subtotal
     * @return array{
     *     float,
     *     float,
     *     DiscountType
     * }
     * @noinspection PhpVariableIsUsedOnlyInClosureInspection
     */
    public function getDiscount(float $subtotal): array
    {
        /** @var DiscountType $discountType */
        $discountType = collect(DiscountType::cases())->random();

        $faker = new Generator;

        return match ($discountType) {
            DiscountType::None       => [
                0,
                $subtotal,
                DiscountType::None,
            ],
            DiscountType::Fixed      => (function () use ($subtotal, $faker) {
                $discountAmount = $faker->randomFloat(2, 0, $subtotal);
                return [
                    $discountAmount,
                    $subtotal - $discountAmount,
                    DiscountType::Fixed,
                ];
            })(),
            DiscountType::Percentage => (function () use ($subtotal, $faker) {
                $discountPercentage = $faker->randomFloat(2, 0, 1);
                $discountAmount     = $discountPercentage * $subtotal;
                return [
                    $discountPercentage,
                    $subtotal - $discountAmount,
                    DiscountType::Percentage,
                ];
            })(),
        };
    }
}
