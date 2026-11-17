<?php
declare(strict_types=1);

/**
 * Statistiques NoSQL de Vite & Gourmand.
 * Une projection non sensible de chaque commande MariaDB est stockée dans
 * MongoDB Atlas (base/collection dédiées). Les chiffres affichés ci-dessous
 * sont lus exclusivement dans MongoDB, et jamais recalculés depuis MySQL.
 * Pas de Composer requis : utilisation de l'extension PHP mongodb native.
 */

function stats_namespace(): string {
    $database = (string)(getenv('MONGODB_DB') ?: 'vite_gourmand_ecf');
    if (!preg_match('/^[a-zA-Z0-9_]{1,60}$/', $database)) {
        throw new RuntimeException('Nom de base MongoDB incorrect.');
    }
    return $database . '.order_stats';
}

function stats_manager(): \MongoDB\Driver\Manager {
    if (!extension_loaded('mongodb')) {
        throw new RuntimeException('L’extension PHP MongoDB n’est pas active.');
    }
    $uri = trim((string)(getenv('MONGODB_URI') ?: ''));
    if ($uri === '') {
        throw new RuntimeException('MONGODB_URI est absent de la configuration du site.');
    }
    // Délai réduit : un incident Atlas ne doit pas bloquer longtemps le site PHP.
    return new \MongoDB\Driver\Manager($uri, ['serverSelectionTimeoutMS' => 4000, 'connectTimeoutMS' => 4000]);
}

function stats_projection(array $row): array {
    return [
        'order_id' => (int)$row['id'],
        'menu_id' => (int)$row['menu_id'],
        'menu_title' => (string)$row['title'],
        'total_cents' => (int)$row['total_cents'],
        'status' => (string)$row['status'],
        'created_date' => substr((string)$row['created_at'], 0, 10),
        'updated_at' => new \MongoDB\BSON\UTCDateTime((int)(microtime(true) * 1000)),
    ];
}

function stats_order_sql(): string {
    return 'SELECT o.id, o.menu_id, m.title, o.total_cents, o.status, o.created_at
            FROM orders o INNER JOIN menus m ON m.id = o.menu_id';
}

function stats_sync_one(int $orderId): void {
    $q = db()->prepare(stats_order_sql() . ' WHERE o.id = ?');
    $q->execute([$orderId]);
    $row = $q->fetch();
    if (!$row) return;
    $bulk = new \MongoDB\Driver\BulkWrite();
    $bulk->update(['_id' => (int)$row['id']], ['$set' => stats_projection($row)], ['multi' => false, 'upsert' => true]);
    stats_manager()->executeBulkWrite(stats_namespace(), $bulk);
}

/** Ne jamais annuler une commande MariaDB si Atlas ne répond pas. */
function stats_try_sync_one(int $orderId): void {
    try {
        stats_sync_one($orderId);
    } catch (Throwable $error) {
        // Ne pas journaliser l'exception : certaines erreurs contiennent l'URI secrète.
        error_log('Vite & Gourmand : projection MongoDB à resynchroniser, commande ' . $orderId);
    }
}

/** Synchronisation manuelle par l'admin pour récupérer les anciennes commandes. */
function stats_sync_all(): int {
    $q = db()->query(stats_order_sql() . ' ORDER BY o.id ASC');
    $manager = stats_manager();
    $n = 0;
    $bulk = new \MongoDB\Driver\BulkWrite(['ordered' => true]);
    while ($row = $q->fetch()) {
        $bulk->update(['_id' => (int)$row['id']], ['$set' => stats_projection($row)], ['multi' => false, 'upsert' => true]);
        $n++;
        if ($n % 200 === 0) {
            $manager->executeBulkWrite(stats_namespace(), $bulk);
            $bulk = new \MongoDB\Driver\BulkWrite(['ordered' => true]);
        }
    }
    if ($n % 200 !== 0) {
        $manager->executeBulkWrite(stats_namespace(), $bulk);
    }
    // Même sans commande, tester que la connexion est valide.
    if ($n === 0) {
        $manager->executeCommand((string)(getenv('MONGODB_DB') ?: 'vite_gourmand_ecf'), new \MongoDB\Driver\Command(['ping' => 1]));
    }
    return $n;
}

/**
 * Filtre par date de commande (et non date de prestation).
 * CA réalisé = commandes livrées, en attente de matériel ou terminées.
 * Les commandes annulées sont exclues. CA potentiel = autres non annulées.
 */
function stats_report(?string $from = null, ?string $to = null, ?int $menuFilter = null): array {
    $cursor = stats_manager()->executeQuery(stats_namespace(), new \MongoDB\Driver\Query([], ['limit' => 20000]));
    $groups = []; $count = 0; $realized = 0; $potential = 0;
    foreach ($cursor as $doc) {
        $status = (string)($doc->status ?? '');
        if ($status === 'cancelled') continue;
        $date = (string)($doc->created_date ?? '');
        if ($from !== null && $date < $from) continue;
        if ($to !== null && $date > $to) continue;
        $id = (int)($doc->menu_id ?? 0);
        if ($menuFilter !== null && $id !== $menuFilter) continue;
        $title = (string)($doc->menu_title ?? 'Menu inconnu');
        $amount = (int)($doc->total_cents ?? 0);
        $isRealized = in_array($status, ['delivered','awaiting_material','completed'], true);
        if (!isset($groups[$id])) $groups[$id] = ['title' => $title, 'count' => 0, 'realized_cents' => 0, 'potential_cents' => 0];
        $groups[$id]['title'] = $title;
        $groups[$id]['count']++;
        $groups[$id][$isRealized ? 'realized_cents' : 'potential_cents'] += $amount;
        $count++;
        if ($isRealized) $realized += $amount; else $potential += $amount;
    }
    uasort($groups, fn($a,$b) => $b['count'] <=> $a['count']);
    return ['groups' => $groups, 'count' => $count, 'realized_cents' => $realized, 'potential_cents' => $potential];
}

function stats_date_filter(string $name): ?string {
    $s = (string)($_GET[$name] ?? '');
    if ($s === '') return null;
    $d = DateTimeImmutable::createFromFormat('!Y-m-d', $s);
    return ($d && $d->format('Y-m-d') === $s) ? $s : null;
}
