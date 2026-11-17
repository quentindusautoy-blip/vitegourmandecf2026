<?php
declare(strict_types=1);

// Diagnostic temporaire réservé à l'administrateur de Vite & Gourmand.
// Supprimer ce fichier du serveur après utilisation.
require_once dirname(__DIR__) . '/app/bootstrap.php';

header('Content-Type: text/plain; charset=utf-8');
header('Cache-Control: no-store, private');

$user = current_user();
if (!$user || $user['role'] !== 'admin') {
    http_response_code(403);
    exit("Acces reserve a l'administrateur de Vite & Gourmand.\n");
}

$extensionOk = extension_loaded('mongodb');
$uri = trim((string)(getenv('MONGODB_URI') ?: ''));
$database = trim((string)(getenv('MONGODB_DB') ?: ''));

echo '1. Extension MongoDB : ' . ($extensionOk ? 'ACTIVE' : 'ABSENTE') . "\n";
echo '2. Variable MONGODB_URI : ' . ($uri !== '' ? 'PRESENTE' : 'ABSENTE') . "\n";
echo '3. Variable MONGODB_DB : ' . ($database !== '' ? 'PRESENTE' : 'ABSENTE') . "\n";

if (!$extensionOk || $uri === '' || $database === '') {
    echo "4. Connexion : NON TESTEE (corriger les points precedents)\n";
    exit;
}

if (!preg_match('/^[a-zA-Z0-9_]{1,60}$/', $database)) {
    echo "4. Connexion : NOM DE BASE INVALIDE\n";
    exit;
}

try {
    $manager = new MongoDB\Driver\Manager($uri, [
        'serverSelectionTimeoutMS' => 8000,
        'connectTimeoutMS' => 8000,
    ]);
    $manager->executeCommand($database, new MongoDB\Driver\Command(['ping' => 1]));
    echo "4. Ping MongoDB Atlas : OK\n";
} catch (Throwable $error) {
    http_response_code(503);
    echo "4. Ping MongoDB Atlas : ECHEC\n";
    echo 'Type de probleme : ' . get_class($error) . "\n";
    // Ne pas imprimer le message de l'exception : il peut contenir l'URI secrete.
    exit;
}

try {
    $manager->executeQuery(
        $database . '.order_stats',
        new MongoDB\Driver\Query([], ['limit' => 1])
    );
    echo "5. Lecture collection order_stats : OK\n";
} catch (Throwable $error) {
    http_response_code(503);
    echo "5. Lecture collection order_stats : ECHEC\n";
    echo 'Type de probleme : ' . get_class($error) . "\n";
}
