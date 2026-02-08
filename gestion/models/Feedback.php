<?php
/**
 * Modèle Feedback (bugs et idées)
 * Stocke les retours soumis via le FAB feedback (app et gestion).
 */
class Feedback
{
    /**
     * @return array<int, array{id: int, type: string, message: string, source: string, user_email: ?string, user_name: ?string, created_at: string}>
     */
    public static function all(): array
    {
        try {
            $pdo = Database::get();
            $stmt = $pdo->query('SELECT id, type, message, source, user_email, user_name, created_at FROM gestion_feedback ORDER BY created_at DESC');
            $rows = [];
            while ($r = $stmt->fetch(PDO::FETCH_ASSOC)) {
                $rows[] = [
                    'id'         => (int) $r['id'],
                    'type'       => $r['type'] ?? 'problem',
                    'message'    => $r['message'] ?? '',
                    'source'     => $r['source'] ?? 'app',
                    'user_email' => $r['user_email'] ?: null,
                    'user_name'  => $r['user_name'] ?: null,
                    'created_at' => $r['created_at'] ?? '',
                ];
            }
            return $rows;
        } catch (Throwable $e) {
            return [];
        }
    }

    /**
     * Créer un nouveau feedback.
     * @param array{type: string, message: string, source?: string, user_email?: ?string, user_name?: ?string, platform_user_id?: ?int} $data
     */
    public static function create(array $data): bool
    {
        try {
            $pdo = Database::get();
            $stmt = $pdo->prepare('INSERT INTO gestion_feedback (type, message, source, user_email, user_name, platform_user_id) VALUES (?, ?, ?, ?, ?, ?)');
            $type = in_array($data['type'] ?? '', ['problem', 'idea'], true) ? $data['type'] : 'problem';
            $message = trim($data['message'] ?? '');
            if ($message === '') {
                return false;
            }
            $source = $data['source'] ?? 'app';
            $userEmail = isset($data['user_email']) ? trim($data['user_email']) : null;
            $userName = isset($data['user_name']) ? trim($data['user_name']) : null;
            $platformUserId = isset($data['platform_user_id']) && $data['platform_user_id'] > 0 ? (int) $data['platform_user_id'] : null;
            $stmt->execute([$type, $message, $source, $userEmail ?: null, $userName ?: null, $platformUserId]);
            return true;
        } catch (Throwable $e) {
            return false;
        }
    }
}
