<?php
/**
 * Modèle Candidat
 * Requêtes sur la table app_candidatures.
 * Filtre par platform_user_id (entreprise) via JOIN app_affichages.
 */
class Candidat
{
    private static array $statusStyles = [
        'new'         => ['bg' => '#DBEAFE', 'color' => '#1D4ED8'],
        'reviewed'    => ['bg' => '#D1FAE5', 'color' => '#065F46'],
        'shortlisted' => ['bg' => '#FEF3C7', 'color' => '#92400E'],
        'rejected'    => ['bg' => '#FEE2E2', 'color' => '#991B1B'],
    ];

    private static array $avatarColors = [
        '3B82F6', '10B981', 'F59E0B', 'EF4444', '8B5CF6',
        '06B6D4', 'EC4899', '14B8A6', 'F97316', '6366F1',
    ];

    /**
     * Tous les candidats (vue globale).
     * @return array<string, array<string, mixed>>  Indexé par id
     */
    public static function getAll(?int $platformUserId = null): array
    {
        if (!$platformUserId || $platformUserId <= 0) {
            return [];
        }

        try {
            require_once dirname(__DIR__, 2) . '/gestion/config.php';
            $pdo = Database::get();

            $stmt = $pdo->prepare("
                SELECT c.*, a.poste_id, p.title AS poste_title
                FROM app_candidatures c
                JOIN app_affichages a ON a.id = c.affichage_id
                JOIN app_postes p ON p.id = a.poste_id
                WHERE a.platform_user_id = ?
                ORDER BY c.created_at DESC
            ");
            $stmt->execute([$platformUserId]);
            $rows = $stmt->fetchAll(PDO::FETCH_ASSOC);

            $result = [];
            foreach ($rows as $row) {
                $id = 'c' . $row['id'];
                $name = trim($row['prenom'] . ' ' . $row['nom']);
                $status = $row['status'] ?: 'new';
                $style = self::$statusStyles[$status] ?? self::$statusStyles['new'];
                $colorIdx = crc32($name) % count(self::$avatarColors);
                $color = self::$avatarColors[abs($colorIdx)];

                // Presigned GET URL pour la vidéo
                $videoUrl = null;
                if (!empty($row['video_path'])) {
                    require_once dirname(__DIR__) . '/helpers/R2Signer.php';
                    $videoUrl = R2Signer::videoUrl($row['video_path']);
                }

                $result[$id] = [
                    'id'          => $id,
                    'name'        => $name,
                    'email'       => $row['email'] ?? '',
                    'phone'       => $row['telephone'] ?? '',
                    'role'        => $row['poste_title'] ?? '',
                    'status'      => $status,
                    'isFavorite'  => false,
                    'color'       => $color,
                    'rating'      => 0,
                    'video'       => $videoUrl,
                    'comments'    => [],
                    'date'        => $row['created_at'] ? date('Y-m-d', strtotime($row['created_at'])) : '',
                    'statusBg'    => $style['bg'],
                    'statusColor' => $style['color'],
                ];
            }

            return $result;
        } catch (Throwable $e) {
            error_log('Candidat::getAll error: ' . $e->getMessage());
            return [];
        }
    }

    /**
     * Candidats regroupés par affichage.
     * @return array<string, array<int, array<string, mixed>>>
     */
    public static function getByAffichage(?int $platformUserId = null): array
    {
        if (!$platformUserId || $platformUserId <= 0) {
            return [];
        }

        try {
            require_once dirname(__DIR__, 2) . '/gestion/config.php';
            $pdo = Database::get();

            $stmt = $pdo->prepare("
                SELECT c.*, a.id AS aff_id
                FROM app_candidatures c
                JOIN app_affichages a ON a.id = c.affichage_id
                WHERE a.platform_user_id = ?
                ORDER BY c.created_at DESC
            ");
            $stmt->execute([$platformUserId]);
            $rows = $stmt->fetchAll(PDO::FETCH_ASSOC);

            $result = [];
            foreach ($rows as $row) {
                $affId = (string) $row['aff_id'];
                $id = 'c' . $row['id'];
                $name = trim($row['prenom'] . ' ' . $row['nom']);
                $status = $row['status'] ?: 'new';
                $style = self::$statusStyles[$status] ?? self::$statusStyles['new'];
                $colorIdx = crc32($name) % count(self::$avatarColors);
                $color = self::$avatarColors[abs($colorIdx)];

                if (!isset($result[$affId])) {
                    $result[$affId] = [];
                }

                $result[$affId][] = [
                    'id'          => $id,
                    'name'        => $name,
                    'email'       => $row['email'] ?? '',
                    'status'      => $status,
                    'isFavorite'  => false,
                    'color'       => $color,
                    'date'        => $row['created_at'] ? date('Y-m-d', strtotime($row['created_at'])) : '',
                    'statusBg'    => $style['bg'],
                    'statusColor' => $style['color'],
                ];
            }

            return $result;
        } catch (Throwable $e) {
            error_log('Candidat::getByAffichage error: ' . $e->getMessage());
            return [];
        }
    }

    /**
     * Retrouver un candidat par son identifiant.
     */
    public static function find(string $id): ?array
    {
        $all = self::getAll();
        return $all[$id] ?? null;
    }
}
