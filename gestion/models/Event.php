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
            LIMIT ?
        ');
        $stmt->execute([$limit]);
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
}
