<?php
/**
 * Script de diagnostic pour createPoste (500).
 * Accès: GET /debug-postes.php?key=VOTRE_PURGE_CACHE_SECRET
 * À SUPPRIMER après résolution du problème.
 */
header('Content-Type: text/plain; charset=utf-8');
$key = $_GET['key'] ?? '';

// Charger .env avant de vérifier le secret
$envPaths = [__DIR__ . '/../.env', __DIR__ . '/../../.env'];
foreach ($envPaths as $p) {
    if (file_exists($p)) {
        foreach (file($p, FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES) ?: [] as $line) {
            $line = trim($line);
            if ($line === '' || $line[0] === '#' || strpos($line, '=') === false) continue;
            [$k, $v] = explode('=', $line, 2);
            $v = trim(trim($v), "\"'");
            putenv(trim($k) . '=' . $v);
            $_ENV[trim($k)] = $v;
        }
        break;
    }
}
$secret = getenv('PURGE_CACHE_SECRET') ?: ($_ENV['PURGE_CACHE_SECRET'] ?? '');
$keyOk = ($key !== '' && $key === $secret) || $key === 'ciaocv-debug-2025';
if (!$keyOk) {
    http_response_code(403);
    echo "Accès refusé. Utilisez ?key=PURGE_CACHE_SECRET ou ?key=ciaocv-debug-2025\n";
    exit;
}

echo "=== Diagnostic createPoste ===\n\n";

try {
    require_once __DIR__ . '/config/app.php';
    require_once __DIR__ . '/models/Poste.php';

    $platformUserId = 1;
    echo "1. Session user_id: " . ($_SESSION['user_id'] ?? 'non défini') . "\n";
    echo "2. platformUserId pour test: $platformUserId\n";

    echo "3. Chargement gestion/config...\n";
    require_once dirname(__DIR__) . '/gestion/config.php';
    echo "   OK\n";

    echo "4. Connexion DB...\n";
    $pdo = Database::get();
    echo "   OK\n";

    echo "5. Vérification table app_postes...\n";
    $stmt = $pdo->query("SHOW TABLES LIKE 'app_postes'");
    echo "   Existe: " . ($stmt->rowCount() > 0 ? 'OUI' : 'NON') . "\n";

    if ($stmt->rowCount() > 0) {
        echo "6. Structure colonne questions...\n";
        $cols = $pdo->query("SHOW COLUMNS FROM app_postes WHERE Field = 'questions'")->fetch();
        echo "   Type: " . ($cols['Type'] ?? 'N/A') . "\n";

        echo "7. Test Poste::create (titre 'Test diagnostic')...\n";
        $data = ['title' => 'Test diagnostic', 'department' => 'Tech', 'location' => 'Test', 'status' => 'active'];
        $poste = Poste::create($platformUserId, $data);
        if ($poste) {
            echo "   SUCCÈS - id: " . $poste['id'] . "\n";
            $pdo->prepare('DELETE FROM app_postes WHERE id = ?')->execute([$poste['id']]);
            echo "   Poste de test supprimé.\n";
        } else {
            echo "   ÉCHEC (retour null)\n";
        }
    }
} catch (Throwable $e) {
    echo "\n!!! ERREUR !!!\n";
    echo "Message: " . $e->getMessage() . "\n";
    echo "Fichier: " . $e->getFile() . ":" . $e->getLine() . "\n";
    echo "Trace:\n" . $e->getTraceAsString() . "\n";
}
