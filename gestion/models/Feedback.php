<?php
/**
 * Modèle Feedback (bugs et idées)
 * Stocke les retours soumis via le FAB feedback (app et gestion).
 */
class Feedback
{
    /**
     * @return array<int, array{id: int, type: string, message: string, source: string, user_email: ?string, user_name: ?string, created_at: string, status: string, internal_note: ?string}>
     */
    public static function all(): array
    {
        try {
            $pdo = Database::get();
            $stmt = $pdo->query('SELECT id, type, message, page_url, source, user_email, user_name, created_at, COALESCE(status, \'new\') as status, internal_note FROM gestion_feedback ORDER BY created_at DESC');
            $rows = [];
            while ($r = $stmt->fetch(PDO::FETCH_ASSOC)) {
                $rows[] = [
                    'id'            => (int) $r['id'],
                    'type'          => $r['type'] ?? 'problem',
                    'message'       => $r['message'] ?? '',
                    'page_url'      => isset($r['page_url']) && $r['page_url'] !== '' ? $r['page_url'] : null,
                    'source'        => $r['source'] ?? 'app',
                    'user_email'    => $r['user_email'] ?: null,
                    'user_name'     => $r['user_name'] ?: null,
                    'created_at'    => $r['created_at'] ?? '',
                    'status'        => $r['status'] ?? 'new',
                    'internal_note' => isset($r['internal_note']) && $r['internal_note'] !== '' ? $r['internal_note'] : null,
                ];
            }
            return $rows;
        } catch (Throwable $e) {
            return [];
        }
    }

    /**
     * Met à jour le statut et la note interne d'un feedback.
     * @param array{status?: string, internal_note?: ?string} $data
     */
    public static function update(int $id, array $data): bool
    {
        $allowed = ['new', 'in_progress', 'resolved'];
        $status = isset($data['status']) && in_array($data['status'], $allowed, true) ? $data['status'] : null;
        $hasInternalNote = array_key_exists('internal_note', $data);
        $internalNote = $hasInternalNote ? (trim($data['internal_note'] ?? '') ?: null) : null;

        if ($status === null && !$hasInternalNote) {
            return false;
        }

        try {
            $pdo = Database::get();
            $updates = [];
            $params = [];
            if ($status !== null) {
                $updates[] = 'status = ?';
                $params[] = $status;
            }
            if ($hasInternalNote) {
                $updates[] = 'internal_note = ?';
                $params[] = $internalNote;
            }
            if (empty($updates)) {
                return false;
            }
            $params[] = $id;
            $stmt = $pdo->prepare('UPDATE gestion_feedback SET ' . implode(', ', $updates) . ' WHERE id = ?');
            $stmt->execute($params);
            return $stmt->rowCount() > 0;
        } catch (Throwable $e) {
            return false;
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
            $stmt = $pdo->prepare('INSERT INTO gestion_feedback (type, message, page_url, source, user_email, user_name, platform_user_id) VALUES (?, ?, ?, ?, ?, ?, ?)');
            $type = in_array($data['type'] ?? '', ['problem', 'idea'], true) ? $data['type'] : 'problem';
            $message = trim($data['message'] ?? '');
            if ($message === '') {
                return false;
            }
            $pageUrl = (isset($data['page_url']) && $type === 'problem') ? trim($data['page_url']) : null;
            $pageUrl = $pageUrl !== '' ? $pageUrl : null;
            $source = $data['source'] ?? 'app';
            $userEmail = isset($data['user_email']) ? trim($data['user_email']) : null;
            $userName = isset($data['user_name']) ? trim($data['user_name']) : null;
            $platformUserId = isset($data['platform_user_id']) && $data['platform_user_id'] > 0 ? (int) $data['platform_user_id'] : null;
            $stmt->execute([$type, $message, $pageUrl, $source, $userEmail ?: null, $userName ?: null, $platformUserId]);
            return true;
        } catch (Throwable $e) {
            return false;
        }
    }

    /**
     * Supprimer un feedback par ID.
     */
    public static function delete(int $id): bool
    {
        try {
            $pdo = Database::get();
            $stmt = $pdo->prepare('DELETE FROM gestion_feedback WHERE id = ?');
            $stmt->execute([$id]);
            return $stmt->rowCount() > 0;
        } catch (Throwable $e) {
            return false;
        }
    }
}
