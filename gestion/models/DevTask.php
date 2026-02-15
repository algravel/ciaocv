<?php
/**
 * Modèle DevTask – Backlog / Kanban page Développement.
 */
class DevTask
{
    public const STATUS_TODO = 'todo';
    public const STATUS_IN_PROGRESS = 'in_progress';
    public const STATUS_TO_TEST = 'to_test';
    public const STATUS_DEPLOYED = 'deployed';
    public const STATUS_DONE = 'done';

    /** @return array<int, array{id: int, title: string, description: ?string, priority: int, status: string, created_at: string, updated_at: string}> */
    public static function all(): array
    {
        try {
            $pdo = Database::get();
            $stmt = $pdo->query('SELECT id, title, description, priority, status, created_at, updated_at FROM gestion_dev_tasks ORDER BY priority ASC, id ASC');
            $rows = [];
            while ($r = $stmt->fetch(PDO::FETCH_ASSOC)) {
                $rows[] = [
                    'id'          => (int) $r['id'],
                    'title'       => $r['title'] ?? '',
                    'description' => isset($r['description']) && $r['description'] !== '' ? $r['description'] : null,
                    'priority'    => (int) ($r['priority'] ?? 0),
                    'status'      => $r['status'] ?? self::STATUS_TODO,
                    'created_at'  => $r['created_at'] ?? '',
                    'updated_at'  => $r['updated_at'] ?? '',
                ];
            }
            return $rows;
        } catch (Throwable $e) {
            return [];
        }
    }

    /** @param array{title: string, description?: ?string, priority?: int, status?: string} $data */
    public static function create(array $data): ?int
    {
        try {
            $pdo = Database::get();
            $title = trim($data['title'] ?? '');
            if ($title === '') {
                return null;
            }
            $description = isset($data['description']) ? trim($data['description']) : null;
            $description = $description !== '' ? $description : null;
            $priority = isset($data['priority']) ? (int) $data['priority'] : 0;
            $status = $data['status'] ?? self::STATUS_TODO;
            if (!self::isValidStatus($status)) {
                $status = self::STATUS_TODO;
            }
            $stmt = $pdo->prepare('INSERT INTO gestion_dev_tasks (title, description, priority, status) VALUES (?, ?, ?, ?)');
            $stmt->execute([$title, $description, $priority, $status]);
            return (int) $pdo->lastInsertId();
        } catch (Throwable $e) {
            return null;
        }
    }

    /** @param array{title?: string, description?: ?string, priority?: int, status?: string} $data */
    public static function update(int $id, array $data): bool
    {
        try {
            $pdo = Database::get();
            $updates = [];
            $params = [];
            if (array_key_exists('title', $data)) {
                $title = trim($data['title'] ?? '');
                $updates[] = 'title = ?';
                $params[] = $title !== '' ? $title : 'Sans titre';
            }
            if (array_key_exists('description', $data)) {
                $desc = isset($data['description']) ? trim($data['description']) : null;
                $updates[] = 'description = ?';
                $params[] = $desc !== '' ? $desc : null;
            }
            if (array_key_exists('priority', $data)) {
                $updates[] = 'priority = ?';
                $params[] = (int) $data['priority'];
            }
            if (array_key_exists('status', $data) && self::isValidStatus($data['status'])) {
                $updates[] = 'status = ?';
                $params[] = $data['status'];
            }
            if (empty($updates)) {
                return false;
            }
            $params[] = $id;
            $stmt = $pdo->prepare('UPDATE gestion_dev_tasks SET ' . implode(', ', $updates) . ' WHERE id = ?');
            $stmt->execute($params);
            return true;
        } catch (Throwable $e) {
            return false;
        }
    }

    public static function delete(int $id): bool
    {
        try {
            $pdo = Database::get();
            $stmt = $pdo->prepare('DELETE FROM gestion_dev_tasks WHERE id = ?');
            $stmt->execute([$id]);
            return $stmt->rowCount() > 0;
        } catch (Throwable $e) {
            return false;
        }
    }

    private static function isValidStatus(string $s): bool
    {
        return in_array($s, [self::STATUS_TODO, self::STATUS_IN_PROGRESS, self::STATUS_TO_TEST, self::STATUS_DEPLOYED, self::STATUS_DONE], true);
    }
}
