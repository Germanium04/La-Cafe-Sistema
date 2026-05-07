<?php

namespace App\Models;

use PDO;

class IngredientModel extends BaseModel
{
    public function all(): array
    {
        $stmt = $this->pdo->query('SELECT ingredient_id, ingredient_name, stock_level, unit FROM ingredients ORDER BY ingredient_name');
        return $stmt->fetchAll(PDO::FETCH_OBJ);
    }

    public function allOrdered(): array
    {
        $stmt = $this->pdo->query('SELECT ingredient_name, stock_level, unit FROM ingredients ORDER BY ingredient_id');
        return $stmt->fetchAll(PDO::FETCH_OBJ);
    }

    public function find(int $id): object|false
    {
        $stmt = $this->pdo->prepare('SELECT ingredient_id, ingredient_name, stock_level, unit FROM ingredients WHERE ingredient_id = ?');
        $stmt->execute([$id]);
        return $stmt->fetch(PDO::FETCH_OBJ);
    }

    public function insert(string $name, string $unit, int $stockLevel): int
    {
        $stmt = $this->pdo->prepare('
            INSERT INTO ingredients (ingredient_name, unit, stock_level, created_at, updated_at)
            VALUES (?, ?, ?, NOW(), NOW())
        ');
        $stmt->execute([$name, $unit, $stockLevel]);
        return (int) $this->pdo->lastInsertId();
    }

    public function updateStock(int $id, int $newLevel): void
    {
        $stmt = $this->pdo->prepare('UPDATE ingredients SET stock_level = ?, updated_at = NOW() WHERE ingredient_id = ?');
        $stmt->execute([$newLevel, $id]);
    }

    public function deductStock(int $id, float $amount): void
    {
        $stmt = $this->pdo->prepare('
            UPDATE ingredients
            SET stock_level = GREATEST(stock_level - ?, 0),
                updated_at  = NOW()
            WHERE ingredient_id = ?
        ');
        $stmt->execute([$amount, $id]);
    }
}