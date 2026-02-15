<?php
/**
 * Vérification read-only : des migrations sont-elles en attente ?
 * Utilisé pour afficher la pastille "mise à jour disponible" sur le lien Migration SQL.
 * Ne modifie pas la base.
 */
function migration_pending(): bool
{
    try {
        $pdo = Database::get();
    } catch (Throwable $e) {
        return false;
    }

    // [requête, nombre de lignes attendu si tout est à jour]
    $checks = [
        ['SHOW TABLES LIKE \'gestion_plans\'', 1],
        ['SHOW COLUMNS FROM gestion_admins LIKE \'deleted_at\'', 1],
        ['SHOW COLUMNS FROM gestion_plans LIKE \'name_fr\'', 1],
        ['SHOW COLUMNS FROM gestion_plans LIKE \'active\'', 1],
        ['SHOW COLUMNS FROM gestion_platform_users LIKE \'billable\'', 1],
        ['SHOW COLUMNS FROM gestion_platform_users LIKE \'prenom_encrypted\'', 1],
        ['SHOW COLUMNS FROM gestion_platform_users LIKE \'active\'', 1],
        ['SHOW COLUMNS FROM gestion_platform_users LIKE \'password_hash\'', 1],
        ['SHOW TABLES LIKE \'gestion_feedback\'', 1],
        ['SHOW COLUMNS FROM gestion_feedback LIKE \'status\'', 1],
        ['SHOW COLUMNS FROM gestion_feedback LIKE \'page_url\'', 1],
        ['SHOW COLUMNS FROM gestion_events LIKE \'platform_user_id\'', 1],
        ['SHOW COLUMNS FROM gestion_events LIKE \'acting_user_name\'', 1],
        ['SHOW TABLES LIKE \'app_entrevues\'', 1],
        ['SHOW TABLES LIKE \'app_postes\'', 1],
        ['SHOW TABLES LIKE \'app_affichages\'', 1],
        ['SHOW TABLES LIKE \'app_affichage_evaluateurs\'', 1],
        ['SHOW TABLES LIKE \'app_entreprises\'', 1],
        ['SHOW TABLES LIKE \'app_candidatures\'', 1],
        ['SHOW COLUMNS FROM app_candidatures LIKE \'ip_address\'', 1],
        ['SHOW COLUMNS FROM app_candidatures LIKE \'is_favorite\'', 1],
        ['SHOW COLUMNS FROM app_candidatures LIKE \'cv_path\'', 1],
        ['SHOW TABLES LIKE \'app_candidature_communications\'', 1],
        ['SHOW TABLES LIKE \'app_candidature_comments\'', 1],
        ['SHOW TABLES LIKE \'app_email_templates\'', 1],
        ['SHOW TABLES LIKE \'app_company_members\'', 1],
        ['SHOW TABLES LIKE \'gestion_dev_tasks\'', 1],
    ];

    foreach ($checks as $check) {
        [$sql, $expectedRows] = $check;
        try {
            $stmt = $pdo->query($sql);
            if (!$stmt || $stmt->rowCount() !== $expectedRows) {
                return true;
            }
        } catch (Throwable $e) {
            return true;
        }
    }

    return false;
}
