<?php
/**
 * Script de seed pour le module gestion.
 * Crée les plans par défaut, un admin, et des données de test.
 * Usage: php gestion/sql/seed.php (depuis la racine du projet)
 */

$projectRoot = dirname(__DIR__, 2);
$envFile = $projectRoot . '/.env';

if (!file_exists($envFile)) {
    fwrite(STDERR, "Fichier .env introuvable.\n");
    exit(1);
}

$lines = file($envFile, FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES);
foreach ($lines as $line) {
    $line = trim($line);
    if ($line === '' || $line[0] === '#') continue;
    if (strpos($line, '=') === false) continue;
    list($key, $value) = explode('=', $line, 2);
    $key = trim($key);
    $value = trim($value);
    if (strlen($value) >= 2 && (($value[0] === '"' && $value[strlen($value) - 1] === '"') || ($value[0] === "'" && $value[strlen($value) - 1] === "'"))) {
        $value = substr($value, 1, -1);
    }
    $_ENV[$key] = $value;
}

require_once $projectRoot . '/gestion/config.php';

$pdo = Database::get();
$encryption = new Encryption();

// ─── Plans par défaut (alignés sur https://www.ciaocv.com/tarifs) ──────────
$plans = [
    ['Découverte', 'Discovery', 5, 0, 0],
    ['À la carte', 'Pay per use', 9999, 79, 79],
    ['Pro', 'Pro', 50, 139, 1188],
    ['Expert', 'Expert', 200, 199, 1788],
];

$stmt = $pdo->query('SELECT COUNT(*) FROM gestion_plans');
if ((int) $stmt->fetchColumn() > 0) {
    echo "Les plans existent déjà. Ignoré.\n";
} else {
    $stmt = $pdo->prepare('INSERT INTO gestion_plans (name_fr, name_en, video_limit, price_monthly, price_yearly) VALUES (?, ?, ?, ?, ?)');
    foreach ($plans as $p) {
        $stmt->execute($p);
    }
    echo "Plans créés (Découverte, À la carte, Pro, Expert).\n";
}

// ─── Admin par défaut ─────────────────────────────────────────────────────
$adminEmail = 'admin@ciaocv.com';
$adminPassword = 'AdminDemo2026!';
$adminName = 'Administrateur';

$hash = hash('sha256', strtolower($adminEmail));
$stmt = $pdo->prepare('SELECT id FROM gestion_admins WHERE email_search_hash = ?');
$stmt->execute([$hash]);
if ($stmt->fetch()) {
    echo "Admin existe déjà. Ignoré.\n";
} else {
    $emailEnc = $encryption->encrypt($adminEmail);
    $nameEnc = $encryption->encrypt($adminName);
    $passHash = password_hash($adminPassword, PASSWORD_DEFAULT);
    $stmt = $pdo->prepare('INSERT INTO gestion_admins (email_search_hash, email_encrypted, password_hash, name_encrypted, role) VALUES (?, ?, ?, ?, ?)');
    $stmt->execute([$hash, $emailEnc, $passHash, $nameEnc, 'admin']);
    echo "Admin créé: $adminEmail / $adminPassword\n";
}

// ─── Utilisateurs plateforme de test ───────────────────────────────────────
$stmt = $pdo->query("SELECT id FROM gestion_plans WHERE name_fr = 'Pro' LIMIT 1");
$planId = $stmt->fetchColumn();
if (!$planId) {
    $planId = (int) $pdo->query('SELECT id FROM gestion_plans ORDER BY price_monthly ASC LIMIT 1')->fetchColumn();
}
$stmt = $pdo->query('SELECT COUNT(*) FROM gestion_platform_users');
if ((int) $stmt->fetchColumn() > 0) {
    echo "Utilisateurs plateforme existent déjà. Ignoré.\n";
} else {
    $users = [
        ['Marie Tremblay', 'marie@example.com', 'admin'],
        ['Pierre Roy', 'pierre@example.com', 'user'],
    ];
    $stmt = $pdo->prepare('INSERT INTO gestion_platform_users (name_encrypted, email_encrypted, role, plan_id) VALUES (?, ?, ?, ?)');
    foreach ($users as $u) {
        $stmt->execute([
            $encryption->encrypt($u[0]),
            $encryption->encrypt($u[1]),
            $u[2],
            $planId,
        ]);
    }
    echo "Utilisateurs plateforme créés.\n";
}

// ─── Événements de test ───────────────────────────────────────────────────
$stmt = $pdo->query('SELECT id FROM gestion_admins ORDER BY id LIMIT 1');
$adminId = $stmt->fetchColumn();
$stmt = $pdo->query('SELECT COUNT(*) FROM gestion_events');
if ((int) $stmt->fetchColumn() > 0) {
    echo "Événements existent déjà. Ignoré.\n";
} else {
    $events = [
        ['modification', 'plan', '1', "A modifié le forfait Pro — limite vidéos : 50 → 75"],
        ['creation', 'plan', null, "A créé le forfait Entreprise — 500 vidéos, 199 \$/mois"],
        ['modification', 'user', '2', "A modifié l'utilisateur Sophie Martin — rôle : Évaluateur → Admin"],
        ['creation', 'user', null, "A ajouté l'utilisateur Luc Bergeron (luc@example.com)"],
        ['suppression', 'plan', null, "A supprimé le forfait Starter"],
        ['sale', 'sale', null, "Nouvelle vente Stripe — Forfait Platine, 99,99 \$ (user@example.com)"],
    ];
    $stmt = $pdo->prepare('INSERT INTO gestion_events (admin_id, action_type, entity_type, entity_id, details_encrypted) VALUES (?, ?, ?, ?, ?)');
    foreach ($events as $e) {
        $detailsEnc = $encryption->encrypt($e[3]);
        $stmt->execute([$adminId, $e[0], $e[1], $e[2], $detailsEnc]);
    }
    echo "Événements de test créés.\n";
}

echo "Seed terminé.\n";
