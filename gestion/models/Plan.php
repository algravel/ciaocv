<?php
/**
 * CRUD forfaits — gestion_plans (pas de chiffrement)
 */
class Plan
{
    private PDO $pdo;

    public function __construct()
    {
        $this->pdo = Database::get();
    }

    /**
     * @return array<int, array{id: int, name_fr: string, name_en: string, name: string, video_limit: int, price_monthly: float, price_yearly: float, created_at: string}>
     */
    public function all(?string $lang = null, bool $activeOnly = true): array
    {
        $where = $activeOnly ? ' WHERE COALESCE(active, 1) = 1' : '';
        $stmt = $this->pdo->query('SELECT id, name_fr, name_en, video_limit, price_monthly, price_yearly, features_json, is_popular, created_at FROM gestion_plans' . $where . ' ORDER BY price_monthly ASC');
        $rows = [];
        $lang = $lang ?? ($_COOKIE['language'] ?? 'fr');
        while ($r = $stmt->fetch()) {
            $nameFr = $r['name_fr'] ?? $r['name'] ?? '';
            $nameEn = $r['name_en'] ?? $r['name'] ?? $nameFr;
            $featuresJson = $r['features_json'] ?? null;
            $features = $featuresJson ? (json_decode($featuresJson, true) ?: []) : [];
            $rows[] = [
                'id' => (int) $r['id'],
                'name_fr' => $nameFr,
                'name_en' => $nameEn,
                'name' => ($lang === 'en' ? $nameEn : $nameFr),
                'video_limit' => (int) $r['video_limit'],
                'price_monthly' => (float) $r['price_monthly'],
                'price_yearly' => (float) $r['price_yearly'],
                'features_json' => $featuresJson,
                'features' => $features,
                'is_popular' => (bool) ($r['is_popular'] ?? false),
                'created_at' => $r['created_at'],
            ];
        }
        return $rows;
    }

    public function create(string $nameFr, string $nameEn, int $videoLimit, float $priceMonthly, float $priceYearly, ?string $featuresJson = null, bool $isPopular = false): int
    {
        $stmt = $this->pdo->prepare('INSERT INTO gestion_plans (name_fr, name_en, video_limit, price_monthly, price_yearly, features_json, is_popular) VALUES (?, ?, ?, ?, ?, ?, ?)');
        $stmt->execute([trim($nameFr), trim($nameEn), $videoLimit, $priceMonthly, $priceYearly, $featuresJson ?: null, $isPopular ? 1 : 0]);
        return (int) $this->pdo->lastInsertId();
    }

    public function count(): int
    {
        return (int) $this->pdo->query('SELECT COUNT(*) FROM gestion_plans')->fetchColumn();
    }

    /**
     * Retourne le forfait gratuit (Découverte) : premier plan avec price_monthly=0 et price_yearly=0.
     */
    public function findFreePlan(): ?array
    {
        $stmt = $this->pdo->query('SELECT id, name_fr, name_en, video_limit, price_monthly, price_yearly, features_json, is_popular, created_at FROM gestion_plans WHERE COALESCE(active, 1) = 1 AND price_monthly = 0 AND price_yearly = 0 ORDER BY id ASC LIMIT 1');
        $r = $stmt ? $stmt->fetch() : null;
        if (!$r) {
            return null;
        }
        return $this->formatPlanRow($r);
    }

    private function formatPlanRow(array $r): array
    {
        $lang = $_COOKIE['language'] ?? 'fr';
        $nameFr = $r['name_fr'] ?? $r['name'] ?? '';
        $nameEn = $r['name_en'] ?? $r['name'] ?? $nameFr;
        $featuresJson = $r['features_json'] ?? null;
        $features = $featuresJson ? (json_decode($featuresJson, true) ?: []) : [];
        return [
            'id' => (int) $r['id'],
            'name_fr' => $nameFr,
            'name_en' => $nameEn,
            'name' => ($lang === 'en' ? $nameEn : $nameFr),
            'video_limit' => (int) $r['video_limit'],
            'price_monthly' => (float) ($r['price_monthly'] ?? 0),
            'price_yearly' => (float) ($r['price_yearly'] ?? 0),
            'features_json' => $featuresJson,
            'features' => $features,
            'is_popular' => (bool) ($r['is_popular'] ?? false),
            'created_at' => $r['created_at'] ?? '',
        ];
    }

    public function findById(int $id): ?array
    {
        $stmt = $this->pdo->prepare('SELECT id, name_fr, name_en, video_limit, price_monthly, price_yearly, features_json, is_popular, created_at FROM gestion_plans WHERE id = ?');
        $stmt->execute([$id]);
        $r = $stmt->fetch();
        if (!$r) {
            return null;
        }
        return $this->formatPlanRow($r);
    }

    public function update(int $id, string $nameFr, string $nameEn, int $videoLimit, float $priceMonthly, float $priceYearly, bool $active = true, ?string $featuresJson = null, bool $isPopular = false): bool
    {
        $stmt = $this->pdo->prepare('UPDATE gestion_plans SET name_fr = ?, name_en = ?, video_limit = ?, price_monthly = ?, price_yearly = ?, active = ?, features_json = ?, is_popular = ? WHERE id = ?');
        return $stmt->execute([trim($nameFr), trim($nameEn), $videoLimit, $priceMonthly, $priceYearly, $active ? 1 : 0, $featuresJson ?: null, $isPopular ? 1 : 0, $id]);
    }

    public function setActive(int $id, bool $active): bool
    {
        $stmt = $this->pdo->prepare('UPDATE gestion_plans SET active = ? WHERE id = ?');
        return $stmt->execute([$active ? 1 : 0, $id]);
    }

    /**
     * Retourne tous les forfaits (actifs et désactivés) avec leur statut.
     * @return array<int, array{id: int, name_fr: string, name_en: string, name: string, video_limit: int, price_monthly: float, price_yearly: float, created_at: string, active: bool}>
     */
    public function allWithStatus(?string $lang = null): array
    {
        $stmt = $this->pdo->query('SELECT id, name_fr, name_en, video_limit, price_monthly, price_yearly, features_json, is_popular, created_at, COALESCE(active, 1) AS active FROM gestion_plans ORDER BY active DESC, price_monthly ASC');
        $rows = [];
        $lang = $lang ?? ($_COOKIE['language'] ?? 'fr');
        while ($r = $stmt->fetch()) {
            $nameFr = $r['name_fr'] ?? $r['name'] ?? '';
            $nameEn = $r['name_en'] ?? $r['name'] ?? $nameFr;
            $featuresJson = $r['features_json'] ?? null;
            $features = $featuresJson ? (json_decode($featuresJson, true) ?: []) : [];
            $rows[] = [
                'id' => (int) $r['id'],
                'name_fr' => $nameFr,
                'name_en' => $nameEn,
                'name' => ($lang === 'en' ? $nameEn : $nameFr),
                'video_limit' => (int) $r['video_limit'],
                'price_monthly' => (float) $r['price_monthly'],
                'price_yearly' => (float) $r['price_yearly'],
                'features_json' => $featuresJson,
                'features' => $features,
                'is_popular' => (bool) ($r['is_popular'] ?? false),
                'created_at' => $r['created_at'],
                'active' => (bool) $r['active'],
            ];
        }
        return $rows;
    }
}
