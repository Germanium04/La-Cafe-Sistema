<?php

namespace App\Models;

use PDO;

class ProductModel extends BaseModel
{
    public function all(): array
    {
        // FIX: added is_active filter so archived products don't appear in order forms
        $stmt = $this->pdo->query('SELECT product_id, product_name, base_price, temperature FROM products WHERE is_active = 1 ORDER BY product_name');
        return $stmt->fetchAll(PDO::FETCH_OBJ);
    }

    public function allWithIngredients(): array
    {
        // FIX: was missing WHERE p.is_active = 1 — archived products were showing in the products list
        $stmt = $this->pdo->query('
            SELECT p.product_id, p.product_name, p.base_price, p.temperature,
                   GROUP_CONCAT(i.ingredient_name ORDER BY i.ingredient_name SEPARATOR \', \') AS ingredients
            FROM products p
            LEFT JOIN product_ingredients pi ON pi.product_id = p.product_id
            LEFT JOIN ingredients i ON i.ingredient_id = pi.ingredient_id
            WHERE p.is_active = 1
            GROUP BY p.product_id, p.product_name, p.base_price, p.temperature
            ORDER BY p.product_name
        ');
        return $stmt->fetchAll(PDO::FETCH_OBJ);
    }

    // Top-selling products with revenue (for dashboard)
    public function topByQuantitySold(int $limit = 5): array
    {
        $stmt = $this->pdo->prepare('
            SELECT p.product_name, p.temperature,
                   COALESCE(SUM(od.quantity), 0) AS total_sold,
                   COALESCE(SUM(od.subtotal), 0)  AS total_revenue
            FROM products p
            LEFT JOIN order_details od ON od.product_id = p.product_id
            WHERE p.is_active = 1
            GROUP BY p.product_id, p.product_name, p.temperature
            ORDER BY total_sold DESC
            LIMIT ?
        ');
        $stmt->execute([$limit]);
        return $stmt->fetchAll(PDO::FETCH_OBJ);
    }

    public function count(): int
    {
        // FIX: count only active products
        return (int) $this->pdo->query('SELECT COUNT(*) FROM products WHERE is_active = 1')->fetchColumn();
    }

    public function countByTemperature(string $temperature): int
    {
        // FIX: count only active products
        $stmt = $this->pdo->prepare('SELECT COUNT(*) FROM products WHERE temperature = ? AND is_active = 1');
        $stmt->execute([$temperature]);
        return (int) $stmt->fetchColumn();
    }

    public function insert(string $name, float $price, string $temperature): int
    {
        // FIX: added is_active = 1 default on insert
        $stmt = $this->pdo->prepare('
            INSERT INTO products (product_name, base_price, temperature, is_active, created_at, updated_at)
            VALUES (?, ?, ?, 1, NOW(), NOW())
        ');
        $stmt->execute([$name, $price, $temperature]);
        return (int) $this->pdo->lastInsertId();
    }
}