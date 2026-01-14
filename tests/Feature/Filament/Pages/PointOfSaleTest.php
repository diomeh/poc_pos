<?php

use App\Enums\PaymentMethod;
use App\Filament\Pages\PointOfSale;
use App\Models\Category;
use App\Models\Customer;
use App\Models\Payment;
use App\Models\Product;
use App\Models\Transaction;
use App\Models\TransactionItem;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;

uses(RefreshDatabase::class);

beforeEach(function () {
    $this->user = User::factory()->create();

    $category = Category::factory()->create(['name' => 'Electronics']);

    $this->product = Product::factory()->create([
        'name'        => 'Test Product',
        'sku'         => 'TEST-001',
        'price'       => 25.00,
        'stock_qtty'  => 50,
        'is_active'   => true,
        'category_id' => $category->id,
    ]);

    $this->customer = Customer::factory()->create();
});

test('pos page can be rendered', function () {
    $this->actingAs($this->user);

    $response = $this->get(PointOfSale::getUrl());

    $response->assertSuccessful();
});

test('it can search products', function () {
    Livewire::actingAs($this->user)
        ->test(PointOfSale::class)
        ->set('search', 'Test')
        ->assertSee('Test Product')
        ->assertSee('TEST-001');
});

test('it can add product to cart', function () {
    Livewire::actingAs($this->user)
        ->test(PointOfSale::class)
        ->call('addToCart', $this->product->id)
        ->assertSet('cartItems.' . $this->product->id . '.name', 'Test Product')
        ->assertSet('cartItems.' . $this->product->id . '.quantity', 1);
});

test('it can increment cart item', function () {
    Livewire::actingAs($this->user)
        ->test(PointOfSale::class)
        ->call('addToCart', $this->product->id)
        ->call('incrementItem', $this->product->id)
        ->assertSet('cartItems.' . $this->product->id . '.quantity', 2);
});

test('it can decrement cart item', function () {
    Livewire::actingAs($this->user)
        ->test(PointOfSale::class)
        ->call('addToCart', $this->product->id)
        ->call('incrementItem', $this->product->id) // qty = 2
        ->call('decrementItem', $this->product->id) // qty = 1
        ->assertSet('cartItems.' . $this->product->id . '.quantity', 1);
});

test('it removes item when decremented to zero', function () {
    Livewire::actingAs($this->user)
        ->test(PointOfSale::class)
        ->call('addToCart', $this->product->id)
        ->call('decrementItem', $this->product->id)
        ->assertSet('cartItems', []);
});

test('it can remove item from cart', function () {
    Livewire::actingAs($this->user)
        ->test(PointOfSale::class)
        ->call('addToCart', $this->product->id)
        ->call('removeItem', $this->product->id)
        ->assertSet('cartItems', []);
});

test('it can clear cart', function () {
    $product2 = Product::factory()->create([
        'is_active'  => true,
        'stock_qtty' => 10,
    ]);

    Livewire::actingAs($this->user)
        ->test(PointOfSale::class)
        ->call('addToCart', $this->product->id)
        ->call('addToCart', $product2->id)
        ->call('clearCart')
        ->assertSet('cartItems', [])
        ->assertSet('amountPaid', 0.0);
});

test('it can set payment method', function () {
    Livewire::actingAs($this->user)
        ->test(PointOfSale::class)
        ->call('setPaymentMethod', PaymentMethod::CreditCard->value)
        ->assertSet('paymentMethod', PaymentMethod::CreditCard->value);
});

test('it auto fills amount for non cash payments', function () {
    Livewire::actingAs($this->user)
        ->test(PointOfSale::class)
        ->call('addToCart', $this->product->id) // $25 + $2.50 tax = $27.50
        ->call('setPaymentMethod', PaymentMethod::CreditCard->value)
        ->assertSet('amountPaid', 27.50);
});

test('it can set cash amount', function () {
    Livewire::actingAs($this->user)
        ->test(PointOfSale::class)
        ->call('addToCart', $this->product->id)
        ->call('setAmountPaid', 50.00)
        ->assertSet('amountPaid', 50.00);
});

test('it calculates correct totals', function () {
    $component = Livewire::actingAs($this->user)
        ->test(PointOfSale::class)
        ->call('addToCart', $this->product->id) // $25
        ->call('incrementItem', $this->product->id); // $50

    $totals = $component->get('totals');

    expect($totals['subtotal'])->toBe(50.00)
        ->and($totals['tax_amount'])->toBe(5.00) // 10%
        ->and($totals['total'])->toBe(55.00);
});

test('it calculates change correctly', function () {
    $component = Livewire::actingAs($this->user)
        ->test(PointOfSale::class)
        ->call('addToCart', $this->product->id) // $27.50 total
        ->call('setAmountPaid', 30.00);

    expect($component->get('change'))->toBe(2.50);
});

test('can checkout is false with empty cart', function () {
    $component = Livewire::actingAs($this->user)
        ->test(PointOfSale::class);

    expect($component->get('canCheckout'))->toBeFalse();
});

test('can checkout is false with insufficient payment', function () {
    $component = Livewire::actingAs($this->user)
        ->test(PointOfSale::class)
        ->call('addToCart', $this->product->id) // $27.50
        ->call('setAmountPaid', 10.00);

    expect($component->get('canCheckout'))->toBeFalse();
});

test('can checkout is true with sufficient payment', function () {
    $component = Livewire::actingAs($this->user)
        ->test(PointOfSale::class)
        ->call('addToCart', $this->product->id) // $27.50
        ->call('setAmountPaid', 30.00);

    expect($component->get('canCheckout'))->toBeTrue();
});

test('it can complete sale', function () {
    Livewire::actingAs($this->user)
        ->test(PointOfSale::class)
        ->set('customerId', $this->customer->id)
        ->call('addToCart', $this->product->id)
        ->call('setAmountPaid', 30.00)
        ->call('completeSale')
        ->assertSet('cartItems', [])
        ->assertSet('amountPaid', 0.0);

    // Verify transaction was created
    expect(Transaction::all())->toHaveCount(1)
        ->and(TransactionItem::all())->toHaveCount(1)
        ->and(Payment::all())->toHaveCount(1);

    // Verify stock was reduced
    $this->product->refresh();
    expect($this->product->stock_qtty)->toBe(49);
});

test('it shows notification on successful sale', function () {
    Livewire::actingAs($this->user)
        ->test(PointOfSale::class)
        ->set('customerId', $this->customer->id)
        ->call('addToCart', $this->product->id)
        ->call('setAmountPaid', 30.00)
        ->call('completeSale')
        ->assertNotified('Sale completed!');
});

test('it prevents sale with insufficient payment', function () {
    Livewire::actingAs($this->user)
        ->test(PointOfSale::class)
        ->set('customerId', $this->customer->id)
        ->call('addToCart', $this->product->id)
        ->call('setAmountPaid', 5.00)
        ->call('completeSale')
        ->assertNotified('Sale failed');

    expect(Transaction::all())->toHaveCount(0);
});

test('it can filter by category', function () {
    $category2 = Category::factory()->create(['name' => 'Clothing']);
    Product::factory()->create([
        'name'        => 'T-Shirt',
        'category_id' => $category2->id,
        'is_active'   => true,
        'stock_qtty'  => 10,
    ]);

    Livewire::actingAs($this->user)
        ->test(PointOfSale::class)
        ->call('setCategory', $category2->id)
        ->assertSet('activeCategory', $category2->id)
        ->assertSet('search', '');
});

test('it shows remaining amount when insufficient', function () {
    $component = Livewire::actingAs($this->user)
        ->test(PointOfSale::class)
        ->call('addToCart', $this->product->id) // $27.50
        ->call('setAmountPaid', 20.00);

    expect($component->get('remainingAmount'))->toBe(7.50);
});

test('it provides cash suggestions', function () {
    $component = Livewire::actingAs($this->user)
        ->test(PointOfSale::class)
        ->call('addToCart', $this->product->id);

    $suggestions = $component->instance()->getCashSuggestions();

    expect($suggestions)->not->toBeEmpty()
        ->and($suggestions)->toContain(27.50); // Exact amount
});

test('categories computed property returns categories with products', function () {
    $component = Livewire::actingAs($this->user)
        ->test(PointOfSale::class);

    $categories = $component->categories;

    // Should include category with products, not empty one
    $categoryNames = $categories->pluck('name')->toArray();
    expect($categoryNames)->toContain('Electronics')
        ->and($categoryNames)->not->toContain('Empty');
});
