<?php
/**
 * Modèle Poste – app_postes
 * Charge depuis la base de données (gestion). Filtre par platform_user_id.
 */
class Poste
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
            'paused'  => ['label' => 'Non actif', 'class' => 'status-paused'],
            'closed'  => ['label' => 'Archivé', 'class' => 'status-closed'],
        ];
    }

    private static function formatRow(array $r): array
    {
        $map = self::statusMap();
        $s = $map[$r['status'] ?? 'active'] ?? $map['active'];
        $questions = $r['questions'] ?? null;
        if (is_string($questions)) {
            $questions = json_decode($questions, true) ?: [];
        }
        return [
            'id'            => (string) $r['id'],
            'platform_user_id' => (int) ($r['platform_user_id'] ?? 0),
            'title'         => $r['title'] ?? '',
            'department'    => $r['department'] ?? '',
            'location'      => $r['location'] ?? '',
            'status'        => $s['label'],
            'statusClass'   => $s['class'],
            'candidates'    => (int) ($r['candidates'] ?? 0),
            'date'          => $r['created_at'] ?? '',
            'description'   => $r['description'] ?? '',
            'recordDuration' => (int) ($r['record_duration'] ?? 3),
            'questions'     => is_array($questions) ? $questions : [],
        ];
    }

    /**
     * @param int|null $platformUserId Filtrer par entreprise
     * @return array<int, array<string, mixed>>
     */
    public static function getAll(?int $platformUserId = null): array
    {
        if ($platformUserId === null || $platformUserId <= 0) {
            return [];
        }
        self::ensureDb();
        $pdo = Database::get();
        $stmt = $pdo->prepare('SELECT * FROM app_postes WHERE platform_user_id = ? ORDER BY created_at DESC');
        $stmt->execute([$platformUserId]);
        $rows = [];
        while ($r = $stmt->fetch()) {
            $rows[] = self::formatRow($r);
        }
        return $rows;
    }

    public static function find(string $id, ?int $platformUserId = null): ?array
    {
        if ($platformUserId === null || $platformUserId <= 0) {
            return null;
        }
        self::ensureDb();
        $pdo = Database::get();
        $stmt = $pdo->prepare('SELECT * FROM app_postes WHERE id = ? AND platform_user_id = ? LIMIT 1');
        $stmt->execute([$id, $platformUserId]);
        $r = $stmt->fetch();
        return $r ? self::formatRow($r) : null;
    }

    public static function create(int $platformUserId, array $data): ?array
    {
        self::ensureDb();
        $pdo = Database::get();
        $title = trim($data['title'] ?? '');
        if ($title === '') {
            return null;
        }
        $stmt = $pdo->prepare('INSERT INTO app_postes (platform_user_id, title, department, location, status, description, record_duration, questions) VALUES (?, ?, ?, ?, ?, ?, ?, ?)');
        $stmt->execute([
            $platformUserId,
            $title,
            trim($data['department'] ?? '') ?: '',
            trim($data['location'] ?? '') ?: '',
            in_array($data['status'] ?? '', ['active', 'paused', 'closed']) ? $data['status'] : 'active',
            trim($data['description'] ?? '') ?: null,
            (int) ($data['record_duration'] ?? 3) ?: 3,
            json_encode($data['questions'] ?? []),
        ]);
        $id = (int) $pdo->lastInsertId();
        return $id ? self::find((string) $id, $platformUserId) : null;
    }

    public static function update(int $id, int $platformUserId, array $data): bool
    {
        self::ensureDb();
        $pdo = Database::get();
        $sets = [];
        $vals = [];
        if (array_key_exists('questions', $data)) {
            $sets[] = 'questions = ?';
            $vals[] = json_encode($data['questions'] ?? []);
        }
        if (array_key_exists('status', $data) && in_array($data['status'], ['active', 'paused', 'closed'], true)) {
            $sets[] = 'status = ?';
            $vals[] = $data['status'];
        }
        if (array_key_exists('record_duration', $data)) {
            $sets[] = 'record_duration = ?';
            $vals[] = (int) $data['record_duration'] ?: 3;
        }
        if (empty($sets)) {
            return false;
        }
        $vals[] = $id;
        $vals[] = $platformUserId;
        $stmt = $pdo->prepare('UPDATE app_postes SET ' . implode(', ', $sets) . ' WHERE id = ? AND platform_user_id = ?');
        return $stmt->execute($vals);
    }

    public static function delete(int $id, int $platformUserId): bool
    {
        self::ensureDb();
        $pdo = Database::get();
        $stmt = $pdo->prepare('DELETE FROM app_postes WHERE id = ? AND platform_user_id = ?');
        $stmt->execute([$id, $platformUserId]);
        return $stmt->rowCount() > 0;
    }
}
