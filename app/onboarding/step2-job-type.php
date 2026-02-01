<?php
/**
 * Étape 2 - Quel type d'emploi cherches-tu ?
 * Type d'emploi + Lieu de travail
 */
session_start();
require_once __DIR__ . '/../db.php';

$requiredStep = 2;
require_once __DIR__ . '/../includes/onboarding-check.php';

$currentStep = 2;
$stepTitle = "Type d'emploi";
$error = null;

// Types d'emploi
$jobTypes = [
    'full_time' => ['icon' => '💼', 'title' => 'Temps plein', 'desc' => '35-40h par semaine'],
    'part_time' => ['icon' => '⏰', 'title' => 'Temps partiel', 'desc' => 'Moins de 35h par semaine'],
    'shift' => ['icon' => '🔄', 'title' => 'Quart de travail', 'desc' => 'Horaires rotatifs'],
    'temporary' => ['icon' => '📅', 'title' => 'Temporaire', 'desc' => 'Contrat à durée déterminée'],
    'internship' => ['icon' => '🎓', 'title' => 'Stage', 'desc' => 'Formation en entreprise'],
];

// Lieux de travail
$workLocations = [
    'on_site' => ['icon' => '🏢', 'title' => 'Sur place', 'desc' => 'Au bureau ou en magasin'],
    'remote' => ['icon' => '🏠', 'title' => 'Télétravail', 'desc' => '100% à distance'],
    'hybrid' => ['icon' => '🔀', 'title' => 'Hybride', 'desc' => 'Mix bureau et maison'],
];

// Charger les valeurs actuelles
$currentJobType = null;
$currentLocation = null;
if ($db) {
    $stmt = $db->prepare('SELECT job_type, work_location FROM users WHERE id = ?');
    $stmt->execute([$userId]);
    $user = $stmt->fetch(PDO::FETCH_ASSOC);
    $currentJobType = $user['job_type'] ?? null;
    $currentLocation = $user['work_location'] ?? null;
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $jobType = $_POST['job_type'] ?? '';
    $workLocation = $_POST['work_location'] ?? '';

    if (empty($jobType)) {
        $error = 'Choisis un type d\'emploi.';
    } elseif (empty($workLocation)) {
        $error = 'Choisis un lieu de travail.';
    } else {
        try {
            $stmt = $db->prepare('UPDATE users SET job_type = ?, work_location = ?, onboarding_step = 3 WHERE id = ?');
            $stmt->execute([$jobType, $workLocation, $userId]);
            
            header('Location: step3-skills.php');
            exit;
        } catch (PDOException $e) {
            $error = 'Erreur. Réessayez.';
        }
    }
}
?>
<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Type d'emploi - CiaoCV</title>
    <link rel="icon" href="data:,">
    <link rel="stylesheet" href="../assets/css/design-system.css?v=<?= ASSET_VERSION ?>">
</head>
<body>
    <div class="onboarding-container">
        <?php include __DIR__ . '/../includes/onboarding-header.php'; ?>

        <h1 class="step-title">Quel type d'emploi cherches-tu ?</h1>
        <p class="step-subtitle">On va commencer le matching tout de suite</p>

        <div class="onboarding-content">
            <?php if ($error): ?>
                <div class="error"><?= htmlspecialchars($error) ?></div>
            <?php endif; ?>

            <form method="POST" id="step2Form">
                <p style="font-weight:600;margin-bottom:0.75rem;color:var(--text-light);font-size:0.9rem;">TYPE D'EMPLOI</p>
                <div class="option-cards" style="margin-bottom:1.5rem;">
                    <?php foreach ($jobTypes as $value => $type): ?>
                    <label class="option-card <?= $currentJobType === $value ? 'selected' : '' ?>" data-group="job_type">
                        <input type="radio" name="job_type" value="<?= $value ?>" <?= $currentJobType === $value ? 'checked' : '' ?> required>
                        <div class="option-icon"><?= $type['icon'] ?></div>
                        <div class="option-text">
                            <div class="option-title"><?= $type['title'] ?></div>
                            <div class="option-desc"><?= $type['desc'] ?></div>
                        </div>
                    </label>
                    <?php endforeach; ?>
                </div>

                <p style="font-weight:600;margin-bottom:0.75rem;color:var(--text-light);font-size:0.9rem;">LIEU DE TRAVAIL</p>
                <div class="option-cards">
                    <?php foreach ($workLocations as $value => $loc): ?>
                    <label class="option-card <?= $currentLocation === $value ? 'selected' : '' ?>" data-group="work_location">
                        <input type="radio" name="work_location" value="<?= $value ?>" <?= $currentLocation === $value ? 'checked' : '' ?> required>
                        <div class="option-icon"><?= $loc['icon'] ?></div>
                        <div class="option-text">
                            <div class="option-title"><?= $loc['title'] ?></div>
                            <div class="option-desc"><?= $loc['desc'] ?></div>
                        </div>
                    </label>
                    <?php endforeach; ?>
                </div>

                <div class="onboarding-footer">
                    <button type="submit" class="btn">Suivant</button>
                </div>
            </form>
        </div>
    </div>

    <script>
        // Gestion de la sélection des cards
        document.querySelectorAll('.option-card').forEach(card => {
            card.addEventListener('click', function() {
                const group = this.dataset.group;
                document.querySelectorAll(`.option-card[data-group="${group}"]`).forEach(c => c.classList.remove('selected'));
                this.classList.add('selected');
            });
        });
    </script>
</body>
</html>
