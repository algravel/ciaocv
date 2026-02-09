<?php
/**
 * Modèle Entreprise – app_entreprises
 * Infos entreprise par utilisateur plateforme (1:1).
 */
class Entreprise
{
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

    public function upsert(int $platformUserId, array $data): bool
    {
        $name = trim($data['name'] ?? '');
        $industry = trim($data['industry'] ?? '') ?: null;
        $email = trim($data['email'] ?? '') ?: null;
        $phone = trim($data['phone'] ?? '') ?: null;
        $address = trim($data['address'] ?? '') ?: null;
        $description = trim($data['description'] ?? '') ?: null;

        $existing = $this->getByPlatformUserId($platformUserId);
        if ($existing) {
            $stmt = $this->pdo->prepare('UPDATE app_entreprises SET name = ?, industry = ?, email = ?, phone = ?, address = ?, description = ? WHERE platform_user_id = ?');
            return $stmt->execute([$name, $industry, $email, $phone, $address, $description, $platformUserId]);
        }
        $stmt = $this->pdo->prepare('INSERT INTO app_entreprises (platform_user_id, name, industry, email, phone, address, description) VALUES (?, ?, ?, ?, ?, ?, ?)');
        return $stmt->execute([$platformUserId, $name, $industry, $email, $phone, $address, $description]);
    }
}
