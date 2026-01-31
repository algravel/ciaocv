<?php
require_once 'db.php';

// Récupérer tous les jobs
try {
    $stmt = $pdo->query("SELECT * FROM jobs ORDER BY created_at DESC");
    $jobs = $stmt->fetchAll();
} catch (PDOException $e) {
    die("Erreur : " . $e->getMessage());
}
?>
<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Espace Employeur - CiaoCV</title>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700;800&display=swap" rel="stylesheet">
    <style>
        :root {
            --primary: #2563eb;
            --bg: #f3f4f6;
            --white: #ffffff;
            --text: #1f2937;
        }
        body { font-family: 'Inter', sans-serif; background: var(--bg); color: var(--text); margin: 0; padding: 0; }
        .container { max-width: 1000px; margin: 0 auto; padding: 2rem; }
        .header { display: flex; justify-content: space-between; align-items: center; margin-bottom: 2rem; }
        .btn { background: var(--primary); color: white; padding: 0.5rem 1rem; border-radius: 0.5rem; text-decoration: none; font-weight: 600; }
        .job-list { display: flex; flex-direction: column; gap: 1rem; }
        .job-card { background: var(--white); padding: 1.5rem; border-radius: 0.5rem; display: flex; justify-content: space-between; align-items: center; box-shadow: 0 1px 3px rgba(0,0,0,0.1); }
        .job-info h3 { margin: 0 0 0.5rem 0; }
        .job-info p { margin: 0; color: #6b7280; font-size: 0.9rem; }
        .actions { display: flex; gap: 0.5rem; }
        .btn-edit { background: #e5e7eb; color: #374151; padding: 0.4rem 0.8rem; border-radius: 0.4rem; text-decoration: none; font-size: 0.9rem; }
        .btn-edit:hover { background: #d1d5db; }
    </style>
</head>
<body>
    <div class="container">
        <div class="header">
            <h1>Mes affichages</h1>
            <a href="create_job.php" class="btn">+ Créer un poste</a>
        </div>

        <div class="job-list">
            <?php foreach ($jobs as $job): ?>
            <div class="job-card">
                <div class="job-info">
                    <h3><?= htmlspecialchars($job['title']) ?></h3>
                    <p><?= htmlspecialchars($job['company']) ?> · <?= htmlspecialchars($job['location']) ?> · <?= htmlspecialchars($job['type']) ?></p>
                </div>
                <div class="actions">
                    <a href="edit_job.php?id=<?= $job['id'] ?>" class="btn-edit">Modifier</a>
                </div>
            </div>
            <?php endforeach; ?>
        </div>
    </div>
</body>
</html>
