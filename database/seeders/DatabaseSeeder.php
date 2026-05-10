<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;

class DatabaseSeeder extends Seeder
{
    public function run(): void
    {
        // ---------------------------------------------------------------
        // USERS
        // ---------------------------------------------------------------
        DB::table('users')->insert([
            [
                'name'           => 'Admin User',
                'username'       => 'admin',
                'password'       => Hash::make('password'),
                'role'           => 'admin',
                'remember_token' => null,
                'created_at'     => now(),
                'updated_at'     => now(),
            ],
            [
                'name'           => 'Staff One',
                'username'       => 'staff1',
                'password'       => Hash::make('password'),
                'role'           => 'staff',
                'remember_token' => null,
                'created_at'     => now(),
                'updated_at'     => now(),
            ],
            [
                'name'           => 'Staff Two',
                'username'       => 'staff2',
                'password'       => Hash::make('password'),
                'role'           => 'staff',
                'remember_token' => null,
                'created_at'     => now(),
                'updated_at'     => now(),
            ],
        ]);

        // ---------------------------------------------------------------
        // PRODUCTS — all ₱39.00
        // ---------------------------------------------------------------
        // COLD — product_id 1–8
        // HOT  — product_id 9–11
        // ---------------------------------------------------------------
        DB::table('products')->insert([
            // ── COLD ──
            ['product_name' => 'Iced Caramel Macchiato', 'base_price' => 39.00, 'temperature' => 'COLD', 'is_active' => true, 'created_at' => now(), 'updated_at' => now()],
            ['product_name' => 'Don Matchatos',          'base_price' => 39.00, 'temperature' => 'COLD', 'is_active' => true, 'created_at' => now(), 'updated_at' => now()],
            ['product_name' => 'Don Darko',              'base_price' => 39.00, 'temperature' => 'COLD', 'is_active' => true, 'created_at' => now(), 'updated_at' => now()],
            ['product_name' => 'Donya Berry',            'base_price' => 39.00, 'temperature' => 'COLD', 'is_active' => true, 'created_at' => now(), 'updated_at' => now()],
            ['product_name' => 'Matcha Berry',           'base_price' => 39.00, 'temperature' => 'COLD', 'is_active' => true, 'created_at' => now(), 'updated_at' => now()],
            ['product_name' => 'Black Forest',           'base_price' => 39.00, 'temperature' => 'COLD', 'is_active' => true, 'created_at' => now(), 'updated_at' => now()],
            ['product_name' => 'Oreo Coffee',            'base_price' => 39.00, 'temperature' => 'COLD', 'is_active' => true, 'created_at' => now(), 'updated_at' => now()],
            ['product_name' => 'Spanish Latte',          'base_price' => 39.00, 'temperature' => 'COLD', 'is_active' => true, 'created_at' => now(), 'updated_at' => now()],
            // ── HOT ──
            ['product_name' => 'Hot Caramel',            'base_price' => 39.00, 'temperature' => 'HOT',  'is_active' => true, 'created_at' => now(), 'updated_at' => now()],
            ['product_name' => 'Hot Don Darko',          'base_price' => 39.00, 'temperature' => 'HOT',  'is_active' => true, 'created_at' => now(), 'updated_at' => now()],
            ['product_name' => 'Hot Don Barako',         'base_price' => 39.00, 'temperature' => 'HOT',  'is_active' => true, 'created_at' => now(), 'updated_at' => now()],
        ]);

        // ---------------------------------------------------------------
        // INGREDIENTS
        // ---------------------------------------------------------------
        // INVENTORY-ONLY (not linked to any product — manual restock/deduct):
        //   - Coffee Beans (id 1): raw stock, ground into espresso shots in batches
        //   - Ice (id 2):          temperature implies iced, staff restock manually
        //
        // AUTO-DEDUCTED on order paid (linked via product_ingredients):
        //   - Espresso Shot (id 3)
        //   - Whole Milk (id 4)
        //   - Caramel Syrup (id 5)
        //   - Matcha Powder (id 6)
        //   - Dark Chocolate Powder (id 7)
        //   - Strawberry Syrup (id 8)
        //   - Belgian Chocolate Syrup (id 9)
        //   - Oreo Crumble (id 10)
        //   - Condensed Milk (id 11)
        //   - Barako Coffee (id 12)
        // ---------------------------------------------------------------
         DB::table('ingredients')->insert([
            // id 1
            [
                'ingredient_name' => 'Coffee',
                'stock_level'     => 10000,
                'unit'            => 'ml',
                'unit_group'      => 'volume',
                'min_stock'       => 2000,   // warn below 2 L
                'max_stock'       => 20000,  // cap at 20 L
                'created_at'      => now(),
                'updated_at'      => now(),
            ],
            // id 2
            [
                'ingredient_name' => 'Ice',
                'stock_level'     => 50000,
                'unit'            => 'g',
                'unit_group'      => 'weight',
                'min_stock'       => 5000,   // warn below 5 kg
                'max_stock'       => null,   // no upper limit
                'created_at'      => now(),
                'updated_at'      => now(),
            ],
            // id 3
            [
                'ingredient_name' => 'Whole Milk',
                'stock_level'     => 20000,
                'unit'            => 'ml',
                'unit_group'      => 'volume',
                'min_stock'       => 3000,
                'max_stock'       => 30000,
                'created_at'      => now(),
                'updated_at'      => now(),
            ],
            // id 4
            [
                'ingredient_name' => 'Caramel Syrup',
                'stock_level'     => 5000,
                'unit'            => 'ml',
                'unit_group'      => 'volume',
                'min_stock'       => 500,
                'max_stock'       => 10000,
                'created_at'      => now(),
                'updated_at'      => now(),
            ],
            // id 5
            [
                'ingredient_name' => 'Matcha Powder',
                'stock_level'     => 3000,
                'unit'            => 'g',
                'unit_group'      => 'weight',
                'min_stock'       => 300,
                'max_stock'       => 5000,
                'created_at'      => now(),
                'updated_at'      => now(),
            ],
            // id 6
            [
                'ingredient_name' => 'Dark Chocolate Powder',
                'stock_level'     => 3000,
                'unit'            => 'g',
                'unit_group'      => 'weight',
                'min_stock'       => 300,
                'max_stock'       => 5000,
                'created_at'      => now(),
                'updated_at'      => now(),
            ],
            // id 7
            [
                'ingredient_name' => 'Strawberry Syrup',
                'stock_level'     => 5000,
                'unit'            => 'ml',
                'unit_group'      => 'volume',
                'min_stock'       => 500,
                'max_stock'       => 10000,
                'created_at'      => now(),
                'updated_at'      => now(),
            ],
            // id 8
            [
                'ingredient_name' => 'Belgian Choco Syrup',
                'stock_level'     => 5000,
                'unit'            => 'ml',
                'unit_group'      => 'volume',
                'min_stock'       => 500,
                'max_stock'       => 10000,
                'created_at'      => now(),
                'updated_at'      => now(),
            ],
            // id 9
            [
                'ingredient_name' => 'Oreo Crumble',
                'stock_level'     => 5000,
                'unit'            => 'g',
                'unit_group'      => 'weight',
                'min_stock'       => 500,
                'max_stock'       => null,
                'created_at'      => now(),
                'updated_at'      => now(),
            ],
            // id 10
            [
                'ingredient_name' => 'Condensed Milk',
                'stock_level'     => 5000,
                'unit'            => 'ml',
                'unit_group'      => 'volume',
                'min_stock'       => 500,
                'max_stock'       => 10000,
                'created_at'      => now(),
                'updated_at'      => now(),
            ],
            // id 11
            [
                'ingredient_name' => 'Barako Coffee',
                'stock_level'     => 3000,
                'unit'            => 'g',
                'unit_group'      => 'weight',
                'min_stock'       => 300,
                'max_stock'       => 5000,
                'created_at'      => now(),
                'updated_at'      => now(),
            ],
        ]);
 

        // ---------------------------------------------------------------
        // PRODUCT INGREDIENTS
        // ---------------------------------------------------------------
        // Coffee Beans (1) and Ice (2) intentionally excluded — inventory only.
        // "with or without coffee" is handled by the with_coffee flag on
        // order_details. Espresso Shot still deducts when with_coffee = true;
        // the controller should respect this flag when deducting stock.
        // ---------------------------------------------------------------
        DB::table('product_ingredients')->insert([
    // 1. Iced Caramel Macchiato — coffee + milk + caramel + ice
            ['product_id' => 1, 'ingredient_id' => 1, 'quantity_used' => 60],
            ['product_id' => 1, 'ingredient_id' => 2, 'quantity_used' => 150],
            ['product_id' => 1, 'ingredient_id' => 3, 'quantity_used' => 150],
            ['product_id' => 1, 'ingredient_id' => 4, 'quantity_used' => 30],

            // 2. Don Matchatos — matcha + milk + ice (no coffee)
            ['product_id' => 2, 'ingredient_id' => 2, 'quantity_used' => 150],
            ['product_id' => 2, 'ingredient_id' => 3, 'quantity_used' => 150],
            ['product_id' => 2, 'ingredient_id' => 5, 'quantity_used' => 15],

            // 3. Don Darko (cold) — dark choco + milk + ice (no coffee)
            ['product_id' => 3, 'ingredient_id' => 2, 'quantity_used' => 150],
            ['product_id' => 3, 'ingredient_id' => 3, 'quantity_used' => 150],
            ['product_id' => 3, 'ingredient_id' => 6, 'quantity_used' => 20],

            // 4. Donya Berry — coffee + milk + strawberry + ice
            ['product_id' => 4, 'ingredient_id' => 1, 'quantity_used' => 60],
            ['product_id' => 4, 'ingredient_id' => 2, 'quantity_used' => 150],
            ['product_id' => 4, 'ingredient_id' => 3, 'quantity_used' => 120],
            ['product_id' => 4, 'ingredient_id' => 7, 'quantity_used' => 30],

            // 5. Matcha Berry — matcha + milk + strawberry + ice (no coffee)
            ['product_id' => 5, 'ingredient_id' => 2, 'quantity_used' => 150],
            ['product_id' => 5, 'ingredient_id' => 3, 'quantity_used' => 120],
            ['product_id' => 5, 'ingredient_id' => 5, 'quantity_used' => 15],
            ['product_id' => 5, 'ingredient_id' => 7, 'quantity_used' => 30],

            // 6. Black Forest — belgian choco + strawberry + milk + ice (no coffee)
            ['product_id' => 6, 'ingredient_id' => 2, 'quantity_used' => 150],
            ['product_id' => 6, 'ingredient_id' => 3, 'quantity_used' => 120],
            ['product_id' => 6, 'ingredient_id' => 7, 'quantity_used' => 20],
            ['product_id' => 6, 'ingredient_id' => 8, 'quantity_used' => 30],

            // 7. Oreo Coffee — coffee + milk + oreo + ice
            ['product_id' => 7, 'ingredient_id' => 1, 'quantity_used' => 60],
            ['product_id' => 7, 'ingredient_id' => 2, 'quantity_used' => 150],
            ['product_id' => 7, 'ingredient_id' => 3, 'quantity_used' => 150],
            ['product_id' => 7, 'ingredient_id' => 9, 'quantity_used' => 25],

            // 8. Spanish Latte — coffee + milk + condensed milk + ice
            ['product_id' => 8, 'ingredient_id' => 1,  'quantity_used' => 60],
            ['product_id' => 8, 'ingredient_id' => 2,  'quantity_used' => 150],
            ['product_id' => 8, 'ingredient_id' => 3,  'quantity_used' => 120],
            ['product_id' => 8, 'ingredient_id' => 10, 'quantity_used' => 30],

            // 9. Hot Caramel — coffee + milk + caramel (HOT, no ice)
            ['product_id' => 9, 'ingredient_id' => 1, 'quantity_used' => 60],
            ['product_id' => 9, 'ingredient_id' => 3, 'quantity_used' => 150],
            ['product_id' => 9, 'ingredient_id' => 4, 'quantity_used' => 30],

            // 10. Hot Don Darko — dark choco + milk (HOT, no coffee, no ice)
            ['product_id' => 10, 'ingredient_id' => 3, 'quantity_used' => 150],
            ['product_id' => 10, 'ingredient_id' => 6, 'quantity_used' => 20],

            // 11. Hot Don Barako — barako coffee + milk (HOT, no ice)
            ['product_id' => 11, 'ingredient_id' => 3,  'quantity_used' => 150],
            ['product_id' => 11, 'ingredient_id' => 11, 'quantity_used' => 60],
        ]);

        // ---------------------------------------------------------------
        // ORDERS — 14 orders across last 7 days
        // All prices ₱39.00 per item
        // ---------------------------------------------------------------
        $orders = [
            ['user_id' => 2, 'days' => 7, 'time' => '08:15', 'amount' => 156.00, 'status' => 'paid'],    // 4 drinks
            ['user_id' => 3, 'days' => 7, 'time' => '10:45', 'amount' => 195.00, 'status' => 'paid'],    // 5 drinks
            ['user_id' => 2, 'days' => 6, 'time' => '09:00', 'amount' => 117.00, 'status' => 'paid'],    // 3 drinks
            ['user_id' => 2, 'days' => 6, 'time' => '14:30', 'amount' => 195.00, 'status' => 'paid'],    // 5 drinks
            ['user_id' => 3, 'days' => 5, 'time' => '11:20', 'amount' => 78.00,  'status' => 'paid'],    // 2 drinks
            ['user_id' => 2, 'days' => 4, 'time' => '08:50', 'amount' => 234.00, 'status' => 'paid'],    // 6 drinks
            ['user_id' => 3, 'days' => 4, 'time' => '15:10', 'amount' => 156.00, 'status' => 'paid'],    // 4 drinks
            ['user_id' => 2, 'days' => 3, 'time' => '09:05', 'amount' => 195.00, 'status' => 'paid'],    // 5 drinks
            ['user_id' => 2, 'days' => 2, 'time' => '08:30', 'amount' => 117.00, 'status' => 'paid'],    // 3 drinks
            ['user_id' => 3, 'days' => 2, 'time' => '13:00', 'amount' => 78.00,  'status' => 'paid'],    // 2 drinks
            ['user_id' => 2, 'days' => 1, 'time' => '09:20', 'amount' => 156.00, 'status' => 'paid'],    // 4 drinks
            ['user_id' => 3, 'days' => 1, 'time' => '16:45', 'amount' => 195.00, 'status' => 'paid'],    // 5 drinks
            ['user_id' => 2, 'days' => 0, 'time' => '08:00', 'amount' => 117.00, 'status' => 'paid'],    // 3 drinks
            ['user_id' => 3, 'days' => 0, 'time' => '10:30', 'amount' => 78.00,  'status' => 'pending'], // 2 drinks
        ];

        foreach ($orders as $data) {
            $date = now()->subDays($data['days'])->setTimeFromTimeString($data['time']);
            DB::table('orders')->insert([
                'user_id'      => $data['user_id'],
                'order_date'   => $date,
                'total_amount' => $data['amount'],
                'status'       => $data['status'],
                'created_at'   => $date,
                'updated_at'   => $date,
            ]);
        }

        // ---------------------------------------------------------------
        // ORDER DETAILS — all subtotals at ₱39.00 per unit
        // ---------------------------------------------------------------
        DB::table('order_details')->insert([
            // Order 1 — ₱156 = 4x drinks
            ['order_id' => 1,  'product_id' => 1,  'quantity' => 2, 'subtotal' => 78.00,  'with_coffee' => true,  'created_at' => now()->subDays(7), 'updated_at' => now()->subDays(7)],
            ['order_id' => 1,  'product_id' => 4,  'quantity' => 1, 'subtotal' => 39.00,  'with_coffee' => true,  'created_at' => now()->subDays(7), 'updated_at' => now()->subDays(7)],
            ['order_id' => 1,  'product_id' => 9,  'quantity' => 1, 'subtotal' => 39.00,  'with_coffee' => true,  'created_at' => now()->subDays(7), 'updated_at' => now()->subDays(7)],

            // Order 2 — ₱195 = 5x drinks
            ['order_id' => 2,  'product_id' => 2,  'quantity' => 1, 'subtotal' => 39.00,  'with_coffee' => false, 'created_at' => now()->subDays(7), 'updated_at' => now()->subDays(7)],
            ['order_id' => 2,  'product_id' => 5,  'quantity' => 1, 'subtotal' => 39.00,  'with_coffee' => false, 'created_at' => now()->subDays(7), 'updated_at' => now()->subDays(7)],
            ['order_id' => 2,  'product_id' => 6,  'quantity' => 1, 'subtotal' => 39.00,  'with_coffee' => false, 'created_at' => now()->subDays(7), 'updated_at' => now()->subDays(7)],
            ['order_id' => 2,  'product_id' => 8,  'quantity' => 1, 'subtotal' => 39.00,  'with_coffee' => true,  'created_at' => now()->subDays(7), 'updated_at' => now()->subDays(7)],
            ['order_id' => 2,  'product_id' => 11, 'quantity' => 1, 'subtotal' => 39.00,  'with_coffee' => true,  'created_at' => now()->subDays(7), 'updated_at' => now()->subDays(7)],

            // Order 3 — ₱117 = 3x drinks
            ['order_id' => 3,  'product_id' => 3,  'quantity' => 1, 'subtotal' => 39.00,  'with_coffee' => false, 'created_at' => now()->subDays(6), 'updated_at' => now()->subDays(6)],
            ['order_id' => 3,  'product_id' => 7,  'quantity' => 1, 'subtotal' => 39.00,  'with_coffee' => true,  'created_at' => now()->subDays(6), 'updated_at' => now()->subDays(6)],
            ['order_id' => 3,  'product_id' => 10, 'quantity' => 1, 'subtotal' => 39.00,  'with_coffee' => false, 'created_at' => now()->subDays(6), 'updated_at' => now()->subDays(6)],

            // Order 4 — ₱195 = 5x drinks
            ['order_id' => 4,  'product_id' => 1,  'quantity' => 2, 'subtotal' => 78.00,  'with_coffee' => true,  'created_at' => now()->subDays(6), 'updated_at' => now()->subDays(6)],
            ['order_id' => 4,  'product_id' => 4,  'quantity' => 1, 'subtotal' => 39.00,  'with_coffee' => true,  'created_at' => now()->subDays(6), 'updated_at' => now()->subDays(6)],
            ['order_id' => 4,  'product_id' => 8,  'quantity' => 1, 'subtotal' => 39.00,  'with_coffee' => true,  'created_at' => now()->subDays(6), 'updated_at' => now()->subDays(6)],
            ['order_id' => 4,  'product_id' => 9,  'quantity' => 1, 'subtotal' => 39.00,  'with_coffee' => true,  'created_at' => now()->subDays(6), 'updated_at' => now()->subDays(6)],

            // Order 5 — ₱78 = 2x drinks
            ['order_id' => 5,  'product_id' => 2,  'quantity' => 1, 'subtotal' => 39.00,  'with_coffee' => false, 'created_at' => now()->subDays(5), 'updated_at' => now()->subDays(5)],
            ['order_id' => 5,  'product_id' => 5,  'quantity' => 1, 'subtotal' => 39.00,  'with_coffee' => false, 'created_at' => now()->subDays(5), 'updated_at' => now()->subDays(5)],

            // Order 6 — ₱234 = 6x drinks
            ['order_id' => 6,  'product_id' => 1,  'quantity' => 2, 'subtotal' => 78.00,  'with_coffee' => true,  'created_at' => now()->subDays(4), 'updated_at' => now()->subDays(4)],
            ['order_id' => 6,  'product_id' => 3,  'quantity' => 1, 'subtotal' => 39.00,  'with_coffee' => false, 'created_at' => now()->subDays(4), 'updated_at' => now()->subDays(4)],
            ['order_id' => 6,  'product_id' => 6,  'quantity' => 1, 'subtotal' => 39.00,  'with_coffee' => false, 'created_at' => now()->subDays(4), 'updated_at' => now()->subDays(4)],
            ['order_id' => 6,  'product_id' => 7,  'quantity' => 1, 'subtotal' => 39.00,  'with_coffee' => true,  'created_at' => now()->subDays(4), 'updated_at' => now()->subDays(4)],
            ['order_id' => 6,  'product_id' => 11, 'quantity' => 1, 'subtotal' => 39.00,  'with_coffee' => true,  'created_at' => now()->subDays(4), 'updated_at' => now()->subDays(4)],

            // Order 7 — ₱156 = 4x drinks
            ['order_id' => 7,  'product_id' => 4,  'quantity' => 1, 'subtotal' => 39.00,  'with_coffee' => true,  'created_at' => now()->subDays(4), 'updated_at' => now()->subDays(4)],
            ['order_id' => 7,  'product_id' => 8,  'quantity' => 1, 'subtotal' => 39.00,  'with_coffee' => true,  'created_at' => now()->subDays(4), 'updated_at' => now()->subDays(4)],
            ['order_id' => 7,  'product_id' => 9,  'quantity' => 1, 'subtotal' => 39.00,  'with_coffee' => true,  'created_at' => now()->subDays(4), 'updated_at' => now()->subDays(4)],
            ['order_id' => 7,  'product_id' => 10, 'quantity' => 1, 'subtotal' => 39.00,  'with_coffee' => false, 'created_at' => now()->subDays(4), 'updated_at' => now()->subDays(4)],

            // Order 8 — ₱195 = 5x drinks
            ['order_id' => 8,  'product_id' => 1,  'quantity' => 1, 'subtotal' => 39.00,  'with_coffee' => true,  'created_at' => now()->subDays(3), 'updated_at' => now()->subDays(3)],
            ['order_id' => 8,  'product_id' => 2,  'quantity' => 1, 'subtotal' => 39.00,  'with_coffee' => false, 'created_at' => now()->subDays(3), 'updated_at' => now()->subDays(3)],
            ['order_id' => 8,  'product_id' => 5,  'quantity' => 1, 'subtotal' => 39.00,  'with_coffee' => false, 'created_at' => now()->subDays(3), 'updated_at' => now()->subDays(3)],
            ['order_id' => 8,  'product_id' => 6,  'quantity' => 1, 'subtotal' => 39.00,  'with_coffee' => false, 'created_at' => now()->subDays(3), 'updated_at' => now()->subDays(3)],
            ['order_id' => 8,  'product_id' => 7,  'quantity' => 1, 'subtotal' => 39.00,  'with_coffee' => true,  'created_at' => now()->subDays(3), 'updated_at' => now()->subDays(3)],

            // Order 9 — ₱117 = 3x drinks
            ['order_id' => 9,  'product_id' => 3,  'quantity' => 1, 'subtotal' => 39.00,  'with_coffee' => false, 'created_at' => now()->subDays(2), 'updated_at' => now()->subDays(2)],
            ['order_id' => 9,  'product_id' => 8,  'quantity' => 1, 'subtotal' => 39.00,  'with_coffee' => true,  'created_at' => now()->subDays(2), 'updated_at' => now()->subDays(2)],
            ['order_id' => 9,  'product_id' => 11, 'quantity' => 1, 'subtotal' => 39.00,  'with_coffee' => true,  'created_at' => now()->subDays(2), 'updated_at' => now()->subDays(2)],

            // Order 10 — ₱78 = 2x drinks
            ['order_id' => 10, 'product_id' => 4,  'quantity' => 1, 'subtotal' => 39.00,  'with_coffee' => true,  'created_at' => now()->subDays(2), 'updated_at' => now()->subDays(2)],
            ['order_id' => 10, 'product_id' => 9,  'quantity' => 1, 'subtotal' => 39.00,  'with_coffee' => true,  'created_at' => now()->subDays(2), 'updated_at' => now()->subDays(2)],

            // Order 11 — ₱156 = 4x drinks
            ['order_id' => 11, 'product_id' => 1,  'quantity' => 1, 'subtotal' => 39.00,  'with_coffee' => true,  'created_at' => now()->subDays(1), 'updated_at' => now()->subDays(1)],
            ['order_id' => 11, 'product_id' => 5,  'quantity' => 1, 'subtotal' => 39.00,  'with_coffee' => false, 'created_at' => now()->subDays(1), 'updated_at' => now()->subDays(1)],
            ['order_id' => 11, 'product_id' => 7,  'quantity' => 1, 'subtotal' => 39.00,  'with_coffee' => true,  'created_at' => now()->subDays(1), 'updated_at' => now()->subDays(1)],
            ['order_id' => 11, 'product_id' => 10, 'quantity' => 1, 'subtotal' => 39.00,  'with_coffee' => false, 'created_at' => now()->subDays(1), 'updated_at' => now()->subDays(1)],

            // Order 12 — ₱195 = 5x drinks
            ['order_id' => 12, 'product_id' => 2,  'quantity' => 1, 'subtotal' => 39.00,  'with_coffee' => false, 'created_at' => now()->subDays(1), 'updated_at' => now()->subDays(1)],
            ['order_id' => 12, 'product_id' => 3,  'quantity' => 1, 'subtotal' => 39.00,  'with_coffee' => false, 'created_at' => now()->subDays(1), 'updated_at' => now()->subDays(1)],
            ['order_id' => 12, 'product_id' => 6,  'quantity' => 1, 'subtotal' => 39.00,  'with_coffee' => false, 'created_at' => now()->subDays(1), 'updated_at' => now()->subDays(1)],
            ['order_id' => 12, 'product_id' => 8,  'quantity' => 1, 'subtotal' => 39.00,  'with_coffee' => true,  'created_at' => now()->subDays(1), 'updated_at' => now()->subDays(1)],
            ['order_id' => 12, 'product_id' => 11, 'quantity' => 1, 'subtotal' => 39.00,  'with_coffee' => true,  'created_at' => now()->subDays(1), 'updated_at' => now()->subDays(1)],

            // Order 13 — ₱117 = 3x drinks
            ['order_id' => 13, 'product_id' => 1,  'quantity' => 1, 'subtotal' => 39.00,  'with_coffee' => true,  'created_at' => now(), 'updated_at' => now()],
            ['order_id' => 13, 'product_id' => 4,  'quantity' => 1, 'subtotal' => 39.00,  'with_coffee' => true,  'created_at' => now(), 'updated_at' => now()],
            ['order_id' => 13, 'product_id' => 9,  'quantity' => 1, 'subtotal' => 39.00,  'with_coffee' => true,  'created_at' => now(), 'updated_at' => now()],

            // Order 14 — ₱78 = 2x drinks — PENDING
            ['order_id' => 14, 'product_id' => 2,  'quantity' => 1, 'subtotal' => 39.00,  'with_coffee' => false, 'created_at' => now(), 'updated_at' => now()],
            ['order_id' => 14, 'product_id' => 5,  'quantity' => 1, 'subtotal' => 39.00,  'with_coffee' => false, 'created_at' => now(), 'updated_at' => now()],
        ]);

        // ---------------------------------------------------------------
        // PAYMENTS
        // ---------------------------------------------------------------
        $payments = [
            ['order_id' => 1,  'method' => 'cash',  'amount' => 156.00, 'status' => 'paid',    'days' => 7],
            ['order_id' => 2,  'method' => 'gcash', 'amount' => 195.00, 'status' => 'paid',    'days' => 7],
            ['order_id' => 3,  'method' => 'cash',  'amount' => 117.00, 'status' => 'paid',    'days' => 6],
            ['order_id' => 4,  'method' => 'maya',  'amount' => 195.00, 'status' => 'paid',    'days' => 6],
            ['order_id' => 5,  'method' => 'cash',  'amount' => 78.00,  'status' => 'paid',    'days' => 5],
            ['order_id' => 6,  'method' => 'gcash', 'amount' => 234.00, 'status' => 'paid',    'days' => 4],
            ['order_id' => 7,  'method' => 'maya',  'amount' => 156.00, 'status' => 'paid',    'days' => 4],
            ['order_id' => 8,  'method' => 'cash',  'amount' => 195.00, 'status' => 'paid',    'days' => 3],
            ['order_id' => 9,  'method' => 'gcash', 'amount' => 117.00, 'status' => 'paid',    'days' => 2],
            ['order_id' => 10, 'method' => 'cash',  'amount' => 78.00,  'status' => 'paid',    'days' => 2],
            ['order_id' => 11, 'method' => 'maya',  'amount' => 156.00, 'status' => 'paid',    'days' => 1],
            ['order_id' => 12, 'method' => 'cash',  'amount' => 195.00, 'status' => 'paid',    'days' => 1],
            ['order_id' => 13, 'method' => 'gcash', 'amount' => 117.00, 'status' => 'paid',    'days' => 0],
            ['order_id' => 14, 'method' => 'cash',  'amount' => 0.00,   'status' => 'pending', 'days' => 0],
        ];

        foreach ($payments as $pay) {
            $payDate = now()->subDays($pay['days']);
            DB::table('payments')->insert([
                'order_id'       => $pay['order_id'],
                'receipt_number' => $pay['status'] === 'paid'
                    ? 'RCP-' . $payDate->format('Ymd') . '-' . str_pad($pay['order_id'], 4, '0', STR_PAD_LEFT)
                    : null,
                'payment_method' => $pay['method'],
                'amount_paid'    => $pay['amount'],
                'payment_date'   => $pay['status'] === 'paid' ? $payDate : null,
                'payment_status' => $pay['status'],
                'created_at'     => $payDate,
                'updated_at'     => $payDate,
            ]);
        }
    }
}