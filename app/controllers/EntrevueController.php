<?php
/**
 * EntrevueController – API endpoints pour l'entrevue candidat
 *
 * POST /entrevue/upload-url  → Générer un presigned URL (Cloudflare R2 / S3)
 * POST /entrevue/submit      → Sauvegarder la candidature (formulaire + vidéo)
 */
class EntrevueController extends Controller
{
    /**
     * Génère un presigned PUT URL pour upload direct vers Cloudflare R2.
     * Le navigateur envoie la vidéo directement à R2 sans passer par le serveur.
     */
    public function getUploadUrl(): void
    {
        $longId = trim($_POST['longId'] ?? '');
        if (!preg_match('/^[a-f0-9]{16}$/', $longId)) {
            $this->json(['error' => 'longId invalide'], 400);
        }

        // Nom de fichier : entrevue/{longId}/{random}.mp4
        $randomName = bin2hex(random_bytes(8));
        $objectKey = 'entrevue/' . $longId . '/' . $randomName . '.mp4';

        // Générer un presigned PUT URL via R2Signer
        $presignedUrl = R2Signer::presignedUrl($objectKey, 'PUT', 'video/mp4', 3600);

        if (!$presignedUrl) {
            $this->json(['error' => 'Configuration R2 manquante'], 500);
        }

        $this->json([
            'uploadUrl' => $presignedUrl,
            'fileName' => $objectKey,
        ]);
    }

    /**
     * Sauvegarde la candidature en BDD (formulaire + infos vidéo R2).
     */
    public function submit(): void
    {
        // Lire le body JSON
        $input = json_decode(file_get_contents('php://input'), true);
        if (!is_array($input)) {
            $this->json(['error' => 'Données invalides'], 400);
        }

        $longId = trim($input['longId'] ?? '');
        $nom = trim($input['nom'] ?? '');
        $prenom = trim($input['prenom'] ?? '');
        $email = trim($input['email'] ?? '');
        $telephone = trim($input['telephone'] ?? '');
        $videoPath = trim($input['videoPath'] ?? '');
        $retakes = (int) ($input['retakes'] ?? 0);
        $timeSpent = (int) ($input['timeSpent'] ?? 0);

        // Validations
        if (!preg_match('/^[a-f0-9]{16}$/', $longId)) {
            $this->json(['error' => 'longId invalide'], 400);
        }
        if ($nom === '' || $prenom === '' || $email === '') {
            $this->json(['error' => 'Nom, prénom et courriel sont requis'], 400);
        }
        if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
            $this->json(['error' => 'Courriel invalide'], 400);
        }
        if ($videoPath === '') {
            $this->json(['error' => 'Chemin vidéo manquant'], 400);
        }

        // Retrouver l'affichage
        $affichage = Affichage::findByShareLongId($longId);
        if (!$affichage) {
            $this->json(['error' => 'Affichage introuvable'], 404);
        }

        $affichageId = (int) $affichage['id'];

        // IP du candidat (derrière proxy/Cloudflare)
        $ip = $_SERVER['HTTP_CF_CONNECTING_IP'] ?? $_SERVER['HTTP_X_FORWARDED_FOR'] ?? $_SERVER['REMOTE_ADDR'] ?? null;
        if (is_string($ip) && strpos($ip, ',') !== false) {
            $ip = trim(explode(',', $ip)[0]);
        }
        $ipAddress = ($ip && filter_var($ip, FILTER_VALIDATE_IP)) ? $ip : null;

        // Charger la config gestion (Database) si pas déjà fait
        require_once dirname(__DIR__, 2) . '/gestion/config.php';
        $pdo = Database::get();

        $stmt = $pdo->prepare("
            INSERT INTO app_candidatures (affichage_id, nom, prenom, email, telephone, video_path, retakes_count, time_spent_seconds, ip_address)
            VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?)
        ");
        $stmt->execute([$affichageId, $nom, $prenom, $email, $telephone ?: null, $videoPath, $retakes, $timeSpent, $ipAddress]);

        $this->json(['success' => true, 'id' => (int) $pdo->lastInsertId()]);
    }
}
