<?php
declare(strict_types=1);

// Configuration par variables d'environnement : aucune donnée secrète dans Git.
const APP_NAME = 'Vite & Gourmand';

if (PHP_SAPI !== 'cli') {
    ini_set('session.use_strict_mode', '1');
    ini_set('session.cookie_httponly', '1');
    ini_set('session.cookie_samesite', 'Lax');
    if (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off') {
        ini_set('session.cookie_secure', '1');
    }
    session_start();
    header('X-Content-Type-Options: nosniff');
    header('Referrer-Policy: strict-origin-when-cross-origin');
    header('X-Frame-Options: DENY');
} else {
    $_SESSION ??= [];
}

function db(): PDO {
    static $pdo = null;
    if ($pdo instanceof PDO) return $pdo;
    $host = getenv('DB_HOST') ?: '127.0.0.1';
    $name = getenv('DB_NAME') ?: 'vite_gourmand';
    $user = getenv('DB_USER') ?: 'root';
    $pass = getenv('DB_PASSWORD') ?: '';
    $dsn = 'mysql:host=' . $host . ';dbname=' . $name . ';charset=utf8mb4';
    $pdo = new PDO($dsn, $user, $pass, [
        PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
        PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
        PDO::ATTR_EMULATE_PREPARES => false,
    ]);
    return $pdo;
}

function e(?string $text): string { return htmlspecialchars($text ?? '', ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8'); }
function euro(int $cents): string { return number_format($cents / 100, 2, ',', ' ') . ' €'; }
function url(string $page, array $params=[]): string { return 'index.php?' . http_build_query(['page'=>$page] + $params); }
function jump(string $page, array $params=[]): never { header('Location: ' . url($page, $params), true, 303); exit; }
function flash(string $type, string $message): void { $_SESSION['flash'] = ['type'=>$type,'message'=>$message]; }
function csrf(): string { return $_SESSION['csrf'] ??= bin2hex(random_bytes(32)); }
function csrf_input(): string { return '<input type="hidden" name="csrf" value="'.e(csrf()).'">'; }
function csrf_verify(): void {
    if (!isset($_POST['csrf']) || !is_string($_POST['csrf']) || !hash_equals(csrf(), $_POST['csrf'])) {
        http_response_code(419); throw new RuntimeException('Formulaire expiré. Actualisez la page et réessayez.');
    }
}
function current_user(): ?array {
    if (empty($_SESSION['uid'])) return null;
    $stmt = db()->prepare('SELECT id,first_name,last_name,email,phone,address,role,active FROM users WHERE id=?');
    $stmt->execute([(int)$_SESSION['uid']]);
    $u = $stmt->fetch();
    if (!$u || !$u['active']) { unset($_SESSION['uid']); return null; }
    return $u;
}
function require_login(): array {
    $user = current_user();
    if (!$user) { flash('error', 'Connectez-vous pour continuer.'); jump('login'); }
    return $user;
}
function require_staff(): array {
    $user = require_login();
    if (!in_array($user['role'], ['employee','admin'], true)) { http_response_code(403); exit('Accès interdit.'); }
    return $user;
}
function required(string $key, int $max=255): string {
    $value = trim((string)($_POST[$key] ?? ''));
    if ($value === '' || mb_strlen($value) > $max) throw new InvalidArgumentException('Champ incorrect : ' . $key);
    return $value;
}
function password_valid(string $s): bool {
    return mb_strlen($s) >= 10 && preg_match('/[A-Z]/', $s) && preg_match('/[a-z]/', $s)
        && preg_match('/[0-9]/', $s) && preg_match('/[^A-Za-z0-9]/', $s);
}
function menu_by_id(int $id): ?array {
    $q=db()->prepare('SELECT * FROM menus WHERE id = ? AND active=1'); $q->execute([$id]); return $q->fetch() ?: null;
}
