<?php
/**
 * Étape 5 - Disponibilités
 * Quand peux-tu commencer + plages horaires
 */
session_start();
require_once __DIR__ . '/../db.php';

$requiredStep = 5;
require_once __DIR__ . '/../includes/onboarding-check.php';

$currentStep = 5;
$stepTitle = "Disponibilités";
$error = null;

// Options de début
$startOptions = [
    'immediate' => ['icon' => '🚀', 'title' => 'Disponible immédiatement'],
    '2weeks' => ['icon' => '📅', 'title' => 'Dans 2 semaines'],
];

// Plages horaires
$timeSlots = [
    'day' => ['icon' => '☀️', 'title' => 'Jour', 'desc' => '6h - 14h'],
    'evening' => ['icon' => '🌅', 'title' => 'Soir', 'desc' => '14h - 22h'],
    'night' => ['icon' => '🌙', 'title' => 'Nuit', 'desc' => '22h - 6h'],
    'weekend' => ['icon' => '📆', 'title' => 'Fin de semaine', 'desc' => 'Sam-Dim'],
];

// Charger les valeurs actuelles
$availableImmediately = false;
$availableInWeeks = null;
$currentSlots = [];

if ($db) {
    $stmt = $db->prepare('SELECT available_immediately, available_in_weeks FROM users WHERE id = ?');
    $stmt->execute([$userId]);
    $user = $stmt->fetch(PDO::FETCH_ASSOC);
    $availableImmediately = (bool)$user['available_immediately'];
    $availableInWeeks = $user['available_in_weeks'];
    
    $stmt = $db->prepare('SELECT slot FROM user_availability WHERE user_id = ?');
    $stmt->execute([$userId]);
    while ($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
        $currentSlots[] = $row['slot'];
    }
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $startOption = $_POST['start_option'] ?? '';
    $slots = $_POST['slots'] ?? [];

    if (empty($startOption)) {
        $error = 'Indique quand tu peux commencer.';
    } elseif (empty($slots)) {
        $error = 'Choisis au moins une plage horaire.';
    } else {
        try {
            // Mettre à jour la disponibilité de début
            $immediate = ($startOption === 'immediate') ? 1 : 0;
            $weeks = ($startOption === '2weeks') ? 2 : null;
            
            $db->prepare('UPDATE users SET available_immediately = ?, available_in_weeks = ?, onboarding_step = 6 WHERE id = ?')
               ->execute([$immediate, $weeks, $userId]);
            
            // Supprimer les anciennes plages
            $db->prepare('DELETE FROM user_availability WHERE user_id = ?')->execute([$userId]);
            
            // Insérer les nouvelles
            $stmt = $db->prepare('INSERT INTO user_availability (user_id, slot) VALUES (?, ?)');
            foreach ($slots as $slot) {
                $stmt->execute([$userId, $slot]);
            }
            
            header('Location: step6-video.php');
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
    <title>Disponibilités - CiaoCV</title>
    <link rel="icon" href="data:,">
    <link rel="stylesheet" href="../assets/css/design-system.css?v=<?= ASSET_VERSION ?>">
</head>
<body>
    <div class="onboarding-container">
        <?php include __DIR__ . '/../includes/onboarding-header.php'; ?>

        <h1 class="step-title">Tes disponibilités</h1>
        <p class="step-subtitle">Quand es-tu disponible pour travailler ?</p>

        <div class="onboarding-content">
            <?php if ($error): ?>
                <div class="error"><?= htmlspecialchars($error) ?></div>
            <?php endif; ?>

            <form method="POST">
                <p style="font-weight:600;margin-bottom:0.75rem;color:var(--text-light);font-size:0.9rem;">QUAND PEUX-TU COMMENCER ?</p>
                <div class="option-cards" style="margin-bottom:1.5rem;">
                    <?php foreach ($startOptions as $value => $opt): 
                        $checked = ($value === 'immediate' && $availableImmediately) || ($value === '2weeks' && $availableInWeeks == 2);
                    ?>
                    <label class="option-card <?= $checked ? 'selected' : '' ?>" data-group="start">
                        <input type="radio" name="start_option" value="<?= $value ?>" <?= $checked ? 'checked' : '' ?> required>
                        <div class="option-icon"><?= $opt['icon'] ?></div>
                        <div class="option-text">
                            <div class="option-title"><?= $opt['title'] ?></div>
                        </div>
                    </label>
                    <?php endforeach; ?>
                </div>

                <p style="font-weight:600;margin-bottom:0.75rem;color:var(--text-light);font-size:0.9rem;">PLAGES HORAIRES PRÉFÉRÉES</p>
                <div class="time-slots">
                    <?php foreach ($timeSlots as $code => $slot): ?>
                    <label class="time-slot <?= in_array($code, $currentSlots) ? 'selected' : '' ?>">
                        <input type="checkbox" name="slots[]" value="<?= $code ?>" 
                               <?= in_array($code, $currentSlots) ? 'checked' : '' ?>>
                        <div class="slot-icon"><?= $slot['icon'] ?></div>
                        <div class="slot-title"><?= $slot['title'] ?></div>
                        <div class="slot-desc"><?= $slot['desc'] ?></div>
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
        // Sélection radio pour la date de début
        document.querySelectorAll('.option-card[data-group="start"]').forEach(card => {
            card.addEventListener('click', function() {
                document.querySelectorAll('.option-card[data-group="start"]').forEach(c => c.classList.remove('selected'));
                this.classList.add('selected');
            });
        });

        // Sélection multiple pour les plages horaires
        document.querySelectorAll('.time-slot').forEach(slot => {
            slot.addEventListener('click', function() {
                this.classList.toggle('selected');
            });
        });
    </script>
</body>
</html>
