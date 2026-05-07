<?php

namespace App\Models;

use PDO;

class StaffModel extends BaseModel
{
    public function all(): array
    {
        // FIX: queries the `users` table (role='staff') — there is no separate `staff` table in this schema
        $stmt = $this->pdo->query("SELECT id AS staff_id, name FROM users WHERE role = 'staff' ORDER BY name");
        return $stmt->fetchAll(PDO::FETCH_OBJ);
    }

    public function find(int $id): object|false
    {
        // FIX: queries `users` table instead of non-existent `staff` table
        $stmt = $this->pdo->prepare("SELECT id AS staff_id, name FROM users WHERE id = ? AND role = 'staff'");
        $stmt->execute([$id]);
        return $stmt->fetch(PDO::FETCH_OBJ);
    }

    // Returns staff with aggregated order stats (for dashboard/reports)
    public function allWithActivity(): array
    {
        // FIX: was querying `staff` table — corrected to `users` table
        // FIX: status values changed to lowercase 'paid'/'pending' to match AppController inserts
        $stmt = $this->pdo->query("
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
        return $stmt->fetchAll(PDO::FETCH_OBJ);
    }

    public function count(): int
    {
        // FIX: counts from `users` table filtered by role
        return (int) $this->pdo->query("SELECT COUNT(*) FROM users WHERE role = 'staff'")->fetchColumn();
    }
}