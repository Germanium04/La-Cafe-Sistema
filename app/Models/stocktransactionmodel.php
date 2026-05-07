<?php

namespace App\Models;

use PDO;

class StockTransactionModel extends BaseModel
{
    public function allWithIngredient(): array
    {
        // FIX: was missing JOIN on users table — "By" column was always blank in inventory log
        $stmt = $this->pdo->query('
            SELECT i.ingredient_name, i.unit,
                   st.transaction_type, st.quantity, st.transaction_date,
                   u.name AS staff_name
            FROM stock_transactions st
            JOIN ingredients i ON i.ingredient_id = st.ingredient_id
            LEFT JOIN users u ON u.id = st.user_id
            ORDER BY st.transaction_id DESC
        ');
        return $stmt->fetchAll(PDO::FETCH_OBJ);
    }

    public function insert(int $ingredientId, string $type, float $quantity, ?int $userId = null): void
    {
        // FIX: was not storing user_id — "By" column was always blank
        $stmt = $this->pdo->prepare('
            INSERT INTO stock_transactions
                (ingredient_id, user_id, transaction_type, quantity, transaction_date, created_at, updated_at)
            VALUES (?, ?, ?, ?, NOW(), NOW(), NOW())
        ');
        $stmt->execute([$ingredientId, $userId, $type, $quantity]);
    }
}