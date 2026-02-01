<?php
/**
 * Profil candidat - données réelles
 */
session_start();
require_once __DIR__ . '/db.php';

// Vérifier si connecté
if (!isset($_SESSION['user_id'])) {
    header('Location: index.php');
    exit;
}

$userId = $_SESSION['user_id'];
$success = null;
$error = null;

// Charger les données utilisateur
$user = null;
if ($db) {
    $stmt = $db->prepare('SELECT * FROM users WHERE id = ?');
    $stmt->execute([$userId]);
    $user = $stmt->fetch(PDO::FETCH_ASSOC);
}

if (!$user) {
    header('Location: index.php');
    exit;
}

// Traitement du formulaire
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action'])) {
    if ($_POST['action'] === 'update_profile') {
        $firstName = trim($_POST['first_name'] ?? '');
        $email = strtolower(trim($_POST['email'] ?? ''));
        
        if (empty($firstName)) {
            $error = 'Le prénom est requis.';
        } elseif (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
            $error = 'Email invalide.';
        } else {
            try {
                // Vérifier si l'email est déjà utilisé par un autre utilisateur
                $stmt = $db->prepare('SELECT id FROM users WHERE email = ? AND id != ?');
                $stmt->execute([$email, $userId]);
                if ($stmt->fetch()) {
                    $error = 'Cet email est déjà utilisé.';
                } else {
                    $db->prepare('UPDATE users SET first_name = ?, email = ? WHERE id = ?')
                       ->execute([$firstName, $email, $userId]);
                    $success = 'Profil mis à jour.';
                    // Recharger les données
                    $user['first_name'] = $firstName;
                    $user['email'] = $email;
                    $_SESSION['user_email'] = $email;
                }
            } catch (PDOException $e) {
                $error = 'Erreur lors de la mise à jour.';
            }
        }
    }
}

// Données du profil
$firstName = $user['first_name'] ?? '';
$email = $user['email'] ?? '';
$videoUrl = $user['video_url'] ?? null;
$photoUrl = $user['photo_url'] ?? null;
$jobType = $user['job_type'] ?? null;
$workLocation = $user['work_location'] ?? null;
$onboardingCompleted = (bool)$user['onboarding_completed'];

// Labels pour affichage
$jobTypeLabels = [
    'full_time' => 'Temps plein',
    'part_time' => 'Temps partiel',
    'shift' => 'Quart de travail',
    'temporary' => 'Temporaire',
    'internship' => 'Stage'
];
$workLocationLabels = [
    'on_site' => 'Sur place',
    'remote' => 'Télétravail',
    'hybrid' => 'Hybride'
];

// Charger les compétences
$skills = [];
if ($db) {
    $stmt = $db->prepare('SELECT skill_name, skill_level FROM user_skills WHERE user_id = ?');
    $stmt->execute([$userId]);
    $skills = $stmt->fetchAll(PDO::FETCH_ASSOC);
}

// Charger les traits de personnalité
$traits = [];
$traitLabels = [
    'team_player' => 'Travail d\'équipe',
    'result_oriented' => 'Orienté résultats',
    'customer_friendly' => 'Bon avec les clients',
    'fast_efficient' => 'Rapide et efficace',
    'organized' => 'Organisé',
    'fast_learner' => 'Apprends vite'
];
if ($db) {
    $stmt = $db->prepare('SELECT trait_code FROM user_traits WHERE user_id = ?');
    $stmt->execute([$userId]);
    while ($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
        $traits[] = $traitLabels[$row['trait_code']] ?? $row['trait_code'];
    }
}
?>
<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Mon profil - CiaoCV</title>
    <style>
        :root { --primary: #2563eb; --primary-dark: #1e40af; --bg: #111827; --card-bg: #1f2937; --text: #f9fafb; --text-light: #9ca3af; --border: #374151; --success: #22c55e; }
        * { margin: 0; padding: 0; box-sizing: border-box; font-family: system-ui, sans-serif; }
        body { background: var(--bg); color: var(--text); min-height: 100vh; }
        .container { max-width: 600px; margin: 0 auto; padding: 1.5rem; }
        .header { display: flex; justify-content: space-between; align-items: center; margin-bottom: 2rem; }
        .logo { font-size: 1.25rem; font-weight: 800; color: var(--primary); text-decoration: none; }
        .back { color: var(--text-light); text-decoration: none; }
        .back:hover { color: var(--primary); }
        .section { background: var(--card-bg); border: 1px solid var(--border); border-radius: 1rem; padding: 1.5rem; margin-bottom: 1.5rem; }
        .section h2 { font-size: 1.1rem; margin-bottom: 1rem; display: flex; justify-content: space-between; align-items: center; }
        .section h2 a { font-size: 0.85rem; color: var(--primary); text-decoration: none; }
        .form-group { margin-bottom: 1rem; }
        .form-group label { display: block; font-size: 0.9rem; margin-bottom: 0.5rem; color: var(--text-light); }
        .form-group input { width: 100%; padding: 0.75rem; border: 1px solid var(--border); border-radius: 0.5rem; background: #111; color: var(--text); }
        .form-group input:focus { outline: none; border-color: var(--primary); }
        .btn { display: inline-block; padding: 0.5rem 1rem; border-radius: 0.5rem; font-size: 0.9rem; text-decoration: none; font-weight: 600; border: none; cursor: pointer; }
        .btn-primary { background: var(--primary); color: white; }
        .btn-primary:hover { background: var(--primary-dark); }
        .btn-outline { background: transparent; border: 1px solid var(--border); color: var(--text); }
        .btn-outline:hover { border-color: var(--primary); color: var(--primary); }
        
        .profile-header { display: flex; align-items: center; gap: 1rem; margin-bottom: 1.5rem; }
        .profile-photo { width: 80px; height: 80px; border-radius: 50%; background: var(--border); overflow: hidden; flex-shrink: 0; }
        .profile-photo img { width: 100%; height: 100%; object-fit: cover; }
        .profile-photo .placeholder { width: 100%; height: 100%; display: flex; align-items: center; justify-content: center; font-size: 2rem; color: var(--text-light); }
        .profile-info h3 { font-size: 1.25rem; margin-bottom: 0.25rem; }
        .profile-info p { color: var(--text-light); font-size: 0.9rem; }
        
        .info-grid { display: grid; grid-template-columns: 1fr 1fr; gap: 1rem; }
        .info-item { background: #111; padding: 0.75rem 1rem; border-radius: 0.5rem; }
        .info-item label { display: block; font-size: 0.75rem; color: var(--text-light); margin-bottom: 0.25rem; }
        .info-item span { font-weight: 600; }
        
        .video-preview { position: relative; background: #000; border-radius: 0.75rem; overflow: hidden; aspect-ratio: 16/9; margin-bottom: 1rem; }
        .video-preview video { width: 100%; height: 100%; object-fit: cover; }
        .video-preview .no-video { width: 100%; height: 100%; display: flex; flex-direction: column; align-items: center; justify-content: center; color: var(--text-light); }
        .video-preview .no-video span { font-size: 3rem; margin-bottom: 0.5rem; }
        
        .tags { display: flex; flex-wrap: wrap; gap: 0.5rem; }
        .tag { background: rgba(37, 99, 235, 0.2); color: var(--primary); padding: 0.35rem 0.75rem; border-radius: 2rem; font-size: 0.85rem; }
        
        .success { background: rgba(34, 197, 94, 0.2); color: #4ade80; padding: 0.75rem; border-radius: 0.5rem; margin-bottom: 1rem; }
        .error { background: rgba(239, 68, 68, 0.2); color: #f87171; padding: 0.75rem; border-radius: 0.5rem; margin-bottom: 1rem; }
        
        .footer { margin-top: 2rem; text-align: center; font-size: 0.8rem; color: var(--text-light); }
        .footer a { color: var(--primary); }
    </style>
</head>
<body>
    <div class="container">
        <header class="header">
            <a href="candidate.php" class="logo">CiaoCV</a>
            <a href="candidate.php" class="back">← Espace candidat</a>
        </header>

        <?php if ($success): ?>
            <div class="success"><?= htmlspecialchars($success) ?></div>
        <?php endif; ?>
        <?php if ($error): ?>
            <div class="error"><?= htmlspecialchars($error) ?></div>
        <?php endif; ?>

        <!-- En-tête du profil -->
        <div class="profile-header">
            <div class="profile-photo">
                <?php if ($photoUrl): ?>
                    <img src="<?= htmlspecialchars($photoUrl) ?>" alt="Photo de profil">
                <?php else: ?>
                    <div class="placeholder">👤</div>
                <?php endif; ?>
            </div>
            <div class="profile-info">
                <h3><?= htmlspecialchars($firstName ?: 'Candidat') ?></h3>
                <p><?= htmlspecialchars($email) ?></p>
            </div>
        </div>

        <!-- Informations de base -->
        <div class="section">
            <h2>Informations <a href="onboarding/step2-job-type.php">Modifier →</a></h2>
            <form method="POST">
                <input type="hidden" name="action" value="update_profile">
                <div class="form-group">
                    <label>Prénom</label>
                    <input type="text" name="first_name" value="<?= htmlspecialchars($firstName) ?>" placeholder="Votre prénom" required>
                </div>
                <div class="form-group">
                    <label>Courriel</label>
                    <input type="email" name="email" value="<?= htmlspecialchars($email) ?>" placeholder="votre@email.com" required>
                </div>
                <button type="submit" class="btn btn-primary">Sauvegarder</button>
            </form>
        </div>

        <!-- Préférences d'emploi -->
        <div class="section">
            <h2>Préférences <a href="onboarding/step2-job-type.php">Modifier →</a></h2>
            <div class="info-grid">
                <div class="info-item">
                    <label>Type d'emploi</label>
                    <span><?= $jobTypeLabels[$jobType] ?? 'Non défini' ?></span>
                </div>
                <div class="info-item">
                    <label>Lieu de travail</label>
                    <span><?= $workLocationLabels[$workLocation] ?? 'Non défini' ?></span>
                </div>
            </div>
        </div>

        <!-- Compétences -->
        <?php if (!empty($skills)): ?>
        <div class="section">
            <h2>Compétences <a href="onboarding/step3-skills.php">Modifier →</a></h2>
            <div class="tags">
                <?php foreach ($skills as $skill): ?>
                    <span class="tag"><?= htmlspecialchars($skill['skill_name']) ?></span>
                <?php endforeach; ?>
            </div>
        </div>
        <?php endif; ?>

        <!-- Personnalité -->
        <?php if (!empty($traits)): ?>
        <div class="section">
            <h2>Personnalité <a href="onboarding/step4-personality.php">Modifier →</a></h2>
            <div class="tags">
                <?php foreach ($traits as $trait): ?>
                    <span class="tag"><?= htmlspecialchars($trait) ?></span>
                <?php endforeach; ?>
            </div>
        </div>
        <?php endif; ?>

        <!-- Vidéo de présentation -->
        <div class="section">
            <h2>Ma vidéo <a href="onboarding/step6-video.php">Modifier →</a></h2>
            <div class="video-preview">
                <?php if ($videoUrl): ?>
                    <video controls src="<?= htmlspecialchars($videoUrl) ?>"></video>
                <?php else: ?>
                    <div class="no-video">
                        <span>🎬</span>
                        <p>Aucune vidéo</p>
                    </div>
                <?php endif; ?>
            </div>
            <?php if (!$videoUrl): ?>
                <a href="onboarding/step6-video.php" class="btn btn-primary">Enregistrer ma vidéo</a>
            <?php endif; ?>
        </div>

        <!-- Photo -->
        <div class="section">
            <h2>Ma photo <a href="onboarding/step8-photo.php">Modifier →</a></h2>
            <?php if ($photoUrl): ?>
                <div style="text-align:center;">
                    <img src="<?= htmlspecialchars($photoUrl) ?>" alt="Photo" style="width:150px;height:150px;border-radius:50%;object-fit:cover;">
                </div>
            <?php else: ?>
                <p style="color:var(--text-light);margin-bottom:1rem;">Aucune photo de profil</p>
                <a href="onboarding/step8-photo.php" class="btn btn-primary">Ajouter une photo</a>
            <?php endif; ?>
        </div>

        <div class="footer">
            <a href="https://www.ciaocv.com">Retour au site principal</a>
        </div>
    </div>
</body>
</html>
