<?php

declare(strict_types=1);

if (PHP_SAPI !== 'cli') {
    http_response_code(403);
    exit('CLI uniquement');
}

require_once dirname(__DIR__) . '/app/bootstrap.php';

function ask(string $label): string
{
    echo $label . ': ';
    return trim((string) fgets(STDIN));
}

echo "Création de l’administrateur local (pour la démonstration de l’ECF)\n";

$first = ask('Prénom');
$last = ask('Nom');
$email = ask('Email');
$pass = ask('Mot de passe fort (la saisie reste visible)');

if (
    !$first ||
    !$last ||
    !filter_var($email, FILTER_VALIDATE_EMAIL) ||
    !password_valid($pass)
) {
    fwrite(
        STDERR,
        "Données incorrectes. Mot de passe : 10 caractères, majuscule, minuscule, chiffre, caractère spécial.\n"
    );

    exit(1);
}

try {
    $q = db()->prepare(
        'INSERT INTO users(first_name,last_name,email,phone,address,password_hash,role) VALUES (?,?,?,?,?,?,"admin")'
    );

    $q->execute([
        $first,
        $last,
        $email,
        'À compléter',
        'Compte administrateur',
        password_hash($pass, PASSWORD_DEFAULT)
    ]);
    echo "Compte admin créé. Ne transmettez pas votre mot de passe personnel.\n";

} catch (PDOException $e) {
    fwrite(
        STDERR,
        "Échec : adresse déjà utilisée ou base inaccessible.\n"
    );

    exit(1);
}
