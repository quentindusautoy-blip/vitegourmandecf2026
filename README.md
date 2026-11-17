# Vite & Gourmand — ECF Développeur Web et Web Mobile

Application web de consultation et de commande de menus pour **Vite & Gourmand**, entreprise de traiteur fictive implantée à Bordeaux. Projet réalisé dans le cadre de l'**évaluation en cours de formation (ECF)** du titre professionnel Développeur Web et Web Mobile (Studi).

> **État du projet au 17 novembre 2026 : version pédagogique déployée et testable, mais pas encore totalement conforme à toutes les exigences de l'énoncé.** Les vérifications de **sécurité** et d'**accessibilité (RGAA)** restent ouvertes. Voir [Travaux restant à réaliser](#travaux-restant-à-réaliser--todo).

## Liens utiles

| Ressource | Lien |
| --- | --- |
| Application en ligne | [Vite & Gourmand](https://lequaiantique.alwaysdata.net/vite-gourmand/) |
| Dépôt de code public | [GitHub — vitegourmandecf2026](https://github.com/quentindusautoy-blip/vitegourmandecf2026) |
| Branche de développement | [develop](https://github.com/quentindusautoy-blip/vitegourmandecf2026/tree/develop) |
| Gestion de projet | [GitHub Projects](https://github.com/users/quentindusautoy-blip/projects/1/views/1) |

L'URL de l'application comporte volontairement un **`/` final**. Les identifiants du compte administrateur de démonstration sont à transmettre dans la **copie Studi**, jamais dans le dépôt GitHub public. Vérifier également que le tableau GitHub Projects est visible publiquement.

## Contexte et objectifs

Vite & Gourmand est un traiteur bordelais dirigé par Julie et José. L'entreprise souhaite présenter ses menus en ligne, permettre aux clients de commander et centraliser les opérations de gestion. L'application doit offrir une interface accessible sur ordinateur et mobile, des règles de calcul de tarif claires et un suivi des commandes.

Trois rôles applicatifs sont prévus : **client** (`user`), **employé** (`employee`) et **administrateur** (`admin`). L'inscription publique crée exclusivement un compte client. Les droits d'employé et d'administrateur sont vérifiés côté serveur.

## Fonctionnalités développées

### Visiteur

- Consulter l'accueil, les horaires et la présentation du traiteur.
- Parcourir les menus, leurs compositions et allergènes, conditions et disponibilités.
- Filtrer les menus sans rechargement par thème, régime, fourchette de prix et nombre de convives.
- Envoyer un message de contact.
- Créer un compte, se connecter ou demander un lien de réinitialisation du mot de passe.

### Client authentifié

- Mettre à jour ses coordonnées.
- Commander un menu pour une date, une heure et une adresse de prestation.
- Visualiser une estimation de prix avant validation et consulter les commandes enregistrées.
- Modifier ou annuler une commande tant qu'elle est **en attente**.
- Consulter le suivi de commande.
- Laisser un avis après la clôture de sa commande, sous réserve de modération.

### Employé

- Consulter et filtrer les commandes par statut ou adresse email du client.
- Modifier les statuts autorisés et conserver leur historique.
- Gérer les menus et plats, leurs associations, la visibilité, les stocks et les horaires.
- Modérer les avis clients.

### Administrateur

- Accéder aux fonctions de l'employé.
- Créer et désactiver des comptes employés.
- Synchroniser les commandes MariaDB vers MongoDB Atlas.
- Consulter les statistiques par menu et par période, avec **graphiques Chart.js** et tableau détaillé.

### Emails et accès aux comptes

- Email de bienvenue après inscription.
- Confirmation de commande et notifications lors des changements de statut.
- Transmission des messages du formulaire de contact par email.
- Notification de création d'un compte employé, **sans envoyer son mot de passe**.
- Réinitialisation de mot de passe via un **jeton aléatoire à usage unique**, conservé en base sous forme de hash et valable **30 minutes**.

Les emails automatiques ont été testés sur l'hébergement. **Le parcours complet de réinitialisation** (expiration, réutilisation du lien, gestion des erreurs et confirmation finale) reste à vérifier et documenter avant la remise.

## Règles métier mises en œuvre

| Règle | Comportement |
| --- | --- |
| Mot de passe | Minimum 10 caractères, majuscule, minuscule, chiffre et caractère spécial |
| Nombre de convives | Refus d'une commande sous le minimum défini pour le menu |
| Prix du menu | Prix de référence × nombre de convives / minimum du menu ; calcul en centimes côté serveur |
| Réduction | **10 %** à partir de **5 convives supplémentaires** par rapport au minimum |
| Livraison hors Bordeaux | **5 € + 0,59 € par kilomètre** ; distance déclarée par l'utilisateur, pas encore vérifiée automatiquement |
| Disponibilité | Stock décrémenté dans une transaction SQL à la création, recrédité lors d'une annulation permise |
| Commandes | Transitions de statuts contrôlées et historisées |
| Avis | Note de 1 à 5, après commande terminée ; publication après validation |
| Statistiques | Les commandes annulées sont exclues des totaux ; le CA dit « réalisé » correspond aux statuts livrée / matériel en attente / terminée, **sans constituer une preuve de paiement** |

**Limites métier connues :** le suivi complet du prêt et du retour de matériel, le calcul des **10 jours ouvrés** et l'application des frais éventuels de **600 €** ne sont pas automatisés. La distance de livraison doit être confirmée par le traiteur.

## Architecture technique

| Couche | Technologie | Usage |
| --- | --- | --- |
| Front-end | HTML5, CSS3 responsive | Structure, mise en page, formulaires, navigation |
| Interactivité | JavaScript natif | Filtres, aperçu du calcul de prix |
| Graphiques | Chart.js 4.4.8 (CDN) | Histogrammes administrateur, complétés d'un tableau HTML |
| Back-end | PHP 8.2 | Contrôleurs, règles métier, sessions, formulaires et notifications |
| Base SQL | MariaDB + PDO | Comptes, menus, plats, commandes, historiques, avis, contacts |
| Base NoSQL | MongoDB Atlas + extension PHP `mongodb` | Projection et consultation des statistiques de commandes |
| Hébergement | alwaysdata (PHP + MariaDB), HTTPS | Application publique |
| Versionnement | Git + GitHub | Branches `main`, `develop` et `feature/*` |
| Suivi | GitHub Projects | Tâches et livrables de l'ECF |

### Répartition MariaDB / MongoDB

**MariaDB est la source de vérité** pour les données métiers et les transactions. Une projection des commandes (identifiant, menu, total, statut et date) est envoyée vers **`vite_gourmand_ecf.order_stats`** dans MongoDB Atlas. Aucune adresse de livraison ni mot de passe n'est stocké dans cette collection statistique.

La synchronisation est effectuée après les écritures SQL, avec un mécanisme de rattrapage manuel accessible à l'administrateur. Une panne de MongoDB ne doit pas annuler une commande correctement enregistrée dans MariaDB. Il ne s'agit pas d'une transaction distribuée : un décalage temporaire reste possible.

Détails : [Documentation MongoDB](docs/MONGODB_STATS.md).

## Arborescence du projet

```text
vite-gourmand/
├── app/
│   ├── bootstrap.php               # Sessions, PDO, contrôles d'accès et utilitaires
│   ├── actions.php                 # Actions de formulaires et commandes
│   ├── extra_actions.php           # Profil, gestion menus/plats et modifications
│   ├── mongo_stats.php             # Écriture et lecture des statistiques MongoDB
│   └── mail_notifications.php      # Notifications et tokens de réinitialisation
├── database/
│   ├── setup.sql                   # Schéma SQL initial + jeux de données
│   └── 2026_11_17_password_reset_tokens.sql
├── public/
│   ├── index.php                   # Point d'entrée de l'application
│   ├── .htaccess                   # Restrictions Apache
│   └── assets/
│       ├── app.js                  # Filtres / interactions clients
│       ├── stats-charts.js         # Graphiques administrateur
│       └── style.css               # Présentation responsive
├── scripts/
│   └── create_admin.php            # Création sécurisée d'un admin en CLI
├── docs/                          # Plans et documentation technique
├── .gitignore
└── README.md
```

**Important :** sur le serveur web, le répertoire racine doit être **`public/`** ; `app/`, `database/`, `docs/` et `scripts/` ne doivent pas être servis directement par HTTP.

## Installation locale — Windows / XAMPP

### 1. Prérequis

- Apache, PHP **8.2** ou une version compatible, MariaDB/MySQL (XAMPP).
- Extensions PHP : `pdo_mysql`, `mbstring`; extension `mongodb` requise pour les statistiques Atlas.
- Git et un navigateur récent.
- Un cluster MongoDB Atlas existant et autorisé pour le test NoSQL.
- Pas de dépendance Composer ou Node.js nécessaire pour l'exécution actuelle. Chart.js est chargé depuis un CDN.

### 2. Récupérer les fichiers

Depuis PowerShell (si le dossier n'existe pas déjà) :

```powershell
cd C:\xampp\htdocs\SiteWeb
git clone https://github.com/quentindusautoy-blip/vitegourmandecf2026.git vite-gourmand
cd vite-gourmand
git switch develop
```

Si le dossier existe déjà, ne pas le recloner : sauvegarder les modifications locales et utiliser Git pour récupérer les versions souhaitées.

### 3. Créer la base MariaDB

1. Démarrer **Apache** et **MySQL** dans XAMPP.
2. Ouvrir `http://localhost/phpmyadmin`.
3. Importer **`database/setup.sql`** pour créer la base locale `vite_gourmand` et les données de démonstration.
4. Sélectionner cette base, puis importer **`database/2026_11_17_password_reset_tokens.sql`** pour créer la table des jetons de réinitialisation.

Ne pas réimporter les données initiales dans une base contenant déjà des commandes. Effectuer une sauvegarde avant toute migration.

### 4. Connexion SQL locale

Le code lit les variables d'environnement suivantes :

```dotenv
DB_HOST=127.0.0.1
DB_NAME=vite_gourmand
DB_USER=root
DB_PASSWORD=
```

Les valeurs `root` / mot de passe vide sont prévues **uniquement pour un environnement XAMPP local de démonstration**. En production, utiliser un compte SQL dédié aux permissions limitées. **Le projet ne charge pas automatiquement un fichier `.env`** : les variables doivent être fournies par la configuration Apache/PHP ou de l'hébergeur.

### 5. Création d'un compte administrateur local

Depuis le répertoire racine du projet :

```powershell
& 'C:\xampp\php\php.exe' scripts\create_admin.php
```

Le script est réservé à la ligne de commande et requiert un mot de passe fort. Il ne doit jamais être exposé comme page HTTP. La création d'un compte employé se fait ensuite dans l'interface administrateur.

### 6. Lancer l'application

Ouvrir :

```text
http://localhost/SiteWeb/vite-gourmand/public/index.php
```

Les parcours SQL peuvent être testés sans MongoDB. Pour les statistiques, configurer également `MONGODB_URI` et `MONGODB_DB` pour le processus PHP local, avec une autorisation réseau Atlas adaptée. Les emails réels dépendent de la configuration d'envoi du serveur local et ne sont pas garantis sous XAMPP.

## Déploiement — alwaysdata

La version de démonstration est hébergée avec une configuration **indépendante du projet Le Quai Antique**, bien que les deux applications partagent le compte d'hébergement.

- **Adresse publique :** `https://lequaiantique.alwaysdata.net/vite-gourmand/`
- **Type de site :** PHP 8.2.
- **Répertoire racine :** `/home/lequaiantique/www/vite-gourmand/public/`.
- **Répertoire contenant le projet :** `/home/lequaiantique/www/vite-gourmand/`.
- **Base SQL :** `lequaiantique_vitegourmand` ; ne jamais importer dans `lequaiantique_api`.
- **Base NoSQL :** `vite_gourmand_ecf`, collection `order_stats`, sur MongoDB Atlas.

### 1. Transfert des fichiers

Transférer les dossiers du projet dans `/home/lequaiantique/www/vite-gourmand/` avec un client SFTP ou FTPS tel que FileZilla. Le **document root** est le sous-dossier `public/`.

Ne pas envoyer de fichiers `.env`, de sauvegardes de base contenant des données privées, ni de répertoire `.git` sur le serveur web. Laisser séparés les fichiers et configurations du Quai Antique.

### 2. Base de données

La base `lequaiantique_vitegourmand` doit exister sur alwaysdata. Le fichier initial `setup.sql` contient au début des instructions `CREATE DATABASE` et `USE vite_gourmand` prévues pour le **local** : les retirer dans une copie du script avant un **premier import** sur alwaysdata, en sélectionnant explicitement `lequaiantique_vitegourmand` dans phpMyAdmin. Puis appliquer la migration `2026_11_17_password_reset_tokens.sql` **dans cette même base**.

### 3. Variables d'environnement du site PHP

Dans **alwaysdata → Web → Sites → Vite & Gourmand → Variables d'environnement**, configurer les valeurs réelles (les valeurs ci-dessous sont des exemples, **pas des identifiants**) :

```dotenv
DB_HOST=mysql-lequaiantique.alwaysdata.net
DB_NAME=lequaiantique_vitegourmand
DB_USER=UTILISATEUR_SQL_DEDIE
DB_PASSWORD=MOT_DE_PASSE_SQL_PRIVE
MONGODB_URI=mongodb+srv://UTILISATEUR_MONGO:MOT_DE_PASSE_URL_ENCODE@HOTE_ATLAS.mongodb.net/
MONGODB_DB=vite_gourmand_ecf
MAIL_FROM=lequaiantique@alwaysdata.net
APP_BASE_URL=https://lequaiantique.alwaysdata.net/vite-gourmand/
```

Le mot de passe dans une URI MongoDB doit être **encodé pour une URL** si nécessaire. Les secrets ne doivent jamais apparaître dans Git, les captures d'écran, les fichiers publics ou ce README. L'utilisateur MongoDB est limité à la base/collection statistique ; les IP autorisées dans Atlas doivent être restreintes aux hôtes nécessaires.

La fonction `mail()` de PHP est utilisée pour les emails sur alwaysdata. La confirmation de l'envoi **ne garantit pas la réception** : valider avec une boîte de test réelle et vérifier les spams.

### 4. Vérifications après déploiement

1. Ouvrir l'URL publique **avec son slash final**.
2. Tester l'inscription et la connexion.
3. Passer une commande, puis vérifier son apparition dans le compte client.
4. Vérifier les droits employés / administrateur et les changements de statut.
5. Vérifier l'email de confirmation et les autres notifications nécessaires.
6. Contrôler les statistiques dans l'interface administrateur et la collection MongoDB `order_stats`.
7. Retirer les fichiers temporaires de diagnostic (`check-mongodb.php`, `diagnostic-mongodb.php`, `test-mongo-connection.php`, etc.) du répertoire `public/`.
8. Consulter les logs PHP/Apache d'alwaysdata si une fonctionnalité échoue.

## Sécurité — état et vérifications restantes

### Mesures déjà présentes dans le code (à auditer)

- Hachage des mots de passe via `password_hash()` ; vérification via `password_verify()`.
- Vérification de la robustesse du mot de passe côté serveur.
- Accès SQL avec PDO et requêtes préparées.
- Échappement de la plupart des contenus dynamiques affichés (`htmlspecialchars`).
- Jetons CSRF pour les formulaires POST.
- Contrôles de rôle côté serveur (`user`, `employee`, `admin`).
- Sessions PHP régénérées lors de l'authentification ; cookies configurés avec `HttpOnly` et `SameSite=Lax` et `Secure` sous HTTPS.
- Réinitialisation par jeton aléatoire, hashé, temporaire et à usage unique.
- Variables d'environnement pour les secrets du serveur, hors dépôt Git.

**Ces mécanismes présents dans le code ne valent pas audit de sécurité terminé.** Les vérifications suivantes sont **à faire** :

- [ ] Ajouter / tester une limitation des tentatives de connexion et des demandes sensibles (anti-bruteforce / anti-abus).
- [ ] Tester les contrôles d'autorisation sur toutes les routes et actions (accès direct client → employé/admin, changement d'ID, modification de commande d'autrui).
- [ ] Auditer les formulaires et sorties contre les injections, XSS, CSRF et détournements de paramètres ; vérifier les messages d'erreur.
- [ ] Vérifier les en-têtes HTTP, la configuration HTTPS, les cookies, les redirections et l'absence de fichiers sensibles accessibles depuis Internet.
- [ ] Tester la réinitialisation : expiration du jeton, lien utilisé deux fois, mot de passe faible, email inexistant, tentative répétée.
- [ ] Auditer les droits MariaDB / MongoDB, la protection des secrets et les journaux sans données sensibles.
- [ ] Vérifier la conservation et la suppression des données personnelles ; compléter mentions légales, confidentialité et CGV avant toute utilisation réelle.
- [ ] Documenter les scénarios de test et leurs résultats (captures ou compte rendu).

## Accessibilité — état et vérifications restantes (RGAA)

### Éléments présents dans l'interface (à valider)

- Structure HTML avec titres, formulaires étiquetés et régions de navigation.
- Lien d'évitement vers le contenu principal.
- Certains messages d'état avec `role="status"` ou `aria-live`.
- Interface CSS responsive.
- Tableaux HTML affichant les données des statistiques en complément des graphiques visuels.

**Aucun audit RGAA complet n'a encore été réalisé ; le site ne doit pas être présenté comme conforme sans tests.** Travaux à effectuer :

- [ ] Parcourir toutes les pages et les formulaires **uniquement au clavier** (Tab, Maj+Tab, Entrée, Espace, Échap selon le composant).
- [ ] Vérifier l'ordre de tabulation et la visibilité du **focus** ; corriger tout blocage clavier.
- [ ] Mesurer le contraste texte / fond et éléments interactifs, puis corriger les couleurs insuffisantes.
- [ ] Vérifier la hiérarchie des titres, les libellés et les messages d'erreur des champs.
- [ ] Vérifier le texte alternatif des images porteuses d'information et l'absence de contenu uniquement transmis par la couleur.
- [ ] Tester à 200 % de zoom, sur petit écran et sans défilement horizontal inattendu.
- [ ] Vérifier les menus dynamiques et les graphiques avec un lecteur d'écran ; s'assurer que le tableau statistique reste compréhensible sans Chart.js.
- [ ] Effectuer un contrôle avec un outil d'audit automatisé **puis des tests manuels RGAA**, et conserver les résultats / corrections.

## Travaux restant à réaliser — TODO

### Priorité haute avant le rendu Studi

- [ ] **Terminer les tests de sécurité** détaillés ci-dessus ; corriger les anomalies constatées.
- [ ] **Effectuer les tests d'accessibilité RGAA** détaillés ci-dessus et documenter les corrections.
- [ ] Tester de bout en bout la réinitialisation du mot de passe (notamment les cas d'échec et l'usage unique).
- [ ] Compléter et vérifier les mentions légales, les conditions générales de vente et les informations sur les données personnelles.
- [ ] Produire les maquettes et wireframes desktop/mobile demandés, ainsi que la charte graphique.
- [ ] Finaliser les diagrammes MCD / UML, le manuel utilisateur et les documents techniques.
- [ ] Compléter la copie Studi avec les liens GitHub, projet, application, et un **compte administrateur de démonstration**.
- [ ] Vérifier que toutes les informations transmises au jury correspondent à la version déployée.

### Fonctionnalités à approfondir

- [ ] Automatiser intégralement le prêt / retour de matériel, le calcul des 10 jours ouvrés et les éventuels frais de 600 €.
- [ ] Vérifier automatiquement les distances de livraison au lieu de se fier à la saisie du client.
- [ ] Terminer les médias/galeries de menus et certaines opérations de gestion (notamment la suppression si exigée).
- [ ] Écrire et exécuter des tests automatisés représentatifs.
- [ ] Vérifier la délivrabilité des emails et compléter les notifications restant à couvrir par l'énoncé.
- [ ] Mettre à jour les documents `docs/PLAN.md` et `docs/ARCHITECTURE.md`, qui décrivent encore en partie des versions antérieures.

Ces points ouverts doivent aussi apparaître dans le tableau **GitHub Projects** (statut « Todo » ou « In progress »), et non dans « Done ».

## Plan de vérification fonctionnelle

| Parcours | Vérification attendue | État connu |
| --- | --- | --- |
| Inscription / connexion en ligne | Un client peut s'inscrire et se connecter | Testé sur alwaysdata |
| Catalogue et filtres | Les menus s'affichent et se filtrent | Testé en local |
| Commande en ligne | Enregistrement dans MariaDB et affichage client | Testé sur alwaysdata |
| Administrateur | Connexion avec rôle administrateur | Testé sur alwaysdata |
| MongoDB | Connexion Atlas et lecture/écriture dans `order_stats` | Testé sur alwaysdata |
| Graphiques | Statistiques par menu et tableau associé | Affichage validé en ligne |
| Emails automatiques | Réception d'au moins un email réel | Testé par le porteur du projet |
| Lien de réinitialisation | Expiration, usage unique, cas d'échec | À vérifier intégralement |
| Sécurité | Tests manuels et audit des accès | À faire |
| Accessibilité RGAA | Audit et corrections documentés | À faire |

## Gestion Git et contributions

- **`main`** : branche de référence pour les versions stabilisées (peut être en retard sur l'environnement de démonstration tant qu'une validation finale n'a pas eu lieu).
- **`develop`** : branche d'intégration des fonctionnalités en cours.
- **`feature/...`** : branche temporaire pour une fonctionnalité (ex. `feature/stats-mongodb`, `feature/emails-password-reset`).

Cycle recommandé : créer une branche depuis `develop`, développer et tester, créer un commit explicite, pousser la branche, puis fusionner dans `develop` une fois validée. Réaliser la dernière validation avant fusion éventuelle dans `main`.

Le fichier `.gitignore` exclut notamment `.env`, `vendor/`, `node_modules/` et les journaux `*.log`. **Cela ne remplace pas une revue des fichiers avant publication :** contrôler `git diff --cached --name-only` et les fichiers effectivement indexés.

## Documentation complémentaire

- [Plan de réalisation](docs/PLAN.md) — document historique à actualiser.
- [Architecture et diagrammes](docs/ARCHITECTURE.md) — diagrammes de travail ; certaines mentions sont antérieures à MongoDB et aux emails.
- [Architecture MongoDB et statistiques](docs/MONGODB_STATS.md).

---

**Projet pédagogique Studi — Vite & Gourmand.** Ce dépôt et son hébergement public servent de démonstration dans le cadre d'un ECF. Ils ne constituent pas une solution commerciale auditée ou prête pour une exploitation réelle.
