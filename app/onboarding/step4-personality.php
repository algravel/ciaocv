<?php
/**
 * Étape 4 - Ta personnalité au travail
 * Traits de personnalité visuels
 */
session_start();
require_once __DIR__ . '/../db.php';

$requiredStep = 4;
require_once __DIR__ . '/../includes/onboarding-check.php';

$currentStep = 4;
$stepTitle = "Personnalité";
$error = null;

// Traits de personnalité
$traits = [
    'team_player' => ['icon' => '👥', 'title' => 'J\'aime travailler en équipe', 'desc' => 'Collaboration et entraide'],
    'result_oriented' => ['icon' => '🎯', 'title' => 'Orienté résultats', 'desc' => 'Focalisé sur les objectifs'],
    'customer_friendly' => ['icon' => '😊', 'title' => 'Bon avec les clients', 'desc' => 'À l\'aise avec le public'],
    'fast_efficient' => ['icon' => '⚡', 'title' => 'Rapide et efficace', 'desc' => 'Productif et dynamique'],
    'organized' => ['icon' => '📋', 'title' => 'Organisé', 'desc' => 'Structuré et méthodique'],
    'fast_learner' => ['icon' => '🧠', 'title' => 'Apprends vite', 'desc' => 'Adaptable et curieux'],
];

// Charger les traits actuels
$currentTraits = [];
if ($db) {
    $stmt = $db->prepare('SELECT trait_code FROM user_traits WHERE user_id = ?');
    $stmt->execute([$userId]);
    while ($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
        $currentTraits[] = $row['trait_code'];
    }
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $selectedTraits = $_POST['traits'] ?? [];

    if (empty($selectedTraits)) {
        $error = 'Choisis au moins un trait qui te correspond.';
    } else {
        try {
            // Supprimer les anciens traits
            $db->prepare('DELETE FROM user_traits WHERE user_id = ?')->execute([$userId]);
            
            // Insérer les nouveaux
            $stmt = $db->prepare('INSERT INTO user_traits (user_id, trait_code) VALUES (?, ?)');
            foreach ($selectedTraits as $trait) {
                $stmt->execute([$userId, $trait]);
            }
            
            // Mettre à jour l'étape
            $db->prepare('UPDATE users SET onboarding_step = 5 WHERE id = ?')->execute([$userId]);
            
            header('Location: step5-availability.php');
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
    <meta name="viewport" content="width=device-width, initial-scale=1.0, maximum-scale=1.0, user-scalable=no">
    <title>Personnalité - CiaoCV</title>
    <link rel="icon" href="data:,">
    <style>
        .trait-cards {
            display: grid;
            grid-template-columns: 1fr 1fr;
            gap: 0.75rem;
        }
        .trait-card {
            background: var(--card-bg);
            border: 2px solid var(--border);
            border-radius: 1rem;
            padding: 1.25rem 1rem;
            cursor: pointer;
            transition: all 0.2s;
            text-align: center;
        }
        .trait-card:hover {
            border-color: var(--primary);
            transform: translateY(-2px);
        }
        .trait-card.selected {
            border-color: var(--primary);
            background: rgba(37, 99, 235, 0.15);
        }
        .trait-card input[type="checkbox"] {
            display: none;
        }
        .trait-icon {
            font-size: 2rem;
            margin-bottom: 0.5rem;
        }
        .trait-title {
            font-weight: 600;
            font-size: 0.9rem;
            margin-bottom: 0.25rem;
        }
        .trait-desc {
            font-size: 0.75rem;
            color: var(--text-light);
        }
        .trait-card.selected .trait-desc {
            color: var(--text);
        }
        @media (max-width: 360px) {
            .trait-cards {
                grid-template-columns: 1fr;
            }
        }
    </style>
</head>
<body>
    <div class="onboarding-container">
        <?php include __DIR__ . '/../includes/onboarding-header.php'; ?>

        <h1 class="step-title">Ta personnalité au travail</h1>
        <p class="step-subtitle">Comment tu te décrirais au travail ?</p>

        <div class="onboarding-content">
            <?php if ($error): ?>
                <div class="error"><?= htmlspecialchars($error) ?></div>
            <?php endif; ?>

            <form method="POST">
                <div class="trait-cards">
                    <?php foreach ($traits as $code => $trait): ?>
                    <label class="trait-card <?= in_array($code, $currentTraits) ? 'selected' : '' ?>">
                        <input type="checkbox" name="traits[]" value="<?= $code ?>" 
                               <?= in_array($code, $currentTraits) ? 'checked' : '' ?>>
                        <div class="trait-icon"><?= $trait['icon'] ?></div>
                        <div class="trait-title"><?= $trait['title'] ?></div>
                        <div class="trait-desc"><?= $trait['desc'] ?></div>
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
        document.querySelectorAll('.trait-card').forEach(card => {
            const checkbox = card.querySelector('input[type="checkbox"]');
            checkbox.addEventListener('change', function() {
                card.classList.toggle('selected', this.checked);
            });
        });
    </script>
</body>
</html>
