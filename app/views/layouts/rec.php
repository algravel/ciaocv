<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0, maximum-scale=1.0, user-scalable=no">
    <title><?= $pageTitle ?? 'Entrevue de présélection' ?> - <?= APP_NAME ?></title>
    <link rel="icon" type="image/png" href="/<?= asset('assets/img/favicon.png') ?>">
    <link href="https://fonts.googleapis.com/css2?family=Montserrat:wght@400;500;600;700&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.1/css/all.min.css">
    <link rel="stylesheet" href="/<?= asset('assets/css/rec.css') ?>">
    <?= $headExtra ?? '' ?>
</head>
<body class="rec-page">
    <?= $content ?>
</body>
</html>
