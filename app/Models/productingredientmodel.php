<?php

namespace App\Models;

use PDO;

class ProductIngredientModel extends BaseModel
{
    public function allForProduct(int $productId): array
    {
        $stmt = $this->pdo->prepare('
            SELECT ingredient_id, quantity_used
            FROM product_ingredients
            WHERE product_id = ?
        ');
        $stmt->execute([$productId]);
        return $stmt->fetchAll(PDO::FETCH_OBJ);
    }

    public function insert(int $productId, int $ingredientId, float $quantityUsed): void
    {
        $stmt = $this->pdo->prepare('
            INSERT INTO product_ingredients (product_id, ingredient_id, quantity_used)
            VALUES (?, ?, ?)
        ');
        $stmt->execute([$productId, $ingredientId, $quantityUsed]);
    }
}