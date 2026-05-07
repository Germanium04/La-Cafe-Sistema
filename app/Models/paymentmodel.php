<?php

namespace App\Models;

use PDO;

class PaymentModel extends BaseModel
{
    public function insert(int $orderId, string $method, float $amountPaid, string $status, string $receiptNumber): void
    {
        // FIX: was missing receipt_number — receipt view depends on this column
        $stmt = $this->pdo->prepare('
            INSERT INTO payments (order_id, payment_method, amount_paid, payment_date, payment_status, receipt_number, created_at, updated_at)
            VALUES (?, ?, ?, NOW(), ?, ?, NOW(), NOW())
        ');
        $stmt->execute([$orderId, $method, $amountPaid, $status, $receiptNumber]);
    }
}