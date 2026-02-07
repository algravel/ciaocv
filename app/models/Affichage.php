<?php
/**
 * Modèle Affichage
 * Données mock – à remplacer par des requêtes DB.
 */
class Affichage
{
    /**
     * @return array<string, array<string, mixed>>
     */
    public static function getAll(): array
    {
        return [
            'frontend-linkedin' => [
                'id'          => 'frontend-linkedin',
                'title'       => 'Développeur Frontend',
                'platform'    => 'LinkedIn',
                'start'       => '2026-01-15',
                'end'         => '2026-02-15',
                'status'      => 'Actif',
                'statusClass' => 'status-active',
                'views'       => '1,245',
                'apps'        => '12',
            ],
            'frontend-site' => [
                'id'          => 'frontend-site',
                'title'       => 'Développeur Frontend',
                'platform'    => 'Site carrière',
                'start'       => '2026-01-15',
                'end'         => '2026-03-15',
                'status'      => 'Actif',
                'statusClass' => 'status-active',
                'views'       => '458',
                'apps'        => '6',
            ],
            'manager-linkedin' => [
                'id'          => 'manager-linkedin',
                'title'       => 'Chef de projet',
                'platform'    => 'LinkedIn',
                'start'       => '2026-01-20',
                'end'         => '2026-02-20',
                'status'      => 'Actif',
                'statusClass' => 'status-active',
                'views'       => '892',
                'apps'        => '8',
            ],
        ];
    }

    /**
     * Retrouver un affichage par son identifiant.
     */
    public static function find(string $id): ?array
    {
        $all = self::getAll();
        return $all[$id] ?? null;
    }
}
