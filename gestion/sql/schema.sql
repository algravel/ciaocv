-- Schema base de données pour le module /gestion
-- Préfixe gestion_ pour éviter les conflits avec l'app principale
-- Chiffrement AES-256-GCM géré en PHP (colonnes _encrypted)

CREATE TABLE IF NOT EXISTS gestion_plans (
    id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    name_fr VARCHAR(100) NOT NULL,
    name_en VARCHAR(100) NOT NULL,
    video_limit INT UNSIGNED NOT NULL DEFAULT 10,
    price_monthly DECIMAL(10,2) NOT NULL DEFAULT 0,
    price_yearly DECIMAL(10,2) NOT NULL DEFAULT 0,
    active TINYINT(1) NOT NULL DEFAULT 1 COMMENT '0=désactivé',
    created_at DATETIME DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS gestion_admins (
    id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    email_search_hash CHAR(64) NOT NULL COMMENT 'SHA-256(lowercase(email)) pour recherche',
    email_encrypted TEXT NOT NULL,
    password_hash VARCHAR(255) NOT NULL,
    name_encrypted TEXT NOT NULL,
    role ENUM('admin', 'viewer') DEFAULT 'admin',
    created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
    deleted_at DATETIME NULL COMMENT 'Soft delete',
    UNIQUE KEY idx_email_hash (email_search_hash),
    INDEX idx_deleted (deleted_at)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS gestion_platform_users (
    id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    prenom_encrypted TEXT NULL COMMENT 'Prénom',
    name_encrypted TEXT NOT NULL COMMENT 'Nom de famille',
    email_encrypted TEXT NOT NULL,
    password_hash VARCHAR(255) NULL COMMENT 'Hash bcrypt',
    role VARCHAR(50) NOT NULL DEFAULT 'client',
    plan_id INT UNSIGNED NULL,
    billable TINYINT(1) NOT NULL DEFAULT 1 COMMENT '0=non facturable',
    active TINYINT(1) NOT NULL DEFAULT 1 COMMENT '0=désactivé',
    created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (plan_id) REFERENCES gestion_plans(id) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS gestion_stripe_sales (
    id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    stripe_payment_id VARCHAR(100) NOT NULL,
    customer_email_encrypted TEXT NOT NULL,
    amount_cents INT UNSIGNED NOT NULL,
    currency VARCHAR(3) DEFAULT 'cad',
    status VARCHAR(50) NOT NULL,
    platform_user_id INT UNSIGNED NULL,
    created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
    UNIQUE KEY idx_stripe_id (stripe_payment_id),
    FOREIGN KEY (platform_user_id) REFERENCES gestion_platform_users(id) ON DELETE SET NULL,
    INDEX idx_created (created_at)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS gestion_events (
    id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    admin_id INT UNSIGNED NULL,
    platform_user_id INT UNSIGNED NULL COMMENT 'Pour événements app (employeur)',
    acting_user_name VARCHAR(255) NULL COMMENT 'Nom de l''utilisateur ayant agi (app)',
    action_type VARCHAR(50) NOT NULL,
    entity_type VARCHAR(50) NOT NULL,
    entity_id VARCHAR(100) NULL,
    details_encrypted TEXT NULL,
    created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (admin_id) REFERENCES gestion_admins(id) ON DELETE SET NULL,
    FOREIGN KEY (platform_user_id) REFERENCES gestion_platform_users(id) ON DELETE SET NULL,
    INDEX idx_created (created_at),
    INDEX idx_platform_user (platform_user_id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS app_entrevues (
    id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    platform_user_id INT UNSIGNED NOT NULL,
    created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (platform_user_id) REFERENCES gestion_platform_users(id) ON DELETE CASCADE,
    INDEX idx_platform_created (platform_user_id, created_at)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci COMMENT 'Candidatures/entrevues pour graphique par mois';

CREATE TABLE IF NOT EXISTS gestion_sync_logs (
    id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    sync_type VARCHAR(50) NOT NULL,
    status VARCHAR(50) NOT NULL,
    started_at DATETIME NOT NULL,
    completed_at DATETIME NULL,
    details TEXT NULL,
    INDEX idx_started (started_at)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS gestion_feedback (
    id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    type ENUM('problem', 'idea') NOT NULL DEFAULT 'problem',
    message TEXT NOT NULL,
    source VARCHAR(50) NOT NULL DEFAULT 'app' COMMENT 'app|gestion',
    user_email VARCHAR(255) NULL,
    user_name VARCHAR(255) NULL,
    platform_user_id INT UNSIGNED NULL,
    created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (platform_user_id) REFERENCES gestion_platform_users(id) ON DELETE SET NULL,
    INDEX idx_created (created_at),
    INDEX idx_type (type)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
