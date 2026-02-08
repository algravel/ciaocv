<?php
/**
 * Chiffrement AES-256-GCM pour les données d'identification.
 * Clé depuis GESTION_ENCRYPTION_KEY ou APP_ENCRYPTION_KEY dans .env
 */
class Encryption
{
    private const IV_LENGTH = 12;
    private const TAG_LENGTH = 16;
    private const CIPHER = 'aes-256-gcm';

    private string $key;

    public function __construct()
    {
        $keyB64 = $_ENV['GESTION_ENCRYPTION_KEY'] ?? $_ENV['APP_ENCRYPTION_KEY'] ?? '';
        if ($keyB64 === '') {
            throw new RuntimeException('Clé de chiffrement manquante : GESTION_ENCRYPTION_KEY ou APP_ENCRYPTION_KEY dans .env');
        }
        $key = base64_decode($keyB64, true);
        if ($key === false || strlen($key) !== 32) {
            throw new RuntimeException('Clé de chiffrement invalide : doit être 32 octets en base64');
        }
        $this->key = $key;
    }

    public function encrypt(string $plaintext): string
    {
        $iv = random_bytes(self::IV_LENGTH);
        $tag = '';
        $ciphertext = openssl_encrypt(
            $plaintext,
            self::CIPHER,
            $this->key,
            OPENSSL_RAW_DATA,
            $iv,
            $tag,
            '',
            self::TAG_LENGTH
        );
        if ($ciphertext === false) {
            throw new RuntimeException('Échec du chiffrement');
        }
        return base64_encode($iv . $tag . $ciphertext);
    }

    public function decrypt(string $payload): string|false
    {
        $raw = base64_decode($payload, true);
        if ($raw === false || strlen($raw) < self::IV_LENGTH + self::TAG_LENGTH) {
            return false;
        }
        $iv = substr($raw, 0, self::IV_LENGTH);
        $tag = substr($raw, self::IV_LENGTH, self::TAG_LENGTH);
        $ciphertext = substr($raw, self::IV_LENGTH + self::TAG_LENGTH);
        $plaintext = openssl_decrypt(
            $ciphertext,
            self::CIPHER,
            $this->key,
            OPENSSL_RAW_DATA,
            $iv,
            $tag
        );
        return $plaintext !== false ? $plaintext : false;
    }

    public static function hashEmailForSearch(string $email): string
    {
        return hash('sha256', strtolower(trim($email)));
    }
}
