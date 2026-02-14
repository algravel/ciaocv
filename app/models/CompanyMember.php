<?php
/**
 * Membres avec accès entreprise (même périmètre que le propriétaire).
 * Table app_company_members : owner_platform_user_id, member_platform_user_id.
 */
class CompanyMember
{
    private static bool $tableChecked = false;

    /**
     * Crée la table app_company_members si elle n'existe pas encore.
     */
    private static function ensureTable(): void
    {
        if (self::$tableChecked) return;
        self::$tableChecked = true;
        try {
            require_once dirname(__DIR__, 2) . '/gestion/config.php';
            $pdo = Database::get();
            $stmt = $pdo->query("SHOW TABLES LIKE 'app_company_members'");
            if ($stmt->rowCount() === 0) {
                $pdo->exec("CREATE TABLE app_company_members (
                    id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
                    owner_platform_user_id INT UNSIGNED NOT NULL,
                    member_platform_user_id INT UNSIGNED NOT NULL,
                    created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
                    UNIQUE KEY idx_owner_member (owner_platform_user_id, member_platform_user_id),
                    INDEX idx_owner (owner_platform_user_id),
                    INDEX idx_member (member_platform_user_id),
                    FOREIGN KEY (owner_platform_user_id) REFERENCES gestion_platform_users(id) ON DELETE CASCADE,
                    FOREIGN KEY (member_platform_user_id) REFERENCES gestion_platform_users(id) ON DELETE CASCADE
                ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci");
            }
        } catch (Throwable $e) {
            // ignorer
        }
    }

    /**
     * Liste des member_platform_user_id pour un propriétaire.
     * @return array<int>
     */
    public static function getMemberIdsByOwner(int $ownerPlatformUserId): array
    {
        try {
            self::ensureTable();
            $pdo = Database::get();
            $stmt = $pdo->prepare('SELECT member_platform_user_id FROM app_company_members WHERE owner_platform_user_id = ? ORDER BY created_at ASC');
            $stmt->execute([$ownerPlatformUserId]);
            $ids = [];
            while ($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
                $ids[] = (int) $row['member_platform_user_id'];
            }
            return $ids;
        } catch (Throwable $e) {
            return [];
        }
    }

    /**
     * Propriétaire (owner) pour un membre, s'il est uniquement membre (pas propriétaire lui-même).
     * @return int|null owner_platform_user_id ou null
     */
    public static function getOwnerForMember(int $memberPlatformUserId): ?int
    {
        try {
            self::ensureTable();
            $pdo = Database::get();
            $stmt = $pdo->prepare('SELECT owner_platform_user_id FROM app_company_members WHERE member_platform_user_id = ? LIMIT 1');
            $stmt->execute([$memberPlatformUserId]);
            $row = $stmt->fetch(PDO::FETCH_ASSOC);
            return $row ? (int) $row['owner_platform_user_id'] : null;
        } catch (Throwable $e) {
            return null;
        }
    }

    /**
     * Vérifie si l'utilisateur est membre (accès entreprise) d'au moins une entreprise.
     */
    public static function isMemberOfAny(int $memberPlatformUserId): bool
    {
        return self::getOwnerForMember($memberPlatformUserId) !== null;
    }

    /**
     * Ajoute un membre à l'équipe du propriétaire.
     */
    public static function add(int $ownerPlatformUserId, int $memberPlatformUserId): bool
    {
        if ($ownerPlatformUserId === $memberPlatformUserId) {
            return false;
        }
        try {
            self::ensureTable();
            $pdo = Database::get();
            $stmt = $pdo->prepare('INSERT IGNORE INTO app_company_members (owner_platform_user_id, member_platform_user_id) VALUES (?, ?)');
            return $stmt->execute([$ownerPlatformUserId, $memberPlatformUserId]);
        } catch (Throwable $e) {
            return false;
        }
    }

    /**
     * Retire un membre de l'équipe du propriétaire.
     */
    public static function remove(int $ownerPlatformUserId, int $memberPlatformUserId): bool
    {
        try {
            self::ensureTable();
            $pdo = Database::get();
            $stmt = $pdo->prepare('DELETE FROM app_company_members WHERE owner_platform_user_id = ? AND member_platform_user_id = ?');
            return $stmt->execute([$ownerPlatformUserId, $memberPlatformUserId]);
        } catch (Throwable $e) {
            return false;
        }
    }

    /**
     * Active ou désactive les notifications pour un membre.
     */
    public static function toggleNotifications(int $ownerPlatformUserId, int $memberPlatformUserId, bool $enabled): bool
    {
        try {
            self::ensureTable();
            self::ensureNotificationsColumn();
            $pdo = Database::get();
            $stmt = $pdo->prepare('UPDATE app_company_members SET notifications_enabled = ? WHERE owner_platform_user_id = ? AND member_platform_user_id = ?');
            $stmt->execute([$enabled ? 1 : 0, $ownerPlatformUserId, $memberPlatformUserId]);
            return $stmt->rowCount() > 0;
        } catch (Throwable $e) {
            return false;
        }
    }

    /**
     * Récupère les membres avec leur statut de notifications.
     * @return array<array{member_platform_user_id: int, notifications_enabled: bool}>
     */
    public static function getMembersWithNotifications(int $ownerPlatformUserId): array
    {
        try {
            self::ensureTable();
            self::ensureNotificationsColumn();
            $pdo = Database::get();
            $stmt = $pdo->prepare('SELECT member_platform_user_id, notifications_enabled FROM app_company_members WHERE owner_platform_user_id = ? ORDER BY created_at ASC');
            $stmt->execute([$ownerPlatformUserId]);
            $members = [];
            while ($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
                $members[] = [
                    'member_platform_user_id' => (int) $row['member_platform_user_id'],
                    'notifications_enabled' => (bool) ($row['notifications_enabled'] ?? 1),
                ];
            }
            return $members;
        } catch (Throwable $e) {
            return [];
        }
    }

    /**
     * Ajoute la colonne notifications_enabled si elle n'existe pas.
     */
    private static function ensureNotificationsColumn(): void
    {
        static $checked = false;
        if ($checked) return;
        $checked = true;
        try {
            $pdo = Database::get();
            $stmt = $pdo->query("SHOW COLUMNS FROM app_company_members LIKE 'notifications_enabled'");
            if ($stmt->rowCount() === 0) {
                $pdo->exec("ALTER TABLE app_company_members ADD COLUMN notifications_enabled TINYINT(1) NOT NULL DEFAULT 1 AFTER member_platform_user_id");
            }
        } catch (Throwable $e) {
            // ignorer
        }
    }
}
