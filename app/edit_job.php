<?php
session_start();
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
        $stmt = $db->prepare("UPDATE jobs SET title=?, company=?, location=?, type=?, salary=?, description=? WHERE id=?");
        $stmt->execute([$title, $company, $location, $type, $salary, $description, $id]);
        $message = "Offre mise à jour avec succès !";
    } catch (PDOException $e) {
        $message = "Erreur : " . $e->getMessage();
    }
}

// Récupérer les infos du job
try {
    $stmt = $db->prepare("SELECT * FROM jobs WHERE id = ?");
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
    <link rel="stylesheet" href="assets/css/design-system.css?v=<?= ASSET_VERSION ?>">
</head>
<body>
    <div class="app-shell">
        <?php $sidebarActive = 'list'; include __DIR__ . '/includes/employer-sidebar.php'; ?>
        <main class="app-main">
        <?php include __DIR__ . '/includes/employer-header.php'; ?>
        <div class="app-main-content layout-app">
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
        </main>
    </div>
</body>
</html>
