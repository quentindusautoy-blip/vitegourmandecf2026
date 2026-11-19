# Vite & Gourmand — Audit de sécurité (17 novembre 2026)

## Périmètre
Relecture statique de `app/bootstrap.php`, `app/actions.php`, `app/extra_actions.php`, `app/mail_notifications.php`, `public/index.php`, `public/.htaccess` et des scripts SQL présents sur `develop`. Ce document ne prétend pas constituer un audit d'intrusion ni une certification de sécurité.

## Protections observées dans le code
- Requêtes PDO préparées, émulation désactivée.
- Hachage des mots de passe avec `password_hash` / contrôle `password_verify`.
- Jeton CSRF pour les actions POST.
- Vérification des rôles sur les routes de gestion et des identifiants propriétaires sur les commandes clients.
- Cookie de session `HttpOnly`, `SameSite=Lax`, `Secure` lorsque PHP détecte HTTPS.
- Regénération d'identifiant de session à la connexion.
- Jetons de réinitialisation hachés, expiration 30 minutes et utilisation unique.
- En-têtes `X-Frame-Options: DENY`, `X-Content-Type-Options: nosniff`.

## Correctif de cette branche
Limitation anti-bruteforce du formulaire de connexion à 5 échecs par couple email normalisé / IP serveur pendant 15 minutes. Les empreintes sont stockées dans MariaDB, sans adresse en clair. L'état persiste entre requêtes et sessions.

**Installation obligatoire, dans cet ordre :**
1. Sauvegarder les fichiers et la base Vite & Gourmand.
2. Importer `database/2026_11_17_login_rate_limits.sql` dans `vite_gourmand` (XAMPP), puis dans `lequaiantique_vitegourmand` (alwaysdata). Ne pas intervenir sur `lequaiantique_api`.
3. Copier `app/login_guard.php` et la nouvelle version de `app/actions.php` sur le serveur. Ne pas déployer le code avant la migration SQL.
4. Tester avec un **compte de test** : connexion valide, puis 5 échecs consécutifs et 6e tentative bloquée ; vérifier qu'un autre compte demeure utilisable. Ne pas tester les verrouillages sur le compte administrateur principal.
5. Attendre 15 minutes avant de retester une connexion valide avec l'identifiant bloqué. Pour les tests locaux exclusivement, nettoyer la ligne de test dans la table.
6. Vérifier l'absence d'erreurs SQL/PHP dans les journaux alwaysdata.

## Limites restantes — à conserver dans les TODO
- Le contrôle est par couple email/IP ; compléter par une protection globale IP et une surveillance côté hébergeur pour réduire les attaques distribuées.
- Le blocage ne ralentit pas les toutes premières tentatives ; étudier une temporisation progressive et un mécanisme de journalisation anonymisé.
- Purger périodiquement les lignes de `login_rate_limits` âgées de plus de quelques jours.
- Le changement de mot de passe n'invalide pas toutes les autres sessions déjà ouvertes. Prévoir un mécanisme de version de session.
- Vérifier les en-têtes du proxy HTTPS réel et le comportement `Secure` des cookies sur alwaysdata.
- Auditer les accès directs, les actions réservées aux employés, les liens externes, le traitement d'erreurs et la surface exposée publiquement.
- Ajouter des tests automatisés de contrôle d'accès, CSRF, injection, brute-force et réinitialisation.
- Vérifier le contenu légal, la politique de confidentialité et la durée de conservation des données.

## Accessibilité RGAA — toujours à faire
- Navigation clavier complète et focus visible.
- Contrastes textes et composants, zoom 200 % / 400 %, reflow mobile.
- Labels et messages d'erreur des formulaires, liens explicites, titres hiérarchiques.
- Vérification des graphiques : tableau de données HTML de substitution disponible, à tester avec lecteur d'écran.
- Tests manuels lecteur d'écran et vérification avec les critères RGAA pertinents.

## Retour arrière
Si le code bloque les connexions par erreur, restaurer **uniquement** `app/actions.php` depuis `develop` et retirer le nouvel `require_once` ; la table non utilisée peut rester en place sans affecter le site. Conserver une sauvegarde et ne jamais exécuter de `DROP` sur les tables de production sans plan validé.
