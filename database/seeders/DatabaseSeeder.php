<?php

namespace Database\Seeders;

use App\Models\Category;
use App\Models\Customer;
use App\Models\Payment;
use App\Models\Product;
use App\Models\Transaction;
use App\Models\TransactionItem;
use App\Models\User;
use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;

class DatabaseSeeder extends Seeder
{
    public const array PRODUCT_DATA = [
        [
            'Electronics',
            'Devices and gadgets such as phones, laptops, cameras, and accessories.',
            [
                ['Smartphone', 'A handheld mobile device with advanced computing capabilities.'],
                ['Laptop', 'A portable computer suitable for work, study, and entertainment.'],
                ['Digital Camera', 'A camera that captures images and video in digital format.'],
                ['Wireless Earbuds', 'Compact in-ear headphones with Bluetooth connectivity.'],
                ['Smartwatch', 'A wearable device offering notifications and fitness tracking.'],
                ['Tablet', 'A touchscreen-based portable computer larger than a phone.'],
                ['Bluetooth Speaker', 'A wireless speaker offering portable audio playback.'],
                ['Gaming Console', 'A device designed for playing video games on a TV or monitor.'],
                ['Portable Charger', 'A power bank for recharging devices on the go.'],
                ['Computer Monitor', 'A display screen for desktops, gaming, and media viewing.'],
            ],
        ],
        [
            'Home Appliances',
            'Household machines including refrigerators, washing machines, and vacuum cleaners.',
            [
                ['Refrigerator', 'A cooling appliance used for food storage and preservation.'],
                ['Washing Machine', 'A household machine for automatic clothes washing.'],
                ['Microwave Oven', 'An appliance for heating and cooking food quickly.'],
                ['Air Conditioner', 'A machine that cools interior air for comfort.'],
                ['Vacuum Cleaner', 'A device for suction-based cleaning of floors and upholstery.'],
                ['Dishwasher', 'An appliance for automatic washing and drying of dishes.'],
                ['Water Heater', 'A device that heats water for household use.'],
                ['Air Purifier', 'A machine that filters pollutants from indoor air.'],
                ['Electric Kettle', 'A fast-boiling appliance for hot water preparation.'],
                ['Food Processor', 'A counter-top appliance used for chopping and mixing food.'],
            ],
        ],
        [
            'Furniture',
            'Home and office furniture such as desks, chairs, beds, and storage units.',
            [
                ['Office Desk', 'A work desk suitable for computer and office tasks.'],
                ['Ergonomic Chair', 'A chair designed for comfort and posture support.'],
                ['Sofa', 'A cushioned seating unit for living rooms and lounges.'],
                ['Dining Table', 'A table built for eating and gathering.'],
                ['Bookshelf', 'A vertical shelf system for storing books or decor.'],
                ['Bed Frame', 'A structure supporting a mattress for sleeping.'],
                ['Wardrobe', 'A cabinet used for clothing and storage.'],
                ['Coffee Table', 'A low table for living rooms and shared seating areas.'],
                ['TV Stand', 'A cabinet or frame designed to support a television.'],
                ['Storage Cabinet', 'A multi-purpose closed unit for organized storage.'],
            ],
        ],
        [
            'Fashion',
            'Clothing, footwear, and accessories for men, women, and children.',
            [
                ['T-Shirt', 'A casual short-sleeve upper garment.'],
                ['Jeans', 'Durable denim trousers for everyday wear.'],
                ['Sneakers', 'Comfortable casual shoes for walking and sports.'],
                ['Jacket', 'Outerwear used for warmth and style.'],
                ['Dress', 'A one-piece garment typically worn by women.'],
                ['Handbag', 'A portable bag for accessories and personal items.'],
                ['Sunglasses', 'Protective eyewear shielding eyes from sunlight.'],
                ['Wristwatch', 'A timepiece worn around the wrist.'],
                ['Scarf', 'A fabric accessory worn for fashion or warmth.'],
                ['Belts', 'A strap worn around the waist for fit and style.'],
            ],
        ],
        [
            'Beauty & Health',
            'Personal care, cosmetics, skincare, and health products.',
            [
                ['Moisturizer', 'A skincare product for hydrating the skin.'],
                ['Shampoo', 'Hair cleanser applied before conditioning.'],
                ['Toothbrush', 'An oral care tool for brushing teeth.'],
                ['Vitamin Supplements', 'Tablets providing essential nutrients.'],
                ['Perfume', 'A fragrance product for personal scent.'],
                ['Sunscreen', 'A lotion that protects skin from UV rays.'],
                ['Makeup Kit', 'A set of cosmetics for personal application.'],
                ['Hair Dryer', 'A device for blow-drying hair.'],
                ['Face Cleanser', 'A product used to remove dirt and oils from skin.'],
                ['Lipstick', 'A cosmetic applied to the lips for color and enhancement.'],
            ],
        ],
        [
            'Sports & Outdoors',
            'Equipment, apparel, and gear for sports, fitness, and outdoor activities.',
            [
                ['Tennis Racket', 'A racket used to play tennis.'],
                ['Yoga Mat', 'A cushioned mat for yoga and stretching exercises.'],
                ['Dumbbells', 'Handheld weights for fitness training.'],
                ['Camping Tent', 'A portable shelter for outdoor camping.'],
                ['Hiking Backpack', 'A large bag for carrying gear while hiking.'],
                ['Cycling Helmet', 'Protective headgear for bicycle riders.'],
                ['Football', 'A ball used for playing football or soccer.'],
                ['Running Shoes', 'Athletic shoes designed for running.'],
                ['Sleeping Bag', 'An insulated bag for sleeping outdoors.'],
                ['Fitness Tracker', 'A wearable device that monitors physical activity.'],
            ],
        ],
        [
            'Toys & Games',
            'Board games, learning toys, action figures, and other children’s entertainment products.',
            [
                ['Board Game', 'A tabletop multiplayer game with structured rules.'],
                ['Building Blocks', 'Stackable blocks for creative play.'],
                ['Puzzle Set', 'A collection of pieces forming a complete image.'],
                ['Action Figure', 'A toy figure representing a character.'],
                ['Plush Toy', 'A soft stuffed toy animal or doll.'],
                ['Remote Control Car', 'A miniature car controlled wirelessly.'],
                ['Educational Toy', 'A toy designed to support learning development.'],
                ['Card Game', 'A game based on playing cards.'],
                ['Play Kitchen Set', 'A mini pretend kitchen for imaginative play.'],
                ['Doll House', 'A miniature house setting used with dolls.'],
            ],
        ],
        [
            'Automotive',
            'Car accessories, tools, parts, and vehicle maintenance products.',
            [
                ['Car Floor Mats', 'Protective mats for the car interior.'],
                ['Engine Oil', 'Lubricating oil for engine protection.'],
                ['Air Freshener', 'A product that improves vehicle scent.'],
                ['GPS Navigator', 'A digital navigation system for vehicles.'],
                ['Car Wax', 'A polish used to protect and shine car paint.'],
                ['Seat Covers', 'Protective and decorative covers for car seats.'],
                ['Tire Pressure Gauge', 'A device used to measure tire pressure.'],
                ['Dash Cam', 'A video camera installed to record driving.'],
                ['Jump Starter', 'A power device used to start a dead battery.'],
                ['Car Cleaning Kit', 'A set of products for interior and exterior cleaning.'],
            ],
        ],
        [
            'Books & Media',
            'Books, magazines, music, movies, and educational media.',
            [
                ['Novel', 'A long narrative fiction book.'],
                ['Biography', 'A nonfiction account of a person’s life.'],
                ['Textbook', 'An instructional book for education.'],
                ['Cookbook', 'A collection of recipes and cooking tips.'],
                ['Magazine', 'A regularly published periodical on specific topics.'],
                ['Comic Book', 'A book of illustrated stories typically for entertainment.'],
                ['Music CD', 'A compact disc with audio recordings.'],
                ['DVD Movie', 'A digital disc containing film content.'],
                ['Audiobook', 'A spoken performance of a book.'],
                ['Learning Workbook', 'A structured practice book for education.'],
            ],
        ],
        [
            'Groceries',
            'Food items, beverages, snacks, and other everyday grocery products.',
            [
                ['Milk', 'A dairy beverage widely used in households.'],
                ['Bread', 'A baked staple food usually made from flour.'],
                ['Breakfast Cereal', 'A ready-to-eat grain-based breakfast food.'],
                ['Tea Bags', 'Packaged tea leaves for hot beverage preparation.'],
                ['Coffee Beans', 'Roasted beans ground for brewing coffee.'],
                ['Rice', 'A staple grain used in various dishes worldwide.'],
                ['Pasta', 'A wheat-based product used in traditional meals.'],
                ['Canned Soup', 'A ready-to-eat preserved soup.'],
                ['Snack Chips', 'Crispy packaged snack foods.'],
                ['Bottled Water', 'Packaged drinking water in sealed bottles.'],
            ],
        ],
    ];

    use WithoutModelEvents;

    /**
     * Seed the application's database.
     */
    public function run(): void
    {
        $user = User::factory()->create([
            'name'  => 'Admin',
            'email' => 'admin@example.com',
        ]);

        $customers = Customer::factory(20)->create();

        $products = collect();
        foreach (self::PRODUCT_DATA as $item) {
            [$categoryName, $categoryDesc, $productsInfo] = $item;
            $category = Category::factory()->prependState([
                'name'        => $categoryName,
                'description' => $categoryDesc,
            ])->create();

            foreach ($productsInfo as $productInfo) {
                [$productName, $productDesc] = $productInfo;
                $p = Product::factory()->prependState([
                    'category_id' => $category->id,
                    'name'        => $productName,
                    'description' => $productDesc,
                ])->create();
                $products->push($p);
            }
        }

        $result = $this->command->ask('Do you want to seed transactions and transaction items? (Yes/No)', 'no');
        if (!in_array(strtolower($result), ['yes', 'y', 'ye'])) {
            return;
        }

        Transaction::factory(30)->prependState(fn() => [
            'cashier_id'  => $user->id,
            'customer_id' => $customers->random()->id,
        ])->create()->each(function (Transaction $transaction) use ($products) {
            // Ensure each transaction has at least one item, and that amounts are consistent

            foreach (range(1, 10) as $ignored) {
                /** @var Product $product */
                $product = $products->random();

                TransactionItem::factory()->prependState(fn() => [
                    'transaction_id' => $transaction->id,
                    'product_id'     => $product->id,
                    'unit_price'     => $product->price,
                ])->create();
            }

            $transaction->calculateTotal();

            Payment::factory()->state([
                'transaction_id' => $transaction->id,
                'amount'         => $transaction->total,
//                'status'         => $transaction->status,
            ])->create();
        });
    }
}
