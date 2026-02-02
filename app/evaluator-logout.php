<?php
session_start();
unset($_SESSION['evaluator_token'], $_SESSION['evaluator_email'], $_SESSION['evaluator_name'], $_SESSION['evaluator_job_id']);
header('Location: index.php');
exit;
