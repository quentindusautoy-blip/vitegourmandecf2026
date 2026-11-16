# Tableau de bord — ECF Vite & Gourmand

Date limite indiquée par l'étudiant : **19/11/2026**. Durée indicative de l'énoncé : **70 heures**.

## Étape 1 — Base technique (présente dans cette archive)
- [x] Structure de fichiers PHP / HTML / CSS / JS
- [x] Schéma relationnel + données de démonstration en SQL
- [x] Accueil, catalogue, détail, filtres dynamiques
- [x] Inscription, connexion, rôles et sessions
- [x] Passage de commande, calcul du tarif, stock, suivi
- [x] Espace employé basique, modération, création employé administrateur
- [x] Documentation de démarrage en local
- [ ] Tester sur l'environnement XAMPP de l'étudiant

## Étape 2 — Priorités fortes pour conformité
- [ ] CRUD complet menus/plats, conditions, thèmes, allergies, images, horaires (gestion des menus/plats/associations/horaires ajoutée en version 2 ; images et suppression physique des plats restent à faire)
- [x] Mise à jour profil, modification des commandes avant acceptation (code en version 2, à vérifier dans XAMPP)
- [ ] Envoi réel des emails (bienvenue, confirmation, suivi, contact, avis, matériel)
- [ ] Réinitialisation de mot de passe par lien à usage unique
- [ ] **Base NoSQL obligatoire** (MongoDB) alimentant réellement les statistiques
- [ ] Graphique de commandes par menu + chiffre d'affaires filtrable
- [ ] Workflow du prêt matériel, délai 10 jours ouvrés et notifications
- [ ] Disponibilité et galerie des menus
- [ ] Vérification / calcul fiable de la distance de livraison
- [ ] CGV, mentions légales et gestion des données conforme au RGPD
- [ ] Accessibilité RGAA (tests clavier, formulaires, contrastes, erreurs)
- [ ] Tests unitaires / fonctionnels / sécurité

## Étape 3 — Livrables imposés par Studi
- [ ] GitHub public + `main` / `develop` / branches de fonctionnalités
- [ ] Projet Trello / Notion / Jira et URL accessible au correcteur
- [ ] Site déployé, fonctionnel et testable en HTTPS
- [x] SQL de création + insertion (`database/setup.sql`)
- [x] README installation locale (à éprouver réellement)
- [ ] Manuel PDF (parcours visiteurs / clients / employés / administrateur)
- [ ] Charte graphique PDF (couleurs / typographies)
- [ ] Trois wireframes et trois maquettes desktop/mobile
- [ ] MCD / diagramme de classes, use case, diagramme de séquence
- [ ] Documentation technique et déploiement
- [ ] Document Word « copie à rendre » avec 4 parties
- [ ] Compte administrateur de démonstration et informations obligatoires

## Vigilance
Cette version sert d'accélérateur, mais **n'est pas encore la réalisation complète de l'énoncé**. Ne pas indiquer comme réalisés les éléments non cochés.

## Version 2 — ajout technique non encore validé sur le poste étudiant
- [x] Formulaire de mise à jour du profil et sécurité côté serveur
- [x] Modification des commandes en attente et nouveau calcul du prix
- [x] Filtre employé par statut et email
- [x] Saisie des menus, plats, associations et horaires (sans galerie)
- [ ] Vérification des scénarios de `docs/ETAPE_3.md` sur XAMPP
