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
    private static function formatRow(array $r): array
    {
        $map = self::statusMap();
        $s = $map[$r['status'] ?? 'active'] ?? $map['active'];
        $startDate = $r['start_date'] ?? null;
        $endDate = $r['end_date'] ?? null;
        return [
            'id' => (string) $r['id'],
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
            'evaluateurs' => [],
        ];
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
            $result[$id] = self::formatRow($r);
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
        return $r ? self::formatRow($r) : null;
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
        return $r ? self::formatRow($r) : null;
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
}
