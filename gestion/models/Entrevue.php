<?php
/**
 * Comptage entrevues/candidatures par mois pour le graphique app dashboard.
 */
class Entrevue
{
    private PDO $pdo;

    public function __construct()
    {
        $this->pdo = Database::get();
    }

    /**
     * Candidatures par mois (6 derniers mois) pour un utilisateur plateforme.
     * @return array<array{month: string, label: string, count: int}>
     */
    public function countByMonth(int $platformUserId, int $months = 6): array
    {
        $stmt = $this->pdo->prepare("
            SELECT DATE_FORMAT(created_at, '%Y-%m') as ym, COUNT(*) as cnt
            FROM app_entrevues
            WHERE platform_user_id = ? AND created_at >= DATE_SUB(CURDATE(), INTERVAL ? MONTH)
            GROUP BY ym
            ORDER BY ym ASC
        ");
        $stmt->execute([$platformUserId, $months]);
        $byMonth = [];
        while ($r = $stmt->fetch()) {
            $byMonth[$r['ym']] = (int) $r['cnt'];
        }
        $labels = ['janv' => 'Jan', 'fév' => 'Fév', 'mars' => 'Mar', 'avr' => 'Avr', 'mai' => 'Mai', 'juin' => 'Juin', 'juil' => 'Juil', 'août' => 'Août', 'sept' => 'Sep', 'oct' => 'Oct', 'nov' => 'Nov', 'déc' => 'Déc'];
        $result = [];
        for ($i = $months - 1; $i >= 0; $i--) {
            $d = new DateTime("-$i months");
            $ym = $d->format('Y-m');
            $m = (int) $d->format('n');
            $label = ['', 'Jan', 'Fév', 'Mar', 'Avr', 'Mai', 'Juin', 'Juil', 'Août', 'Sep', 'Oct', 'Nov', 'Déc'][$m];
            $result[] = [
                'month' => $ym,
                'label' => $label,
                'count' => $byMonth[$ym] ?? 0,
            ];
        }
        return $result;
    }

    public function countUsed(int $platformUserId): int
    {
        $stmt = $this->pdo->prepare('SELECT COUNT(*) FROM app_entrevues WHERE platform_user_id = ?');
        $stmt->execute([$platformUserId]);
        return (int) $stmt->fetchColumn();
    }
}
