<?php
/**
 * Script de migration et seed de la base de données.
 * À exécuter manuellement ou via une route admin protégée.
 */

// Charger la config (sans la logique de migration qui a été retirée)
require_once __DIR__ . '/config.php';

// Vérification basique de sécurité si accessible via HTTP
if (php_sapi_name() !== 'cli') {
    // Si accédé via navigateur, exiger une session admin active
    if (session_status() === PHP_SESSION_NONE) {
        session_start();
    }
    if (empty($_SESSION['gestion_user_id'])) {
        http_response_code(403);
        die('Accès refusé. Veuillez vous connecter en tant qu\'administrateur.');
    }
}

echo "Début des migrations...\n";

try {
    $pdo = Database::get();
    $gestionBase = __DIR__;

    // ─── Auto-init schéma DB si tables absentes ────────────────────────────────
    $stmt = $pdo->query("SHOW TABLES LIKE 'gestion_plans'");
    if ($stmt->rowCount() === 0) {
        $schemaFile = $gestionBase . '/sql/schema.sql';
        if (file_exists($schemaFile)) {
            echo "Création du schéma initial...\n";
            $sql = file_get_contents($schemaFile);
            $sql = preg_replace('/--[^\n]*\n/', "\n", $sql);
            foreach (array_filter(array_map('trim', explode(';', $sql))) as $st) {
                if ($st !== '') {
                    $pdo->exec($st);
                }
            }
        }
    }

    // ─── Migrations successives ────────────────────────────────────────────────
    
    $stmt = $pdo->query("SHOW COLUMNS FROM gestion_admins LIKE 'deleted_at'");
    if ($stmt->rowCount() === 0) {
        echo "Migration: gestion_admins.deleted_at\n";
        $pdo->exec('ALTER TABLE gestion_admins ADD COLUMN deleted_at DATETIME NULL COMMENT "Soft delete" AFTER created_at, ADD INDEX idx_deleted (deleted_at)');
    }

    $stmt = $pdo->query("SHOW COLUMNS FROM gestion_plans LIKE 'name_fr'");
    if ($stmt->rowCount() === 0) {
        echo "Migration: gestion_plans.name_fr/en\n";
        $pdo->exec('ALTER TABLE gestion_plans ADD COLUMN name_fr VARCHAR(100) NOT NULL DEFAULT "" AFTER id, ADD COLUMN name_en VARCHAR(100) NOT NULL DEFAULT "" AFTER name_fr');
        $pdo->exec('UPDATE gestion_plans SET name_fr = name, name_en = name');
        $pdo->exec('ALTER TABLE gestion_plans DROP COLUMN name');
    }

    $stmt = $pdo->query("SHOW COLUMNS FROM gestion_plans LIKE 'active'");
    if ($stmt->rowCount() === 0) {
        echo "Migration: gestion_plans.active\n";
        $pdo->exec('ALTER TABLE gestion_plans ADD COLUMN active TINYINT(1) NOT NULL DEFAULT 1 COMMENT "0=désactivé" AFTER price_yearly');
    }

    $stmt = $pdo->query("SHOW COLUMNS FROM gestion_platform_users LIKE 'billable'");
    if ($stmt->rowCount() === 0) {
        echo "Migration: gestion_platform_users.billable\n";
        $pdo->exec('ALTER TABLE gestion_platform_users ADD COLUMN billable TINYINT(1) NOT NULL DEFAULT 1 COMMENT "0=non facturable" AFTER plan_id');
    }

    $stmt = $pdo->query("SHOW COLUMNS FROM gestion_platform_users LIKE 'prenom_encrypted'");
    if ($stmt->rowCount() === 0) {
        echo "Migration: gestion_platform_users.prenom_encrypted\n";
        $pdo->exec('ALTER TABLE gestion_platform_users ADD COLUMN prenom_encrypted TEXT NULL COMMENT "Prénom" AFTER id');
    }

    $stmt = $pdo->query("SHOW COLUMNS FROM gestion_platform_users LIKE 'active'");
    if ($stmt->rowCount() === 0) {
         echo "Migration: gestion_platform_users.active\n";
        $pdo->exec('ALTER TABLE gestion_platform_users ADD COLUMN active TINYINT(1) NOT NULL DEFAULT 1 COMMENT "0=désactivé" AFTER billable');
    }

    $stmt = $pdo->query("SHOW COLUMNS FROM gestion_platform_users LIKE 'password_hash'");
    if ($stmt->rowCount() === 0) {
         echo "Migration: gestion_platform_users.password_hash\n";
        $pdo->exec('ALTER TABLE gestion_platform_users ADD COLUMN password_hash VARCHAR(255) NULL COMMENT "Hash bcrypt" AFTER email_encrypted');
    }

    $stmt = $pdo->query("SHOW TABLES LIKE 'gestion_feedback'");
    if ($stmt->rowCount() === 0) {
         echo "Migration: table gestion_feedback\n";
        $pdo->exec("CREATE TABLE gestion_feedback (
            id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
            type ENUM('problem', 'idea') NOT NULL DEFAULT 'problem',
            message TEXT NOT NULL,
            source VARCHAR(50) NOT NULL DEFAULT 'app',
            user_email VARCHAR(255) NULL,
            user_name VARCHAR(255) NULL,
            platform_user_id INT UNSIGNED NULL,
            created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
            FOREIGN KEY (platform_user_id) REFERENCES gestion_platform_users(id) ON DELETE SET NULL,
            INDEX idx_created (created_at),
            INDEX idx_type (type)
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci");
    }

    $stmt = $pdo->query("SHOW COLUMNS FROM gestion_feedback LIKE 'status'");
    if ($stmt->rowCount() === 0) {
        echo "Migration: gestion_feedback.status, internal_note\n";
        $pdo->exec("ALTER TABLE gestion_feedback ADD COLUMN status VARCHAR(50) NOT NULL DEFAULT 'new' COMMENT 'new|in_progress|resolved' AFTER created_at");
        $pdo->exec("ALTER TABLE gestion_feedback ADD COLUMN internal_note TEXT NULL COMMENT 'Note interne admin' AFTER status");
    }

    $stmt = $pdo->query("SHOW COLUMNS FROM gestion_events LIKE 'platform_user_id'");
    if ($stmt->rowCount() === 0) {
         echo "Migration: gestion_events.platform_user_id\n";
        $pdo->exec('ALTER TABLE gestion_events ADD COLUMN platform_user_id INT UNSIGNED NULL COMMENT "Pour événements app (employeur)" AFTER admin_id');
        $pdo->exec('ALTER TABLE gestion_events ADD INDEX idx_platform_user (platform_user_id)');
        try {
            $pdo->exec('ALTER TABLE gestion_events ADD CONSTRAINT fk_events_platform FOREIGN KEY (platform_user_id) REFERENCES gestion_platform_users(id) ON DELETE SET NULL');
        } catch (Throwable $e) {
            // FK peut échouer si données incompatibles
        }
    }

    $stmt = $pdo->query("SHOW COLUMNS FROM gestion_events LIKE 'acting_user_name'");
    if ($stmt->rowCount() === 0) {
         echo "Migration: gestion_events.acting_user_name\n";
        $pdo->exec('ALTER TABLE gestion_events ADD COLUMN acting_user_name VARCHAR(255) NULL COMMENT "Nom utilisateur ayant agi (app)" AFTER platform_user_id');
    }

    // App tables
    $stmt = $pdo->query("SHOW TABLES LIKE 'app_entrevues'");
    if ($stmt->rowCount() === 0) {
         echo "Migration: table app_entrevues\n";
        $pdo->exec("CREATE TABLE app_entrevues (
            id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
            platform_user_id INT UNSIGNED NOT NULL,
            created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
            FOREIGN KEY (platform_user_id) REFERENCES gestion_platform_users(id) ON DELETE CASCADE,
            INDEX idx_platform_created (platform_user_id, created_at)
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci");
    }

    $stmt = $pdo->query("SHOW TABLES LIKE 'app_postes'");
    if ($stmt->rowCount() === 0) {
         echo "Migration: table app_postes\n";
        $pdo->exec("CREATE TABLE app_postes (
            id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
            platform_user_id INT UNSIGNED NOT NULL,
            title VARCHAR(255) NOT NULL,
            department VARCHAR(100) NOT NULL DEFAULT '',
            location VARCHAR(255) NOT NULL DEFAULT '',
            status VARCHAR(50) NOT NULL DEFAULT 'active',
            description TEXT NULL,
            record_duration INT UNSIGNED NOT NULL DEFAULT 3,
            questions TEXT NULL COMMENT 'JSON array',
            created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
            FOREIGN KEY (platform_user_id) REFERENCES gestion_platform_users(id) ON DELETE CASCADE,
            INDEX idx_platform_user (platform_user_id)
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci");
    }

    $stmt = $pdo->query("SHOW TABLES LIKE 'app_affichages'");
    if ($stmt->rowCount() === 0) {
         echo "Migration: table app_affichages\n";
        $pdo->exec("CREATE TABLE app_affichages (
            id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
            platform_user_id INT UNSIGNED NOT NULL,
            poste_id INT UNSIGNED NOT NULL,
            share_long_id CHAR(16) NOT NULL,
            platform VARCHAR(100) NOT NULL DEFAULT 'LinkedIn',
            start_date DATE NULL,
            end_date DATE NULL,
            status VARCHAR(50) NOT NULL DEFAULT 'active',
            created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
            UNIQUE KEY idx_share_long (share_long_id),
            FOREIGN KEY (platform_user_id) REFERENCES gestion_platform_users(id) ON DELETE CASCADE,
            FOREIGN KEY (poste_id) REFERENCES app_postes(id) ON DELETE CASCADE,
            INDEX idx_platform_user (platform_user_id)
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci");
    }

    $stmt = $pdo->query("SHOW TABLES LIKE 'app_affichage_evaluateurs'");
    if ($stmt->rowCount() === 0) {
         echo "Migration: table app_affichage_evaluateurs\n";
        $pdo->exec("CREATE TABLE app_affichage_evaluateurs (
            affichage_id INT UNSIGNED NOT NULL,
            platform_user_id INT UNSIGNED NOT NULL,
            notifications_enabled TINYINT(1) NOT NULL DEFAULT 1,
            PRIMARY KEY (affichage_id, platform_user_id),
            FOREIGN KEY (affichage_id) REFERENCES app_affichages(id) ON DELETE CASCADE,
            FOREIGN KEY (platform_user_id) REFERENCES gestion_platform_users(id) ON DELETE CASCADE
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci");
    }
    try {
        $stmt = $pdo->query("SHOW COLUMNS FROM app_affichage_evaluateurs LIKE 'notifications_enabled'");
        if ($stmt->rowCount() === 0) {
             echo "Migration: app_affichage_evaluateurs.notifications_enabled\n";
            $pdo->exec("ALTER TABLE app_affichage_evaluateurs ADD COLUMN notifications_enabled TINYINT(1) NOT NULL DEFAULT 1 AFTER platform_user_id");
        }
    } catch (Throwable $e) { /* ignorer */ }

    $stmt = $pdo->query("SHOW TABLES LIKE 'app_entreprises'");
    if ($stmt->rowCount() === 0) {
         echo "Migration: table app_entreprises\n";
        $pdo->exec("CREATE TABLE app_entreprises (
            id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
            platform_user_id INT UNSIGNED NOT NULL,
            name VARCHAR(255) NOT NULL DEFAULT '',
            industry VARCHAR(100) NULL,
            email VARCHAR(255) NULL,
            phone VARCHAR(50) NULL,
            address TEXT NULL,
            description TEXT NULL,
            timezone VARCHAR(64) NOT NULL DEFAULT 'America/Montreal',
            created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
            updated_at DATETIME DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
            UNIQUE KEY idx_platform_user (platform_user_id),
            FOREIGN KEY (platform_user_id) REFERENCES gestion_platform_users(id) ON DELETE CASCADE
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci");
    }
    try {
        $stmt = $pdo->query("SHOW COLUMNS FROM app_entreprises LIKE 'timezone'");
        if ($stmt->rowCount() === 0) {
            echo "Migration: app_entreprises.timezone\n";
            $pdo->exec("ALTER TABLE app_entreprises ADD COLUMN timezone VARCHAR(64) NOT NULL DEFAULT 'America/Montreal' AFTER description");
        }
    } catch (Throwable $e) { /* ignorer */ }

    // Migration : retirer Design et Stratégie des départements (app_entreprises)
    try {
        $stmt = $pdo->query("SHOW COLUMNS FROM app_entreprises LIKE 'departments'");
        if ($stmt->rowCount() > 0) {
            $rows = $pdo->query("SELECT id, platform_user_id, departments FROM app_entreprises WHERE departments IS NOT NULL AND departments != ''")->fetchAll(PDO::FETCH_ASSOC);
            $excluded = ['Design', 'Stratégie'];
            $updated = 0;
            foreach ($rows as $r) {
                $decoded = json_decode($r['departments'] ?? '[]', true);
                if (!is_array($decoded)) continue;
                $filtered = array_values(array_filter($decoded, fn ($d) => !in_array($d, $excluded, true)));
                if (count($filtered) !== count($decoded)) {
                    $pdo->prepare("UPDATE app_entreprises SET departments = ? WHERE id = ?")->execute([json_encode($filtered), $r['id']]);
                    $updated++;
                }
            }
            if ($updated > 0) {
                echo "Migration: app_entreprises – retrait Design/Stratégie de $updated entreprise(s)\n";
            }
        }
    } catch (Throwable $e) { /* ignorer */ }

    $stmt = $pdo->query("SHOW TABLES LIKE 'app_candidatures'");
    if ($stmt->rowCount() === 0) {
         echo "Migration: table app_candidatures\n";
        $pdo->exec("CREATE TABLE app_candidatures (
            id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
            affichage_id INT UNSIGNED NOT NULL,
            nom VARCHAR(255) NOT NULL,
            prenom VARCHAR(255) NOT NULL,
            email VARCHAR(255) NOT NULL,
            telephone VARCHAR(50) NULL,
            video_path VARCHAR(500) NULL COMMENT 'Chemin B2: entrevue/{longId}/{filename}',
            video_file_id VARCHAR(255) NULL COMMENT 'B2 fileId pour suppression',
            status VARCHAR(50) NOT NULL DEFAULT 'new',
            ip_address VARCHAR(45) NULL COMMENT 'IP du candidat au moment de l\'enregistrement',
            created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
            FOREIGN KEY (affichage_id) REFERENCES app_affichages(id) ON DELETE CASCADE,
            INDEX idx_affichage (affichage_id),
            INDEX idx_status (status)
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci");
    }
    
    // Migration : IP address
    try {
        $stmt = $pdo->query("SHOW COLUMNS FROM app_candidatures LIKE 'ip_address'");
        if ($stmt->rowCount() === 0) {
             echo "Migration: app_candidatures.ip_address\n";
            $pdo->exec("ALTER TABLE app_candidatures ADD COLUMN ip_address VARCHAR(45) NULL COMMENT 'IP du candidat au moment de l\\'enregistrement' AFTER status");
        }
    } catch (Throwable $e) {
        // ignorer si la table n'existe pas encore
    }
    
    // Migration : is_favorite et rating
    try {
        $stmt = $pdo->query("SHOW COLUMNS FROM app_candidatures LIKE 'is_favorite'");
        if ($stmt->rowCount() === 0) {
             echo "Migration: app_candidatures.is_favorite\n";
            $pdo->exec("ALTER TABLE app_candidatures ADD COLUMN is_favorite TINYINT(1) NOT NULL DEFAULT 0 AFTER status");
        }
        $stmt = $pdo->query("SHOW COLUMNS FROM app_candidatures LIKE 'rating'");
        if ($stmt->rowCount() === 0) {
             echo "Migration: app_candidatures.rating\n";
            $pdo->exec("ALTER TABLE app_candidatures ADD COLUMN rating TINYINT UNSIGNED NOT NULL DEFAULT 0 COMMENT 'Appréciation 0-5' AFTER is_favorite");
        }
        $stmt = $pdo->query("SHOW COLUMNS FROM app_candidatures LIKE 'cv_path'");
        if ($stmt->rowCount() === 0) {
             echo "Migration: app_candidatures.cv_path\n";
            $pdo->exec("ALTER TABLE app_candidatures ADD COLUMN cv_path VARCHAR(500) NULL COMMENT 'Chemin R2: entrevue/{longId}/{filename}' AFTER video_path");
        }
    } catch (Throwable $e) {
        // ignorer si la table n'existe pas encore
    }

    // ─── Auto-seed si nécessaire ─────────────────────────────────────────────
    
    // Seed PlatformUser id=1 (Demo)
    $stmt = $pdo->query('SELECT id FROM gestion_platform_users WHERE id = 1 LIMIT 1');
    if (!$stmt->fetch()) {
        echo "Seeding: Demo User (id=1)\n";
        $enc = new Encryption();
        $planId = null;
        $planRow = $pdo->query('SELECT id FROM gestion_plans LIMIT 1')->fetch(PDO::FETCH_ASSOC);
        if ($planRow && !empty($planRow['id'])) {
            $planId = (int) $planRow['id'];
        }
        if ($planId !== null) {
            $pdo->prepare('INSERT INTO gestion_platform_users (id, prenom_encrypted, name_encrypted, email_encrypted, role, plan_id) VALUES (1, ?, ?, ?, ?, ?)')
                ->execute([$enc->encrypt('Demo'), $enc->encrypt('Utilisateur'), $enc->encrypt('demo@ciaocv.com'), 'user', $planId]);
        } else {
            $pdo->prepare('INSERT INTO gestion_platform_users (id, prenom_encrypted, name_encrypted, email_encrypted, role, plan_id) VALUES (1, ?, ?, ?, ?, NULL)')
                ->execute([$enc->encrypt('Demo'), $enc->encrypt('Utilisateur'), $enc->encrypt('demo@ciaocv.com'), 'user']);
        }
    }

    // Seed Plans
    $stmt = $pdo->query('SELECT COUNT(*) FROM gestion_plans');
    if ((int) $stmt->fetchColumn() === 0) {
        echo "Seeding: Plans par défaut\n";
        $pdo->exec("INSERT INTO gestion_plans (name_fr, name_en, video_limit, price_monthly, price_yearly) VALUES
            ('Découverte', 'Discovery', 5, 0, 0),
            ('À la carte', 'Pay per use', 9999, 79, 79),
            ('Pro', 'Pro', 50, 139, 1188),
            ('Expert', 'Expert', 200, 199, 1788)");
    }
    
    echo "Migrations terminées avec succès.\n";

} catch (Throwable $e) {
    echo "Erreur lors des migrations : " . $e->getMessage() . "\n";
    if (php_sapi_name() !== 'cli') {
        http_response_code(500);
    }
}
