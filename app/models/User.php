<?php
/**
 * Modèle User (équipe / utilisateurs de l'entreprise)
 * Données mock – à remplacer par des requêtes DB.
 */
class User
{
    /**
     * Liste des membres de l'équipe (utilisateurs de l'app).
     * @return array<int, array{id: string, name: string, email: string, role: string}>
     */
    public static function getAll(): array
    {
        return [
            ['id' => '1', 'name' => 'Marie Tremblay', 'email' => 'marie.t@acme.com', 'role' => 'administrateur'],
            ['id' => '2', 'name' => 'Pierre Roy', 'email' => 'pierre.r@acme.com', 'role' => 'evaluateur'],
            ['id' => '3', 'name' => 'Sophie Martin', 'email' => 'sophie.m@acme.com', 'role' => 'evaluateur'],
        ];
    }
}
