<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

use App\Models\IngredientModel;
use App\Models\StockTransactionModel;
use App\Models\ProductModel;
use App\Models\ProductIngredientModel;
use App\Models\OrderModel;
use App\Models\OrderDetailModel;
use App\Models\PaymentModel;

// FIX: removed StaffModel import — this codebase uses the `users` table directly,
//      not a separate `staff` table. StaffModel has been corrected to wrap `users`.
use App\Models\StaffModel;

class AppController extends Controller
{
    // ─────────────────────────────────────────
    //  DASHBOARD
    // ─────────────────────────────────────────
    public function dashboard()
    {
        $hour     = now()->hour;
        $greeting = $hour < 12 ? 'morning' : ($hour < 18 ? 'afternoon' : 'evening');

        $orders      = new OrderModel();
        $products    = new ProductModel();
        $staff       = new StaffModel();
        $ingredientM = new IngredientModel();

        // FIX: status strings use lowercase to match what ordersStore/ordersUpdateStatus insert
        $totalRevenue       = $orders->totalRevenueByStatus('paid');
        $paidOrdersCount    = $orders->countByStatus('paid');
        $pendingOrdersCount = $orders->countByStatus('pending');

        $productsCount     = $products->count();
        $coldProductsCount = $products->countByTemperature('COLD');
        $hotProductsCount  = $products->countByTemperature('HOT');

        $staffCount    = $staff->count();
        $recentOrders  = $orders->recentWithDetails(5);
        $topProducts   = $products->topByQuantitySold(5);
        $ingredients   = $ingredientM->allOrdered();  // FIX: was overwriting $ingredients variable with IngredientModel instance
        $staffActivity = $staff->allWithActivity();

        return view('dashboard', compact(
            'greeting', 'totalRevenue', 'paidOrdersCount', 'pendingOrdersCount',
            'productsCount', 'coldProductsCount', 'hotProductsCount', 'staffCount',
            'recentOrders', 'topProducts', 'ingredients', 'staffActivity'
        ));
    }

    // ─────────────────────────────────────────
    //  ORDERS — LIST
    // ─────────────────────────────────────────
    public function ordersList()
    {
        $orders = new OrderModel();

        $pendingOrders = $orders->pendingViaProc();
        $allOrders     = $orders->allWithDetailsAndPayment();

        return view('orderlist', compact('pendingOrders', 'allOrders'));
    }

    // ─────────────────────────────────────────
    //  ORDERS — CREATE FORM
    // ─────────────────────────────────────────
    public function ordersCreate()
    {
        // FIX: removed StaffModel usage here — staff picks are driven by session('user_id')
        //      since each logged-in staff member places orders under their own account
        $products = (new ProductModel())->all();

        return view('orders', compact('products'));
    }

    // ─────────────────────────────────────────
    //  ORDERS — STORE
    //  ✦ NO ingredient deduction here.
    //    Deduction happens only when the order is marked paid.
    // ─────────────────────────────────────────
    public function ordersStore(Request $request)
    {
        $request->validate([
            'payment_method' => 'required|in:cash,gcash,maya',
            'items'          => 'required|array|min:1',
        ]);

        $total = collect($request->items)->sum('subtotal');

        $orders       = new OrderModel();
        $orderDetails = new OrderDetailModel();

        // FIX: use session user_id (not a submitted staff_id) — staff place orders as themselves
        $orderId = $orders->insert(session('user_id'), $total, 'pending');

        foreach ($request->items as $item) {
            $orderDetails->insert(
                $orderId,
                $item['product_id'],
                $item['quantity'],
                $item['subtotal'],
                $item['with_coffee'] ?? 0,
            );
        }

        session(["order_{$orderId}_payment_method" => $request->payment_method]);

        return redirect('/orders')->with('success',
            'Order #' . str_pad($orderId, 3, '0', STR_PAD_LEFT) . ' placed — awaiting payment.');
    }

    // ─────────────────────────────────────────
    //  ORDERS — EDIT FORM
    // ─────────────────────────────────────────
    public function ordersEdit($id)
    {
        $orders = new OrderModel();
        $order  = $orders->findWithStaff($id);

        if (!$order) {
            return redirect('/orders')->with('error', 'Order not found.');
        }
        if (strtolower($order->status) !== 'pending') {
            return redirect('/orders')->with('error', 'Only pending orders can be edited.');
        }

        // FIX: staff can only edit their own orders
        if (session('user_role') === 'staff' && $order->user_id !== session('user_id')) {
            return redirect('/orders')->with('error', 'You can only edit your own orders.');
        }

        $orderDetails = (new OrderDetailModel())->allForOrder($id);
        $products     = (new ProductModel())->all();

        return view('order-edit', compact('order', 'orderDetails', 'products'));
    }

    // ─────────────────────────────────────────
    //  ORDERS — UPDATE (save edits to pending order)
    // ─────────────────────────────────────────
    public function ordersUpdate(Request $request, $id)
    {
        $request->validate([
            'items' => 'required|array|min:1',
        ]);

        $orders = new OrderModel();
        $order  = $orders->find($id);

        if (!$order || strtolower($order->status) !== 'pending') {
            return redirect('/orders')->with('error', 'This order can no longer be edited.');
        }

        $orderDetails = new OrderDetailModel();
        $orderDetails->deleteForOrder($id);

        $total = 0;
        foreach ($request->items as $item) {
            $orderDetails->insert($id, $item['product_id'], $item['quantity'], $item['subtotal'], $item['with_coffee'] ?? 0);
            $total += $item['subtotal'];
        }

        $orders->updateTotal($id, $total);

        return redirect('/orders')->with('success',
            'Order #' . str_pad($id, 3, '0', STR_PAD_LEFT) . ' updated successfully.');
    }

    // ─────────────────────────────────────────
    //  ORDERS — UPDATE STATUS (mark paid / cancelled)
    //
    //  ✦ Ingredient deduction happens HERE — only on paid.
    //    Uses a DB transaction so deductions are all-or-nothing.
    // ─────────────────────────────────────────
    public function ordersUpdateStatus(Request $request, $id)
    {
        $request->validate([
            'status'         => 'required|in:paid,cancelled',
            'payment_method' => 'nullable|in:cash,gcash,maya',
        ]);

        $orders = new OrderModel();
        $order  = $orders->find($id);

        if (!$order) {
            return redirect('/orders')->with('error', 'Order not found.');
        }
        if (strtolower($order->status) !== 'pending') {
            return redirect('/orders')->with('error', 'This order has already been processed.');
        }

        // FIX: staff can only update their own orders
        if (session('user_role') === 'staff') {
            $fullOrder = DB::table('orders')->where('order_id', $id)->first();
            if ($fullOrder && $fullOrder->user_id !== session('user_id')) {
                return redirect('/orders')->with('error', 'You can only update your own orders.');
            }
        }

        DB::beginTransaction();

        try {
            $orders->updateStatus($id, $request->status);

            if ($request->status === 'paid') {

                $payMethod = $request->payment_method
                    ?? session("order_{$id}_payment_method", 'cash');

                // FIX: generate receipt number and pass it to PaymentModel::insert()
                //      Previously receipt_number was never stored, breaking the receipt view
                $receiptNumber = 'RCP-' . now()->format('Ymd') . '-' . str_pad($id, 4, '0', STR_PAD_LEFT);

                (new PaymentModel())->insert($id, $payMethod, $order->total_amount, 'paid', $receiptNumber);

                $items = (new OrderDetailModel())->itemsForOrder($id);

                $productIngredients = new ProductIngredientModel();
                $ingredientM        = new IngredientModel();
                $stockTransactions  = new StockTransactionModel();

                foreach ($items as $item) {
                    $usedIngredients = $productIngredients->allForProduct($item->product_id);

                    foreach ($usedIngredients as $ing) {
                        $totalUsed = $ing->quantity_used * $item->quantity;

                        $ingredientM->deductStock($ing->ingredient_id, $totalUsed);

                        // FIX: pass session user_id so the "By" column is populated in the inventory log
                        $stockTransactions->insert($ing->ingredient_id, 'OUT', $totalUsed, session('user_id'));
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
    //  PRODUCTS
    // ─────────────────────────────────────────
    public function products()
    {
        $products = (new ProductModel())->allWithIngredients();

        return view('products', compact('products'));
    }

    // ─────────────────────────────────────────
    //  PRODUCTS — CREATE FORM
    // ─────────────────────────────────────────
    public function productsCreate()
    {
        $ingredients = (new IngredientModel())->all();

        return view('product-create', compact('ingredients'));
    }

    // ─────────────────────────────────────────
    //  PRODUCTS — STORE
    // ─────────────────────────────────────────
    public function productsStore(Request $request)
    {
        $request->validate([
            'product_name' => 'required|string|max:255',
            'base_price'   => 'required|numeric|min:1',
            'temperature'  => 'required|in:HOT,COLD',
        ]);

        $productId = (new ProductModel())->insert(
            $request->product_name,
            $request->base_price,
            $request->temperature,
        );

        if ($request->has('ingredients')) {
            $productIngredients = new ProductIngredientModel();

            foreach ($request->ingredients as $ingredientId => $data) {
                if (!empty($data['selected'])) {
                    $productIngredients->insert($productId, (int)$ingredientId, $data['quantity_used'] ?? 1);
                }
            }
        }

        return redirect('/products')->with('success', $request->product_name . ' added to the menu!');
    }

    // ─────────────────────────────────────────
    //  INVENTORY
    // ─────────────────────────────────────────
    public function inventory()
    {
        $ingredients  = (new IngredientModel())->all();
        $transactions = (new StockTransactionModel())->allWithIngredient();

        return view('inventory', compact('ingredients', 'transactions'));
    }

    // ─────────────────────────────────────────
    //  INVENTORY — LOG MANUAL TRANSACTION
    // ─────────────────────────────────────────
    public function inventoryTransaction(Request $request)
    {
        $request->validate([
            'ingredient_id'    => 'required|exists:ingredients,ingredient_id',
            'transaction_type' => 'required|in:IN,OUT',
            'quantity'         => 'required|integer|min:1',
        ]);

        $ingredientM = new IngredientModel();
        $ingredient  = $ingredientM->find($request->ingredient_id);

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

        $ingredientM->updateStock($request->ingredient_id, $newLevel);

        // FIX: pass session user_id so manual transactions also show who logged them
        (new StockTransactionModel())->insert(
            $request->ingredient_id,
            $request->transaction_type,
            $request->quantity,
            session('user_id'),
        );

        $label = $request->transaction_type === 'IN' ? 'stocked in' : 'stocked out';
        return redirect('/inventory')->with('success',
            "Successfully {$label} {$request->quantity} units.");
    }

    // ─────────────────────────────────────────
    //  INGREDIENTS — STORE (AJAX from product-create)
    // ─────────────────────────────────────────
    public function ingredientsStore(Request $request)
    {
        $request->validate([
            'ingredient_name' => 'required|string|max:255',
            'unit'            => 'required|string|max:50',
            'stock_level'     => 'nullable|integer|min:0',
        ]);

        $ingredientM  = new IngredientModel();
        $ingredientId = $ingredientM->insert(
            $request->ingredient_name,
            $request->unit,
            $request->stock_level ?? 0,
        );

        return response()->json([
            'ingredient_id'   => $ingredientId,
            'ingredient_name' => $request->ingredient_name,
            'unit'            => $request->unit,
        ]);
    }

    // ─────────────────────────────────────────
    //  REPORTS
    // ─────────────────────────────────────────
    public function reports()
    {
        $pdo = DB::connection()->getPdo();

        $stmt         = $pdo->query('SELECT * FROM product_sales_report ORDER BY total_revenue DESC');
        $productSales = $stmt->fetchAll(\PDO::FETCH_OBJ);

        $stmt         = $pdo->query('SELECT * FROM sales_summary ORDER BY order_id');
        $salesSummary = $stmt->fetchAll(\PDO::FETCH_OBJ);

        $stmt         = $pdo->query('SELECT GetTotalSales() AS total');
        $totalRevenue = $stmt->fetchColumn() ?? 0;

        $topProduct = $productSales[0] ?? null;

        return view('reports', compact('productSales', 'salesSummary', 'totalRevenue', 'topProduct'));
    }
}