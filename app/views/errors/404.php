<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>404 - Page non trouvée | <?= APP_NAME ?></title>
    <link href="https://fonts.googleapis.com/css2?family=Montserrat:wght@400;600;800&display=swap" rel="stylesheet">
    <style>
        body { font-family: 'Montserrat', sans-serif; display: flex; align-items: center; justify-content: center; min-height: 100vh; margin: 0; background: #F8FAFC; color: #0F172A; }
        .container { text-align: center; }
        h1 { font-size: 6rem; font-weight: 800; color: #2563EB; margin: 0; }
        p { font-size: 1.1rem; color: #64748B; margin: 1rem 0 2rem; }
        a { display: inline-block; padding: 0.75rem 2rem; background: #2563EB; color: white; border-radius: 12px; text-decoration: none; font-weight: 600; transition: background 0.2s; }
        a:hover { background: #1d4ed8; }
    </style>
</head>
<body>
    <div class="container">
        <h1>404</h1>
        <p>La page que vous cherchez n'existe pas.</p>
        <a href="<?= SITE_URL ?>">Retour à l'accueil</a>
    </div>
</body>
</html>
