<?php
/**
 * Modèle Poste
 * Données mock – à remplacer par des requêtes DB.
 * Filtre par platform_user_id (entreprise) quand fourni.
 */
class Poste
{
    /**
     * @param int|null $platformUserId Filtrer par entreprise (utilisateur plateforme)
     * @return array<int, array<string, mixed>>
     */
    public static function getAll(?int $platformUserId = null): array
    {
        $all = [
            [
                'id'                => 'frontend',
                'platform_user_id'  => 1,
                'title'       => 'Développeur Frontend',
                'department'  => 'Technologie',
                'location'    => 'Montréal, QC',
                'status'      => 'Actif',
                'statusClass' => 'status-active',
                'candidates'  => 12,
                'date'        => '2026-01-15',
                'description' => "Nous recherchons un développeur frontend passionné pour rejoindre notre équipe. Vous travaillerez avec React, Tailwind et TypeScript pour créer des expériences utilisateur exceptionnelles.",
                'recordDuration' => 3,
                'questions'   => [
                    "Combien d'années d'expérience avez-vous avec React ?",
                    "Avez-vous déjà travaillé avec TypeScript ?",
                    "Pouvez-vous fournir un lien vers votre portfolio ?"
                ],
            ],
            [
                'id'                => 'manager',
                'platform_user_id'  => 1,
                'title'             => 'Chef de projet',
                'department'  => 'Gestion',
                'location'    => 'Toronto, ON',
                'status'      => 'Actif',
                'statusClass' => 'status-active',
                'candidates'  => 8,
                'date'        => '2026-01-20',
                'description' => "Nous cherchons un chef de projet expérimenté pour mener à bien nos initiatives technologiques. Expérience en Agile/Scrum requise.",
                'recordDuration' => 2,
                'questions'   => [
                    "Avez-vous une certification PMP ?",
                    "Décrivez un projet complexe que vous avez géré.",
                    "Quel est votre style de leadership ?"
                ],
            ],
            [
                'id'                => 'designer',
                'platform_user_id'  => 1,
                'title'             => 'Designer UX/UI',
                'department'  => 'Design',
                'location'    => 'Télétravail',
                'status'      => 'Non actif',
                'statusClass' => 'status-paused',
                'candidates'  => 5,
                'date'        => '2026-01-10',
                'description' => "Rejoignez notre équipe créative pour concevoir des interfaces intuitives et esthétiques. Figma est notre outil principal.",
                'recordDuration' => 3,
                'questions'   => [
                    "Lien vers votre portfolio Dribbble/Behance ?",
                    "Racontez-nous un défi UX que vous avez résolu.",
                    "Maîtrisez-vous les design systems ?"
                ],
            ],
            [
                'id'                => 'analyst',
                'platform_user_id'  => 1,
                'title'             => "Analyste d'affaires",
                'department'  => 'Stratégie',
                'location'    => 'Vancouver, BC',
                'status'      => 'Archivé',
                'statusClass' => 'status-closed',
                'candidates'  => 15,
                'date'        => '2025-12-01',
                'description' => "Poste comblé. Merci de votre intérêt.",
                'recordDuration' => 3,
                'questions'   => [],
            ],
        ];

        if ($platformUserId !== null) {
            $all = array_values(array_filter($all, fn ($p) => (int) ($p['platform_user_id'] ?? 0) === $platformUserId));
        }
        return $all;
    }

    /**
     * Retrouver un poste par son identifiant.
     */
    public static function find(string $id, ?int $platformUserId = null): ?array
    {
        foreach (self::getAll($platformUserId) as $poste) {
            if ($poste['id'] === $id) return $poste;
        }
        return null;
    }
}
