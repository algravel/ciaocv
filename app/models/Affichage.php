<?php
/**
 * Modèle Affichage
 * Données mock – à remplacer par des requêtes DB.
 * Filtre par platform_user_id (entreprise) quand fourni.
 */
class Affichage
{
    /**
     * @param int|null $platformUserId Filtrer par entreprise (utilisateur plateforme)
     * @return array<string, array<string, mixed>>
     */
    public static function getAll(?int $platformUserId = null): array
    {
        $all = [
            'frontend-linkedin' => [
                'id'          => 'frontend-linkedin',
                'shareLongId' => '0cb075d860fa55c4',
                'posteId'     => 'frontend',
                'title'       => 'Développeur Frontend',
                'department'  => 'Technologie',
                'platform'    => 'LinkedIn',
                'start'       => '2026-01-15',
                'end'         => '2026-02-15',
                'status'      => 'Actif',
                'statusClass' => 'status-active',
                'views'       => '1,245',
                'apps'        => '12',
                'completed'   => 5,
                'sent'        => 12,
                'evaluateurs' => [
                    ['name' => 'Marie Tremblay', 'email' => 'marie.t@acme.com'],
                    ['name' => 'Pierre Roy', 'email' => 'pierre.r@acme.com'],
                ],
            ],
            'frontend-site' => [
                'id'          => 'frontend-site',
                'shareLongId' => 'a6f354c813a3c23e',
                'posteId'     => 'frontend',
                'title'       => 'Développeur Frontend',
                'department'  => 'Technologie',
                'platform'    => 'Site carrière',
                'start'       => '2026-01-15',
                'end'         => '2026-03-15',
                'status'      => 'Actif',
                'statusClass' => 'status-active',
                'views'       => '458',
                'apps'        => '6',
                'completed'   => 2,
                'sent'        => 6,
                'evaluateurs' => [
                    ['name' => 'Marie Tremblay', 'email' => 'marie.t@acme.com'],
                ],
            ],
            'manager-linkedin' => [
                'id'          => 'manager-linkedin',
                'shareLongId' => 'b7c465e924b4d35f',
                'posteId'     => 'manager',
                'title'       => 'Chef de projet',
                'department'  => 'Gestion',
                'platform'    => 'LinkedIn',
                'start'       => '2026-01-20',
                'end'         => '2026-02-20',
                'status'      => 'Actif',
                'statusClass' => 'status-active',
                'views'       => '892',
                'apps'        => '8',
                'completed'   => 4,
                'sent'        => 8,
                'evaluateurs' => [
                    ['name' => 'Jean Dupont', 'email' => 'jean.d@acme.com'],
                    ['name' => 'Sophie Martin', 'email' => 'sophie.m@acme.com'],
                ],
            ],
        ];

        if ($platformUserId !== null) {
            $posteIds = array_column(Poste::getAll($platformUserId), 'id');
            $all = array_filter($all, fn ($a) => in_array($a['posteId'] ?? '', $posteIds, true));
        }
        return $all;
    }

    /**
     * Retrouver un affichage par son identifiant.
     */
    public static function find(string $id): ?array
    {
        $all = self::getAll();
        return $all[$id] ?? null;
    }

    /**
     * Retrouver un affichage par son shareLongId (lien rec).
     * @return array|null Données affichage ou null
     */
    public static function findByShareLongId(string $longId): ?array
    {
        foreach (self::getAll() as $aff) {
            if (($aff['shareLongId'] ?? '') === $longId) {
                return $aff;
            }
        }
        return null;
    }

    /**
     * Données poste pour la page rec (candidat), à partir du longId.
     * Source unique : affichage → poste lié (questions, durée, lieu).
     * @return array|null tableau avec title, department, location, description, questions, recordDuration
     */
    public static function getPosteByLongId(string $longId): ?array
    {
        $affichage = self::findByShareLongId($longId);
        if (!$affichage) {
            return null;
        }
        $posteId = $affichage['posteId'] ?? null;
        $poste   = $posteId ? Poste::find($posteId) : null;
        if (!$poste) {
            return [
                'title'          => $affichage['title'] ?? '',
                'department'     => $affichage['department'] ?? '',
                'location'       => '',
                'description'    => '',
                'questions'      => [],
                'recordDuration' => 3,
            ];
        }
        return [
            'title'          => $poste['title'],
            'department'     => $poste['department'],
            'location'       => $poste['location'],
            'description'    => $poste['description'] ?? '',
            'questions'      => $poste['questions'] ?? [],
            'recordDuration' => (int) ($poste['recordDuration'] ?? 3),
        ];
    }
}
