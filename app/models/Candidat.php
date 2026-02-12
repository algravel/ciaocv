<?php
/**
 * Modèle Candidat
 * Requêtes sur la table app_candidatures.
 * Filtre par platform_user_id (entreprise) via JOIN app_affichages.
 */
class Candidat
{
    private static array $statusStyles = [
        'new' => ['bg' => '#DBEAFE', 'color' => '#1D4ED8'],
        'reviewed' => ['bg' => '#D1FAE5', 'color' => '#065F46'],
        'shortlisted' => ['bg' => '#FEF3C7', 'color' => '#92400E'],
        'rejected' => ['bg' => '#FEE2E2', 'color' => '#991B1B'],
    ];

    private static array $avatarColors = [
        '3B82F6',
        '10B981',
        'F59E0B',
        'EF4444',
        '8B5CF6',
        '06B6D4',
        'EC4899',
        '14B8A6',
        'F97316',
        '6366F1',
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

                // Presigned GET URL pour la vidéo et le CV
                $videoUrl = null;
                $cvUrl = null;
                if (!empty($row['video_path'])) {
                    require_once dirname(__DIR__) . '/helpers/R2Signer.php';
                    $videoUrl = R2Signer::videoUrl($row['video_path']);
                }
                if (!empty($row['cv_path'])) {
                    if (!class_exists('R2Signer')) {
                        require_once dirname(__DIR__) . '/helpers/R2Signer.php';
                    }
                    $cvUrl = R2Signer::videoUrl($row['cv_path']);
                }

                $result[$id] = [
                    'id' => $id,
                    'name' => $name,
                    'email' => $row['email'] ?? '',
                    'phone' => $row['telephone'] ?? '',
                    'role' => $row['poste_title'] ?? '',
                    'status' => $status,
                    'isFavorite' => (bool) ($row['is_favorite'] ?? false),
                    'color' => $color,
                    'rating' => (int) ($row['rating'] ?? 0),
                    'video' => $videoUrl,
                    'cv' => $cvUrl,
                    'comments' => [], // TODO: load comments
                    'date' => $row['created_at'] ? date('Y-m-d H:i', strtotime($row['created_at'])) : '',
                    'statusBg' => $style['bg'],
                    'statusColor' => $style['color'],
                    'retakes' => (int) ($row['retakes_count'] ?? 0),
                    'timeSpent' => (int) ($row['time_spent_seconds'] ?? 0),
                ];
            }

            return $result;
        } catch (Throwable $e) {
            error_log('Candidat::getAll error: ' . $e->getMessage());
            return [];
        }
    }

    /**
     * Tous les candidats pour un évaluateur (affichages invités uniquement).
     */
    public static function getAllForEvaluateur(int $evaluateurPlatformUserId): array
    {
        if ($evaluateurPlatformUserId <= 0) {
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
                JOIN app_affichage_evaluateurs ae ON ae.affichage_id = a.id AND ae.platform_user_id = ?
                ORDER BY c.created_at DESC
            ");
            $stmt->execute([$evaluateurPlatformUserId]);
            $rows = $stmt->fetchAll(PDO::FETCH_ASSOC);

            $result = [];
            foreach ($rows as $row) {
                $id = 'c' . $row['id'];
                $name = trim($row['prenom'] . ' ' . $row['nom']);
                $status = $row['status'] ?: 'new';
                $style = self::$statusStyles[$status] ?? self::$statusStyles['new'];
                $colorIdx = crc32($name) % count(self::$avatarColors);
                $color = self::$avatarColors[abs($colorIdx)];

                $videoUrl = null;
                $cvUrl = null;
                if (!empty($row['video_path'])) {
                    require_once dirname(__DIR__) . '/helpers/R2Signer.php';
                    $videoUrl = R2Signer::videoUrl($row['video_path']);
                }
                if (!empty($row['cv_path'])) {
                    if (!class_exists('R2Signer')) {
                        require_once dirname(__DIR__) . '/helpers/R2Signer.php';
                    }
                    $cvUrl = R2Signer::videoUrl($row['cv_path']);
                }

                $result[$id] = [
                    'id' => $id,
                    'name' => $name,
                    'email' => $row['email'] ?? '',
                    'phone' => $row['telephone'] ?? '',
                    'role' => $row['poste_title'] ?? '',
                    'status' => $status,
                    'isFavorite' => (bool) ($row['is_favorite'] ?? false),
                    'color' => $color,
                    'rating' => (int) ($row['rating'] ?? 0),
                    'video' => $videoUrl,
                    'cv' => $cvUrl,
                    'comments' => [],
                    'date' => $row['created_at'] ? date('Y-m-d H:i', strtotime($row['created_at'])) : '',
                    'statusBg' => $style['bg'],
                    'statusColor' => $style['color'],
                    'retakes' => (int) ($row['retakes_count'] ?? 0),
                    'timeSpent' => (int) ($row['time_spent_seconds'] ?? 0),
                ];
            }

            return $result;
        } catch (Throwable $e) {
            error_log('Candidat::getAllForEvaluateur error: ' . $e->getMessage());
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

                // Presigned GET URL pour la vidéo et le CV
                $videoUrl = null;
                $cvUrl = null;
                if (!empty($row['video_path'])) {
                    if (!class_exists('R2Signer')) {
                        require_once dirname(__DIR__) . '/helpers/R2Signer.php';
                    }
                    $videoUrl = R2Signer::videoUrl($row['video_path']);
                }
                if (!empty($row['cv_path'])) {
                    if (!class_exists('R2Signer')) {
                        require_once dirname(__DIR__) . '/helpers/R2Signer.php';
                    }
                    $cvUrl = R2Signer::videoUrl($row['cv_path']);
                }

                if (!isset($result[$affId])) {
                    $result[$affId] = [];
                }

                $result[$affId][] = [
                    'id' => $id,
                    'name' => $name,
                    'email' => $row['email'] ?? '',
                    'status' => $status,
                    'isFavorite' => (bool) ($row['is_favorite'] ?? false),
                    'color' => $color,
                    'rating' => (int) ($row['rating'] ?? 0),
                    'video' => $videoUrl,
                    'cv' => $cvUrl,
                    'date' => $row['created_at'] ? date('Y-m-d H:i', strtotime($row['created_at'])) : '',
                    'statusBg' => $style['bg'],
                    'statusColor' => $style['color'],
                    'retakes' => (int) ($row['retakes_count'] ?? 0),
                    'timeSpent' => (int) ($row['time_spent_seconds'] ?? 0),
                ];
            }

            return $result;
        } catch (Throwable $e) {
            error_log('Candidat::getByAffichage error: ' . $e->getMessage());
            return [];
        }
    }

    /**
     * Candidats regroupés par affichage, pour un évaluateur (affichages invités uniquement).
     */
    public static function getByAffichageForEvaluateur(int $evaluateurPlatformUserId): array
    {
        if ($evaluateurPlatformUserId <= 0) {
            return [];
        }
        try {
            require_once dirname(__DIR__, 2) . '/gestion/config.php';
            $pdo = Database::get();
            $stmt = $pdo->prepare("
                SELECT c.*, a.id AS aff_id
                FROM app_candidatures c
                JOIN app_affichages a ON a.id = c.affichage_id
                JOIN app_affichage_evaluateurs ae ON ae.affichage_id = a.id AND ae.platform_user_id = ?
                ORDER BY c.created_at DESC
            ");
            $stmt->execute([$evaluateurPlatformUserId]);
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

                $videoUrl = null;
                $cvUrl = null;
                if (!empty($row['video_path'])) {
                    if (!class_exists('R2Signer')) {
                        require_once dirname(__DIR__) . '/helpers/R2Signer.php';
                    }
                    $videoUrl = R2Signer::videoUrl($row['video_path']);
                }
                if (!empty($row['cv_path'])) {
                    if (!class_exists('R2Signer')) {
                        require_once dirname(__DIR__) . '/helpers/R2Signer.php';
                    }
                    $cvUrl = R2Signer::videoUrl($row['cv_path']);
                }

                if (!isset($result[$affId])) {
                    $result[$affId] = [];
                }

                $result[$affId][] = [
                    'id' => $id,
                    'name' => $name,
                    'email' => $row['email'] ?? '',
                    'status' => $status,
                    'isFavorite' => (bool) ($row['is_favorite'] ?? false),
                    'color' => $color,
                    'rating' => (int) ($row['rating'] ?? 0),
                    'video' => $videoUrl,
                    'cv' => $cvUrl,
                    'date' => $row['created_at'] ? date('Y-m-d H:i', strtotime($row['created_at'])) : '',
                    'statusBg' => $style['bg'],
                    'statusColor' => $style['color'],
                    'retakes' => (int) ($row['retakes_count'] ?? 0),
                    'timeSpent' => (int) ($row['time_spent_seconds'] ?? 0),
                ];
            }

            return $result;
        } catch (Throwable $e) {
            error_log('Candidat::getByAffichageForEvaluateur error: ' . $e->getMessage());
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
    /**
     * Mettre à jour le statut ou le favori d'un candidat.
     * @param string $id ID du candidat (ex: 'c123')
     * @param int $platformUserId ID de l'entreprise (pour sécu)
     * @param array $data ['status' => 'new'|'reviewed'|'rejected'|'shortlisted', 'is_favorite' => bool]
     * @return bool
     */
    public static function update(string $id, int $platformUserId, array $data): bool
    {
        if (empty($data) || !$platformUserId) {
            return false;
        }

        $candidatId = (int) str_replace('c', '', $id);
        if ($candidatId <= 0) {
            return false;
        }

        try {
            require_once dirname(__DIR__, 2) . '/gestion/config.php';
            $pdo = Database::get();

            // Vérifier que le candidat appartient à un affichage de l'entreprise ou que l'utilisateur est évaluateur invité
            $stmt = $pdo->prepare("
                SELECT c.id 
                FROM app_candidatures c
                JOIN app_affichages a ON a.id = c.affichage_id
                LEFT JOIN app_affichage_evaluateurs ae ON ae.affichage_id = a.id AND ae.platform_user_id = ?
                WHERE c.id = ? AND (a.platform_user_id = ? OR ae.platform_user_id IS NOT NULL)
                LIMIT 1
            ");
            $stmt->execute([$platformUserId, $candidatId, $platformUserId]);
            if (!$stmt->fetch()) {
                return false;
            }

            $fields = [];
            $params = [];

            if (isset($data['status'])) {
                // Map frontend status to DB status if needed, or store direct
                // Frontend: new, reviewed, rejected, shortlisted
                // DB: (text)
                $fields[] = 'status = ?';
                $params[] = $data['status'];
            }

            // Note: 'is_favorite' concept might be 'shortlisted' status or a separate field? 
            // The frontend treats them somewhat separately but 'shortlisted' is also a status.
            // If the DB has 'is_favorite', use it. If not, maybe use 'status'.
            // Based on earlier view code, 'shortlisted' IS the favorite status?
            // "if (data.status === 'Favori' || data.status === 'shortlisted') isFav = true;"
            // But the user asked for a star AND a status dropdown.
            // Let's assume for now we only update 'status'. If 'is_favorite' is passed, 
            // we might need a dedicated column or just rely on 'shortlisted'.
            // HOWEVER, the UI has a star button separate from status select.
            // If the user clicks star, it should likely just toggle a visual state OR update a column.
            // Let's check if 'is_favorite' exists in `app_candidatures`.
            // The `getAll` method didn't select it, it just set `'isFavorite' => false`. 
            // So arguably, it's NOT in the DB yet.
            // I will add a `is_favorite` column check or just update `status` if `shortlisted`.
            // User request: "le statut... ainsi que l'étoile pour favoris soit enregistrer".
            // If I can't modify DB schema easily, I might have to map Star -> Status='shortlisted'.
            // But if Status is 'Refusé' and Star is On? 
            // Standard ATS: Star is separate.
            // I'll try to update `is_favorite` if column exists, else ignore (or log).
            // Actually, I'll attempt to set it. If it fails, I'll know.
            // Wait, I can't see the schema. Safest is to stick to what I see in `getAll`.
            // `getAll` had `'isFavorite' => false`.
            // This suggests it wasn't implemented backend-side.
            // I should probably add it to the schema? 
            // I don't have migrations content.
            // I'll assume for this task I should use `status = 'shortlisted'` as the "Favorite" state?
            // But the dropdown exists.
            // Let's look at `app/assets/js/app.js`:
            // `if (data.status === 'Favori' || data.status === 'shortlisted') isFav = true;`
            // So if I set status to 'shortlisted', the star lights up?
            // BUT the user might want a separate favorite flag.
            // If I look at the `getAll` query: `SELECT c.* ...`.
            // If `is_favorite` column exists, it would be in `$row`.
            // I'll check `app/models/Candidat.php` `getAll` method again (Step 153).
            // It selects `c.*`.
            // It returns `'isFavorite' => false`. Hardcoded?
            // Yes: `'isFavorite'  => false,`.
            // So existing code DOES NOT support favorites from DB.
            // I should probably implement it properly by checking for the column or adding it.
            // Since I can't migrate, I might have to map Star -> Status 'shortlisted'.
            // But duplicate logic: Status Dropdown has 'Favori' (value 'shortlisted').
            // If I select 'Favori' in dropdown -> Status becomes 'shortlisted' -> Star on.
            // If I click Star -> Status becomes 'shortlisted'.
            // If I click Star off -> Status becomes ?? 'reviewed'?
            // This seems plausible for now without schema change.
            // The UI logic `toggleFavorite` (JS) sends `isFavorite`.
            // If `isFavorite` is true, I update status to `shortlisted`.
            // If `isFavorite` is false, update status to `reviewed` (if it was shortlisted) or keep as is?
            // Let's assume Star == Status 'shortlisted'.
            // And Dropdown can also set 'shortlisted'.

            if (isset($data['is_favorite'])) {
                $isFav = (bool) $data['is_favorite'];
                $fields[] = 'is_favorite = ?';
                $params[] = $isFav ? 1 : 0;
            }

            if (isset($data['rating']) && $data['rating'] !== '') {
                $rating = (int) $data['rating'];
                $rating = max(0, min(5, $rating));
                $fields[] = 'rating = ?';
                $params[] = $rating;
            }

            if (empty($fields)) {
                return true; // Nothing to update
            }

            $params[] = $candidatId;
            $sql = "UPDATE app_candidatures SET " . implode(', ', $fields) . " WHERE id = ?";
            $stmt = $pdo->prepare($sql);
            return $stmt->execute($params);

        } catch (Throwable $e) {
            error_log('Candidat::update error: ' . $e->getMessage());
            return false;
        }
    }
}
