<?php
/**
 * Modèle Candidat
 * Données mock – à remplacer par des requêtes DB.
 * Filtre par platform_user_id (entreprise) quand fourni.
 */
class Candidat
{
    /**
     * Tous les candidats (vue globale).
     * @param int|null $platformUserId Filtrer par entreprise (utilisateur plateforme)
     * @return array<string, array<string, mixed>>
     */
    public static function getAll(?int $platformUserId = null): array
    {
        // Utilisateur réel connecté → pas de données mock
        if ($platformUserId !== null && $platformUserId > 0) {
            return [];
        }
        return [];
    }

    /**
     * Candidats regroupés par affichage.
     * @param int|null $platformUserId Filtrer par entreprise (utilisateur plateforme)
     * @return array<string, array<int, array<string, mixed>>>
     */
    public static function getByAffichage(?int $platformUserId = null): array
    {
        // Utilisateur réel connecté → pas de données mock
        if ($platformUserId !== null && $platformUserId > 0) {
            return [];
        }
        return [];
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
