<?php
/**
 * CRUD admins — authentification gestion_admins
 * Chiffrement email/nom via Encryption
 */
class Admin
{
    private PDO $pdo;
    private Encryption $encryption;

    public function __construct()
    {
        $this->pdo = Database::get();
        $this->encryption = new Encryption();
    }

    public function findById(int $id): ?array
    {
        $stmt = $this->pdo->prepare('SELECT id, email_encrypted, password_hash, name_encrypted, role FROM gestion_admins WHERE id = ? AND deleted_at IS NULL');
        $stmt->execute([$id]);
        $row = $stmt->fetch();
        if (!$row) {
            return null;
        }
        $emailDec = $this->encryption->decrypt($row['email_encrypted']);
        $nameDec = $this->encryption->decrypt($row['name_encrypted']);
        if ($emailDec === false || $nameDec === false) {
            return null;
        }
        return [
            'id' => (int) $row['id'],
            'email' => $emailDec,
            'password_hash' => $row['password_hash'],
            'name' => $nameDec,
            'role' => $row['role'],
        ];
    }

    public function findByEmail(string $email): ?array
    {
        $hash = Encryption::hashEmailForSearch($email);
        $stmt = $this->pdo->prepare('SELECT id, email_encrypted, password_hash, name_encrypted, role FROM gestion_admins WHERE email_search_hash = ? AND deleted_at IS NULL');
        $stmt->execute([$hash]);
        $row = $stmt->fetch();
        if (!$row) {
            return null;
        }
        $emailDec = $this->encryption->decrypt($row['email_encrypted']);
        $nameDec = $this->encryption->decrypt($row['name_encrypted']);
        if ($emailDec === false || $nameDec === false) {
            return null;
        }
        return [
            'id' => (int) $row['id'],
            'email' => $emailDec,
            'password_hash' => $row['password_hash'],
            'name' => $nameDec,
            'role' => $row['role'],
        ];
    }

    /**
     * @return array<int, array{id: int, email: string, name: string, role: string, created_at: string}>
     */
    public function all(): array
    {
        $stmt = $this->pdo->query('SELECT id, email_encrypted, name_encrypted, role, created_at FROM gestion_admins WHERE deleted_at IS NULL ORDER BY created_at ASC');
        $rows = [];
        while ($r = $stmt->fetch()) {
            $email = $this->encryption->decrypt($r['email_encrypted']);
            $name = $this->encryption->decrypt($r['name_encrypted']);
            if ($email === false) {
                $email = '(clé de chiffrement invalide)';
            }
            if ($name === false) {
                $name = '(clé de chiffrement invalide)';
            }
            $rows[] = [
                'id' => (int) $r['id'],
                'email' => $email,
                'name' => $name,
                'role' => $r['role'],
                'created_at' => $r['created_at'],
            ];
        }
        return $rows;
    }

    public function verifyPassword(string $plainPassword, string $hash): bool
    {
        return password_verify($plainPassword, $hash);
    }

    public function create(string $email, string $plainPassword, string $name, string $role = 'admin'): int
    {
        $email = strtolower(trim($email));
        $hash = Encryption::hashEmailForSearch($email);
        $emailEnc = $this->encryption->encrypt($email);
        $nameEnc = $this->encryption->encrypt(trim($name));
        $passHash = password_hash($plainPassword, PASSWORD_DEFAULT);
        $stmt = $this->pdo->prepare('INSERT INTO gestion_admins (email_search_hash, email_encrypted, password_hash, name_encrypted, role) VALUES (?, ?, ?, ?, ?)');
        $stmt->execute([$hash, $emailEnc, $passHash, $nameEnc, $role]);
        return (int) $this->pdo->lastInsertId();
    }

    public function softDelete(int $id): bool
    {
        $stmt = $this->pdo->prepare('UPDATE gestion_admins SET deleted_at = NOW() WHERE id = ? AND deleted_at IS NULL');
        $stmt->execute([$id]);
        return $stmt->rowCount() > 0;
    }

    public function update(int $id, string $name, string $email, string $role = 'admin'): bool
    {
        $email = strtolower(trim($email));
        $hash = Encryption::hashEmailForSearch($email);
        $existing = $this->findById($id);
        if (!$existing) {
            return false;
        }
        if ($email !== strtolower($existing['email'])) {
            $stmt = $this->pdo->prepare('SELECT id FROM gestion_admins WHERE email_search_hash = ? AND deleted_at IS NULL AND id != ?');
            $stmt->execute([$hash, $id]);
            if ($stmt->fetch()) {
                return false;
            }
        }
        $emailEnc = $this->encryption->encrypt($email);
        $nameEnc = $this->encryption->encrypt(trim($name));
        $stmt = $this->pdo->prepare('UPDATE gestion_admins SET email_search_hash = ?, email_encrypted = ?, name_encrypted = ?, role = ? WHERE id = ? AND deleted_at IS NULL');
        $stmt->execute([$hash, $emailEnc, $nameEnc, $role, $id]);
        return $stmt->rowCount() > 0;
    }

    public function resetPassword(int $id, string $plainPassword): bool
    {
        $passHash = password_hash($plainPassword, PASSWORD_DEFAULT);
        $stmt = $this->pdo->prepare('UPDATE gestion_admins SET password_hash = ? WHERE id = ? AND deleted_at IS NULL');
        $stmt->execute([$passHash, $id]);
        return $stmt->rowCount() > 0;
    }
}
