-- ============================================================
--  Je-Conduis.com — Base de données leads
--  À exécuter une seule fois dans phpMyAdmin ou MySQL CLI
-- ============================================================

CREATE DATABASE IF NOT EXISTS jeconduis CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;

USE jeconduis;

CREATE TABLE IF NOT EXISTS leads (
    id                  INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,

    -- Coordonnées (chiffrées côté PHP avant insertion)
    prenom              VARCHAR(100)  NOT NULL,
    nom                 VARCHAR(100)  NOT NULL,
    email               VARCHAR(255)  NOT NULL,
    telephone           VARCHAR(30)   NOT NULL,

    -- Profil formulaire
    delai               VARCHAR(30)   DEFAULT NULL,
    type_achat          VARCHAR(20)   DEFAULT NULL,
    usage               VARCHAR(30)   DEFAULT NULL,
    priorite            VARCHAR(30)   DEFAULT NULL,
    kilometrage         VARCHAR(20)   DEFAULT NULL,
    motorisation        VARCHAR(40)   DEFAULT NULL,
    carrosserie         VARCHAR(30)   DEFAULT NULL,
    marque              VARCHAR(80)   DEFAULT NULL,
    modele              VARCHAR(80)   DEFAULT NULL,
    budget              VARCHAR(20)   NOT NULL,

    -- Résultat IA
    ai_recommandations  JSON          DEFAULT NULL,   -- Les 3 véhicules retournés
    ai_conseil          TEXT          DEFAULT NULL,   -- Conseil global
    ai_succes           TINYINT(1)    DEFAULT 0,      -- 1 = IA a répondu, 0 = fallback

    -- Tracking
    ip_hash             VARCHAR(64)   DEFAULT NULL,   -- IP hashée (RGPD)
    user_agent          VARCHAR(255)  DEFAULT NULL,
    created_at          DATETIME      DEFAULT CURRENT_TIMESTAMP,
    updated_at          DATETIME      DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,

    -- Index utiles
    INDEX idx_email     (email),
    INDEX idx_budget    (budget),
    INDEX idx_created   (created_at),
    INDEX idx_ai_succes (ai_succes)

) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
