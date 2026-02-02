<?php
/**
 * Maquette : candidatures du candidat (postes où il a postulé)
 */
session_start();
require_once __DIR__ . '/db.php';

// Vérifier si l'utilisateur est connecté
$userId = $_SESSION['user_id'] ?? null;
$userEmail = $_SESSION['user_email'] ?? null;

if (!$userId) {
    header('Location: login.php');
    exit;
}

$withdrawn = false;

// S'assurer que le statut 'withdrawn' existe dans l'ENUM
if ($db) {
    try {
        // Modifier l'ENUM pour ajouter 'withdrawn' si pas déjà présent
        $db->exec("ALTER TABLE applications MODIFY COLUMN status ENUM('new','viewed','accepted','rejected','pool','withdrawn') DEFAULT 'new'");
    } catch (PDOException $e) {
        // Ignorer si déjà modifié
    }
}

// Traitement du retrait de candidature
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['withdraw_application'])) {
    $applicationId = (int)$_POST['application_id'];
    if ($applicationId && $db) {
        try {
            // Vérifier que la candidature appartient bien à l'utilisateur
            $stmt = $db->prepare('UPDATE applications SET status = ? WHERE id = ? AND candidate_email = ?');
            $stmt->execute(['withdrawn', $applicationId, $userEmail]);
            $withdrawn = true;
        } catch (PDOException $e) {
            // Ignorer
        }
    }
}

$applications = [];
$statusLabels = ['new' => 'Nouveau', 'viewed' => 'Vue', 'accepted' => 'Acceptée', 'rejected' => 'Refusée', 'pool' => 'En attente', 'withdrawn' => 'Retirée'];

if ($db) {
    try {
        // Récupérer les candidatures de l'utilisateur (sauf retirées)
        $stmt = $db->prepare('
            SELECT 
                a.id as application_id,
                a.job_id,
                a.status as application_status,
                a.created_at as applied_at,
                j.title as job_title,
                j.status as job_status,
                j.description as job_description
            FROM applications a
            JOIN jobs j ON a.job_id = j.id
            WHERE a.candidate_email = ? AND a.status != ?
            ORDER BY a.created_at DESC
        ');
        $stmt->execute([$userEmail, 'withdrawn']);
        $applications = $stmt->fetchAll(PDO::FETCH_ASSOC);
    } catch (PDOException $e) {
        $applications = [];
    }
}

$jobStatusLabels = ['active' => 'En cours', 'draft' => 'Brouillon', 'closed' => 'Terminé'];
?>
<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Mes candidatures - CiaoCV</title>
    <link rel="stylesheet" href="assets/css/design-system.css?v=<?= ASSET_VERSION ?>">
</head>
<body>
    <div class="app-shell">
        <?php $sidebarActive = 'applications'; include __DIR__ . '/includes/candidate-sidebar.php'; ?>
        <main class="app-main">
        <?php include __DIR__ . '/includes/candidate-header.php'; ?>
        <div class="app-main-content layout-app">

        <h2 style="margin-bottom:1.5rem;font-size:1.25rem;">Mes candidatures</h2>

        <?php if (isset($_GET['success']) && $_GET['success'] == '1'): ?>
            <div class="success" style="padding:1rem;background:#efe;border:1px solid #cfc;border-radius:8px;margin-bottom:1rem;color:#060;">
                ✓ Votre candidature a été envoyée avec succès !
            </div>
        <?php endif; ?>

        <?php if ($withdrawn): ?>
            <div class="success" style="padding:1rem;background:#fef3c7;border:1px solid #fcd34d;border-radius:8px;margin-bottom:1rem;color:#92400e;">
                ✓ Votre candidature a été retirée.
            </div>
        <?php endif; ?>

        <?php if (empty($applications)): ?>
            <div class="empty-state">
                <p>Aucune candidature pour le moment.</p>
                <a href="candidate-jobs.php" style="color:var(--primary);">Voir les offres</a>
            </div>
        <?php else: ?>
            <?php foreach ($applications as $app): ?>
                <?php 
                $jobStatus = $app['job_status'] ?? 'active';
                $jobLabel = $jobStatusLabels[$jobStatus] ?? $jobStatus;
                $appStatus = $app['application_status'] ?? 'new';
                $appLabel = $statusLabels[$appStatus] ?? $appStatus;
                $appliedDate = isset($app['applied_at']) ? date('d/m/Y', strtotime($app['applied_at'])) : '';
                ?>
                <div class="app-card" style="padding:1.25rem;background:#fff;border:1px solid #e5e7eb;border-radius:12px;margin-bottom:1rem;">
                    <div style="display:flex;justify-content:space-between;align-items:flex-start;gap:1rem;">
                        <a href="candidate-job-apply.php?id=<?= $app['job_id'] ?>" style="flex:1;text-decoration:none;color:inherit;">
                            <h3 style="font-size:1.1rem;margin:0 0 0.5rem 0;color:var(--text);"><?= htmlspecialchars($app['job_title']) ?></h3>
                            <?php if (!empty($app['job_description'])): ?>
                                <p style="font-size:0.9rem;color:var(--text-secondary);margin:0 0 0.75rem 0;"><?= htmlspecialchars(mb_substr($app['job_description'], 0, 150)) ?><?= mb_strlen($app['job_description']) > 150 ? '...' : '' ?></p>
                            <?php endif; ?>
                        </a>
                        <form method="POST" onsubmit="return confirm('Es-tu sûr de vouloir retirer ta candidature ? Cette action est irréversible.');">
                            <input type="hidden" name="withdraw_application" value="1">
                            <input type="hidden" name="application_id" value="<?= $app['application_id'] ?>">
                            <button type="submit" style="background:none;border:1px solid #ef4444;color:#ef4444;padding:0.4rem 0.75rem;border-radius:6px;font-size:0.8rem;cursor:pointer;white-space:nowrap;" title="Retirer ma candidature">
                                ✕ Retirer
                            </button>
                        </form>
                    </div>
                    <div class="app-meta" style="display:flex;gap:1rem;flex-wrap:wrap;align-items:center;font-size:0.85rem;color:var(--text-secondary);">
                        <span class="badge badge-<?= $appStatus ?>" style="display:inline-block;padding:0.25rem 0.75rem;background:var(--primary-light, #dbeafe);color:var(--primary);border-radius:6px;font-weight:500;">
                            <?= $appLabel ?>
                        </span>
                        <span class="badge badge-<?= $jobStatus ?>" style="display:inline-block;padding:0.25rem 0.75rem;background:var(--bg-alt, #f3f4f6);color:var(--text-secondary);border-radius:6px;">
                            <?= $jobLabel ?>
                        </span>
                        <?php if ($appliedDate): ?>
                            <span>Postulé le <?= $appliedDate ?></span>
                        <?php endif; ?>
                    </div>
                </div>
            <?php endforeach; ?>
        <?php endif; ?>


        </div>
        </main>
    </div>
</body>
</html>
