<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class AppController extends Controller
{
    // ─────────────────────────────────────────
    //  AUTH — SHOW LOGIN FORM
    // ─────────────────────────────────────────
    public function showLogin()
    {
        if (session()->has('user_id')) {
            return redirect('/');
        }
        return view('login');
    }

    // ─────────────────────────────────────────
    //  AUTH — HANDLE LOGIN SUBMIT
    // ─────────────────────────────────────────
    public function login(Request $request)
    {
        $request->validate([
            'username' => 'required|string',
            'password' => 'required|string',
        ]);

        $user = DB::table('users')
            ->where('username', $request->username)
            ->first();

        if (!$user) {
            return back()
                ->withInput(['username' => $request->username])
                ->with('error', 'No account found with that username.');
        }

        $isHashed      = str_starts_with($user->password, '$2y$');
        $passwordValid = $isHashed
            ? password_verify($request->password, $user->password)
            : $request->password === $user->password;

        if (!$passwordValid) {
            return back()
                ->withInput(['username' => $request->username])
                ->with('error', 'Incorrect password. Please try again.');
        }

        if (!$isHashed) {
            DB::table('users')
                ->where('id', $user->id)
                ->update(['password' => password_hash($request->password, PASSWORD_BCRYPT)]);
        }

        $request->session()->regenerate();
        session([
            'user_id'   => $user->id,
            'user_name' => $user->name,
            'user_role' => $user->role,
        ]);

        return redirect('/');
    }

    // ─────────────────────────────────────────
    //  AUTH — LOGOUT
    // ─────────────────────────────────────────
    public function logout(Request $request)
    {
        $request->session()->flush();
        $request->session()->regenerate();

        return redirect('/login')->withHeaders([
            'Cache-Control' => 'no-store, no-cache, must-revalidate',
            'Pragma'        => 'no-cache',
        ]);
    }

    // ─────────────────────────────────────────
    //  DASHBOARD
    // ─────────────────────────────────────────
    public function dashboard()
    {
        $hour     = now()->hour;
        $greeting = $hour < 12 ? 'morning' : ($hour < 18 ? 'afternoon' : 'evening');

        // ── ADMIN: full shop overview ──────────
        if (session('user_role') === 'admin') {

            $totalRevenue       = DB::table('orders')->where('status', 'paid')->sum('total_amount');
            $paidOrdersCount    = DB::table('orders')->where('status', 'paid')->count();
            $pendingOrdersCount = DB::table('orders')->where('status', 'pending')->count();
            $productsCount      = DB::table('products')->count();
            $coldProductsCount  = DB::table('products')->where('temperature', 'COLD')->count();
            $hotProductsCount   = DB::table('products')->where('temperature', 'HOT')->count();
            $staffCount         = DB::table('users')->where('role', 'staff')->count();

            $recentOrders = DB::select('
                SELECT o.order_id, p.product_name, u.name AS staff_name,
                       o.total_amount, o.status
                FROM orders o
                JOIN users u ON o.user_id = u.id
                JOIN order_details od ON od.order_id = o.order_id
                JOIN products p ON od.product_id = p.product_id
                ORDER BY o.order_id DESC
                LIMIT 5
            ');

            $topProducts = DB::select('
                SELECT p.product_name, p.temperature,
                       COALESCE(SUM(od.quantity), 0) AS total_sold,
                       COALESCE(SUM(od.subtotal),  0) AS total_revenue
                FROM products p
                LEFT JOIN order_details od ON od.product_id = p.product_id
                GROUP BY p.product_id, p.product_name, p.temperature
                ORDER BY total_sold DESC
                LIMIT 5
            ');

            $ingredients = DB::table('ingredients')
                ->select('ingredient_name', 'stock_level', 'unit')
                ->orderBy('ingredient_id')
                ->get();

            $staffActivity = DB::select("
                SELECT u.name,
                       COUNT(o.order_id) AS order_count,
                       COALESCE(SUM(CASE WHEN o.status = 'paid' THEN o.total_amount ELSE 0 END), 0) AS total_revenue,
                       SUM(CASE WHEN o.status = 'pending' THEN 1 ELSE 0 END) AS pending_orders
                FROM users u
                LEFT JOIN orders o ON o.user_id = u.id
                WHERE u.role = 'staff'
                GROUP BY u.id, u.name
                ORDER BY total_revenue DESC
            ");

            return view('dashboard-admin', compact(
                'greeting', 'totalRevenue', 'paidOrdersCount', 'pendingOrdersCount',
                'productsCount', 'coldProductsCount', 'hotProductsCount', 'staffCount',
                'recentOrders', 'topProducts', 'ingredients', 'staffActivity'
            ));
        }

        // ── STAFF: personal shift view ─────────
        $userId = session('user_id');

        $myOrdersToday = DB::table('orders')
            ->where('user_id', $userId)
            ->whereDate('order_date', today())
            ->count();

        $myRevenueToday = DB::table('orders')
            ->where('user_id', $userId)
            ->where('status', 'paid')
            ->whereDate('order_date', today())
            ->sum('total_amount');

        $myPendingOrders = DB::table('orders')
            ->where('user_id', $userId)
            ->where('status', 'pending')
            ->count();

        $lowStockAlerts = DB::table('ingredients')
            ->where('stock_level', '<=', 50)
            ->select('ingredient_name', 'stock_level', 'unit')
            ->orderBy('stock_level')
            ->get();

        $myRecentOrders = DB::select("
            SELECT o.order_id, p.product_name, o.total_amount, o.status, o.order_date
            FROM orders o
            JOIN order_details od ON od.order_id = o.order_id
            JOIN products p ON od.product_id = p.product_id
            WHERE o.user_id = ?
            ORDER BY o.order_id DESC
            LIMIT 8
        ", [$userId]);

        return view('dashboard-staff', compact(
            'greeting', 'myOrdersToday', 'myRevenueToday',
            'myPendingOrders', 'lowStockAlerts', 'myRecentOrders'
        ));
    }

    // ─────────────────────────────────────────
    //  ORDERS — LIST
    // ─────────────────────────────────────────
    public function ordersList()
    {
        $userId  = session('user_id');
        $isAdmin = session('user_role') === 'admin';

        $allOrders = DB::select("
            SELECT o.order_id, u.name AS staff_name, o.total_amount, o.status,
                   o.order_date,
                   GROUP_CONCAT(p.product_name ORDER BY p.product_name SEPARATOR ', ') AS products,
                   pay.payment_method, pay.payment_status, pay.receipt_number
            FROM orders o
            JOIN users u ON o.user_id = u.id
            JOIN order_details od ON od.order_id = o.order_id
            JOIN products p ON od.product_id = p.product_id
            LEFT JOIN payments pay ON pay.order_id = o.order_id
            " . ($isAdmin ? '' : 'WHERE o.user_id = ' . (int)$userId) . "
            GROUP BY o.order_id, u.name, o.total_amount, o.status, o.order_date,
                     pay.payment_method, pay.payment_status, pay.receipt_number
            ORDER BY o.order_id DESC
        ");

        return view('orderlist', compact('allOrders'));
    }

    // ─────────────────────────────────────────
    //  ORDERS — RECEIPT
    // ─────────────────────────────────────────
    public function orderReceipt($id)
    {
        $order = DB::selectOne("
            SELECT o.order_id, o.order_date, o.total_amount, o.status,
                   u.name AS staff_name,
                   pay.payment_method, pay.payment_status, pay.amount_paid,
                   pay.payment_date, pay.receipt_number
            FROM orders o
            JOIN users u ON o.user_id = u.id
            LEFT JOIN payments pay ON pay.order_id = o.order_id
            WHERE o.order_id = ?
        ", [$id]);

        if (!$order) {
            return redirect('/orders')->with('error', 'Order not found.');
        }

        $items = DB::select("
            SELECT p.product_name, p.temperature, p.base_price,
                   od.quantity, od.subtotal, od.with_coffee
            FROM order_details od
            JOIN products p ON p.product_id = od.product_id
            WHERE od.order_id = ?
            ORDER BY p.product_name
        ", [$id]);

        return view('receipt', compact('order', 'items'));
    }

    // ─────────────────────────────────────────
    //  ORDERS — CREATE FORM  (staff only)
    // ─────────────────────────────────────────
    public function ordersCreate()
    {
        $products = DB::table('products')
            ->select('product_id', 'product_name', 'base_price', 'temperature')
            ->orderBy('product_name')
            ->get();

        return view('orders', compact('products'));
    }

    // ─────────────────────────────────────────
    //  ORDERS — STORE  (staff only)
    // ─────────────────────────────────────────
    public function ordersStore(Request $request)
    {
        $request->validate([
            'payment_method' => 'required|in:cash,gcash,maya',
            'items'          => 'required|array|min:1',
        ]);

        $total = collect($request->items)->sum('subtotal');

        $orderId = DB::table('orders')->insertGetId([
            'user_id'      => session('user_id'),
            'total_amount' => $total,
            'status'       => 'pending',
            'order_date'   => now(),
            'created_at'   => now(),
            'updated_at'   => now(),
        ]);

        foreach ($request->items as $item) {
            DB::table('order_details')->insert([
                'order_id'    => $orderId,
                'product_id'  => $item['product_id'],
                'quantity'    => $item['quantity'],
                'subtotal'    => $item['subtotal'],
                'with_coffee' => $item['with_coffee'] ?? 0,
                'created_at'  => now(),
                'updated_at'  => now(),
            ]);
        }

        // Store payment method in session for when staff marks it paid
        session(["order_{$orderId}_payment_method" => $request->payment_method]);

        return redirect('/orders')->with('success',
            'Order #' . str_pad($orderId, 3, '0', STR_PAD_LEFT) . ' placed — awaiting payment.');
    }

    // ─────────────────────────────────────────
    //  ORDERS — EDIT FORM  (staff only)
    // ─────────────────────────────────────────
    public function ordersEdit($id)
    {
        $order = DB::selectOne('
            SELECT o.order_id, u.name AS staff_name, o.status
            FROM orders o
            JOIN users u ON o.user_id = u.id
            WHERE o.order_id = ?
        ', [$id]);

        if (!$order) {
            return redirect('/orders')->with('error', 'Order not found.');
        }
        if ($order->status !== 'pending') {
            return redirect('/orders')->with('error', 'Only pending orders can be edited.');
        }

        $currentOrder = DB::table('orders')->where('order_id', $id)->first();
        if (session('user_role') === 'staff' && $currentOrder->user_id !== session('user_id')) {
            return redirect('/orders')->with('error', 'You can only edit your own orders.');
        }

        $orderDetails = DB::select('
            SELECT od.order_detail_id, od.product_id, od.quantity, od.subtotal, od.with_coffee,
                   p.product_name, p.base_price, p.temperature
            FROM order_details od
            JOIN products p ON p.product_id = od.product_id
            WHERE od.order_id = ?
        ', [$id]);

        $products = DB::table('products')
            ->select('product_id', 'product_name', 'base_price', 'temperature')
            ->orderBy('product_name')
            ->get();

        return view('order-edit', compact('order', 'orderDetails', 'products'));
    }

    // ─────────────────────────────────────────
    //  ORDERS — UPDATE  (staff only)
    // ─────────────────────────────────────────
    public function ordersUpdate(Request $request, $id)
    {
        $request->validate([
            'items' => 'required|array|min:1',
        ]);

        $order = DB::table('orders')->where('order_id', $id)->first();

        if (!$order || $order->status !== 'pending') {
            return redirect('/orders')->with('error', 'This order can no longer be edited.');
        }

        if (session('user_role') === 'staff' && $order->user_id !== session('user_id')) {
            return redirect('/orders')->with('error', 'You can only edit your own orders.');
        }

        DB::table('order_details')->where('order_id', $id)->delete();

        $total = 0;
        foreach ($request->items as $item) {
            DB::table('order_details')->insert([
                'order_id'    => $id,
                'product_id'  => $item['product_id'],
                'quantity'    => $item['quantity'],
                'subtotal'    => $item['subtotal'],
                'with_coffee' => $item['with_coffee'] ?? 0,
                'created_at'  => now(),
                'updated_at'  => now(),
            ]);
            $total += $item['subtotal'];
        }

        DB::table('orders')
            ->where('order_id', $id)
            ->update(['total_amount' => $total, 'updated_at' => now()]);

        return redirect('/orders')->with('success',
            'Order #' . str_pad($id, 3, '0', STR_PAD_LEFT) . ' updated successfully.');
    }

    // ─────────────────────────────────────────
    //  ORDERS — UPDATE STATUS  (staff only)
    // ─────────────────────────────────────────
    public function ordersUpdateStatus(Request $request, $id)
    {
        $request->validate([
            'status'         => 'required|in:paid,cancelled',
            'payment_method' => 'nullable|in:cash,gcash,maya',
        ]);

        $order = DB::table('orders')->where('order_id', $id)->first();

        if (!$order) {
            return redirect('/orders')->with('error', 'Order not found.');
        }
        if ($order->status !== 'pending') {
            return redirect('/orders')->with('error', 'This order has already been processed.');
        }

        if (session('user_role') === 'staff' && $order->user_id !== session('user_id')) {
            return redirect('/orders')->with('error', 'You can only update your own orders.');
        }

        DB::beginTransaction();

        try {
            DB::table('orders')
                ->where('order_id', $id)
                ->update(['status' => $request->status, 'updated_at' => now()]);

            if ($request->status === 'paid') {

                $payMethod = $request->payment_method
                    ?? session("order_{$id}_payment_method", 'cash');

                // Generate receipt number: RCP-YYYYMMDD-####
                $receiptNumber = 'RCP-' . now()->format('Ymd') . '-' . str_pad($id, 4, '0', STR_PAD_LEFT);

                DB::table('payments')->insert([
                    'order_id'       => $id,
                    'payment_method' => $payMethod,
                    'amount_paid'    => $order->total_amount,
                    'payment_date'   => now(),
                    'payment_status' => 'paid',
                    'receipt_number' => $receiptNumber,
                    'created_at'     => now(),
                    'updated_at'     => now(),
                ]);

                // Deduct ingredients from stock
                $items = DB::table('order_details')
                    ->select('product_id', 'quantity')
                    ->where('order_id', $id)
                    ->get();

                foreach ($items as $item) {
                    $usedIngredients = DB::table('product_ingredients')
                        ->select('ingredient_id', 'quantity_used')
                        ->where('product_id', $item->product_id)
                        ->get();

                    foreach ($usedIngredients as $ing) {
                        $totalUsed = $ing->quantity_used * $item->quantity;

                        DB::statement('
                            UPDATE ingredients
                            SET stock_level = GREATEST(stock_level - ?, 0),
                                updated_at  = NOW()
                            WHERE ingredient_id = ?
                        ', [$totalUsed, $ing->ingredient_id]);

                        DB::table('stock_transactions')->insert([
                            'ingredient_id'    => $ing->ingredient_id,
                            'user_id'          => session('user_id'),
                            'transaction_type' => 'OUT',
                            'quantity'         => $totalUsed,
                            'transaction_date' => now(),
                            'created_at'       => now(),
                            'updated_at'       => now(),
                        ]);
                    }
                }
            }

            DB::commit();

        } catch (\Exception $e) {
            DB::rollBack();
            return redirect('/orders')->with('error', 'Something went wrong: ' . $e->getMessage());
        }

        $label = $request->status === 'paid' ? 'marked as paid' : 'cancelled';
        return redirect('/orders')->with('success',
            'Order #' . str_pad($id, 3, '0', STR_PAD_LEFT) . ' ' . $label . '.');
    }

    // ─────────────────────────────────────────
    //  PRODUCTS — LIST  (all roles)
    // ─────────────────────────────────────────
    public function products()
    {
        $products = DB::select("
            SELECT p.product_id, p.product_name, p.base_price, p.temperature,
                GROUP_CONCAT(i.ingredient_name ORDER BY i.ingredient_name SEPARATOR ', ') AS ingredients
            FROM products p
            LEFT JOIN product_ingredients pi ON pi.product_id = p.product_id
            LEFT JOIN ingredients i ON i.ingredient_id = pi.ingredient_id
            WHERE p.is_active = 1
            GROUP BY p.product_id, p.product_name, p.base_price, p.temperature
            ORDER BY p.product_name
        ");

        return view('products', compact('products'));
    }

    // ─────────────────────────────────────────
    //  PRODUCTS — CREATE FORM  (admin only)
    // ─────────────────────────────────────────
    public function productsCreate()
    {
        $ingredients = DB::table('ingredients')
            ->select('ingredient_id', 'ingredient_name', 'unit')
            ->orderBy('ingredient_name')
            ->get();

        return view('product-create', compact('ingredients'));
    }

    // ─────────────────────────────────────────
    //  PRODUCTS — STORE  (admin only)
    // ─────────────────────────────────────────
    public function productsStore(Request $request)
    {
        $request->validate([
            'product_name' => 'required|string|max:255',
            'base_price'   => 'required|numeric|min:1',
            'temperature'  => 'required|in:HOT,COLD',
        ]);

        $productId = DB::table('products')->insertGetId([
            'product_name' => $request->product_name,
            'base_price'   => $request->base_price,
            'temperature'  => $request->temperature,
            'created_at'   => now(),
            'updated_at'   => now(),
        ]);

        if ($request->has('ingredients')) {
            foreach ($request->ingredients as $ingredientId => $data) {
                if (!empty($data['selected'])) {
                    DB::table('product_ingredients')->insert([
                        'product_id'    => $productId,
                        'ingredient_id' => $ingredientId,
                        'quantity_used' => $data['quantity_used'] ?? 1,
                    ]);
                }
            }
        }

        return redirect('/products')->with('success', $request->product_name . ' added to the menu!');
    }

    // ─────────────────────────────────────────
    //  INVENTORY — VIEW  (all roles)
    // ─────────────────────────────────────────
    public function inventory()
    {
        $ingredients = DB::table('ingredients')
            ->select('ingredient_id', 'ingredient_name', 'stock_level', 'unit')
            ->orderBy('ingredient_name')
            ->get();

        $transactions = DB::select('
            SELECT i.ingredient_name, i.unit,
                   st.transaction_type, st.quantity, st.transaction_date,
                   u.name AS staff_name
            FROM stock_transactions st
            JOIN ingredients i ON i.ingredient_id = st.ingredient_id
            LEFT JOIN users u ON u.id = st.user_id
            ORDER BY st.transaction_id DESC
        ');

        return view('inventory', compact('ingredients', 'transactions'));
    }

    // ─────────────────────────────────────────
    //  INVENTORY — LOG TRANSACTION  (staff only)
    // ─────────────────────────────────────────
    public function inventoryTransaction(Request $request)
    {
        $request->validate([
            'ingredient_id'    => 'required|exists:ingredients,ingredient_id',
            'transaction_type' => 'required|in:IN,OUT',
            'quantity'         => 'required|integer|min:1',
        ]);

        $ingredient = DB::table('ingredients')
            ->where('ingredient_id', $request->ingredient_id)
            ->first();

        if (!$ingredient) {
            return redirect('/inventory')->with('error', 'Ingredient not found.');
        }

        if ($request->transaction_type === 'OUT' && $request->quantity > $ingredient->stock_level) {
            return redirect('/inventory')->with('error',
                'Cannot stock OUT more than the current level (' . $ingredient->stock_level . ').');
        }

        $newLevel = $request->transaction_type === 'IN'
            ? $ingredient->stock_level + $request->quantity
            : $ingredient->stock_level - $request->quantity;

        DB::table('ingredients')
            ->where('ingredient_id', $request->ingredient_id)
            ->update(['stock_level' => $newLevel, 'updated_at' => now()]);

        DB::table('stock_transactions')->insert([
            'ingredient_id'    => $request->ingredient_id,
            'user_id'          => session('user_id'),
            'transaction_type' => $request->transaction_type,
            'quantity'         => $request->quantity,
            'transaction_date' => now(),
            'created_at'       => now(),
            'updated_at'       => now(),
        ]);

        $label = $request->transaction_type === 'IN' ? 'stocked in' : 'stocked out';
        return redirect('/inventory')->with('success',
            "Successfully {$label} {$request->quantity} units.");
    }

    // ─────────────────────────────────────────
    //  INGREDIENTS — STORE via AJAX  (admin only)
    // ─────────────────────────────────────────
    public function ingredientsStore(Request $request)
    {
        $request->validate([
            'ingredient_name' => 'required|string|max:255',
            'unit'            => 'required|string|max:50',
            'stock_level'     => 'nullable|integer|min:0',
        ]);

        $ingredientId = DB::table('ingredients')->insertGetId([
            'ingredient_name' => $request->ingredient_name,
            'unit'            => $request->unit,
            'stock_level'     => $request->stock_level ?? 0,
            'created_at'      => now(),
            'updated_at'      => now(),
        ]);

        return response()->json([
            'ingredient_id'   => $ingredientId,
            'ingredient_name' => $request->ingredient_name,
            'unit'            => $request->unit,
        ]);
    }

    // ─────────────────────────────────────────
    //  REPORTS  (admin only)
    // ─────────────────────────────────────────
    public function reports(Request $request)
    {
        $dateFrom = $request->input('date_from', now()->startOfMonth()->toDateString());
        $dateTo   = $request->input('date_to',   now()->toDateString());

        $productSales = DB::select("
            SELECT
                p.product_id,
                p.product_name,
                COALESCE(SUM(od.quantity), 0)  AS total_sold,
                COALESCE(SUM(od.subtotal),  0) AS total_revenue
            FROM products p
            LEFT JOIN order_details od ON od.product_id = p.product_id
            LEFT JOIN orders o ON o.order_id = od.order_id
                AND o.status = 'paid'
                AND DATE(o.order_date) BETWEEN ? AND ?
            GROUP BY p.product_id, p.product_name
            ORDER BY total_revenue DESC
        ", [$dateFrom, $dateTo]);

        $salesSummary = DB::select("
            SELECT
                o.order_id,
                u.name           AS staff_name,
                o.total_amount,
                o.status,
                o.order_date,
                pay.payment_method,
                pay.payment_status,
                pay.receipt_number
            FROM orders o
            JOIN users u ON u.id = o.user_id
            LEFT JOIN payments pay ON pay.order_id = o.order_id
            WHERE DATE(o.order_date) BETWEEN ? AND ?
            ORDER BY o.order_id ASC
        ", [$dateFrom, $dateTo]);

        $totalRevenue = DB::selectOne("
            SELECT COALESCE(SUM(total_amount), 0) AS total
            FROM orders
            WHERE status = 'paid'
              AND DATE(order_date) BETWEEN ? AND ?
        ", [$dateFrom, $dateTo])->total ?? 0;

        $topProduct = $productSales[0] ?? null;

        return view('reports', compact(
            'productSales', 'salesSummary', 'totalRevenue', 'topProduct',
            'dateFrom', 'dateTo'
        ));
    }

    // ─────────────────────────────────────────
//  PRODUCTS — EDIT FORM  (admin only)
// ─────────────────────────────────────────
public function productsEdit($id)
{
    $product = DB::table('products')->where('product_id', $id)->first();

    if (!$product) {
        return redirect('/products')->with('error', 'Product not found.');
    }

    $ingredients = DB::table('ingredients')
        ->select('ingredient_id', 'ingredient_name', 'unit')
        ->orderBy('ingredient_name')
        ->get();

    $productIngredients = DB::table('product_ingredients')
        ->where('product_id', $id)
        ->get();

    return view('product-edit', compact('product', 'ingredients', 'productIngredients'));
}

// ─────────────────────────────────────────
//  PRODUCTS — UPDATE  (admin only)
// ─────────────────────────────────────────
public function productsUpdate(Request $request, $id)
{
    $request->validate([
        'product_name' => 'required|string|max:255',
        'base_price'   => 'required|numeric|min:1',
        'temperature'  => 'required|in:HOT,COLD',
    ]);

    $product = DB::table('products')->where('product_id', $id)->first();

    if (!$product) {
        return redirect('/products')->with('error', 'Product not found.');
    }

    DB::table('products')->where('product_id', $id)->update([
        'product_name' => $request->product_name,
        'base_price'   => $request->base_price,
        'temperature'  => $request->temperature,
        'updated_at'   => now(),
    ]);

    // Replace ingredient links
    DB::table('product_ingredients')->where('product_id', $id)->delete();

    if ($request->has('ingredients')) {
        foreach ($request->ingredients as $ingredientId => $data) {
            if (!empty($data['selected'])) {
                DB::table('product_ingredients')->insert([
                    'product_id'    => $id,
                    'ingredient_id' => $ingredientId,
                    'quantity_used' => $data['quantity_used'] ?? 1,
                ]);
            }
        }
    }

    return redirect('/products')->with('success', $request->product_name . ' updated successfully!');
}

// ─────────────────────────────────────────
//  PRODUCTS — SOFT DELETE  (admin only)
// ─────────────────────────────────────────
public function productsDelete($id)
{
    $product = DB::table('products')->where('product_id', $id)->first();

    if (!$product) {
        return redirect('/products')->with('error', 'Product not found.');
    }

    DB::table('products')->where('product_id', $id)->update([
        'is_active'  => false,
        'updated_at' => now(),
    ]);

    return redirect('/products')->with('success', $product->product_name . ' has been archived.');
}
}