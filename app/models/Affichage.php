<?php
/**
 * Modèle Affichage – app_affichages
 * Charge depuis la base de données (gestion). Filtre par platform_user_id.
 */
class Affichage
{
    private static function ensureDb(): void
    {
        static $loaded = false;
        if (!$loaded) {
            require_once dirname(__DIR__, 2) . '/gestion/config.php';
            $loaded = true;
        }
    }

    private static function statusMap(): array
    {
        return [
            'active' => ['label' => 'Actif', 'class' => 'status-active'],
            'paused' => ['label' => 'Non actif', 'class' => 'status-paused'],
            'closed' => ['label' => 'Archivé', 'class' => 'status-closed'],
            'expired' => ['label' => 'Expiré', 'class' => 'status-expired'],
        ];
    }

    /**
     * Formate une ligne DB en structure attendue par le frontend.
     */
    private static function formatRow(array $r, array $evaluateurs = []): array
    {
        $map = self::statusMap();
        $s = $map[$r['status'] ?? 'active'] ?? $map['active'];
        $startDate = $r['start_date'] ?? null;
        $endDate = $r['end_date'] ?? null;
        return [
            'id' => (string) $r['id'],
            'platform_user_id' => (int) ($r['platform_user_id'] ?? 0),
            'shareLongId' => $r['share_long_id'] ?? '',
            'posteId' => (string) ($r['poste_id'] ?? ''),
            'title' => $r['title'] ?? '',
            'department' => $r['department'] ?? '',
            'platform' => $r['platform'] ?? 'LinkedIn',
            'start' => $startDate ? (string) $startDate : '',
            'end' => $endDate ? (string) $endDate : '',
            'status' => $s['label'],
            'statusClass' => $s['class'],
            'views' => '0',
            'apps' => '0',
            'completed' => 0,
            'sent' => 0,
            'evaluateurs' => $evaluateurs,
        ];
    }

    /**
     * Retourne les évaluateurs assignés à un affichage.
     * @return array<array{id: int, name: string, email: string}>
     */
    public static function getEvaluateursByAffichageId(int $affichageId): array
    {
        self::ensureDb();
        $pdo = Database::get();
        $hasNotifications = $pdo->query("SHOW COLUMNS FROM app_affichage_evaluateurs LIKE 'notifications_enabled'")->rowCount() > 0;
        $cols = 'u.id, u.prenom_encrypted, u.name_encrypted, u.email_encrypted';
        if ($hasNotifications) {
            $cols .= ', ae.notifications_enabled';
        }
        $stmt = $pdo->prepare("
            SELECT {$cols}
            FROM app_affichage_evaluateurs ae
            INNER JOIN gestion_platform_users u ON u.id = ae.platform_user_id
            WHERE ae.affichage_id = ?
        ");
        $stmt->execute([$affichageId]);
        $list = [];
        $encryption = new \Encryption();
        while ($r = $stmt->fetch(PDO::FETCH_ASSOC)) {
            $nom = $encryption->decrypt($r['name_encrypted'] ?? '');
            $prenom = '';
            if (!empty($r['prenom_encrypted'])) {
                $dec = $encryption->decrypt($r['prenom_encrypted']);
                $prenom = $dec !== false ? $dec : '';
            }
            $email = $encryption->decrypt($r['email_encrypted'] ?? '');
            if ($nom === false || $email === false) {
                continue;
            }
            $fullName = trim($prenom . ' ' . $nom) ?: $nom;
            $notifications = $hasNotifications ? (bool) ($r['notifications_enabled'] ?? 1) : true;
            $list[] = ['id' => (int) $r['id'], 'name' => $fullName, 'email' => $email, 'notifications_enabled' => $notifications];
        }
        return $list;
    }

    /**
     * Retourne les destinataires pour notification nouvelle candidature.
     * Propriétaire de l'affichage + évaluateurs avec notifications activées.
     * @return array<array{email: string, name: string}>
     */
    public static function getNotificationRecipientsForAffichage(int $affichageId): array
    {
        self::ensureDb();
        $pdo = Database::get();
        $encryption = new \Encryption();

        $stmt = $pdo->prepare("
            SELECT a.platform_user_id
            FROM app_affichages a
            WHERE a.id = ?
            LIMIT 1
        ");
        $stmt->execute([$affichageId]);
        $row = $stmt->fetch(PDO::FETCH_ASSOC);
        if (!$row) {
            return [];
        }
        $ownerId = (int) $row['platform_user_id'];

        $hasNotifications = $pdo->query("SHOW COLUMNS FROM app_affichage_evaluateurs LIKE 'notifications_enabled'")->rowCount() > 0;
        $recipients = [];
        $seenEmails = [];

        $platformUserStmt = $pdo->prepare("SELECT id, prenom_encrypted, name_encrypted, email_encrypted FROM gestion_platform_users WHERE id = ?");
        $platformUserStmt->execute([$ownerId]);
        $owner = $platformUserStmt->fetch(PDO::FETCH_ASSOC);
        if ($owner) {
            $email = $encryption->decrypt($owner['email_encrypted'] ?? '');
            $nom = $encryption->decrypt($owner['name_encrypted'] ?? '');
            $prenom = '';
            if (!empty($owner['prenom_encrypted'])) {
                $dec = $encryption->decrypt($owner['prenom_encrypted']);
                $prenom = $dec !== false ? $dec : '';
            }
            if ($email !== false && $nom !== false && $email !== '') {
                $emailNorm = strtolower(trim($email));
                $seenEmails[$emailNorm] = true;
                $recipients[] = ['email' => $email, 'name' => trim($prenom . ' ' . $nom) ?: $nom];
            }
        }

        $cols = 'u.id, u.prenom_encrypted, u.name_encrypted, u.email_encrypted';
        if ($hasNotifications) {
            $cols .= ', ae.notifications_enabled';
        }
        $stmt = $pdo->prepare("
            SELECT {$cols}
            FROM app_affichage_evaluateurs ae
            INNER JOIN gestion_platform_users u ON u.id = ae.platform_user_id
            WHERE ae.affichage_id = ?
        ");
        $stmt->execute([$affichageId]);
        while ($r = $stmt->fetch(PDO::FETCH_ASSOC)) {
            if ($hasNotifications && empty($r['notifications_enabled'])) {
                continue;
            }
            $email = $encryption->decrypt($r['email_encrypted'] ?? '');
            $nom = $encryption->decrypt($r['name_encrypted'] ?? '');
            $prenom = '';
            if (!empty($r['prenom_encrypted'])) {
                $dec = $encryption->decrypt($r['prenom_encrypted']);
                $prenom = $dec !== false ? $dec : '';
            }
            if ($email === false || $nom === false || $email === '') {
                continue;
            }
            $emailNorm = strtolower(trim($email));
            if (!empty($seenEmails[$emailNorm])) {
                continue;
            }
            $seenEmails[$emailNorm] = true;
            $recipients[] = ['email' => $email, 'name' => trim($prenom . ' ' . $nom) ?: $nom];
        }

        return $recipients;
    }

    /**
     * @param int|null $platformUserId Filtrer par entreprise (utilisateur plateforme)
     * @return array<string, array<string, mixed>> Indexé par id
     */
    public static function getAll(?int $platformUserId = null): array
    {
        if ($platformUserId === null || $platformUserId <= 0) {
            return [];
        }
        self::ensureDb();
        $pdo = Database::get();
        $stmt = $pdo->prepare("
            SELECT a.id, a.platform_user_id, a.poste_id, a.share_long_id, a.platform,
                   a.start_date, a.end_date, a.status, a.created_at,
                   p.title, p.department
            FROM app_affichages a
            INNER JOIN app_postes p ON p.id = a.poste_id AND p.platform_user_id = a.platform_user_id
            WHERE a.platform_user_id = ?
            ORDER BY a.created_at DESC
        ");
        $stmt->execute([$platformUserId]);
        $result = [];
        while ($r = $stmt->fetch(PDO::FETCH_ASSOC)) {
            $id = (string) $r['id'];
            $evaluateurs = self::getEvaluateursByAffichageId((int) $r['id']);
            $result[$id] = self::formatRow($r, $evaluateurs);
        }
        return $result;
    }

    /**
     * Affichages accessibles par un évaluateur (ceux auxquels il est invité).
     * @param int $evaluateurPlatformUserId ID de l'évaluateur
     * @return array<string, array<string, mixed>> Indexé par id
     */
    public static function getAllForEvaluateur(int $evaluateurPlatformUserId): array
    {
        self::ensureDb();
        $pdo = Database::get();
        $stmt = $pdo->prepare("
            SELECT a.id, a.platform_user_id, a.poste_id, a.share_long_id, a.platform,
                   a.start_date, a.end_date, a.status, a.created_at,
                   p.title, p.department
            FROM app_affichages a
            INNER JOIN app_postes p ON p.id = a.poste_id AND p.platform_user_id = a.platform_user_id
            INNER JOIN app_affichage_evaluateurs ae ON ae.affichage_id = a.id AND ae.platform_user_id = ?
            ORDER BY a.created_at DESC
        ");
        $stmt->execute([$evaluateurPlatformUserId]);
        $result = [];
        while ($r = $stmt->fetch(PDO::FETCH_ASSOC)) {
            $id = (string) $r['id'];
            $evaluateurs = self::getEvaluateursByAffichageId((int) $r['id']);
            $result[$id] = self::formatRow($r, $evaluateurs);
        }
        return $result;
    }

    /**
     * Retrouver un affichage par son identifiant.
     */
    public static function find(string $id, ?int $platformUserId = null): ?array
    {
        if ($platformUserId === null || $platformUserId <= 0) {
            return null;
        }
        self::ensureDb();
        $pdo = Database::get();
        $stmt = $pdo->prepare("
            SELECT a.id, a.platform_user_id, a.poste_id, a.share_long_id, a.platform,
                   a.start_date, a.end_date, a.status, a.created_at,
                   p.title, p.department
            FROM app_affichages a
            INNER JOIN app_postes p ON p.id = a.poste_id AND p.platform_user_id = a.platform_user_id
            WHERE a.id = ? AND a.platform_user_id = ?
            LIMIT 1
        ");
        $stmt->execute([$id, $platformUserId]);
        $r = $stmt->fetch(PDO::FETCH_ASSOC);
        if (!$r) {
            return null;
        }
        $evaluateurs = self::getEvaluateursByAffichageId((int) $r['id']);
        return self::formatRow($r, $evaluateurs);
    }

    /**
     * Retrouver un affichage par son shareLongId (lien rec).
     * @return array|null Données affichage ou null
     */
    public static function findByShareLongId(string $longId): ?array
    {
        self::ensureDb();
        $pdo = Database::get();
        $stmt = $pdo->prepare("
            SELECT a.id, a.platform_user_id, a.poste_id, a.share_long_id, a.platform,
                   a.start_date, a.end_date, a.status, a.created_at,
                   p.title, p.department
            FROM app_affichages a
            INNER JOIN app_postes p ON p.id = a.poste_id AND p.platform_user_id = a.platform_user_id
            WHERE a.share_long_id = ?
            LIMIT 1
        ");
        $stmt->execute([$longId]);
        $r = $stmt->fetch(PDO::FETCH_ASSOC);
        if (!$r) {
            return null;
        }
        $evaluateurs = self::getEvaluateursByAffichageId((int) $r['id']);
        return self::formatRow($r, $evaluateurs);
    }

    /**
     * Vérifie qu'un évaluateur a accès à un affichage et le retourne.
     */
    public static function findForEvaluateur(string $id, int $evaluateurPlatformUserId): ?array
    {
        self::ensureDb();
        $pdo = Database::get();
        $stmt = $pdo->prepare("
            SELECT a.id, a.platform_user_id, a.poste_id, a.share_long_id, a.platform,
                   a.start_date, a.end_date, a.status, a.created_at,
                   p.title, p.department
            FROM app_affichages a
            INNER JOIN app_postes p ON p.id = a.poste_id AND p.platform_user_id = a.platform_user_id
            INNER JOIN app_affichage_evaluateurs ae ON ae.affichage_id = a.id AND ae.platform_user_id = ?
            WHERE a.id = ?
            LIMIT 1
        ");
        $stmt->execute([$evaluateurPlatformUserId, $id]);
        $r = $stmt->fetch(PDO::FETCH_ASSOC);
        if (!$r) {
            return null;
        }
        $evaluateurs = self::getEvaluateursByAffichageId((int) $r['id']);
        return self::formatRow($r, $evaluateurs);
    }

    /**
     * Données poste pour la page rec (candidat), à partir du longId.
     * Source unique : affichage → poste lié (questions, durée, lieu).
     * @return array|null tableau avec title, department, location, description, questions, recordDuration
     */
    public static function getPosteByLongId(string $longId): ?array
    {
        $affichage = self::findByShareLongId($longId);
        if (!$affichage) {
            return null;
        }
        $posteId = $affichage['posteId'] ?? null;
        if (!$posteId) {
            return [
                'title' => $affichage['title'] ?? '',
                'department' => $affichage['department'] ?? '',
                'location' => '',
                'description' => '',
                'questions' => [],
                'recordDuration' => 3,
            ];
        }
        self::ensureDb();
        $pdo = Database::get();
        $stmt = $pdo->prepare('SELECT * FROM app_postes WHERE id = ? LIMIT 1');
        $stmt->execute([$posteId]);
        $poste = $stmt->fetch(PDO::FETCH_ASSOC);
        if (!$poste) {
            return [
                'title' => $affichage['title'] ?? '',
                'department' => $affichage['department'] ?? '',
                'location' => '',
                'description' => '',
                'questions' => [],
                'recordDuration' => 3,
            ];
        }
        $questions = $poste['questions'] ?? null;
        if (is_string($questions)) {
            $questions = json_decode($questions, true) ?: [];
        }
        return [
            'title' => $poste['title'] ?? '',
            'department' => $poste['department'] ?? '',
            'location' => $poste['location'] ?? '',
            'description' => $poste['description'] ?? '',
            'questions' => is_array($questions) ? $questions : [],
            'recordDuration' => (int) ($poste['record_duration'] ?? 3) ?: 3,
        ];
    }

    /**
     * Créer un nouvel affichage.
     * @return array|null Affichage formaté ou null si erreur
     */
    public static function create(int $platformUserId, array $data): ?array
    {
        self::ensureDb();
        $pdo = Database::get();
        $posteId = (int) ($data['poste_id'] ?? 0);
        if ($posteId <= 0) {
            return null;
        }
        // Vérifier que le poste appartient au platform_user
        $stmt = $pdo->prepare('SELECT id FROM app_postes WHERE id = ? AND platform_user_id = ? LIMIT 1');
        $stmt->execute([$posteId, $platformUserId]);
        if (!$stmt->fetch()) {
            return null;
        }
        $shareLongId = bin2hex(random_bytes(8));
        $platform = trim($data['platform'] ?? '') ?: 'LinkedIn';
        $status = in_array($data['status'] ?? '', ['active', 'paused', 'closed'], true) ? $data['status'] : 'active';
        $stmt = $pdo->prepare('
            INSERT INTO app_affichages (platform_user_id, poste_id, share_long_id, platform, status)
            VALUES (?, ?, ?, ?, ?)
        ');
        $stmt->execute([$platformUserId, $posteId, $shareLongId, $platform, $status]);
        $id = (int) $pdo->lastInsertId();
        return $id ? self::find((string) $id, $platformUserId) : null;
    }

    /**
     * Mettre à jour le statut d'un affichage.
     * @param string $id ID affichage
     * @param int $platformUserId Utilisateur (sécu)
     * @param string $status 'active' | 'paused' | 'closed'
     */
    public static function updateStatus(string $id, int $platformUserId, string $status): bool
    {
        if (!in_array($status, ['active', 'paused', 'closed'], true)) {
            return false;
        }
        self::ensureDb();
        $pdo = Database::get();
        $stmt = $pdo->prepare('UPDATE app_affichages SET status = ? WHERE id = ? AND platform_user_id = ?');
        $stmt->execute([$status, $id, $platformUserId]);
        return $stmt->rowCount() > 0;
    }

    /**
     * Supprimer un affichage.
     */
    public static function delete(string $id, int $platformUserId): bool
    {
        self::ensureDb();
        $pdo = Database::get();
        $stmt = $pdo->prepare("DELETE FROM app_affichages WHERE id = ? AND platform_user_id = ?");
        $stmt->execute([$id, $platformUserId]);
        return $stmt->rowCount() > 0;
    }

    /**
     * Associe un évaluateur (platform_user_id) à un affichage.
     */
    public static function addEvaluateur(int $affichageId, int $platformUserId): bool
    {
        self::ensureDb();
        $pdo = Database::get();
        try {
            $stmt = $pdo->prepare('INSERT IGNORE INTO app_affichage_evaluateurs (affichage_id, platform_user_id) VALUES (?, ?)');
            $stmt->execute([$affichageId, $platformUserId]);
            return $stmt->rowCount() > 0;
        } catch (Throwable $e) {
            return false;
        }
    }

    /**
     * Active ou désactive les notifications pour un évaluateur d'un affichage.
     */
    public static function updateEvaluateurNotifications(int $affichageId, int $platformUserId, bool $enabled): bool
    {
        self::ensureDb();
        $pdo = Database::get();
        $stmt = $pdo->query("SHOW COLUMNS FROM app_affichage_evaluateurs LIKE 'notifications_enabled'");
        if ($stmt->rowCount() === 0) {
            return false;
        }
        $stmt = $pdo->prepare('UPDATE app_affichage_evaluateurs SET notifications_enabled = ? WHERE affichage_id = ? AND platform_user_id = ?');
        $stmt->execute([$enabled ? 1 : 0, $affichageId, $platformUserId]);
        return $stmt->rowCount() > 0;
    }

    /**
     * Retire un évaluateur d'un affichage.
     */
    public static function removeEvaluateur(int $affichageId, int $platformUserId): bool
    {
        self::ensureDb();
        $pdo = Database::get();
        $stmt = $pdo->prepare('DELETE FROM app_affichage_evaluateurs WHERE affichage_id = ? AND platform_user_id = ?');
        $stmt->execute([$affichageId, $platformUserId]);
        return $stmt->rowCount() > 0;
    }
}
