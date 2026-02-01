<?php
/**
 * Page de connexion - Redirige vers index.php
 * Si déjà connecté, redirige directement
 */
session_start();

// Si déjà connecté, rediriger vers l'accueil
if (isset($_SESSION['user_id'])) {
    header('Location: index.php');
    exit;
}

// Sinon, rediriger vers index.php qui contient le formulaire de connexion
header('Location: index.php');
exit;
