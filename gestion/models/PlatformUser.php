<?php
/**
 * CRUD utilisateurs plateforme — gestion_platform_users
 * Chiffrement name_encrypted, email_encrypted
 */
class PlatformUser
{
    private PDO $pdo;
    private Encryption $encryption;

    public function __construct()
    {
        $this->pdo = Database::get();
        $this->encryption = new Encryption();
    }

    /**
     * @return array<int, array{id: int, name: string, email: string, role: string, plan_id: ?int, created_at: string}>
     */
    public function all(): array
    {
        $stmt = $this->pdo->query('SELECT id, name_encrypted, email_encrypted, role, plan_id, created_at FROM gestion_platform_users ORDER BY created_at DESC');
        $rows = [];
        while ($r = $stmt->fetch()) {
            $name = $this->encryption->decrypt($r['name_encrypted']);
            $email = $this->encryption->decrypt($r['email_encrypted']);
            if ($name === false || $email === false) {
                continue;
            }
            $rows[] = [
                'id' => (int) $r['id'],
                'name' => $name,
                'email' => $email,
                'role' => $r['role'],
                'plan_id' => $r['plan_id'] ? (int) $r['plan_id'] : null,
                'created_at' => $r['created_at'],
            ];
        }
        return $rows;
    }

    public function count(): int
    {
        return (int) $this->pdo->query('SELECT COUNT(*) FROM gestion_platform_users')->fetchColumn();
    }
}
