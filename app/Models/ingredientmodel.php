<?php

namespace App\Models;

use PDO;

class IngredientModel extends BaseModel
{
    // Unit conversion factors — all relative to the base unit (smallest in group)
    // weight base = grams  → kg  = ×1000
    // volume base = ml     → L   = ×1000
    // piece  base = pcs    → no conversion needed
    public const CONVERSIONS = [
        'weight' => ['g'   => 1,    'kg' => 1000],
        'volume' => ['ml'  => 1,    'L'  => 1000],
        'piece'  => ['pcs' => 1],
    ];

    // Returns the allowed unit options for a given unit_group
    public static function unitsFor(string $group): array
    {
        return array_keys(self::CONVERSIONS[$group] ?? ['pcs' => 1]);
    }

    // Converts an entered quantity+unit into the base unit value
    // e.g. convertToBase(2, 'kg', 'weight') → 2000
    public static function convertToBase(float $quantity, string $enteredUnit, string $group): int
    {
        $factor = self::CONVERSIONS[$group][$enteredUnit] ?? 1;
        return (int) round($quantity * $factor);
    }

    // ─── Queries ──────────────────────────────────────────────────────────────

    public function all(): array
    {
        $stmt = $this->pdo->query('
            SELECT ingredient_id, ingredient_name, stock_level,
                   unit, unit_group, min_stock, max_stock
            FROM ingredients
            ORDER BY ingredient_name
        ');
        return $stmt->fetchAll(PDO::FETCH_OBJ);
    }

    public function allOrdered(): array
    {
        $stmt = $this->pdo->query('
            SELECT ingredient_id, ingredient_name, stock_level,
                   unit, unit_group, min_stock, max_stock
            FROM ingredients
            ORDER BY ingredient_id
        ');
        return $stmt->fetchAll(PDO::FETCH_OBJ);
    }

    public function find(int $id): object|false
    {
        $stmt = $this->pdo->prepare('
            SELECT ingredient_id, ingredient_name, stock_level,
                   unit, unit_group, min_stock, max_stock
            FROM ingredients
            WHERE ingredient_id = ?
        ');
        $stmt->execute([$id]);
        return $stmt->fetch(PDO::FETCH_OBJ);
    }

    public function insert(
        string $name,
        string $unit,
        string $unitGroup,
        int    $stockLevel,
        int    $minStock  = 0,
        ?int   $maxStock  = null
    ): int {
        $stmt = $this->pdo->prepare('
            INSERT INTO ingredients
                (ingredient_name, unit, unit_group, stock_level, min_stock, max_stock, created_at, updated_at)
            VALUES (?, ?, ?, ?, ?, ?, NOW(), NOW())
        ');
        $stmt->execute([$name, $unit, $unitGroup, $stockLevel, $minStock, $maxStock]);
        return (int) $this->pdo->lastInsertId();
    }

    public function updateStock(int $id, int $newLevel): void
    {
        $stmt = $this->pdo->prepare('
            UPDATE ingredients
            SET stock_level = ?, updated_at = NOW()
            WHERE ingredient_id = ?
        ');
        $stmt->execute([$newLevel, $id]);
    }

    public function deductStock(int $id, float $amount): void
    {
        // Used by the auto-deduct on order paid — no approval needed
        $stmt = $this->pdo->prepare('
            UPDATE ingredients
            SET stock_level = GREATEST(stock_level - ?, 0),
                updated_at  = NOW()
            WHERE ingredient_id = ?
        ');
        $stmt->execute([$amount, $id]);
    }
}