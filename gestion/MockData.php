<?php
/**
 * Données mock pour l'espace gestion.
 * Maquette fonctionnelle sans base de données.
 */
class MockData
{
    /**
     * @return array<int, array<string, mixed>>
     */
    public static function getPostes(): array
    {
        return [
            [
                'id'             => 'frontend',
                'title'          => 'Développeur Frontend',
                'department'     => 'Technologie',
                'location'       => 'Montréal, QC',
                'status'         => 'Actif',
                'statusClass'    => 'status-active',
                'candidates'     => 12,
                'date'           => '2026-01-15',
                'description'    => "Nous recherchons un développeur frontend passionné pour rejoindre notre équipe.",
                'recordDuration' => 3,
                'questions'      => [
                    "Combien d'années d'expérience avez-vous avec React ?",
                    "Avez-vous déjà travaillé avec TypeScript ?",
                    "Pouvez-vous fournir un lien vers votre portfolio ?",
                ],
            ],
            [
                'id'             => 'manager',
                'title'          => 'Chef de projet',
                'department'     => 'Gestion',
                'location'       => 'Toronto, ON',
                'status'         => 'Actif',
                'statusClass'    => 'status-active',
                'candidates'     => 8,
                'date'           => '2026-01-20',
                'description'    => "Nous cherchons un chef de projet expérimenté pour mener à bien nos initiatives technologiques.",
                'recordDuration' => 2,
                'questions'      => [
                    "Avez-vous une certification PMP ?",
                    "Décrivez un projet complexe que vous avez géré.",
                    "Quel est votre style de leadership ?",
                ],
            ],
            [
                'id'             => 'designer',
                'title'          => 'Designer UX/UI',
                'department'     => 'Design',
                'location'       => 'Télétravail',
                'status'         => 'Non actif',
                'statusClass'    => 'status-paused',
                'candidates'     => 5,
                'date'           => '2026-01-10',
                'description'    => "Rejoignez notre équipe créative pour concevoir des interfaces intuitives et esthétiques.",
                'recordDuration' => 3,
                'questions'      => [
                    "Lien vers votre portfolio Dribbble/Behance ?",
                    "Racontez-nous un défi UX que vous avez résolu.",
                ],
            ],
            [
                'id'             => 'analyst',
                'title'          => "Analyste d'affaires",
                'department'     => 'Stratégie',
                'location'       => 'Vancouver, BC',
                'status'         => 'Archivé',
                'statusClass'    => 'status-closed',
                'candidates'     => 15,
                'date'           => '2025-12-01',
                'description'    => "Poste comblé. Merci de votre intérêt.",
                'recordDuration' => 3,
                'questions'      => [],
            ],
        ];
    }

    /**
     * @return array<string, array<string, mixed>>
     */
    public static function getAffichages(): array
    {
        return [
            'frontend-linkedin' => [
                'id'           => 'frontend-linkedin',
                'shareLongId'  => '0cb075d860fa55c4',
                'posteId'      => 'frontend',
                'title'        => 'Développeur Frontend',
                'department'   => 'Technologie',
                'platform'     => 'LinkedIn',
                'start'        => '2026-01-15',
                'end'          => '2026-02-15',
                'status'       => 'Actif',
                'statusClass'  => 'status-active',
                'views'        => '1,245',
                'apps'         => '12',
                'completed'    => 5,
                'sent'         => 12,
                'evaluateurs'  => [
                    ['name' => 'Marie Tremblay', 'email' => 'marie.t@acme.com'],
                    ['name' => 'Pierre Roy', 'email' => 'pierre.r@acme.com'],
                ],
            ],
            'frontend-site' => [
                'id'           => 'frontend-site',
                'shareLongId'  => 'a6f354c813a3c23e',
                'posteId'      => 'frontend',
                'title'        => 'Développeur Frontend',
                'department'   => 'Technologie',
                'platform'     => 'Site carrière',
                'start'        => '2026-01-15',
                'end'          => '2026-03-15',
                'status'       => 'Actif',
                'statusClass'  => 'status-active',
                'views'        => '458',
                'apps'         => '6',
                'completed'    => 2,
                'sent'         => 6,
                'evaluateurs'  => [
                    ['name' => 'Marie Tremblay', 'email' => 'marie.t@acme.com'],
                ],
            ],
            'manager-linkedin' => [
                'id'           => 'manager-linkedin',
                'shareLongId'  => 'b7c465e924b4d35f',
                'posteId'      => 'manager',
                'title'        => 'Chef de projet',
                'department'   => 'Gestion',
                'platform'     => 'LinkedIn',
                'start'        => '2026-01-20',
                'end'          => '2026-02-20',
                'status'       => 'Actif',
                'statusClass'  => 'status-active',
                'views'        => '892',
                'apps'         => '8',
                'completed'    => 4,
                'sent'         => 8,
                'evaluateurs'  => [
                    ['name' => 'Jean Dupont', 'email' => 'jean.d@acme.com'],
                    ['name' => 'Sophie Martin', 'email' => 'sophie.m@acme.com'],
                ],
            ],
        ];
    }

    /**
     * @return array<string, array<string, mixed>>
     */
    public static function getCandidats(): array
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
                    ['user' => 'Jean R.', 'date' => 'Il y a 2h', 'text' => 'Très bon portfolio, à rencontrer rapidement.'],
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
                'comments'   => [],
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
                'comments'   => [],
            ],
        ];
    }

    /**
     * @return array<string, array<int, array<string, mixed>>>
     */
    public static function getCandidatsByAffichage(): array
    {
        return [
            'frontend-linkedin' => [
                ['name' => 'Sophie Martin', 'email' => 'sophie.martin@email.com', 'color' => '3B82F6', 'status' => 'Nouveau', 'statusBg' => '#F5E6EA', 'statusColor' => '#5C1A1F', 'video' => true, 'stars' => 4, 'date' => '2026-02-01', 'id' => 'sophie', 'isFavorite' => false],
                ['name' => 'Pierre Lavoie', 'email' => 'pierre.l@email.com', 'color' => 'EC4899', 'status' => 'Évalué', 'statusBg' => '#D1FAE5', 'statusColor' => '#065F46', 'video' => true, 'stars' => 4, 'date' => '2026-01-22', 'id' => 'pierre', 'isFavorite' => true],
            ],
            'frontend-site' => [
                ['name' => 'Julie Fortin', 'email' => 'julie.f@email.com', 'color' => '3B82F6', 'status' => 'Nouveau', 'statusBg' => '#F5E6EA', 'statusColor' => '#5C1A1F', 'video' => true, 'stars' => 3, 'date' => '2026-02-02', 'id' => 'julie', 'isFavorite' => false],
            ],
            'manager-linkedin' => [
                ['name' => 'Jean Dupont', 'email' => 'jean.dupont@email.com', 'color' => '10B981', 'status' => 'Évalué', 'statusBg' => '#D1FAE5', 'statusColor' => '#065F46', 'video' => true, 'stars' => 5, 'date' => '2026-01-28', 'id' => 'jean', 'isFavorite' => true],
            ],
        ];
    }

    /**
     * @return array<int, array{title: string, content: string}>
     */
    public static function getEmailTemplates(): array
    {
        return [
            [
                'title'   => 'Confirmation de réception',
                'content' => "Bonjour {{nom_candidat}},\n\nNous avons bien reçu votre candidature pour le poste de {{titre_poste}} chez {{nom_entreprise}}.",
            ],
            [
                'title'   => "Invitation à l'entrevue vidéo",
                'content' => "Bonjour {{nom_candidat}},\n\nFélicitations ! Votre profil a retenu notre attention pour le poste de {{titre_poste}}.",
            ],
            [
                'title'   => 'Refus poli',
                'content' => "Bonjour {{nom_candidat}},\n\nNous vous remercions sincèrement pour l'intérêt que vous avez porté à notre offre.",
            ],
        ];
    }

    /**
     * @return array<int, array{id: string, name: string, email: string, role: string}>
     */
    public static function getTeamMembers(): array
    {
        return [
            ['id' => '1', 'name' => 'Marie Tremblay', 'email' => 'marie.t@acme.com', 'role' => 'administrateur'],
            ['id' => '2', 'name' => 'Pierre Roy', 'email' => 'pierre.r@acme.com', 'role' => 'evaluateur'],
            ['id' => '3', 'name' => 'Sophie Martin', 'email' => 'sophie.m@acme.com', 'role' => 'evaluateur'],
        ];
    }
}
