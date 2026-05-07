<?php

namespace App\Models;

use PDO;

class OrderModel extends BaseModel
{
    public function find(int $id): object|false
    {
        $stmt = $this->pdo->prepare('SELECT status, total_amount FROM orders WHERE order_id = ?');
        $stmt->execute([$id]);
        return $stmt->fetch(PDO::FETCH_OBJ);
    }

    public function findWithStaff(int $id): object|false
    {
        // FIX: was JOINing `staff` table — corrected to `users` table (matches actual DB schema)
        $stmt = $this->pdo->prepare('
            SELECT o.order_id, u.name AS staff_name, o.status, o.user_id
            FROM orders o
            JOIN users u ON o.user_id = u.id
            WHERE o.order_id = ?
        ');
        $stmt->execute([$id]);
        return $stmt->fetch(PDO::FETCH_OBJ);
    }

    // Recent orders for the dashboard (limit 5, with first product name per order)
    public function recentWithDetails(int $limit = 5): array
    {
        // FIX: was ORDER BY ASC — corrected to DESC so newest orders appear first
        $stmt = $this->pdo->prepare('
            SELECT o.order_id, p.product_name, u.name AS staff_name, o.total_amount, o.status
            FROM orders o
            JOIN users u ON o.user_id = u.id
            JOIN order_details od ON od.order_id = o.order_id
            JOIN products p ON od.product_id = p.product_id
            ORDER BY o.order_id DESC
            LIMIT ?
        ');
        $stmt->execute([$limit]);
        return $stmt->fetchAll(PDO::FETCH_OBJ);
    }

    // Full list for the orders page, grouped with payment info
    public function allWithDetailsAndPayment(): array
    {
        // FIX: was JOINing `staff` table — corrected to `users` table
        $stmt = $this->pdo->query('
            SELECT o.order_id, u.name AS staff_name, o.total_amount, o.status,
                   o.order_date,
                   GROUP_CONCAT(p.product_name ORDER BY p.product_name SEPARATOR \', \') AS products,
                   pay.payment_method, pay.payment_status
            FROM orders o
            JOIN users u ON o.user_id = u.id
            JOIN order_details od ON od.order_id = o.order_id
            JOIN products p ON od.product_id = p.product_id
            LEFT JOIN payments pay ON pay.order_id = o.order_id
            GROUP BY o.order_id, u.name, o.total_amount, o.status, o.order_date,
                     pay.payment_method, pay.payment_status
            ORDER BY o.order_id DESC
        ');
        return $stmt->fetchAll(PDO::FETCH_OBJ);
    }

    // Stored procedure — returns pending orders via CALL
    public function pendingViaProc(): array
    {
        $stmt = $this->pdo->prepare('CALL GetOrdersByStatus(?)');
        $stmt->execute(['PENDING']);
        $rows = $stmt->fetchAll(PDO::FETCH_OBJ);
        $stmt->closeCursor();
        return $rows;
    }

    public function countByStatus(string $status): int
    {
        $stmt = $this->pdo->prepare('SELECT COUNT(*) FROM orders WHERE status = ?');
        $stmt->execute([$status]);
        return (int) $stmt->fetchColumn();
    }

    public function totalRevenueByStatus(string $status): float
    {
        $stmt = $this->pdo->prepare('SELECT COALESCE(SUM(total_amount), 0) AS total FROM orders WHERE status = ?');
        $stmt->execute([$status]);
        return (float) $stmt->fetchColumn();
    }

    public function insert(int $userId, float $total, string $status): int
    {
        // FIX: was inserting into `staff_id` column — corrected to `user_id` (matches users table)
        $stmt = $this->pdo->prepare('
            INSERT INTO orders (user_id, total_amount, status, order_date, created_at, updated_at)
            VALUES (?, ?, ?, NOW(), NOW(), NOW())
        ');
        $stmt->execute([$userId, $total, $status]);
        return (int) $this->pdo->lastInsertId();
    }

    public function updateStatus(int $id, string $status): void
    {
        $stmt = $this->pdo->prepare('UPDATE orders SET status = ?, updated_at = NOW() WHERE order_id = ?');
        $stmt->execute([$status, $id]);
    }

    public function updateTotal(int $id, float $total): void
    {
        $stmt = $this->pdo->prepare('UPDATE orders SET total_amount = ?, updated_at = NOW() WHERE order_id = ?');
        $stmt->execute([$total, $id]);
    }
}