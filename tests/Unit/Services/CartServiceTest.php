<?php
/**
 * @noinspection PhpUnhandledExceptionInspection
 */

use App\DataTransferObjects\CartItemData;
use App\Enums\DiscountType;
use App\Enums\PaymentMethod;
use App\Models\Category;
use App\Models\Customer;
use App\Models\Product;
use App\Models\User;
use App\Services\CartService;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

beforeEach(function () {
    $this->cartService = new CartService(taxRate: 0.10);

    $category = Category::factory()->create(['name' => 'Test Category']);

    $this->product = Product::factory()->create([
        'name'        => 'Test Product',
        'sku'         => 'TEST-001',
        'price'       => 10.00,
        'stock_qtty'  => 100,
        'is_active'   => true,
        'category_id' => $category->id,
    ]);

    $this->user     = User::factory()->create();
    $this->customer = Customer::factory()->create();
});

/*
|--------------------------------------------------------------------------
| Adding Products
|--------------------------------------------------------------------------
*/

test('it can add product to cart', function () {
    $item = $this->cartService->addProduct($this->product);

    expect($item)->toBeInstanceOf(CartItemData::class)
        ->and($item->productId)->toBe($this->product->id)
        ->and($item->quantity)->toBe(1)
        ->and($item->unitPrice)->toBe(10.00);
});

test('it increments quantity when adding existing product', function () {
    $this->cartService->addProduct($this->product);
    $item = $this->cartService->addProduct($this->product);

    expect($item->quantity)->toBe(2)
        ->and($this->cartService->getItems())->toHaveCount(1);
});

test('it can add multiple different products', function () {
    $product2 = Product::factory()->create([
        'name'       => 'Product 2',
        'stock_qtty' => 50,
        'is_active'  => true,
    ]);

    $this->cartService->addProduct($this->product);
    $this->cartService->addProduct($product2);

    expect($this->cartService->getItems())->toHaveCount(2);
});

test('it throws exception for inactive product', function () {
    $inactiveProduct = Product::factory()->create(['is_active' => false]);

    $this->cartService->addProduct($inactiveProduct);
})->throws(Exception::class, 'is inactive');

test('it throws exception for out of stock product', function () {
    $outOfStock = Product::factory()->create([
        'stock_qtty' => 0,
        'is_active'  => true,
    ]);

    $this->cartService->addProduct($outOfStock);
})->throws(Exception::class, 'Insufficient stock');

test('it throws exception when exceeding stock', function () {
    $limitedStock = Product::factory()->create([
        'stock_qtty' => 2,
        'is_active'  => true,
    ]);

    $this->cartService->addProduct($limitedStock);
    $this->cartService->addProduct($limitedStock);

    $this->cartService->addProduct($limitedStock);
})->throws(Exception::class);

/*
|--------------------------------------------------------------------------
| Updating Quantity
|--------------------------------------------------------------------------
*/

test('it can update item quantity', function () {
    $this->cartService->addProduct($this->product);
    $item = $this->cartService->updateQuantity($this->product->id, 5);

    expect($item->quantity)->toBe(5);
});

test('it removes item when quantity is zero', function () {
    $this->cartService->addProduct($this->product);
    $result = $this->cartService->updateQuantity($this->product->id, 0);

    expect($result)->toBeNull()
        ->and($this->cartService->isEmpty())->toBeTrue();
});

test('it can increment quantity', function () {
    $this->cartService->addProduct($this->product);
    $item = $this->cartService->incrementQuantity($this->product->id);

    expect($item->quantity)->toBe(2);
});

test('it can decrement quantity', function () {
    $this->cartService->addProduct($this->product, 3);
    $item = $this->cartService->decrementQuantity($this->product->id);

    expect($item->quantity)->toBe(2);
});

test('it removes item when decrementing to zero', function () {
    $this->cartService->addProduct($this->product);
    $this->cartService->decrementQuantity($this->product->id);

    expect($this->cartService->isEmpty())->toBeTrue();
});

test('it enforces max stock on quantity update', function () {
    $limitedProduct = Product::factory()->create([
        'stock_qtty' => 5,
        'is_active'  => true,
    ]);

    $this->cartService->addProduct($limitedProduct);

    $this->cartService->updateQuantity($limitedProduct->id, 10);
})->throws(Exception::class, '5 units available');

/*
|--------------------------------------------------------------------------
| Removing Items
|--------------------------------------------------------------------------
*/

test('it can remove item from cart', function () {
    $this->cartService->addProduct($this->product);
    $removed = $this->cartService->removeItem($this->product->id);

    expect($removed)->toBeTrue()
        ->and($this->cartService->isEmpty())->toBeTrue();
});

test('it returns false when removing nonexistent item', function () {
    $removed = $this->cartService->removeItem(999);

    expect($removed)->toBeFalse();
});

test('it can clear entire cart', function () {
    $product2 = Product::factory()->create(['is_active' => true, 'stock_qtty' => 10]);

    $this->cartService->addProduct($this->product);
    $this->cartService->addProduct($product2);
    $this->cartService->setOrderDiscount(5.00, DiscountType::Fixed);

    $this->cartService->clear();

    expect($this->cartService->isEmpty())->toBeTrue()
        ->and($this->cartService->getOrderDiscount())->toBe(0.0);
});

/*
|--------------------------------------------------------------------------
| Item Discounts
|--------------------------------------------------------------------------
*/

test('it can apply fixed item discount', function () {
    $this->cartService->addProduct($this->product, 2); // $20 total
    $this->cartService->updateItemDiscount($this->product->id, 5.00, DiscountType::Fixed);

    $item = $this->cartService->getItem($this->product->id);

    expect($item->getDiscountAmount())->toBe(5.00)
        ->and($item->getSubtotal())->toBe(15.00);
});

test('it can apply percentage item discount', function () {
    $this->cartService->addProduct($this->product, 2); // $20 total
    $this->cartService->updateItemDiscount($this->product->id, 10, DiscountType::Percentage);

    $item = $this->cartService->getItem($this->product->id);

    expect($item->getDiscountAmount())->toBe(2.00) // 10% of $20
        ->and($item->getSubtotal())->toBe(18.00);
});

test('it caps fixed discount at line total', function () {
    $this->cartService->addProduct($this->product); // $10 total
    $this->cartService->updateItemDiscount($this->product->id, 50.00, DiscountType::Fixed);

    $item = $this->cartService->getItem($this->product->id);

    expect($item->getDiscountAmount())->toBe(10.00) // Capped at line total
        ->and($item->getSubtotal())->toBe(0.00);
});

test('it caps percentage discount at 100', function () {
    $this->cartService->addProduct($this->product); // $10 total
    $this->cartService->updateItemDiscount($this->product->id, 150, DiscountType::Percentage);

    $item = $this->cartService->getItem($this->product->id);

    expect($item->getDiscountAmount())->toBe(10.00) // 100% max
        ->and($item->getSubtotal())->toBe(0.00);
});

/*
|--------------------------------------------------------------------------
| Order Discounts
|--------------------------------------------------------------------------
*/

test('it can apply fixed order discount', function () {
    $this->cartService->addProduct($this->product, 10); // $100 subtotal
    $this->cartService->setOrderDiscount(10.00, DiscountType::Fixed);

    $totals = $this->cartService->getTotals();

    // Subtotal: $100, Tax: $10, Gross: $110, Discount: $10, Total: $100
    expect($totals->subtotal)->toBe(100.00)
        ->and($totals->taxAmount)->toBe(10.00)
        ->and($totals->grossTotal)->toBe(110.00)
        ->and($totals->orderDiscountAmount)->toBe(10.00)
        ->and($totals->total)->toBe(100.00);
});

test('it can apply percentage order discount', function () {
    $this->cartService->addProduct($this->product, 10); // $100 subtotal
    $this->cartService->setOrderDiscount(10, DiscountType::Percentage);

    $totals = $this->cartService->getTotals();

    // Subtotal: $100, Tax: $10, Gross: $110, Discount: $11 (10% of $110), Total: $99
    expect($totals->grossTotal)->toBe(110.00)
        ->and($totals->orderDiscountAmount)->toBe(11.00)
        ->and($totals->total)->toBe(99.00);
});

/*
|--------------------------------------------------------------------------
| Totals Calculation
|--------------------------------------------------------------------------
*/

test('it calculates totals correctly', function () {
    $this->cartService->addProduct($this->product, 3); // $30

    $totals = $this->cartService->getTotals();

    expect($totals->subtotal)->toBe(30.00)
        ->and($totals->taxAmount)->toBe(3.00)
        ->and($totals->grossTotal)->toBe(33.00)
        ->and($totals->total)->toBe(33.00)
        ->and($totals->totalQuantity)->toBe(3)
        ->and($totals->itemCount)->toBe(1);
    // 10% tax
});

test('it calculates totals with item and order discounts', function () {
    $this->cartService->addProduct($this->product, 10);                                     // $100
    $this->cartService->updateItemDiscount($this->product->id, 10.00, DiscountType::Fixed); // -$10
    $this->cartService->setOrderDiscount(5.00, DiscountType::Fixed);

    $totals = $this->cartService->getTotals();

    // Subtotal (after item discount): $90
    // Tax: $9
    // Gross: $99
    // Order discount: $5
    // Total: $94
    expect($totals->subtotal)->toBe(90.00)
        ->and($totals->taxAmount)->toBe(9.00)
        ->and($totals->grossTotal)->toBe(99.00)
        ->and($totals->orderDiscountAmount)->toBe(5.00)
        ->and($totals->total)->toBe(94.00)
        ->and($totals->getTotalSavings())->toBe(15.00); // $10 item + $5 order
});

test('empty cart has zero totals', function () {
    $totals = $this->cartService->getTotals();

    expect($totals->subtotal)->toBe(0.00)
        ->and($totals->taxAmount)->toBe(0.00)
        ->and($totals->total)->toBe(0.00)
        ->and($totals->itemCount)->toBe(0);
});

/*
|--------------------------------------------------------------------------
| Change Calculation
|--------------------------------------------------------------------------
*/

test('it calculates change correctly', function () {
    $this->cartService->addProduct($this->product); // $10 + $1 tax = $11
    $change = $this->cartService->calculateChange(20.00);

    expect($change)->toBe(9.00);
});

test('it returns zero change for insufficient payment', function () {
    $this->cartService->addProduct($this->product); // $11 total
    $change = $this->cartService->calculateChange(5.00);

    expect($change)->toBe(0.00);
});

/*
|--------------------------------------------------------------------------
| State Serialization
|--------------------------------------------------------------------------
*/

test('it can serialize and restore state', function () {
    $this->cartService->addProduct($this->product, 2);
    $this->cartService->updateItemDiscount($this->product->id, 1.50, DiscountType::Fixed);
    $this->cartService->setOrderDiscount(2.00, DiscountType::Fixed);

    $state = $this->cartService->toArray();

    $newService = new CartService();
    $newService->fromArray($state);

    expect($newService->getTotals()->total)->toBe($this->cartService->getTotals()->total)
        ->and($newService->getItems())->toHaveCount(1)
        ->and($newService->getOrderDiscount())->toBe(2.00);
});

/*
|--------------------------------------------------------------------------
| Checkout
|--------------------------------------------------------------------------
*/

test('it can complete checkout', function () {
    $this->cartService->addProduct($this->product, 2); // $22 total

    $transaction = $this->cartService->checkout(
        cashierId: $this->user->id,
        customerId: $this->customer->id,
        amountPaid: 25.00,
        paymentMethod: PaymentMethod::Cash,
    );

    expect($transaction)->not->toBeNull()
        ->and($transaction->invoice_number)->not->toBeNull()
        ->and((float)$transaction->total)->toBe(22.00)
        ->and($transaction->payment)->not->toBeNull()
        ->and($transaction->payment->method)->toBe(PaymentMethod::Cash);

    // Stock should be reduced
    $this->product->refresh();
    expect($this->product->stock_qtty)->toBe(98);
});

test('checkout fails with empty cart', function () {
    $this->cartService->checkout(
        cashierId: $this->user->id,
        customerId: $this->customer->id,
        amountPaid: 50.00,
        paymentMethod: PaymentMethod::Cash,
    );
})->throws(Exception::class, 'Cart is empty');

test('checkout fails with insufficient payment', function () {
    $this->cartService->addProduct($this->product); // $11 total

    $this->cartService->checkout(
        cashierId: $this->user->id,
        customerId: $this->customer->id,
        amountPaid: 5.00,
        paymentMethod: PaymentMethod::Cash,
    );
})->throws(Exception::class, 'Insufficient payment');

test('checkout rolls back on stock error', function () {
    $this->cartService->addProduct($this->product, 2);

    // Simulate stock being reduced by another transaction
    $this->product->update(['stock_qtty' => 1]);

    expect(fn() => $this->cartService->checkout(
        cashierId: $this->user->id,
        customerId: $this->customer->id,
        amountPaid: 50.00,
        paymentMethod: PaymentMethod::Cash,
    ))->toThrow(Exception::class, 'insufficient stock');

    // Stock should not have changed
    $this->product->refresh();
    expect($this->product->stock_qtty)->toBe(1);
});

/*
|--------------------------------------------------------------------------
| Product Search
|--------------------------------------------------------------------------
*/

test('it can search products by name', function () {
    $results = $this->cartService->searchProducts('Test');

    expect($results)->toHaveCount(1)
        ->and($results->first()->name)->toBe('Test Product');
});

test('it can search products by sku', function () {
    $results = $this->cartService->searchProducts('TEST-001');

    expect($results)->toHaveCount(1);
});

test('it excludes inactive products from search', function () {
    Product::factory()->create([
        'name'      => 'Inactive Test',
        'is_active' => false,
    ]);

    $results = $this->cartService->searchProducts('Inactive');

    expect($results)->toHaveCount(0);
});

test('it excludes out of stock products from search', function () {
    Product::factory()->create([
        'name'       => 'Out of Stock Test',
        'stock_qtty' => 0,
        'is_active'  => true,
    ]);

    $results = $this->cartService->searchProducts('Out of Stock');

    expect($results)->toHaveCount(0);
});

test('it returns empty for short search query', function () {
    $results = $this->cartService->searchProducts('T');

    expect($results)->toHaveCount(0);
});

test('it can get products by category', function () {
    $category = Category::factory()->create();
    Product::factory()->count(3)->create([
        'category_id' => $category->id,
        'is_active'   => true,
        'stock_qtty'  => 10,
    ]);

    $results = $this->cartService->getProductsByCategory($category->id);

    expect($results)->toHaveCount(3);
});

test('it can get all products when category is null', function () {
    Product::factory()->count(2)->create([
        'is_active'  => true,
        'stock_qtty' => 10,
    ]);

    $results = $this->cartService->getProductsByCategory(null);

    // Original product + 2 new ones
    expect($results->count())->toBeGreaterThanOrEqual(3);
});
