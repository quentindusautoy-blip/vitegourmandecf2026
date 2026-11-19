-- Migration Vite & Gourmand : exécuter dans vite_gourmand (local)
-- puis lequaiantique_vitegourmand (alwaysdata), jamais dans lequaiantique_api.
-- Non destructive : aucun DROP, CREATE DATABASE ou USE.
CREATE TABLE IF NOT EXISTS login_rate_limits (
    key_hash CHAR(64) CHARACTER SET ascii COLLATE ascii_bin NOT NULL PRIMARY KEY,
    failed_count SMALLINT UNSIGNED NOT NULL DEFAULT 0,
    window_started_at DATETIME NOT NULL,
    INDEX idx_login_window_started_at (window_started_at)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
