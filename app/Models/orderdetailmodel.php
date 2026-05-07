<?php

namespace App\Models;

use PDO;

class OrderDetailModel extends BaseModel
{
    public function allForOrder(int $orderId): array
    {
        $stmt = $this->pdo->prepare('
            SELECT od.order_detail_id, od.product_id, od.quantity, od.subtotal, od.with_coffee,
                   p.product_name, p.base_price, p.temperature
            FROM order_details od
            JOIN products p ON p.product_id = od.product_id
            WHERE od.order_id = ?
        ');
        $stmt->execute([$orderId]);
        return $stmt->fetchAll(PDO::FETCH_OBJ);
    }

    // Returns only product_id and quantity — used for ingredient deduction
    public function itemsForOrder(int $orderId): array
    {
        $stmt = $this->pdo->prepare('SELECT product_id, quantity FROM order_details WHERE order_id = ?');
        $stmt->execute([$orderId]);
        return $stmt->fetchAll(PDO::FETCH_OBJ);
    }

    public function insert(int $orderId, int $productId, int $quantity, float $subtotal, int $withCoffee): void
    {
        $stmt = $this->pdo->prepare('
            INSERT INTO order_details (order_id, product_id, quantity, subtotal, with_coffee)
            VALUES (?, ?, ?, ?, ?)
        ');
        $stmt->execute([$orderId, $productId, $quantity, $subtotal, $withCoffee]);
    }

    public function deleteForOrder(int $orderId): void
    {
        $stmt = $this->pdo->prepare('DELETE FROM order_details WHERE order_id = ?');
        $stmt->execute([$orderId]);
    }
}