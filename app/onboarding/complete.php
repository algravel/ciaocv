<?php
/**
 * Étape 9 - Profil complété !
 * Page de succès et actions suivantes
 */
session_start();
require_once __DIR__ . '/../db.php';

// Vérifier la session (pas de requiredStep car c'est la dernière page)
if (!isset($_SESSION['user_id'])) {
    header('Location: signup.php');
    exit;
}

$currentStep = 9;
$stepTitle = "Terminé";
$userId = $_SESSION['user_id'];
$firstName = $_SESSION['user_first_name'] ?? 'Candidat';

// Charger les infos du profil
$profileData = null;
$completionScore = 0;
if ($db) {
    $stmt = $db->prepare('SELECT * FROM users WHERE id = ?');
    $stmt->execute([$userId]);
    $profileData = $stmt->fetch(PDO::FETCH_ASSOC);
    
    // Calculer le score de complétion
    if ($profileData) {
        if (!empty($profileData['first_name'])) $completionScore += 10;
        if (!empty($profileData['job_type'])) $completionScore += 15;
        if (!empty($profileData['work_location'])) $completionScore += 10;
        if (!empty($profileData['video_url'])) $completionScore += 30;
        if (!empty($profileData['photo_url'])) $completionScore += 15;
        
        // Compétences
        $stmt = $db->prepare('SELECT COUNT(*) FROM user_skills WHERE user_id = ?');
        $stmt->execute([$userId]);
        if ($stmt->fetchColumn() > 0) $completionScore += 10;
        
        // Traits
        $stmt = $db->prepare('SELECT COUNT(*) FROM user_traits WHERE user_id = ?');
        $stmt->execute([$userId]);
        if ($stmt->fetchColumn() > 0) $completionScore += 5;
        
        // Disponibilités
        $stmt = $db->prepare('SELECT COUNT(*) FROM user_availability WHERE user_id = ?');
        $stmt->execute([$userId]);
        if ($stmt->fetchColumn() > 0) $completionScore += 5;
    }
}

$profileUrl = 'https://app.ciaocv.com/profile/' . $userId;
?>
<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Profil complété ! - CiaoCV</title>
    <link rel="icon" href="data:,">
    <link rel="stylesheet" href="../assets/css/design-system.css?v=<?= ASSET_VERSION ?>">
</head>
<body>
    <div class="onboarding-container">
        <div class="success-container">
            <div class="success-icon animate-bounce">🚀</div>
            <h1 class="success-title">Ton profil est en ligne !</h1>
            <p class="success-subtitle">Les employeurs peuvent maintenant te découvrir</p>

            <div class="profile-card">
                <div class="profile-header">
                    <div class="profile-photo">
                        <?php if (!empty($profileData['photo_url'])): ?>
                            <img src="<?= htmlspecialchars($profileData['photo_url']) ?>" alt="Photo">
                        <?php else: ?>
                            👤
                        <?php endif; ?>
                    </div>
                    <div>
                        <div class="profile-name"><?= htmlspecialchars($firstName) ?></div>
                        <div class="profile-email"><?= htmlspecialchars($profileData['email'] ?? '') ?></div>
                    </div>
                </div>
                
                <div class="completion-bar">
                    <div class="completion-fill" style="width: <?= $completionScore ?>%"></div>
                </div>
                <div class="completion-text">
                    <span>Profil complété</span>
                    <span><?= $completionScore ?>%</span>
                </div>
            </div>

            <div class="action-buttons">
                <a href="../candidate-jobs.php" class="action-btn primary">
                    <span>💼</span> Voir les emplois pour moi
                </a>
                <a href="../candidate.php" class="action-btn secondary">
                    <span>👤</span> Mon espace candidat
                </a>
            </div>

            <div class="share-section">
                <h3>Partage ton profil</h3>
                <div class="share-buttons">
                    <a href="https://www.linkedin.com/sharing/share-offsite/?url=<?= urlencode($profileUrl) ?>" 
                       target="_blank" class="share-btn linkedin" title="LinkedIn">
                        in
                    </a>
                    <button class="share-btn copy" onclick="copyLink()" title="Copier le lien">
                        🔗
                    </button>
                </div>
            </div>
        </div>
    </div>

    <div class="copy-toast" id="copyToast">Lien copié !</div>

    <script>
        const profileUrl = '<?= $profileUrl ?>';
        
        function copyLink() {
            navigator.clipboard.writeText(profileUrl).then(() => {
                const toast = document.getElementById('copyToast');
                toast.classList.add('show');
                setTimeout(() => toast.classList.remove('show'), 2000);
            });
        }

        // Animation de la barre de progression
        setTimeout(() => {
            document.querySelector('.completion-fill').style.width = '<?= $completionScore ?>%';
        }, 100);
    </script>
</body>
</html>
