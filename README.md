# Vite & Gourmand — ECF TP Développeur Web et Web Mobile

**Statut : version de départ fonctionnelle, non conforme à 100 % à l'énoncé.**

## Stack
- Front-end : HTML5, CSS3 responsive, JavaScript natif (filtres sans rechargement).
- Back-end : PHP 8.1+, PDO, sessions.
- Base relationnelle : MariaDB/MySQL, script SQL explicite.
- Base NoSQL : **non intégrée dans cette version** (étape suivante : MongoDB).

## Installation sous Windows / XAMPP
1. Copier le dossier `vite-gourmand-ecf` dans `C:\xampp\htdocs\` et le renommer `vite-gourmand`.
2. Démarrer **Apache** et **MySQL** dans le panneau XAMPP.
3. Ouvrir `http://localhost/phpmyadmin` puis onglet **Importer** et sélectionner `database/setup.sql`.
4. Avec les réglages XAMPP habituels, accès SQL : `root` sans mot de passe (uniquement en local). Pour un autre compte, définir les variables d'environnement `DB_HOST`, `DB_NAME`, `DB_USER`, `DB_PASSWORD` avant de lancer PHP/Apache.
5. Ouvrir `http://localhost/vite-gourmand/public/index.php`.
6. Créer le compte administrateur **en ligne de commande uniquement** :
   ```powershell
   cd C:\xampp\htdocs\vite-gourmand
   & 'C:\xampp\php\php.exe' scripts\create_admin.php
   ```
7. Créer un compte client par le formulaire `Inscription`. Dans le compte administrateur, créer ensuite un employé.

Si Apache n'utilise pas le port 80, adapter l'URL : `http://localhost:PORT/...`.

## Parcours testables
- Anonyme : accueil, catalogue, filtre dynamique, détails d'un menu, contact (enregistrement base).
- Client : inscription, connexion, commande, calcul du prix, suivi, annulation si en attente, avis après clôture.
- Employé : consulter les commandes, changer leur statut, modérer les avis, modifier le stock.
- Administrateur : mêmes droits que l'employé et création/désactivation d'employés.

### Règles métier implémentées
- Rôles `user`, `employee`, `admin` ; l'inscription crée toujours le rôle `user`.
- Mots de passe hachés et politique de robustesse : au moins 10 caractères, majuscule, minuscule, chiffre et caractère spécial.
- Commande sous le minimum refusée ; préavis de réservation contrôlé côté serveur.
- Pour N convives : base tarifaire `prix du menu × N / minimum de personnes`, arrondie au centime (hypothèse de calcul pédagogique à valider avec le client).
- Dès `minimum + 5` convives : réduction de 10 % sur le montant du menu.
- Hors Bordeaux : `5 € + 0,59 €/km` ; la distance est provisoirement saisie manuellement et **n'est pas vérifiée automatiquement**.
- Décrément du stock dans une transaction SQL verrouillée lors de l'achat ; recrédit si annulation.
- Une commande terminée peut être évaluée (1 à 5) ; l'avis n'apparaît qu'après modération.
- Historique horodaté des statuts de commande.

## Éléments encore à développer avant la remise
Voir [`docs/PLAN.md`](docs/PLAN.md) pour la liste priorisée et l'état réel. Notamment : base NoSQL / graphiques, notifications email SMTP, réinitialisation de mot de passe, CRUD intégral des menus et plats, édition de commande et profil, prêt de matériel et relance, galerie d'images, RGAA, documents légaux réels, tests automatisés, déploiement.

## Sécurité
- PDO et requêtes préparées pour les données externes.
- Échappement HTML via `htmlspecialchars`.
- Protection CSRF des formulaires par jeton en session.
- Vérification des droits sur le serveur, pas seulement dans l'interface.
- Hachage avec `password_hash`, session régénérée après authentification.
- Politique de cookie HttpOnly, SameSite Lax ; Secure si HTTPS.
- Aucune clé ni mot de passe de production ne doit être commité.

**Avant mise en ligne** : HTTPS, compte SQL avec droits minimaux, configuration hors du répertoire public, journalisation appropriée, secrets d'environnement, contrôle des emails, protection anti-bruteforce et tests de sécurité.

## Répertoire
```
app/            # logique métier PHP, configuration DB
public/         # point d'entrée HTTP, JS, CSS
database/       # script SQL de création et jeux d'essai
scripts/        # création sécurisée du compte admin en CLI
docs/           # suivi des exigences et livrables
```

## Gestion Git recommandée
- `main` pour la version stable ; `develop` pour l'intégration.
- Une branche `feature/...` par fonctionnalité puis merge dans `develop` après test ; validation avant fusion dans `main`.
- Exclure `.env`, mots de passe et fichiers privés.

## Déploiement
**Non effectué.** Le README sera complété après choix d'un hébergeur compatible PHP/MySQL + service NoSQL et SMTP. Ne pas annoncer un site déployé avant vérification de l'URL publique.

## Ajouts version 2
- Compte : édition des coordonnées et édition des commandes tant qu’elles sont en attente.
- Employé : filtres de commandes, édition et création de menus/plats, associations menu-plat, horaires hebdomadaires.
- Voir `docs/ETAPE_3.md` pour l’installation incrémentale sans réimport SQL.
- Voir `docs/ARCHITECTURE.md` pour les diagrammes de travail (Mermaid à exporter ensuite).
