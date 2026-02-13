<?php
/**
 * Modèle EmailTemplate
 * Modèles de courriels stockés en base (app_email_templates), par platform_user_id.
 */
class EmailTemplate
{
    private static function defaultTemplates(): array
    {
        return [
            ['title' => 'Confirmation de réception', 'content' => "Bonjour {{nom_candidat}},\n\nNous accusons réception de votre candidature pour le poste de {{titre_poste}} au sein de {{nom_entreprise}} et vous en remercions.\n\nVotre dossier sera examiné dans les prochains jours. Nous vous tiendrons informé(e) des suites données à votre candidature.\n\nCordialement,\nL'équipe Recrutement"],
            ['title' => "Invitation à l'entrevue vidéo", 'content' => "Bonjour {{nom_candidat}},\n\nNous avons le plaisir de vous annoncer que votre candidature pour le poste de {{titre_poste}} ({{nom_entreprise}}) a été retenue pour la prochaine étape de notre processus de sélection.\n\nNotre équipe vous contactera sous peu pour convenir d'un rendez-vous (entrevue en personne ou visioconférence).\n\nCordialement,\nL'équipe Recrutement"],
            ['title' => 'Refus poli', 'content' => "Bonjour {{nom_candidat}},\n\nNous vous remercions de l'intérêt que vous avez porté au poste de {{titre_poste}} chez {{nom_entreprise}}. Après examen de l'ensemble des candidatures reçues, nous avons retenu un autre profil pour ce poste.\n\nNous conservons votre candidature et ne manquerons pas de vous recontacter si une opportunité correspondant à votre profil se présente.\n\nCordialement,\nL'équipe Recrutement"],
            ['title' => 'Poste comblé', 'content' => "Bonjour {{nom_candidat}},\n\nNous vous informons que le poste de {{titre_poste}} chez {{nom_entreprise}} pour lequel vous aviez postulé a été pourvu.\n\nNous avons reçu de nombreuses candidatures de qualité et vous remercions pour votre intérêt.\n\nNous vous invitons à consulter nos autres offres et à postuler à nouveau.\n\nCordialement,\nL'équipe Recrutement"],
        ];
    }

    /**
     * @return array<int, array{id: int, title: string, content: string}>
     */
    public static function getAll(?int $platformUserId = null): array
    {
        if (!$platformUserId || $platformUserId <= 0) {
            return [];
        }
        try {
            require_once dirname(__DIR__, 2) . '/gestion/config.php';
            $pdo = Database::get();
            $stmt = $pdo->prepare('SELECT id, title, content FROM app_email_templates WHERE platform_user_id = ? ORDER BY id ASC');
            $stmt->execute([$platformUserId]);
            $rows = $stmt->fetchAll(PDO::FETCH_ASSOC);
            $result = [];
            foreach ($rows as $r) {
                $result[] = [
                    'id' => (int) $r['id'],
                    'title' => $r['title'] ?? '',
                    'content' => $r['content'] ?? '',
                ];
            }
            if (empty($result)) {
                foreach (self::defaultTemplates() as $tpl) {
                    $stmt = $pdo->prepare('INSERT INTO app_email_templates (platform_user_id, title, content) VALUES (?, ?, ?)');
                    $stmt->execute([$platformUserId, $tpl['title'], $tpl['content']]);
                    $result[] = [
                        'id' => (int) $pdo->lastInsertId(),
                        'title' => $tpl['title'],
                        'content' => $tpl['content'],
                    ];
                }
            }
            return $result;
        } catch (Throwable $e) {
            error_log('EmailTemplate::getAll error: ' . $e->getMessage());
            return [];
        }
    }

    public static function save(?int $platformUserId, ?int $id, string $title, string $content): ?array
    {
        if (!$platformUserId || $platformUserId <= 0 || trim($title) === '') {
            return null;
        }
        $title = trim($title);
        $content = trim($content);
        try {
            require_once dirname(__DIR__, 2) . '/gestion/config.php';
            $pdo = Database::get();
            if ($id && $id > 0) {
                $stmt = $pdo->prepare('UPDATE app_email_templates SET title = ?, content = ? WHERE id = ? AND platform_user_id = ?');
                $stmt->execute([$title, $content, $id, $platformUserId]);
                if ($stmt->rowCount() > 0) {
                    return ['id' => $id, 'title' => $title, 'content' => $content];
                }
                return null;
            }
            $stmt = $pdo->prepare('INSERT INTO app_email_templates (platform_user_id, title, content) VALUES (?, ?, ?)');
            $stmt->execute([$platformUserId, $title, $content]);
            $newId = (int) $pdo->lastInsertId();
            return $newId ? ['id' => $newId, 'title' => $title, 'content' => $content] : null;
        } catch (Throwable $e) {
            error_log('EmailTemplate::save error: ' . $e->getMessage());
            return null;
        }
    }

    public static function delete(?int $platformUserId, int $id): bool
    {
        if (!$platformUserId || $platformUserId <= 0 || $id <= 0) {
            return false;
        }
        try {
            require_once dirname(__DIR__, 2) . '/gestion/config.php';
            $pdo = Database::get();
            $stmt = $pdo->prepare('DELETE FROM app_email_templates WHERE id = ? AND platform_user_id = ?');
            $stmt->execute([$id, $platformUserId]);
            return $stmt->rowCount() > 0;
        } catch (Throwable $e) {
            error_log('EmailTemplate::delete error: ' . $e->getMessage());
            return false;
        }
    }
}
