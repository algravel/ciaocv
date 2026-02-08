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

    public function all(): array
    {
        try {
            return $this->fetchAllUsers();
        } catch (Throwable $e) {
            return [];
        }
    }

    /**
     * @return array<int, array{id: int, prenom: string, nom: string, name: string, email: string, role: string, plan_id: ?int, billable: bool, active: bool, created_at: string}>
     */
    private function fetchAllUsers(): array
    {
        $hasPrenom = $this->pdo->query("SHOW COLUMNS FROM gestion_platform_users LIKE 'prenom_encrypted'")->rowCount() > 0;
        $hasBillable = $this->pdo->query("SHOW COLUMNS FROM gestion_platform_users LIKE 'billable'")->rowCount() > 0;
        $hasActive = $this->pdo->query("SHOW COLUMNS FROM gestion_platform_users LIKE 'active'")->rowCount() > 0;

        $cols = ['id', 'name_encrypted', 'email_encrypted', 'role', 'plan_id', 'created_at'];
        if ($hasPrenom) {
            array_splice($cols, 1, 0, ['prenom_encrypted']);
        }
        if ($hasBillable) {
            $cols[] = 'COALESCE(billable, 1) AS billable';
        }
        if ($hasActive) {
            $cols[] = 'COALESCE(active, 1) AS active';
        }
        $colsStr = implode(', ', $cols);

        try {
            $stmt = $this->pdo->query("SELECT {$colsStr} FROM gestion_platform_users ORDER BY created_at DESC");
        } catch (Throwable $e) {
            $hasPrenom = false;
            $stmt = $this->pdo->query('SELECT id, name_encrypted, email_encrypted, role, plan_id, created_at FROM gestion_platform_users ORDER BY created_at DESC');
        }

        $rows = [];
        while ($r = $stmt->fetch()) {
            $nom = $this->encryption->decrypt($r['name_encrypted'] ?? '');
            $email = $this->encryption->decrypt($r['email_encrypted'] ?? '');
            if ($nom === false || $email === false) {
                continue;
            }
            $prenom = '';
            if ($hasPrenom && !empty($r['prenom_encrypted'])) {
                $dec = $this->encryption->decrypt($r['prenom_encrypted']);
                $prenom = $dec !== false ? $dec : '';
            }
            $fullName = trim($prenom . ' ' . $nom);
            $rows[] = [
                'id' => (int) $r['id'],
                'prenom' => $prenom,
                'nom' => $nom,
                'name' => $fullName ?: $nom,
                'email' => $email,
                'role' => $r['role'],
                'plan_id' => $r['plan_id'] ? (int) $r['plan_id'] : null,
                'billable' => (bool) ($r['billable'] ?? 1),
                'active' => (bool) ($r['active'] ?? 1),
                'created_at' => $r['created_at'],
            ];
        }
        return $rows;
    }

    /**
     * @param array{prenom: string, nom: string, email: string, role: string, plan_id: ?int, billable: bool, active: bool, password?: string} $data
     */
    public function create(array $data): int
    {
        $prenom = trim($data['prenom'] ?? '');
        $nom = trim($data['nom'] ?? '');
        $nom = $nom !== '' ? $nom : trim($data['name'] ?? '');
        if ($nom === '') {
            throw new InvalidArgumentException('Le nom est requis.');
        }
        $prenomEnc = $prenom !== '' ? $this->encryption->encrypt($prenom) : null;
        $nomEnc = $this->encryption->encrypt($nom);
        $emailEnc = $this->encryption->encrypt(trim($data['email']));
        $role = in_array($data['role'] ?? '', ['client', 'evaluateur'], true) ? $data['role'] : 'client';
        $planId = isset($data['plan_id']) && $data['plan_id'] > 0 ? (int) $data['plan_id'] : null;
        $billable = !empty($data['billable']) ? 1 : 0;
        $active = !empty($data['active']) ? 1 : 0;
        $passwordHash = null;
        if (!empty($data['password'])) {
            $passwordHash = password_hash($data['password'], PASSWORD_DEFAULT);
        }

        $hasPrenom = $this->pdo->query("SHOW COLUMNS FROM gestion_platform_users LIKE 'prenom_encrypted'")->rowCount() > 0;
        $hasBillable = $this->pdo->query("SHOW COLUMNS FROM gestion_platform_users LIKE 'billable'")->rowCount() > 0;
        $hasActive = $this->pdo->query("SHOW COLUMNS FROM gestion_platform_users LIKE 'active'")->rowCount() > 0;
        $hasPasswordHash = $this->pdo->query("SHOW COLUMNS FROM gestion_platform_users LIKE 'password_hash'")->rowCount() > 0;

        $cols = ['name_encrypted', 'email_encrypted', 'role', 'plan_id'];
        $vals = [$nomEnc, $emailEnc, $role, $planId];
        if ($hasPrenom) {
            array_splice($cols, 0, 0, ['prenom_encrypted']);
            array_splice($vals, 0, 0, [$prenomEnc]);
        }
        if ($hasPasswordHash && $passwordHash !== null) {
            $cols[] = 'password_hash';
            $vals[] = $passwordHash;
        }
        if ($hasBillable) {
            $cols[] = 'billable';
            $vals[] = $billable;
        }
        if ($hasActive) {
            $cols[] = 'active';
            $vals[] = $active;
        }
        $placeholders = implode(', ', array_fill(0, count($vals), '?'));
        $stmt = $this->pdo->prepare('INSERT INTO gestion_platform_users (' . implode(', ', $cols) . ') VALUES (' . $placeholders . ')');
        $stmt->execute($vals);
        return (int) $this->pdo->lastInsertId();
    }

    /**
     * @param array{prenom: string, nom: string, email: string, role: string, plan_id: ?int, billable: bool, active: bool} $data
     */
    public function update(int $id, array $data): bool
    {
        $prenom = trim($data['prenom'] ?? '');
        $nom = trim($data['nom'] ?? '');
        $nom = $nom !== '' ? $nom : trim($data['name'] ?? '');
        if ($nom === '') {
            throw new InvalidArgumentException('Le nom est requis.');
        }
        $prenomEnc = $prenom !== '' ? $this->encryption->encrypt($prenom) : null;
        $nomEnc = $this->encryption->encrypt($nom);
        $emailEnc = $this->encryption->encrypt(trim($data['email']));
        $role = in_array($data['role'] ?? '', ['client', 'evaluateur'], true) ? $data['role'] : 'client';
        $planId = isset($data['plan_id']) && $data['plan_id'] > 0 ? (int) $data['plan_id'] : null;
        $billable = !empty($data['billable']) ? 1 : 0;
        $active = !empty($data['active']) ? 1 : 0;

        $hasPrenom = $this->pdo->query("SHOW COLUMNS FROM gestion_platform_users LIKE 'prenom_encrypted'")->rowCount() > 0;
        $hasBillable = $this->pdo->query("SHOW COLUMNS FROM gestion_platform_users LIKE 'billable'")->rowCount() > 0;
        $hasActive = $this->pdo->query("SHOW COLUMNS FROM gestion_platform_users LIKE 'active'")->rowCount() > 0;

        $sets = [];
        $vals = [];
        if ($hasPrenom) {
            $sets[] = 'prenom_encrypted = ?';
            $vals[] = $prenomEnc;
        }
        $sets = array_merge($sets, ['name_encrypted = ?', 'email_encrypted = ?', 'role = ?', 'plan_id = ?']);
        $vals = array_merge($vals, [$nomEnc, $emailEnc, $role, $planId]);
        if ($hasBillable) {
            $sets[] = 'billable = ?';
            $vals[] = $billable;
        }
        if ($hasActive) {
            $sets[] = 'active = ?';
            $vals[] = $active;
        }
        $vals[] = $id;
        $stmt = $this->pdo->prepare('UPDATE gestion_platform_users SET ' . implode(', ', $sets) . ' WHERE id = ?');
        return $stmt->execute($vals);
    }

    public function findByEmail(string $email): ?array
    {
        $emailNorm = strtolower(trim($email));
        $hasPrenom = $this->pdo->query("SHOW COLUMNS FROM gestion_platform_users LIKE 'prenom_encrypted'")->rowCount() > 0;
        $hasPasswordHash = $this->pdo->query("SHOW COLUMNS FROM gestion_platform_users LIKE 'password_hash'")->rowCount() > 0;
        $cols = ['id', 'name_encrypted', 'email_encrypted', 'role', 'plan_id'];
        if ($hasPrenom) {
            $cols[] = 'prenom_encrypted';
        }
        if ($hasPasswordHash) {
            $cols[] = 'password_hash';
        }
        $cols[] = 'COALESCE(active, 1) AS active';
        $colsStr = implode(', ', $cols);
        try {
            $stmt = $this->pdo->query("SELECT {$colsStr} FROM gestion_platform_users");
        } catch (Throwable $e) {
            return null;
        }
        while ($r = $stmt->fetch()) {
            $emailDec = $this->encryption->decrypt($r['email_encrypted'] ?? '');
            if ($emailDec === false || strtolower(trim($emailDec)) !== $emailNorm) {
                continue;
            }
            $nom = $this->encryption->decrypt($r['name_encrypted'] ?? '');
            $prenom = '';
            if ($hasPrenom && !empty($r['prenom_encrypted'])) {
                $dec = $this->encryption->decrypt($r['prenom_encrypted']);
                $prenom = $dec !== false ? $dec : '';
            }
            $fullName = trim($prenom . ' ' . ($nom !== false ? $nom : ''));
            return [
                'id' => (int) $r['id'],
                'prenom' => $prenom,
                'nom' => $nom !== false ? $nom : '',
                'name' => $fullName ?: ($nom !== false ? $nom : ''),
                'email' => $emailDec,
                'role' => $r['role'],
                'plan_id' => $r['plan_id'] ? (int) $r['plan_id'] : null,
                'active' => (bool) ($r['active'] ?? 1),
                'password_hash' => $hasPasswordHash ? ($r['password_hash'] ?? null) : null,
            ];
        }
        return null;
    }

    public function verifyPassword(string $plainPassword, string $hash): bool
    {
        return password_verify($plainPassword, $hash);
    }

    /**
     * @return array{id: int, prenom: string, nom: string, name: string, email: string, role: string, plan_id: ?int, billable: bool, created_at: string}|null
     */
    public function findById(int $id): ?array
    {
        $users = $this->fetchAllUsers();
        foreach ($users as $u) {
            if ((int) $u['id'] === $id) {
                return $u;
            }
        }
        return null;
    }

    public function resetPassword(int $id, string $plainPassword): bool
    {
        $hasPasswordHash = $this->pdo->query("SHOW COLUMNS FROM gestion_platform_users LIKE 'password_hash'")->rowCount() > 0;
        if (!$hasPasswordHash) {
            return false;
        }
        $passHash = password_hash($plainPassword, PASSWORD_DEFAULT);
        $stmt = $this->pdo->prepare('UPDATE gestion_platform_users SET password_hash = ? WHERE id = ?');
        return $stmt->execute([$passHash, $id]);
    }

    public function delete(int $id): bool
    {
        $stmt = $this->pdo->prepare('DELETE FROM gestion_platform_users WHERE id = ?');
        return $stmt->execute([$id]);
    }

    public function count(): int
    {
        try {
            return (int) $this->pdo->query('SELECT COUNT(*) FROM gestion_platform_users')->fetchColumn();
        } catch (Throwable $e) {
            return 0;
        }
    }
}
