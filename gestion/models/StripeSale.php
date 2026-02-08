<?php
/**
 * CRUD ventes Stripe — gestion_stripe_sales
 * Chiffrement customer_email_encrypted
 */
class StripeSale
{
    private PDO $pdo;
    private Encryption $encryption;

    public function __construct()
    {
        $this->pdo = Database::get();
        $this->encryption = new Encryption();
    }

    /**
     * @return array<int, array{id: int, stripe_payment_id: string, customer_email: string, amount_cents: int, currency: string, status: string, created_at: string}>
     */
    public function all(): array
    {
        $stmt = $this->pdo->query('SELECT id, stripe_payment_id, customer_email_encrypted, amount_cents, currency, status, created_at FROM gestion_stripe_sales ORDER BY created_at DESC');
        $rows = [];
        while ($r = $stmt->fetch()) {
            $email = $this->encryption->decrypt($r['customer_email_encrypted']);
            if ($email === false) {
                $email = '(indisponible)';
            }
            $rows[] = [
                'id' => (int) $r['id'],
                'stripe_payment_id' => $r['stripe_payment_id'],
                'customer_email' => $email,
                'amount_cents' => (int) $r['amount_cents'],
                'currency' => $r['currency'],
                'status' => $r['status'],
                'created_at' => $r['created_at'],
            ];
        }
        return $rows;
    }

    public function totalAmountCentsThisMonth(): int
    {
        $stmt = $this->pdo->query("SELECT COALESCE(SUM(amount_cents), 0) FROM gestion_stripe_sales WHERE status = 'paid' AND created_at >= DATE_FORMAT(NOW(), '%Y-%m-01')");
        return (int) $stmt->fetchColumn();
    }

    public function count(): int
    {
        return (int) $this->pdo->query('SELECT COUNT(*) FROM gestion_stripe_sales')->fetchColumn();
    }
}
