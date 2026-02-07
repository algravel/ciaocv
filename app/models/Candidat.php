<?php
/**
 * Modèle Candidat
 * Données mock – à remplacer par des requêtes DB.
 */
class Candidat
{
    /**
     * Tous les candidats (vue globale).
     * @return array<string, array<string, mixed>>
     */
    public static function getAll(): array
    {
        return [
            'sophie' => [
                'id'         => 'sophie',
                'name'       => 'Sophie Martin',
                'email'      => 'sophie.martin@email.com',
                'phone'      => '+1 514 555-0101',
                'role'       => 'Développeur Frontend',
                'status'     => 'new',
                'isFavorite' => false,
                'rating'     => 4,
                'video'      => '',
                'color'      => '3B82F6',
                'comments'   => [
                    ['user' => 'Jean R.',  'date' => 'Il y a 2h', 'text' => 'Très bon portfolio, à rencontrer rapidement.'],
                    ['user' => 'Marie L.', 'date' => 'Hier',      'text' => "CV intéressant, mais manque d'expérience en React."],
                ],
            ],
            'jean' => [
                'id'         => 'jean',
                'name'       => 'Jean Dupont',
                'email'      => 'jean.dupont@email.com',
                'phone'      => '+1 514 555-0202',
                'role'       => 'Chef de projet',
                'status'     => 'reviewed',
                'isFavorite' => true,
                'rating'     => 5,
                'video'      => '',
                'color'      => '10B981',
                'comments'   => [
                    ['user' => 'Paul D.', 'date' => 'Il y a 1j', 'text' => 'Excellent fit culturel.'],
                ],
            ],
            'marie' => [
                'id'         => 'marie',
                'name'       => 'Marie Tremblay',
                'email'      => 'marie.t@email.com',
                'phone'      => '+1 514 555-0303',
                'role'       => 'Designer UX/UI',
                'status'     => 'rejected',
                'isFavorite' => false,
                'rating'     => 2,
                'video'      => '',
                'color'      => '8B5CF6',
                'comments'   => [
                    ['user' => 'Sophie M.', 'date' => 'Il y a 3j', 'text' => 'Portfolio trop junior pour le poste senior.'],
                ],
            ],
            'pierre' => [
                'id'         => 'pierre',
                'name'       => 'Pierre Lavoie',
                'email'      => 'pierre.l@email.com',
                'phone'      => '+1 514 555-0404',
                'role'       => 'Développeur Frontend',
                'status'     => 'shortlisted',
                'isFavorite' => true,
                'rating'     => 5,
                'video'      => '',
                'color'      => 'EC4899',
                'comments'   => [
                    ['user' => 'Jean R.', 'date' => 'Il y a 4h', 'text' => 'Candidat coup de coeur !'],
                ],
            ],
        ];
    }

    /**
     * Candidats regroupés par affichage.
     * @return array<string, array<int, array<string, mixed>>>
     */
    public static function getByAffichage(): array
    {
        return [
            'frontend-linkedin' => [
                ['name' => 'Sophie Martin',  'email' => 'sophie.martin@email.com', 'color' => '3B82F6', 'status' => 'Nouveau', 'statusBg' => '#DBEAFE', 'statusColor' => '#1E40AF', 'video' => true,  'stars' => 4, 'date' => '2026-02-01', 'id' => 'sophie'],
                ['name' => 'Pierre Lavoie',  'email' => 'pierre.l@email.com',      'color' => 'EC4899', 'status' => 'Favori',  'statusBg' => '#D1FAE5', 'statusColor' => '#065F46', 'video' => true,  'stars' => 4, 'date' => '2026-01-22', 'id' => 'pierre'],
                ['name' => 'Luc Bergeron',   'email' => 'luc.b@email.com',         'color' => 'F59E0B', 'status' => 'Nouveau', 'statusBg' => '#DBEAFE', 'statusColor' => '#1E40AF', 'video' => true,  'stars' => 3, 'date' => '2026-02-03', 'id' => 'luc'],
                ['name' => 'Amélie Côté',    'email' => 'amelie.c@email.com',      'color' => '10B981', 'status' => 'Évalué',  'statusBg' => '#DBEAFE', 'statusColor' => '#1D4ED8', 'video' => true,  'stars' => 5, 'date' => '2026-01-30', 'id' => 'amelie'],
                ['name' => 'Marc Gagnon',    'email' => 'marc.g@email.com',        'color' => '8B5CF6', 'status' => 'Nouveau', 'statusBg' => '#DBEAFE', 'statusColor' => '#1E40AF', 'video' => false, 'stars' => 0, 'date' => '2026-02-05', 'id' => 'marc'],
            ],
            'frontend-site' => [
                ['name' => 'Julie Fortin', 'email' => 'julie.f@email.com', 'color' => '3B82F6', 'status' => 'Nouveau', 'statusBg' => '#DBEAFE', 'statusColor' => '#1E40AF', 'video' => true, 'stars' => 3, 'date' => '2026-02-02', 'id' => 'julie'],
                ['name' => 'David Chen',   'email' => 'david.c@email.com', 'color' => '10B981', 'status' => 'Évalué',  'statusBg' => '#DBEAFE', 'statusColor' => '#1D4ED8', 'video' => true, 'stars' => 4, 'date' => '2026-01-28', 'id' => 'david'],
            ],
            'manager-linkedin' => [
                ['name' => 'Jean Dupont',     'email' => 'jean.dupont@email.com', 'color' => '10B981', 'status' => 'Évalué',  'statusBg' => '#DBEAFE', 'statusColor' => '#1D4ED8', 'video' => true, 'stars' => 5, 'date' => '2026-01-28', 'id' => 'jean'],
                ['name' => 'Nathalie Roy',    'email' => 'nathalie.r@email.com',  'color' => 'EC4899', 'status' => 'Nouveau', 'statusBg' => '#DBEAFE', 'statusColor' => '#1E40AF', 'video' => true, 'stars' => 3, 'date' => '2026-02-04', 'id' => 'nathalie'],
                ['name' => 'François Léger',  'email' => 'francois.l@email.com',  'color' => 'F59E0B', 'status' => 'Favori',  'statusBg' => '#D1FAE5', 'statusColor' => '#065F46', 'video' => true, 'stars' => 4, 'date' => '2026-01-26', 'id' => 'francois'],
            ],
        ];
    }

    /**
     * Retrouver un candidat par son identifiant.
     */
    public static function find(string $id): ?array
    {
        $all = self::getAll();
        return $all[$id] ?? null;
    }
}
