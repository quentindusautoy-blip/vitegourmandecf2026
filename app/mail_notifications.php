<?php
declare(strict_types=1);

/**
 * Notifications simples, sans bibliothèque SMTP ni secret dans le dépôt Git.
 * Depuis alwaysdata, PHP mail() utilise le relais local (à tester avec une vraie adresse).
 * Une tentative d'envoi n'est PAS une garantie de réception.
 */
function vg_mail_sender(): string {
    $sender = trim((string)(getenv('MAIL_FROM') ?: 'lequaiantique@alwaysdata.net'));
    if (!filter_var($sender, FILTER_VALIDATE_EMAIL) || preg_match('/[\r\n]/', $sender)) {
        throw new RuntimeException('Expéditeur de messagerie invalide.');
    }
    return $sender;
}

/** Pas de construction à partir de HTTP_HOST : évite les liens piégés dans les emails. */
function vg_public_url(string $page, array $query = []): string {
    $base = (string)(getenv('APP_BASE_URL') ?: 'https://lequaiantique.alwaysdata.net/vite-gourmand/');
    if (!str_starts_with($base, 'https://')) {
        throw new RuntimeException('APP_BASE_URL doit commencer par https://');
    }
    return rtrim($base, '/') . '/index.php?' . http_build_query(['page' => $page] + $query);
}

/** Tous les destinataires et Reply-To sont validés pour éviter l'injection d'en-têtes. */
function vg_send_mail(string $to, string $subject, string $body, ?string $replyTo = null): bool {
    $to = trim($to);
    if (!filter_var($to, FILTER_VALIDATE_EMAIL) || preg_match('/[\r\n]/', $to)) {
        error_log('Vite Gourmand: adresse email destinataire invalide');
        return false;
    }
    if ($replyTo !== null && (!filter_var($replyTo, FILTER_VALIDATE_EMAIL) || preg_match('/[\r\n]/', $replyTo))) {
        return false;
    }
    try {
        $from = vg_mail_sender();
        $headers = [
            'From' => 'Vite & Gourmand <' . $from . '>',
            'Reply-To' => $replyTo ?: $from,
            'MIME-Version' => '1.0',
            'Content-Type' => 'text/plain; charset=UTF-8',
            'Content-Transfer-Encoding' => '8bit',
            'X-Auto-Response-Suppress' => 'All',
        ];
        $encodedSubject = mb_encode_mimeheader($subject, 'UTF-8', 'B', "\r\n");
        $ok = @mail($to, $encodedSubject, str_replace("\n", "\r\n", str_replace(["\r\n", "\r"], "\n", $body)), $headers);
        if (!$ok) error_log('Vite Gourmand: mail() a refusé un envoi');
        return $ok;
    } catch (Throwable $error) {
        error_log('Vite Gourmand: envoi email impossible (' . get_class($error) . ')');
        return false;
    }
}

function vg_mail_welcome(array $user): bool {
    $name = trim((string)($user['first_name'] ?? ''));
    return vg_send_mail((string)$user['email'], 'Bienvenue chez Vite & Gourmand',
        "Bonjour " . $name . ",\n\nVotre compte Vite & Gourmand a été créé avec succès.\n\n" .
        "Découvrez nos menus : " . vg_public_url('menus') . "\n\nÀ bientôt !\nL'équipe Vite & Gourmand");
}

/** Lecture SQL restreinte pour composer un message transactionnel, jamais d'information MongoDB privée. */
function vg_mail_order_details(int $orderId): ?array {
    $query = db()->prepare('SELECT o.id,o.event_date,o.delivery_time,o.people,o.total_cents,o.status, m.title AS menu_title, u.email,u.first_name FROM orders o JOIN menus m ON m.id=o.menu_id JOIN users u ON u.id=o.user_id WHERE o.id=? LIMIT 1');
    $query->execute([$orderId]);
    return $query->fetch() ?: null;
}

function vg_mail_order_confirmation(int $orderId): bool {
    $order = vg_mail_order_details($orderId);
    if (!$order) return false;
    $body = "Bonjour " . $order['first_name'] . ",\n\n" .
        "Votre commande n°" . $order['id'] . " a bien été enregistrée.\n" .
        "Menu : " . $order['menu_title'] . "\n" .
        "Convives : " . $order['people'] . "\n" .
        "Date de prestation : " . $order['event_date'] . "\n" .
        "Heure de livraison : " . substr((string)$order['delivery_time'], 0, 5) . "\n" .
        "Total estimé : " . euro((int)$order['total_cents']) . "\n" .
        "Statut : en attente de validation.\n\n" .
        "Suivre ma commande : " . vg_public_url('account') . "\n\nL'équipe Vite & Gourmand";
    return vg_send_mail((string)$order['email'], 'Confirmation de votre commande n°' . $orderId, $body);
}

function vg_mail_order_status(int $orderId, string $status): bool {
    $order = vg_mail_order_details($orderId);
    if (!$order) return false;
    $statuses = [
        'accepted' => 'acceptée',
        'preparing' => 'en préparation',
        'shipping' => 'en cours de livraison',
        'delivered' => 'livrée',
        'awaiting_material' => 'en attente du retour du matériel',
        'completed' => 'terminée',
        'cancelled' => 'annulée',
    ];
    if (!isset($statuses[$status])) return true;
    $body = "Bonjour " . $order['first_name'] . ",\n\n" .
        "Votre commande n°" . $order['id'] . " (" . $order['menu_title'] . ") est désormais " . $statuses[$status] . ".\n\n";
    if ($status === 'completed') {
        $body .= "Vous pouvez maintenant laisser un avis depuis votre compte.\n\n";
    }
    if ($status === 'awaiting_material') {
        $body .= "Merci de prendre contact avec notre équipe pour restituer le matériel prêté. " .
            "Selon les conditions de vente, des frais de 600 € pourront être appliqués en l'absence de restitution " .
            "sous dix jours ouvrés.\n\n";
    }
    $body .= "Suivi : " . vg_public_url('account') . "\n\nL'équipe Vite & Gourmand";
    return vg_send_mail((string)$order['email'], 'Commande n°' . $orderId . ' — ' . $statuses[$status], $body);
}

function vg_mail_contact(string $replyTo, string $subject, string $message): bool {
    try { $receiver = vg_mail_sender(); }
    catch (Throwable $e) { error_log('Vite Gourmand: email de contact non configuré'); return false; }
    return vg_send_mail($receiver, 'Contact Vite & Gourmand — nouveau message',
        "Objet : " . $subject . "\nEmail du visiteur : " . $replyTo . "\n\n" . $message,
        $replyTo);
}

function vg_mail_employee_created(string $email, string $firstName): bool {
    return vg_send_mail($email, 'Votre accès employé Vite & Gourmand',
        "Bonjour " . $firstName . ",\n\nUn compte employé Vite & Gourmand vient d'être créé pour vous.\n" .
        "Contactez l'administrateur par un canal sûr afin d'obtenir vos identifiants.\n" .
        "Aucun mot de passe n'est transmis par email.\n\n" . vg_public_url('login'));
}

/** Retour identique si le compte n'existe pas : empêche l'énumération d'utilisateurs. */
function vg_request_password_reset(string $email): void {
    $user = db()->prepare('SELECT id,email,first_name FROM users WHERE email=? AND active=1 LIMIT 1');
    $user->execute([$email]);
    $account = $user->fetch();
    if (!$account) return;

    // Limite à une demande toutes les cinq minutes par compte.
    $recent = db()->prepare('SELECT 1 FROM password_reset_tokens WHERE user_id=? AND created_at > DATE_SUB(NOW(), INTERVAL 5 MINUTE) LIMIT 1');
    $recent->execute([$account['id']]);
    if ($recent->fetchColumn()) return;

    $token = bin2hex(random_bytes(32)); // 256 bits, présent uniquement dans le mail.
    $hash = hash('sha256', $token);
    db()->prepare('INSERT INTO password_reset_tokens (user_id, token_hash, expires_at) VALUES (?, ?, DATE_ADD(NOW(), INTERVAL 30 MINUTE))')
        ->execute([$account['id'], $hash]);
    $id = (int)db()->lastInsertId();
    $link = vg_public_url('reset-password', ['token' => $token]);
    $sent = vg_send_mail((string)$account['email'], 'Réinitialisation de votre mot de passe',
        "Bonjour " . $account['first_name'] . ",\n\n" .
        "Pour changer votre mot de passe, utilisez ce lien valable 30 minutes et une seule fois :\n" .
        $link . "\n\nSi vous n'avez pas demandé ce changement, ignorez ce message.\n\nL'équipe Vite & Gourmand");
    // Échec d'envoi : ne pas laisser un jeton de réinitialisation inutile actif.
    if (!$sent) db()->prepare('DELETE FROM password_reset_tokens WHERE id=?')->execute([$id]);
}

function vg_reset_token_is_valid(string $token): bool {
    if (!preg_match('/^[a-f0-9]{64}$/D', $token)) return false;
    $stmt = db()->prepare('SELECT 1 FROM password_reset_tokens p JOIN users u ON u.id=p.user_id WHERE p.token_hash=? AND p.used_at IS NULL AND p.expires_at > NOW() AND u.active=1 LIMIT 1');
    $stmt->execute([hash('sha256', $token)]);
    return (bool)$stmt->fetchColumn();
}

function vg_change_password_with_token(string $token, string $password): void {
    if (!preg_match('/^[a-f0-9]{64}$/D', $token)) throw new InvalidArgumentException('Lien invalide ou expiré.');
    if (!password_valid($password)) throw new InvalidArgumentException('Mot de passe non conforme aux règles de sécurité.');
    $pdo = db();
    $pdo->beginTransaction();
    try {
        $stmt = $pdo->prepare('SELECT p.id,p.user_id FROM password_reset_tokens p JOIN users u ON u.id=p.user_id WHERE p.token_hash=? AND p.used_at IS NULL AND p.expires_at > NOW() AND u.active=1 LIMIT 1 FOR UPDATE');
        $stmt->execute([hash('sha256', $token)]);
        $row = $stmt->fetch();
        if (!$row) throw new InvalidArgumentException('Lien invalide ou expiré.');
        $pdo->prepare('UPDATE users SET password_hash=? WHERE id=? AND active=1')->execute([password_hash($password, PASSWORD_DEFAULT), (int)$row['user_id']]);
        $pdo->prepare('UPDATE password_reset_tokens SET used_at=NOW() WHERE user_id=? AND used_at IS NULL')->execute([(int)$row['user_id']]);
        $pdo->commit();
    } catch (Throwable $e) {
        if ($pdo->inTransaction()) $pdo->rollBack();
        throw $e;
    }
}
