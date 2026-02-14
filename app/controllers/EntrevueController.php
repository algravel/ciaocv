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
     * Le navigateur envoie la vidéo (et optionnellement le CV) directement à R2 sans passer par le serveur.
     */
    public function getUploadUrl(): void
    {
        $longId = trim($_POST['longId'] ?? '');
        if (!preg_match('/^[a-f0-9]{16}$/', $longId)) {
            $this->json(['error' => 'longId invalide'], 400);
            return;
        }

        $randomName = bin2hex(random_bytes(8));
        $videoKey = 'entrevue/' . $longId . '/' . $randomName . '.mp4';

        $presignedUrl = R2Signer::presignedUrl($videoKey, 'PUT', 'video/mp4', 3600);
        if (!$presignedUrl) {
            $this->json(['error' => 'Configuration R2 manquante'], 500);
            return;
        }

        $result = [
            'uploadUrl' => $presignedUrl,
            'fileName' => $videoKey,
        ];

        // Générer une URL presignée pour le CV si demandé
        $cvFileName = trim($_POST['cvFileName'] ?? '');
        if ($cvFileName !== '') {
            $ext = strtolower(pathinfo($cvFileName, PATHINFO_EXTENSION));
            $mimeMap = [
                'pdf' => 'application/pdf',
                'doc' => 'application/msword',
                'docx' => 'application/vnd.openxmlformats-officedocument.wordprocessingml.document',
            ];
            $cvMime = $mimeMap[$ext] ?? 'application/octet-stream';
            $cvKey = 'entrevue/' . $longId . '/' . bin2hex(random_bytes(8)) . '.' . ($ext ?: 'pdf');
            $cvPresignedUrl = R2Signer::presignedUrl($cvKey, 'PUT', $cvMime, 3600);
            if ($cvPresignedUrl) {
                $result['cvUploadUrl'] = $cvPresignedUrl;
                $result['cvFileName'] = $cvKey;
            }
        }

        $this->json($result);
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
        $cvPath = trim($input['cvPath'] ?? '');
        $retakes = (int) ($input['retakes'] ?? 0);
        $timeSpent = (int) ($input['timeSpent'] ?? 0);
        $referrer = trim($input['referrer'] ?? '');

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

        // S'assurer que la colonne referrer_url existe
        try {
            $cols = $pdo->query("SHOW COLUMNS FROM app_candidatures LIKE 'referrer_url'")->fetchAll();
            if (empty($cols)) {
                $pdo->exec("ALTER TABLE app_candidatures ADD COLUMN referrer_url VARCHAR(1024) DEFAULT NULL AFTER ip_address");
            }
        } catch (Throwable $e) {
            // ignorer
        }

        $stmt = $pdo->prepare("
            INSERT INTO app_candidatures (affichage_id, nom, prenom, email, telephone, video_path, cv_path, retakes_count, time_spent_seconds, ip_address, referrer_url)
            VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)
        ");
        $stmt->execute([$affichageId, $nom, $prenom, $email, $telephone ?: null, $videoPath, $cvPath ?: null, $retakes, $timeSpent, $ipAddress, $referrer ?: null]);

        $posteTitle = $affichage['title'] ?? 'Poste';
        $candidatName = trim($prenom . ' ' . $nom) ?: $nom;
        $siteUrl = rtrim($_ENV['SITE_URL'] ?? 'https://www.ciaocv.com', '/');
        $viewPath = '/affichages?open=' . $affichageId;
        $viewUrl = $siteUrl . '/connexion?next=' . rawurlencode($viewPath);

        $recipients = Affichage::getNotificationRecipientsForAffichage($affichageId);
        foreach ($recipients as $r) {
            zeptomail_send_new_candidature_notification($r['email'], $r['name'], $posteTitle, $candidatName, $viewUrl);
        }

        $this->json(['success' => true, 'id' => (int) $pdo->lastInsertId()]);
    }
}
