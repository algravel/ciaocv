<?php
/**
 * view.php - Affichage de toutes les vidéos stockées dans B2
 */

// Charger les variables d'environnement
$envFile = dirname(__DIR__) . '/.env';
if (file_exists($envFile)) {
    $lines = file($envFile, FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES);
    foreach ($lines as $line) {
        if (strpos($line, '#') === 0) continue;
        if (strpos($line, '=') !== false) {
            list($key, $value) = explode('=', $line, 2);
            $value = trim($value, '"\'');
            $_ENV[trim($key)] = $value;
        }
    }
}

$b2KeyId = $_ENV['B2_KEY_ID'] ?? '';
$b2AppKey = $_ENV['B2_APPLICATION_KEY'] ?? '';
$b2BucketId = $_ENV['B2_BUCKET_ID'] ?? '';

$videos = [];
$error = null;
$downloadAuth = null;
$downloadUrl = null;

try {
    // Étape 1: Autorisation B2
    $authUrl = 'https://api.backblazeb2.com/b2api/v2/b2_authorize_account';
    $credentials = base64_encode($b2KeyId . ':' . $b2AppKey);
    
    $ch = curl_init($authUrl);
    curl_setopt($ch, CURLOPT_HTTPHEADER, ['Authorization: Basic ' . $credentials]);
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    $authResponse = json_decode(curl_exec($ch), true);
    curl_close($ch);
    
    if (!isset($authResponse['authorizationToken'])) {
        throw new Exception('Échec authentification B2');
    }
    
    $apiUrl = $authResponse['apiUrl'];
    $authToken = $authResponse['authorizationToken'];
    $downloadUrl = $authResponse['downloadUrl'];
    
    // Étape 2: Obtenir l'autorisation de téléchargement
    $getDownloadAuthUrl = $apiUrl . '/b2api/v2/b2_get_download_authorization';
    $ch = curl_init($getDownloadAuthUrl);
    curl_setopt($ch, CURLOPT_HTTPHEADER, [
        'Authorization: ' . $authToken,
        'Content-Type: application/json'
    ]);
    curl_setopt($ch, CURLOPT_POST, true);
    curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode([
        'bucketId' => $b2BucketId,
        'fileNamePrefix' => '',
        'validDurationInSeconds' => 3600 // 1 heure
    ]));
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    $downloadAuthResponse = json_decode(curl_exec($ch), true);
    curl_close($ch);
    
    $downloadAuth = $downloadAuthResponse['authorizationToken'] ?? null;
    
    // Étape 3: Lister les fichiers
    $listFilesUrl = $apiUrl . '/b2api/v2/b2_list_file_names';
    $ch = curl_init($listFilesUrl);
    curl_setopt($ch, CURLOPT_HTTPHEADER, [
        'Authorization: ' . $authToken,
        'Content-Type: application/json'
    ]);
    curl_setopt($ch, CURLOPT_POST, true);
    curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode([
        'bucketId' => $b2BucketId,
        'maxFileCount' => 100
    ]));
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    $listResponse = json_decode(curl_exec($ch), true);
    curl_close($ch);
    
    $totalSize = 0;
    if (isset($listResponse['files'])) {
        foreach ($listResponse['files'] as $file) {
            // Filtrer pour n'afficher que les vidéos
            if (strpos($file['contentType'], 'video') !== false || 
                preg_match('/\.(webm|mp4|mov)$/i', $file['fileName'])) {
                $videos[] = [
                    'id' => $file['fileId'],
                    'name' => $file['fileName'],
                    'size' => $file['contentLength'],
                    'uploaded' => $file['uploadTimestamp'],
                    'type' => $file['contentType']
                ];
                $totalSize += $file['contentLength'];
            }
        }
    }
    
    // Trier par date (plus récent en premier)
    usort($videos, function($a, $b) {
        return $b['uploaded'] - $a['uploaded'];
    });
    
} catch (Exception $e) {
    $error = $e->getMessage();
}

// Fonction pour formater la taille
function formatSize($bytes) {
    if ($bytes >= 1073741824) {
        return number_format($bytes / 1073741824, 2) . ' Go';
    } elseif ($bytes >= 1048576) {
        return number_format($bytes / 1048576, 2) . ' Mo';
    } elseif ($bytes >= 1024) {
        return number_format($bytes / 1024, 2) . ' Ko';
    }
    return $bytes . ' octets';
}

// Fonction pour formater la date
function formatDate($timestamp) {
    return date('d/m/Y H:i', $timestamp / 1000);
}
?>
<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0, maximum-scale=1.0, user-scalable=no">
    <title>Mes vidéos - CiaoCV</title>
    <style>
        :root {
            --primary: #2563eb;
            --primary-dark: #1e40af;
            --bg: #111827;
            --card-bg: #1f2937;
            --white: #ffffff;
            --text: #f9fafb;
            --text-light: #9ca3af;
            --border: #374151;
        }

        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
            font-family: 'Segoe UI', system-ui, -apple-system, sans-serif;
        }

        body {
            background-color: var(--bg);
            color: var(--text);
            min-height: 100vh;
        }

        .container {
            max-width: 800px;
            margin: 0 auto;
            padding: 1rem;
        }

        .header {
            display: flex;
            justify-content: space-between;
            align-items: center;
            padding: 0.5rem 0 1.5rem;
        }

        .logo {
            font-size: 1.25rem;
            font-weight: 800;
            color: var(--primary);
        }

        .back-link {
            color: var(--text-light);
            text-decoration: none;
            font-size: 0.875rem;
        }

        h1 {
            font-size: 1.5rem;
            margin-bottom: 1.5rem;
        }

        .video-count {
            color: var(--text-light);
            font-size: 0.875rem;
            margin-bottom: 1rem;
        }

        .video-grid {
            display: grid;
            grid-template-columns: repeat(auto-fill, minmax(280px, 1fr));
            gap: 1rem;
        }

        .video-card {
            background: var(--card-bg);
            border-radius: 0.75rem;
            overflow: hidden;
            border: 1px solid var(--border);
        }

        .video-player {
            width: 100%;
            aspect-ratio: 16/9;
            background: #000;
        }

        .video-info {
            padding: 1rem;
        }

        .video-name {
            font-size: 0.875rem;
            font-weight: 500;
            margin-bottom: 0.5rem;
            word-break: break-all;
        }

        .video-meta {
            font-size: 0.75rem;
            color: var(--text-light);
            display: flex;
            justify-content: space-between;
        }

        .empty-state {
            text-align: center;
            padding: 4rem 2rem;
            color: var(--text-light);
        }

        .empty-state svg {
            width: 80px;
            height: 80px;
            margin-bottom: 1rem;
            opacity: 0.5;
        }

        .empty-state h2 {
            font-size: 1.25rem;
            margin-bottom: 0.5rem;
            color: var(--text);
        }

        .btn {
            display: inline-block;
            background: var(--primary);
            color: white;
            padding: 0.75rem 1.5rem;
            border-radius: 0.5rem;
            text-decoration: none;
            font-weight: 600;
            margin-top: 1rem;
        }

        .btn:hover {
            background: var(--primary-dark);
        }

        .error {
            background: #7f1d1d;
            color: #fecaca;
            padding: 1rem;
            border-radius: 0.5rem;
            margin-bottom: 1rem;
        }

        /* iPhone safe area */
        @supports (padding-top: env(safe-area-inset-top)) {
            .container {
                padding-top: calc(1rem + env(safe-area-inset-top));
                padding-bottom: calc(1rem + env(safe-area-inset-bottom));
            }
        }

        @media (max-width: 600px) {
            .video-grid {
                grid-template-columns: 1fr;
            }
        }
    </style>
</head>
<body>
    <div class="container">
        <div class="header">
            <div class="logo">CiaoCV</div>
            <a href="index2.php" class="back-link">← Retour</a>
        </div>

        <h1>📹 Mes vidéos</h1>

        <?php if ($error): ?>
            <div class="error">
                <strong>Erreur:</strong> <?= htmlspecialchars($error) ?>
            </div>
        <?php endif; ?>

        <?php if (empty($videos)): ?>
            <div class="empty-state">
                <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M15 10l4.553-2.276A1 1 0 0121 8.618v6.764a1 1 0 01-1.447.894L15 14M5 18h8a2 2 0 002-2V8a2 2 0 00-2-2H5a2 2 0 00-2 2v8a2 2 0 002 2z" />
                </svg>
                <h2>Aucune vidéo</h2>
                <p>Vous n'avez pas encore enregistré de vidéo.</p>
                <a href="record.php" class="btn">🎬 Enregistrer ma première vidéo</a>
            </div>
        <?php else: ?>
            <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 1rem;">
                <p class="video-count"><?= count($videos) ?> vidéo<?= count($videos) > 1 ? 's' : '' ?></p>
                <p class="video-count">Espace utilisé: <strong><?= formatSize($totalSize) ?></strong></p>
            </div>
            
            <div class="video-grid">
                <?php foreach ($videos as $video): ?>
                    <?php
                    // Construire l'URL de téléchargement avec autorisation
                    $videoUrl = $downloadUrl . '/file/ciaocv/' . urlencode($video['name']);
                    if ($downloadAuth) {
                        $videoUrl .= '?Authorization=' . $downloadAuth;
                    }
                    ?>
                    <div class="video-card">
                        <video class="video-player" controls preload="metadata">
                            <source src="<?= htmlspecialchars($videoUrl) ?>" type="<?= htmlspecialchars($video['type']) ?>">
                            Votre navigateur ne supporte pas la lecture vidéo.
                        </video>
                        <div class="video-info">
                            <div class="video-name"><?= htmlspecialchars($video['name']) ?></div>
                            <div class="video-meta">
                                <span><?= formatSize($video['size']) ?></span>
                                <span><?= formatDate($video['uploaded']) ?></span>
                            </div>
                        </div>
                    </div>
                <?php endforeach; ?>
            </div>
        <?php endif; ?>
    </div>
</body>
</html>
