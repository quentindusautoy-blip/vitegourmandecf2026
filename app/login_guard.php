<?php
declare(strict_types=1);

/**
 * Limitation des tentatives de connexion pour Vite & Gourmand.
 * Aucune adresse email ni IP en clair n'est conservée dans cette table.
 * L'IP est issue de REMOTE_ADDR et non des en-têtes client non fiables.
 */
function vg_login_key(string $email): string {
    $ip = (string) ($_SERVER['REMOTE_ADDR'] ?? 'unknown');
    return hash('sha256', mb_strtolower(trim($email)) . "\n" . $ip);
}

/** Blocage après cinq échecs pendant une fenêtre de quinze minutes. */
function vg_login_is_blocked(string $email): bool {
    $statement = db()->prepare(
        'SELECT failed_count FROM login_rate_limits
         WHERE key_hash = ?
         AND window_started_at >= UTC_TIMESTAMP() - INTERVAL 15 MINUTE LIMIT 1'
    );
    $statement->execute([vg_login_key($email)]);
    return (int) ($statement->fetchColumn() ?: 0) >= 5;
}

function vg_login_record_failure(string $email): void {
    $statement = db()->prepare(
        'INSERT INTO login_rate_limits (key_hash, failed_count, window_started_at)
         VALUES (?, 1, UTC_TIMESTAMP())
         ON DUPLICATE KEY UPDATE
             failed_count = IF(
                 window_started_at < UTC_TIMESTAMP() - INTERVAL 15 MINUTE,
                 1,
                 LEAST(failed_count + 1, 65535)
             ),
             window_started_at = IF(
                 window_started_at < UTC_TIMESTAMP() - INTERVAL 15 MINUTE,
                 UTC_TIMESTAMP(),
                 window_started_at
             )'
    );
    $statement->execute([vg_login_key($email)]);
}

function vg_login_clear_failures(string $email): void {
    $statement = db()->prepare('DELETE FROM login_rate_limits WHERE key_hash = ?');
    $statement->execute([vg_login_key($email)]);
}
