<?php
/**
 * Redirection vers la vue unique création/édition.
 * La maintenance se fait dans employer-job-create.php.
 */
$id = isset($_GET['id']) ? (int)$_GET['id'] : 0;
if ($id) {
    header('Location: employer-job-create.php?id=' . $id);
} else {
    header('Location: employer.php');
}
exit;
