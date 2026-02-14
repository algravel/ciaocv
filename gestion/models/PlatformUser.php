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
        // On suppose le schéma à jour (géré par migrate.php)
        // Colonnes: id, prenom_encrypted, name_encrypted, email_encrypted, role, plan_id, billable, active, created_at
        
        try {
            $sql = "SELECT id, prenom_encrypted, name_encrypted, email_encrypted, role, plan_id, 
                           COALESCE(billable, 1) as billable, COALESCE(active, 1) as active, created_at 
                    FROM gestion_platform_users 
                    ORDER BY created_at DESC";
            $stmt = $this->pdo->query($sql);
        } catch (Throwable $e) {
            return [];
        }

        $rows = [];
        while ($r = $stmt->fetch()) {
            $nom = $this->encryption->decrypt($r['name_encrypted'] ?? '');
            $email = $this->encryption->decrypt($r['email_encrypted'] ?? '');
            if ($nom === false || $email === false) {
                continue;
            }
            
            $prenom = '';
            if (!empty($r['prenom_encrypted'])) {
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

        $cols = ['name_encrypted', 'email_encrypted', 'role', 'plan_id', 'prenom_encrypted', 'password_hash', 'billable', 'active'];
        $vals = [$nomEnc, $emailEnc, $role, $planId, $prenomEnc, $passwordHash, $billable, $active];

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

        $sets = [
            'prenom_encrypted = ?',
            'name_encrypted = ?',
            'email_encrypted = ?',
            'role = ?',
            'plan_id = ?',
            'billable = ?',
            'active = ?'
        ];
        $vals = [$prenomEnc, $nomEnc, $emailEnc, $role, $planId, $billable, $active, $id];

        $stmt = $this->pdo->prepare('UPDATE gestion_platform_users SET ' . implode(', ', $sets) . ' WHERE id = ?');
        return $stmt->execute($vals);
    }

    public function findByEmail(string $email): ?array
    {
        $emailNorm = strtolower(trim($email));
        // On sélectionne tout
        $cols = ['id', 'name_encrypted', 'email_encrypted', 'role', 'plan_id', 'prenom_encrypted', 'password_hash', 'COALESCE(active, 1) AS active'];
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
            if (!empty($r['prenom_encrypted'])) {
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
                'password_hash' => $r['password_hash'] ?? null,
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

    /**
     * Active ou désactive les notifications pour un utilisateur.
     */
    public function setNotificationsEnabled(int $id, bool $enabled): bool
    {
        $this->ensureNotificationsColumn();
        $stmt = $this->pdo->prepare('UPDATE gestion_platform_users SET notifications_enabled = ? WHERE id = ?');
        $stmt->execute([$enabled ? 1 : 0, $id]);
        return $stmt->rowCount() > 0;
    }

    /**
     * Récupère le statut notifications_enabled pour un utilisateur.
     */
    public function getNotificationsEnabled(int $id): bool
    {
        $this->ensureNotificationsColumn();
        $stmt = $this->pdo->prepare('SELECT COALESCE(notifications_enabled, 1) AS notif FROM gestion_platform_users WHERE id = ?');
        $stmt->execute([$id]);
        $row = $stmt->fetch(PDO::FETCH_ASSOC);
        return $row ? (bool) $row['notif'] : true;
    }

    /**
     * Ajoute la colonne notifications_enabled si elle n'existe pas.
     */
    private function ensureNotificationsColumn(): void
    {
        static $checked = false;
        if ($checked) return;
        $checked = true;
        try {
            $stmt = $this->pdo->query("SHOW COLUMNS FROM gestion_platform_users LIKE 'notifications_enabled'");
            if ($stmt->rowCount() === 0) {
                $this->pdo->exec("ALTER TABLE gestion_platform_users ADD COLUMN notifications_enabled TINYINT(1) NOT NULL DEFAULT 1 AFTER active");
            }
        } catch (Throwable $e) {
            // ignorer
        }
    }

    public function resetPassword(int $id, string $plainPassword): bool
    {
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
