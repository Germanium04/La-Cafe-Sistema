<?php

namespace App\Models;

use PDO;

class StockTransactionModel extends BaseModel
{
    // ─── Read ─────────────────────────────────────────────────────────────────

    // Full log for the inventory page — shows entered unit for readability
    public function allWithIngredient(): array
    {
        $stmt = $this->pdo->query('
            SELECT
                i.ingredient_name,
                i.unit                  AS base_unit,
                st.transaction_type,
                st.quantity             AS base_quantity,   -- converted base value
                st.entered_quantity,                        -- what staff typed
                st.entered_unit,                            -- unit staff selected
                st.reason,
                st.notes,
                st.status,
                st.rejection_note,
                st.transaction_date,
                u.name                  AS staff_name,
                a.name                  AS approved_by_name,
                st.approved_at
            FROM stock_transactions st
            JOIN ingredients i ON i.ingredient_id = st.ingredient_id
            LEFT JOIN users u ON u.id = st.user_id
            LEFT JOIN users a ON a.id = st.approved_by
            ORDER BY st.transaction_id DESC
        ');
        return $stmt->fetchAll(PDO::FETCH_OBJ);
    }

    // Pending-only — used for admin approval queue
    public function pendingWithIngredient(): array
    {
        $stmt = $this->pdo->query('
            SELECT
                st.transaction_id,
                i.ingredient_name,
                i.unit                  AS base_unit,
                st.transaction_type,
                st.quantity             AS base_quantity,
                st.entered_quantity,
                st.entered_unit,
                st.reason,
                st.notes,
                st.transaction_date,
                u.name                  AS staff_name
            FROM stock_transactions st
            JOIN ingredients i ON i.ingredient_id = st.ingredient_id
            LEFT JOIN users u ON u.id = st.user_id
            WHERE st.status = "pending"
            ORDER BY st.transaction_id ASC
        ');
        return $stmt->fetchAll(PDO::FETCH_OBJ);
    }

    public function find(int $id): object|false
    {
        $stmt = $this->pdo->prepare('
            SELECT st.*, i.stock_level, i.unit AS base_unit, i.unit_group
            FROM stock_transactions st
            JOIN ingredients i ON i.ingredient_id = st.ingredient_id
            WHERE st.transaction_id = ?
        ');
        $stmt->execute([$id]);
        return $stmt->fetch(PDO::FETCH_OBJ);
    }

    // ─── Write ────────────────────────────────────────────────────────────────

    // Staff submits a transaction — saves as PENDING, does NOT touch stock_level yet
    public function insert(
        int    $ingredientId,
        string $type,
        int    $baseQuantity,    // already converted to base unit
        int    $enteredQuantity, // raw number staff typed
        string $enteredUnit,     // unit staff selected
        string $reason,
        ?string $notes   = null,
        ?int   $userId   = null
    ): void {
        $stmt = $this->pdo->prepare('
            INSERT INTO stock_transactions
                (ingredient_id, user_id, transaction_type,
                 quantity, entered_quantity, entered_unit,
                 reason, notes,
                 status, approved_by, approved_at, rejection_note,
                 transaction_date, created_at, updated_at)
            VALUES (?, ?, ?, ?, ?, ?, ?, ?, "pending", NULL, NULL, NULL, NOW(), NOW(), NOW())
        ');
        $stmt->execute([
            $ingredientId, $userId, $type,
            $baseQuantity, $enteredQuantity, $enteredUnit,
            $reason, $notes,
        ]);
    }

    // Admin approves — now we actually update stock_level
    public function approve(int $transactionId, int $adminId): bool
    {
        $tx = $this->find($transactionId);
        if (!$tx || $tx->status !== 'pending') {
            return false;
        }

        // Apply stock change
        $sql = $tx->transaction_type === 'IN'
            ? 'UPDATE ingredients SET stock_level = stock_level + ?, updated_at = NOW() WHERE ingredient_id = ?'
            : 'UPDATE ingredients SET stock_level = GREATEST(stock_level - ?, 0), updated_at = NOW() WHERE ingredient_id = ?';

        $stmt = $this->pdo->prepare($sql);
        $stmt->execute([$tx->base_quantity, $tx->ingredient_id]);

        // Mark transaction approved
        $stmt = $this->pdo->prepare('
            UPDATE stock_transactions
            SET status = "approved", approved_by = ?, approved_at = NOW(), updated_at = NOW()
            WHERE transaction_id = ?
        ');
        $stmt->execute([$adminId, $transactionId]);

        return true;
    }

    // Admin rejects — stock_level unchanged, stores reason for staff to see
    public function reject(int $transactionId, int $adminId, ?string $note = null): bool
    {
        $tx = $this->find($transactionId);
        if (!$tx || $tx->status !== 'pending') {
            return false;
        }

        $stmt = $this->pdo->prepare('
            UPDATE stock_transactions
            SET status = "rejected", approved_by = ?, approved_at = NOW(),
                rejection_note = ?, updated_at = NOW()
            WHERE transaction_id = ?
        ');
        $stmt->execute([$adminId, $note, $transactionId]);

        return true;
    }
}