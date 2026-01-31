<?php
require_once 'db.php';

$id = $_GET['id'] ?? null;
$message = '';

if (!$id) {
    header('Location: employer.php');
    exit;
}

// Traitement du formulaire
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $title = $_POST['title'];
    $company = $_POST['company'];
    $location = $_POST['location'];
    $type = $_POST['type'];
    $salary = $_POST['salary'];
    $description = $_POST['description'];

    try {
        $stmt = $pdo->prepare("UPDATE jobs SET title=?, company=?, location=?, type=?, salary=?, description=? WHERE id=?");
        $stmt->execute([$title, $company, $location, $type, $salary, $description, $id]);
        $message = "Offre mise à jour avec succès !";
    } catch (PDOException $e) {
        $message = "Erreur : " . $e->getMessage();
    }
}

// Récupérer les infos du job
try {
    $stmt = $pdo->prepare("SELECT * FROM jobs WHERE id = ?");
    $stmt->execute([$id]);
    $job = $stmt->fetch();
    if (!$job) die("Offre introuvable.");
} catch (PDOException $e) {
    die("Erreur : " . $e->getMessage());
}
?>
<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Modifier l'offre - CiaoCV</title>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700;800&display=swap" rel="stylesheet">
    <style>
        body { font-family: 'Inter', sans-serif; background: #f3f4f6; padding: 2rem; }
        .container { max-width: 600px; margin: 0 auto; background: white; padding: 2rem; border-radius: 0.5rem; box-shadow: 0 4px 6px rgba(0,0,0,0.1); }
        h1 { margin-top: 0; }
        .form-group { margin-bottom: 1rem; }
        label { display: block; margin-bottom: 0.5rem; font-weight: 500; }
        input, select, textarea { width: 100%; padding: 0.5rem; border: 1px solid #d1d5db; border-radius: 0.375rem; }
        .btn { background: #2563eb; color: white; padding: 0.75rem 1.5rem; border: none; border-radius: 0.375rem; cursor: pointer; font-weight: 600; }
        .btn-back { color: #6b7280; text-decoration: none; margin-right: 1rem; }
        .alert { background: #d1fae5; color: #065f46; padding: 1rem; border-radius: 0.375rem; margin-bottom: 1rem; }
    </style>
</head>
<body>
    <div class="container">
        <a href="employer.php" class="btn-back">← Retour</a>
        <h1>Modifier l'offre</h1>
        
        <?php if ($message): ?>
            <div class="alert"><?= $message ?></div>
        <?php endif; ?>

        <form method="POST">
            <div class="form-group">
                <label>Titre du poste</label>
                <input type="text" name="title" value="<?= htmlspecialchars($job['title']) ?>" required>
            </div>
            <div class="form-group">
                <label>Entreprise</label>
                <input type="text" name="company" value="<?= htmlspecialchars($job['company']) ?>" required>
            </div>
            <div class="form-group">
                <label>Lieu</label>
                <input type="text" name="location" value="<?= htmlspecialchars($job['location']) ?>" required>
            </div>
            <div class="form-group">
                <label>Type</label>
                <select name="type">
                    <option <?= $job['type'] == 'Temps plein' ? 'selected' : '' ?>>Temps plein</option>
                    <option <?= $job['type'] == 'Temps partiel' ? 'selected' : '' ?>>Temps partiel</option>
                    <option <?= $job['type'] == 'Stage' ? 'selected' : '' ?>>Stage</option>
                    <option <?= $job['type'] == 'Contrat' ? 'selected' : '' ?>>Contrat</option>
                </select>
            </div>
            <div class="form-group">
                <label>Salaire</label>
                <input type="text" name="salary" value="<?= htmlspecialchars($job['salary']) ?>">
            </div>
            <div class="form-group">
                <label>Description</label>
                <textarea name="description" rows="5" required><?= htmlspecialchars($job['description']) ?></textarea>
            </div>
            <button type="submit" class="btn">Enregistrer les modifications</button>
        </form>
    </div>
</body>
</html>
