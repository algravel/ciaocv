<?php
/**
 * Modèle User (équipe / utilisateurs de l'entreprise)
 * À connecter à la base de données.
 */
class User
{
    /**
     * Liste des membres de l'équipe (utilisateurs de l'app).
     * @return array<int, array{id: string, name: string, email: string, role: string}>
     */
    public static function getAll(): array
    {
        // Pas encore de table app_users — retourner vide
        return [];
    }
}
