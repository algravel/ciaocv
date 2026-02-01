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
    <meta name="viewport" content="width=device-width, initial-scale=1.0, maximum-scale=1.0, user-scalable=no">
    <title>Profil complété ! - CiaoCV</title>
    <link rel="icon" href="data:,">
    <style>
        .success-container {
            text-align: center;
            padding: 2rem 1rem;
        }
        .success-icon {
            font-size: 5rem;
            margin-bottom: 1rem;
            animation: bounce 0.6s ease;
        }
        @keyframes bounce {
            0%, 100% { transform: translateY(0); }
            50% { transform: translateY(-20px); }
        }
        .success-title {
            font-size: 1.75rem;
            font-weight: 700;
            margin-bottom: 0.5rem;
        }
        .success-subtitle {
            color: var(--text-light);
            font-size: 1rem;
            margin-bottom: 2rem;
        }
        .profile-card {
            background: var(--card-bg);
            border: 1px solid var(--border);
            border-radius: 1rem;
            padding: 1.5rem;
            margin-bottom: 1.5rem;
            text-align: left;
        }
        .profile-header {
            display: flex;
            align-items: center;
            gap: 1rem;
            margin-bottom: 1rem;
        }
        .profile-photo {
            width: 64px;
            height: 64px;
            border-radius: 50%;
            background: var(--border);
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 1.5rem;
            overflow: hidden;
        }
        .profile-photo img {
            width: 100%;
            height: 100%;
            object-fit: cover;
        }
        .profile-name {
            font-weight: 600;
            font-size: 1.1rem;
        }
        .profile-email {
            color: var(--text-light);
            font-size: 0.85rem;
        }
        .completion-bar {
            height: 8px;
            background: var(--border);
            border-radius: 4px;
            overflow: hidden;
            margin-bottom: 0.5rem;
        }
        .completion-fill {
            height: 100%;
            background: linear-gradient(90deg, var(--primary), var(--success));
            border-radius: 4px;
            transition: width 1s ease;
        }
        .completion-text {
            display: flex;
            justify-content: space-between;
            font-size: 0.85rem;
            color: var(--text-light);
        }
        .action-buttons {
            display: flex;
            flex-direction: column;
            gap: 0.75rem;
        }
        .action-btn {
            display: flex;
            align-items: center;
            justify-content: center;
            gap: 0.75rem;
            padding: 1rem;
            border-radius: 0.75rem;
            text-decoration: none;
            font-weight: 600;
            transition: all 0.2s;
        }
        .action-btn.primary {
            background: var(--primary);
            color: white;
        }
        .action-btn.primary:hover {
            background: var(--primary-dark);
        }
        .action-btn.secondary {
            background: var(--card-bg);
            border: 1px solid var(--border);
            color: var(--text);
        }
        .action-btn.secondary:hover {
            background: rgba(37, 99, 235, 0.1);
            border-color: var(--primary);
        }
        .share-section {
            margin-top: 2rem;
            padding-top: 1.5rem;
            border-top: 1px solid var(--border);
        }
        .share-section h3 {
            font-size: 1rem;
            margin-bottom: 1rem;
            color: var(--text-light);
        }
        .share-buttons {
            display: flex;
            gap: 0.75rem;
            justify-content: center;
        }
        .share-btn {
            width: 48px;
            height: 48px;
            border-radius: 50%;
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 1.25rem;
            text-decoration: none;
            transition: transform 0.2s;
        }
        .share-btn:hover {
            transform: scale(1.1);
        }
        .share-btn.linkedin { background: #0077b5; color: white; }
        .share-btn.copy { background: var(--card-bg); border: 1px solid var(--border); }
        .copy-toast {
            position: fixed;
            bottom: 2rem;
            left: 50%;
            transform: translateX(-50%);
            background: var(--success);
            color: white;
            padding: 0.75rem 1.5rem;
            border-radius: 2rem;
            font-size: 0.9rem;
            opacity: 0;
            transition: opacity 0.3s;
            z-index: 100;
        }
        .copy-toast.show { opacity: 1; }
    </style>
</head>
<body>
    <div class="onboarding-container">
        <div class="success-container">
            <div class="success-icon">🚀</div>
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
