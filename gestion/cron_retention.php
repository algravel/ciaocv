<?php
/**
 * Script de rétention des données (Cron Job)
 * À exécuter une fois par jour.
 * 
 * Politiques :
 * 1. 60 jours après fin d'affichage : Suppression des fichiers (vidéo/CV).
 * 2. 1 an après fin d'affichage : Suppression complète de la candidature (Database).
 */

require_once __DIR__ . '/config.php';
require_once __DIR__ . '/../app/helpers/R2Signer.php';

// Vérification CLI ou Admin
if (php_sapi_name() !== 'cli') {
    if (session_status() === PHP_SESSION_NONE) session_start();
    if (empty($_SESSION['gestion_user_id'])) {
        http_response_code(403);
        die('Accès refusé.');
    }
}

echo "Début du nettoyage des données...\n";
$pdo = Database::get();

// ─── 1. Suppression des fichiers (60 jours après fin) ─────────────────────
// On cherche les candidatures qui ont encore des fichiers (video_path ou cv_path non NULL)
// et dont l'affichage est terminé depuis > 60 jours.

$daysFileRetention = 60;
$dateLimitFiles = date('Y-m-d', strtotime("-$daysFileRetention days"));

echo "Recherche des fichiers à supprimer (Affichages terminés avant le $dateLimitFiles)...\n";

// Sélectionner les affichages "fermés" ou dont la date de fin est passée
// Note: Le statut "closed" ou "expired" dépend de l'implémentation. Ici on se base sur end_date ou status.
$sqlFiles = "
    SELECT c.id, c.video_path, c.cv_path, c.video_file_id 
    FROM app_candidatures c
    JOIN app_affichages a ON c.affichage_id = a.id
    WHERE (c.video_path IS NOT NULL OR c.cv_path IS NOT NULL)
    AND (
        (a.status IN ('closed', 'expired', 'archive')) 
        OR 
        (a.end_date IS NOT NULL AND a.end_date < :limitDate)
    )
";

$stmt = $pdo->prepare($sqlFiles);
$stmt->execute(['limitDate' => $dateLimitFiles]);
$candidatesWithFiles = $stmt->fetchAll(PDO::FETCH_ASSOC);

echo count($candidatesWithFiles) . " candidatures trouvées pour suppression de fichiers.\n";

foreach ($candidatesWithFiles as $cand) {
    $deletedVideo = false;
    $deletedCv = false;

    // Supprimer Vidéo
    if (!empty($cand['video_path'])) {
        if (R2Signer::deleteFile($cand['video_path'])) {
            $deletedVideo = true;
            echo "[OK] Vidéo supprimée: {$cand['video_path']}\n";
        } else {
            echo "[ERR] Échec suppression vidéo: {$cand['video_path']}\n";
        }
    } else {
        $deletedVideo = true; // Déjà vide
    }

    // Supprimer CV
    if (!empty($cand['cv_path'])) {
        if (R2Signer::deleteFile($cand['cv_path'])) {
            $deletedCv = true;
            echo "[OK] CV supprimé: {$cand['cv_path']}\n";
        } else {
            echo "[ERR] Échec suppression CV: {$cand['cv_path']}\n";
        }
    } else {
        $deletedCv = true; // Déjà vide
    }

    // Mise à jour DB si succès
    if ($deletedVideo && $deletedCv) {
        $upd = $pdo->prepare("UPDATE app_candidatures SET video_path = NULL, cv_path = NULL, video_file_id = NULL WHERE id = ?");
        $upd->execute([$cand['id']]);
    } elseif ($deletedVideo) {
        $upd = $pdo->prepare("UPDATE app_candidatures SET video_path = NULL, video_file_id = NULL WHERE id = ?");
        $upd->execute([$cand['id']]);
    } elseif ($deletedCv) {
        $upd = $pdo->prepare("UPDATE app_candidatures SET cv_path = NULL WHERE id = ?");
        $upd->execute([$cand['id']]);
    }
}

// ─── 2. Suppression complète (1 an après fin) ─────────────────────────────
$yearsRetention = 1;
$dateLimitDelete = date('Y-m-d', strtotime("-$yearsRetention year"));

echo "Recherche des candidatures à purger (Affichages terminés avant le $dateLimitDelete)...\n";

$sqlPurge = "
    SELECT c.id 
    FROM app_candidatures c
    JOIN app_affichages a ON c.affichage_id = a.id
    WHERE (
        (a.status IN ('closed', 'expired', 'archive')) 
        OR 
        (a.end_date IS NOT NULL AND a.end_date < :limitDate)
    )
";

$stmt = $pdo->prepare($sqlPurge);
$stmt->execute(['limitDate' => $dateLimitDelete]);
$prospects = $stmt->fetchAll(PDO::FETCH_COLUMN);

$countPurge = count($prospects);
echo "$countPurge candidatures à purger définitivement.\n";

if ($countPurge > 0) {
    // Suppression par lots
    $ids = implode(',', array_map('intval', $prospects));
    $del = $pdo->exec("DELETE FROM app_candidatures WHERE id IN ($ids)");
    echo "Suppression effectuée : $del enregistrements supprimés.\n";
}

echo "Terminé.\n";
