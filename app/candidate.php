<?php
/**
 * Espace Candidats - Redirection vers la page par défaut (Esplanade / candidate-jobs.php)
 */
session_start();

if (!isset($_SESSION['user_id'])) {
    header('Location: index.php');
    exit;
}

header('Location: candidate-jobs.php');
exit;
