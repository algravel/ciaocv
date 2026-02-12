<?php
/**
 * Modèle Entreprise – app_entreprises
 * Infos entreprise par utilisateur plateforme (1:1).
 */
class Entreprise
{
    /** Liste des départements par défaut pour les nouvelles entreprises. */
    public const DEFAULT_DEPARTMENTS = ['Technologie', 'Gestion', 'Marketing', 'Ressources humaines', 'Finance', 'Opérations'];

    private PDO $pdo;

    public function __construct()
    {
        $this->pdo = Database::get();
    }

    public function getByPlatformUserId(int $platformUserId): ?array
    {
        $stmt = $this->pdo->prepare('SELECT * FROM app_entreprises WHERE platform_user_id = ? LIMIT 1');
        $stmt->execute([$platformUserId]);
        $row = $stmt->fetch();
        return $row ?: null;
    }

    /**
     * Crée une entreprise avec les valeurs par défaut (départements inclus).
     * Appelé lors de la création d'un utilisateur dans /gestion.
     */
    public function createWithDefaults(int $platformUserId): bool
    {
        if ($this->getByPlatformUserId($platformUserId)) {
            return true; // existe déjà
        }
        $departmentsJson = json_encode(self::DEFAULT_DEPARTMENTS);
        $stmt = $this->pdo->prepare('INSERT INTO app_entreprises (platform_user_id, name, departments) VALUES (?, ?, ?)');
        return $stmt->execute([$platformUserId, 'Mon entreprise', $departmentsJson]);
    }

    public function upsert(int $platformUserId, array $data): bool
    {
        $name = trim($data['name'] ?? '');
        $industry = trim($data['industry'] ?? '') ?: null;
        $email = trim($data['email'] ?? '') ?: null;
        $phone = trim($data['phone'] ?? '') ?: null;
        $address = trim($data['address'] ?? '') ?: null;
        $description = trim($data['description'] ?? '') ?: null;
        $timezone = trim($data['timezone'] ?? '') ?: 'America/Montreal';
        if (!in_array($timezone, timezone_identifiers_list(), true)) {
            $timezone = 'America/Montreal';
        }

        $existing = $this->getByPlatformUserId($platformUserId);
        if ($existing) {
            $stmt = $this->pdo->prepare('UPDATE app_entreprises SET name = ?, industry = ?, email = ?, phone = ?, address = ?, description = ?, timezone = ? WHERE platform_user_id = ?');
            return $stmt->execute([$name, $industry, $email, $phone, $address, $description, $timezone, $platformUserId]);
        }
        $departmentsJson = json_encode(self::DEFAULT_DEPARTMENTS);
        $stmt = $this->pdo->prepare('INSERT INTO app_entreprises (platform_user_id, name, industry, email, phone, address, description, timezone, departments) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?)');
        return $stmt->execute([$platformUserId, $name, $industry, $email, $phone, $address, $description, $timezone, $departmentsJson]);
    }

    public function updateDepartments(int $platformUserId, array $departments): bool
    {
        $departmentsJson = json_encode(array_values(array_filter(array_map('trim', $departments))));
        $stmt = $this->pdo->prepare('UPDATE app_entreprises SET departments = ? WHERE platform_user_id = ?');
        $ok = $stmt->execute([$departmentsJson, $platformUserId]);
        if ($ok && $stmt->rowCount() === 0) {
            $stmt = $this->pdo->prepare('INSERT INTO app_entreprises (platform_user_id, name, departments) VALUES (?, ?, ?)');
            return $stmt->execute([$platformUserId, 'Mon entreprise', $departmentsJson]);
        }
        return $ok;
    }
}
