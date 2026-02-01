<?php
/**
 * Étape 7 - Mini tests rapides (optionnel mais puissant)
 * Tests de compétences optionnels
 */
session_start();
require_once __DIR__ . '/../db.php';

$requiredStep = 7;
require_once __DIR__ . '/../includes/onboarding-check.php';

$currentStep = 7;
$stepTitle = "Tests";

// Types de tests disponibles
$availableTests = [
    'logic' => [
        'icon' => '🧩',
        'title' => 'Test logique',
        'desc' => '5 questions en 2 min',
        'duration' => '2 min'
    ],
    'customer_service' => [
        'icon' => '🤝',
        'title' => 'Test service client',
        'desc' => 'Mises en situation',
        'duration' => '2 min'
    ],
    'communication' => [
        'icon' => '✍️',
        'title' => 'Communication écrite',
        'desc' => 'Rédaction et clarté',
        'duration' => '3 min'
    ],
];

// Charger les tests déjà complétés
$completedTests = [];
if ($db) {
    $stmt = $db->prepare('SELECT test_type, score FROM user_tests WHERE user_id = ?');
    $stmt->execute([$userId]);
    while ($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
        $completedTests[$row['test_type']] = $row['score'];
    }
}

// Passer à l'étape suivante
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action'])) {
    if ($_POST['action'] === 'skip' || $_POST['action'] === 'continue') {
        $db->prepare('UPDATE users SET onboarding_step = 8 WHERE id = ?')->execute([$userId]);
        header('Location: step8-photo.php');
        exit;
    }
    
    // Simuler un test complété (dans une vraie app, il y aurait le vrai test)
    if ($_POST['action'] === 'complete_test') {
        $testType = $_POST['test_type'] ?? '';
        $score = rand(70, 100); // Score simulé
        
        if ($testType && isset($availableTests[$testType])) {
            $stmt = $db->prepare('INSERT INTO user_tests (user_id, test_type, score) VALUES (?, ?, ?) 
                                  ON DUPLICATE KEY UPDATE score = ?, completed_at = NOW()');
            $stmt->execute([$userId, $testType, $score, $score]);
            
            header('Content-Type: application/json');
            echo json_encode(['success' => true, 'score' => $score]);
            exit;
        }
    }
}
?>
<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0, maximum-scale=1.0, user-scalable=no">
    <title>Tests - CiaoCV</title>
    <link rel="icon" href="data:,">
    <style>
        .tests-intro {
            text-align: center;
            margin-bottom: 1.5rem;
            padding: 1rem;
            background: linear-gradient(135deg, rgba(37, 99, 235, 0.1), rgba(139, 92, 246, 0.1));
            border-radius: 1rem;
        }
        .tests-intro .icon {
            font-size: 2.5rem;
            margin-bottom: 0.5rem;
        }
        .tests-intro p {
            color: var(--text-light);
            font-size: 0.9rem;
        }
        .test-card {
            background: var(--card-bg);
            border: 2px solid var(--border);
            border-radius: 1rem;
            padding: 1.25rem;
            margin-bottom: 0.75rem;
            display: flex;
            align-items: center;
            gap: 1rem;
            cursor: pointer;
            transition: all 0.2s;
        }
        .test-card:hover {
            border-color: var(--primary);
            transform: translateY(-2px);
        }
        .test-card.completed {
            border-color: var(--success);
            background: rgba(34, 197, 94, 0.1);
        }
        .test-icon {
            font-size: 2rem;
            width: 56px;
            height: 56px;
            display: flex;
            align-items: center;
            justify-content: center;
            background: rgba(37, 99, 235, 0.2);
            border-radius: 0.75rem;
        }
        .test-card.completed .test-icon {
            background: rgba(34, 197, 94, 0.2);
        }
        .test-info {
            flex: 1;
        }
        .test-title {
            font-weight: 600;
            margin-bottom: 0.2rem;
        }
        .test-desc {
            font-size: 0.85rem;
            color: var(--text-light);
        }
        .test-badge {
            background: var(--border);
            padding: 0.25rem 0.75rem;
            border-radius: 1rem;
            font-size: 0.75rem;
            color: var(--text-light);
        }
        .test-card.completed .test-badge {
            background: var(--success);
            color: white;
        }
        .test-score {
            font-weight: 700;
            color: var(--success);
            font-size: 1.1rem;
        }
        
        /* Modal de test */
        .test-modal {
            display: none;
            position: fixed;
            top: 0;
            left: 0;
            width: 100%;
            height: 100%;
            background: rgba(0,0,0,0.9);
            z-index: 100;
            justify-content: center;
            align-items: center;
            padding: 1rem;
        }
        .test-modal.active { display: flex; }
        .test-modal-content {
            background: var(--card-bg);
            border-radius: 1rem;
            padding: 2rem;
            max-width: 400px;
            width: 100%;
            text-align: center;
        }
        .test-modal h3 {
            margin-bottom: 1rem;
        }
        .test-modal p {
            color: var(--text-light);
            margin-bottom: 1.5rem;
        }
        .test-progress {
            width: 100%;
            height: 8px;
            background: var(--border);
            border-radius: 4px;
            margin-bottom: 1rem;
            overflow: hidden;
        }
        .test-progress-bar {
            height: 100%;
            background: var(--primary);
            width: 0%;
            transition: width 0.5s;
        }
    </style>
</head>
<body>
    <div class="onboarding-container">
        <?php include __DIR__ . '/../includes/onboarding-header.php'; ?>

        <h1 class="step-title">Ajoute des points forts</h1>
        <p class="step-subtitle">Ces tests sont optionnels mais augmentent ta visibilité</p>

        <div class="onboarding-content">
            <div class="tests-intro">
                <div class="icon">🏆</div>
                <p>Les profils avec tests complétés reçoivent <strong>2x plus de vues</strong></p>
            </div>

            <?php foreach ($availableTests as $testType => $test): 
                $isCompleted = isset($completedTests[$testType]);
                $score = $completedTests[$testType] ?? null;
            ?>
            <div class="test-card <?= $isCompleted ? 'completed' : '' ?>" 
                 data-test="<?= $testType ?>" 
                 onclick="<?= $isCompleted ? '' : "startTest('$testType')" ?>">
                <div class="test-icon"><?= $test['icon'] ?></div>
                <div class="test-info">
                    <div class="test-title"><?= $test['title'] ?></div>
                    <div class="test-desc"><?= $test['desc'] ?></div>
                </div>
                <?php if ($isCompleted): ?>
                    <div class="test-score"><?= $score ?>%</div>
                <?php else: ?>
                    <div class="test-badge"><?= $test['duration'] ?></div>
                <?php endif; ?>
            </div>
            <?php endforeach; ?>
        </div>

        <div class="onboarding-footer">
            <form method="POST" id="continueForm">
                <input type="hidden" name="action" value="continue">
                <button type="submit" class="btn">Continuer</button>
            </form>
            <form method="POST">
                <input type="hidden" name="action" value="skip">
                <button type="submit" class="btn-skip">Passer pour l'instant</button>
            </form>
        </div>
    </div>

    <!-- Modal de test simulé -->
    <div class="test-modal" id="testModal">
        <div class="test-modal-content">
            <h3 id="modalTitle">Test en cours...</h3>
            <div class="test-progress">
                <div class="test-progress-bar" id="testProgressBar"></div>
            </div>
            <p id="modalMessage">Simulation du test en cours...</p>
            <button class="btn hidden" id="modalClose" onclick="closeModal()">Fermer</button>
        </div>
    </div>

    <script>
        const testModal = document.getElementById('testModal');
        const modalTitle = document.getElementById('modalTitle');
        const modalMessage = document.getElementById('modalMessage');
        const testProgressBar = document.getElementById('testProgressBar');
        const modalClose = document.getElementById('modalClose');

        async function startTest(testType) {
            const testNames = {
                'logic': 'Test logique',
                'customer_service': 'Test service client',
                'communication': 'Communication écrite'
            };
            
            testModal.classList.add('active');
            modalTitle.textContent = testNames[testType];
            modalMessage.textContent = 'Préparation du test...';
            testProgressBar.style.width = '0%';
            modalClose.classList.add('hidden');

            // Simulation du test (dans une vraie app, il y aurait de vraies questions)
            for (let i = 0; i <= 100; i += 10) {
                await new Promise(r => setTimeout(r, 200));
                testProgressBar.style.width = i + '%';
                if (i < 100) {
                    modalMessage.textContent = 'Question ' + Math.ceil(i/20) + ' / 5';
                }
            }

            // Envoyer le résultat
            const formData = new FormData();
            formData.append('action', 'complete_test');
            formData.append('test_type', testType);
            
            const response = await fetch('step7-tests.php', { method: 'POST', body: formData });
            const result = await response.json();

            if (result.success) {
                modalTitle.textContent = 'Test terminé !';
                modalMessage.textContent = 'Score: ' + result.score + '%';
                modalClose.classList.remove('hidden');
                
                // Mettre à jour la carte
                const card = document.querySelector(`[data-test="${testType}"]`);
                card.classList.add('completed');
                card.onclick = null;
                card.querySelector('.test-badge').outerHTML = '<div class="test-score">' + result.score + '%</div>';
            }
        }

        function closeModal() {
            testModal.classList.remove('active');
        }
    </script>
</body>
</html>
