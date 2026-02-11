<?php
/**
 * Journal d'audit — gestion_events
 * details_encrypted peut contenir des noms
 */
class Event
{
    private PDO $pdo;
    private Encryption $encryption;

    public function __construct()
    {
        $this->pdo = Database::get();
        $this->encryption = new Encryption();
    }

    /**
     * @return array<int, array{id: int, admin_name: string, action_type: string, entity_type: string, entity_id: ?string, details: string, created_at: string}>
     */
    public function recent(int $limit = 20): array
    {
        $stmt = $this->pdo->prepare('
            SELECT e.id, e.admin_id, e.action_type, e.entity_type, e.entity_id, e.details_encrypted, e.created_at,
                   a.name_encrypted as admin_name_encrypted
            FROM gestion_events e
            LEFT JOIN gestion_admins a ON e.admin_id = a.id
            ORDER BY e.created_at DESC
            LIMIT ' . (int) $limit . '
        ');
        $stmt->execute();
        $rows = [];
        while ($r = $stmt->fetch()) {
            $details = $r['details_encrypted'] ? $this->encryption->decrypt($r['details_encrypted']) : '';
            if ($details === false) {
                $details = '(indisponible)';
            }
            $adminName = '—';
            if ($r['admin_name_encrypted']) {
                $dec = $this->encryption->decrypt($r['admin_name_encrypted']);
                if ($dec !== false) {
                    $adminName = $dec;
                }
            }
            $rows[] = [
                'id' => (int) $r['id'],
                'admin_name' => $adminName,
                'action_type' => $r['action_type'],
                'entity_type' => $r['entity_type'],
                'entity_id' => $r['entity_id'],
                'details' => $details,
                'created_at' => $r['created_at'],
            ];
        }
        return $rows;
    }

    public function log(?int $adminId, string $actionType, string $entityType, ?string $entityId, ?string $detailsPlain): void
    {
        $detailsEnc = $detailsPlain ? $this->encryption->encrypt($detailsPlain) : null;
        $stmt = $this->pdo->prepare('INSERT INTO gestion_events (admin_id, action_type, entity_type, entity_id, details_encrypted) VALUES (?, ?, ?, ?, ?)');
        $stmt->execute([$adminId, $actionType, $entityType, $entityId, $detailsEnc]);
    }

    /**
     * Événements par utilisateur plateforme (app employeur).
     * @return array<int, array{id: int, user_name: string, action_type: string, entity_type: string, details: string, created_at: string}>
     */
    public function recentByPlatformUser(int $platformUserId, int $limit = 20): array
    {
        $hasPlatformUser = $this->pdo->query("SHOW COLUMNS FROM gestion_events LIKE 'platform_user_id'")->rowCount() > 0;
        if (!$hasPlatformUser) {
            return [];
        }
        $hasActing = $this->pdo->query("SHOW COLUMNS FROM gestion_events LIKE 'acting_user_name'")->rowCount() > 0;
        $stmt = $this->pdo->prepare('
            SELECT e.id, e.action_type, e.entity_type, e.details_encrypted, e.created_at' . ($hasActing ? ', e.acting_user_name' : '') . '
            FROM gestion_events e
            WHERE e.platform_user_id = ?
            ORDER BY e.created_at DESC
            LIMIT ' . (int) $limit . '
        ');
        $stmt->execute([$platformUserId]);
        $rows = [];
        while ($r = $stmt->fetch()) {
            $details = $r['details_encrypted'] ? $this->encryption->decrypt($r['details_encrypted']) : '';
            if ($details === false) {
                $details = '(indisponible)';
            }
            $userName = ($hasActing && !empty($r['acting_user_name'])) ? $r['acting_user_name'] : '—';
            $rows[] = [
                'id' => (int) $r['id'],
                'user_name' => $userName,
                'action_type' => $r['action_type'],
                'entity_type' => $r['entity_type'],
                'details' => $details,
                'created_at' => $r['created_at'],
            ];
        }
        return $rows;
    }

    /**
     * @param string|null $actingUserName Nom de l'utilisateur (équipe) ayant effectué l'action
     */
    public function logForPlatformUser(int $platformUserId, string $actionType, string $entityType, ?string $entityId, ?string $detailsPlain, ?string $actingUserName = null): void
    {
        $hasPlatformUser = $this->pdo->query("SHOW COLUMNS FROM gestion_events LIKE 'platform_user_id'")->rowCount() > 0;
        if (!$hasPlatformUser) {
            return;
        }
        $detailsEnc = $detailsPlain ? $this->encryption->encrypt($detailsPlain) : null;
        $hasActing = $this->pdo->query("SHOW COLUMNS FROM gestion_events LIKE 'acting_user_name'")->rowCount() > 0;
        if ($hasActing) {
            $stmt = $this->pdo->prepare('INSERT INTO gestion_events (platform_user_id, acting_user_name, action_type, entity_type, entity_id, details_encrypted) VALUES (?, ?, ?, ?, ?, ?)');
            $stmt->execute([$platformUserId, $actingUserName ?: null, $actionType, $entityType, $entityId, $detailsEnc]);
        } else {
            $stmt = $this->pdo->prepare('INSERT INTO gestion_events (platform_user_id, action_type, entity_type, entity_id, details_encrypted) VALUES (?, ?, ?, ?, ?)');
            $stmt->execute([$platformUserId, $actionType, $entityType, $entityId, $detailsEnc]);
        }
    }
}
