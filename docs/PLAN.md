# Plan de réalisation — ECF Vite & Gourmand

**État connu : 17 novembre 2026. Date limite de rendu annoncée : 19 novembre 2026.**

> Ce tableau reflète uniquement ce qui a été développé ou testé. Les fonctionnalités présentes dans le code mais non auditées ne doivent pas être considérées comme conformes sur le plan de la sécurité ou de l'accessibilité.

## Développement / déploiement déjà réalisés

- [x] Créer le projet PHP / HTML / CSS / JavaScript et son catalogue.
- [x] Définir le schéma MariaDB et importer les données de démonstration.
- [x] Développer inscription, connexion, commandes et suivi client.
- [x] Développer les rôles client, employé, administrateur.
- [x] Mettre en place la gestion de menus, plats et horaires (certaines opérations restent à finaliser).
- [x] Créer un dépôt GitHub public avec `main`, `develop` et des branches de fonctionnalité.
- [x] Créer le tableau de gestion GitHub Projects (visibilité publique à revérifier).
- [x] Déployer le site sur alwaysdata et tester l'inscription et les commandes en ligne.
- [x] Configurer une base NoSQL MongoDB Atlas indépendante de MariaDB.
- [x] Synchroniser les commandes MariaDB vers `vite_gourmand_ecf.order_stats`.
- [x] Ajouter les graphiques statistiques Chart.js et les contrôler sur le site.
- [x] Implémenter les emails automatiques et constater leur fonctionnement sur alwaysdata.
- [x] Implémenter les formulaires et jetons de réinitialisation de mot de passe.

## Priorité 1 — Sécurité (TODO)

- [ ] Tester les permissions sur **chaque** route et action PHP (client / employé / admin, accès direct, IDOR).
- [ ] Ajouter et tester des protections anti-bruteforce à la connexion et sur les actions sensibles.
- [ ] Tester les validations serveur et les risques XSS, injection SQL, CSRF, contournement des règles métier.
- [ ] Vérifier les cookies de session, headers de sécurité, HTTPS, redirections et restrictions Apache.
- [ ] Vérifier les permissions MariaDB / MongoDB Atlas et la protection des variables d'environnement.
- [ ] Supprimer tous les scripts de test publics et vérifier qu'aucune donnée sensible n'est accessible.
- [ ] Tester le mot de passe oublié de bout en bout (expiration, lien réutilisé, inconnu, limitation des demandes).
- [ ] Vérifier la conformité des traitements de données : mentions légales, confidentialité, conservation/suppression, CGV.
- [ ] Documenter les tests, anomalies constatées et corrections appliquées.

## Priorité 1 — Accessibilité RGAA (TODO)

- [ ] Tester la navigation complète au clavier, sans souris et sans pièges de focus.
- [ ] Vérifier les contrastes et la visibilité du focus.
- [ ] Vérifier chaque formulaire : labels, messages d'erreur, champs obligatoires, instructions.
- [ ] Vérifier hiérarchie de titres, landmarks et textes alternatifs pertinents.
- [ ] Vérifier l'affichage à 200 % de zoom, sur smartphone et avec petit viewport.
- [ ] Vérifier l'accès aux données des graphiques sans couleur, sans CDN et avec lecteur d'écran.
- [ ] Effectuer des tests manuels RGAA (un outil automatique seul ne suffit pas).
- [ ] Consigner les résultats, les corrections et les limites résiduelles.

## Priorité 2 — Fonctionnalités et vérifications métier (TODO)

- [ ] Automatiser le prêt / retour du matériel et le délai de 10 jours ouvrés.
- [ ] Vérifier les conditions et la gestion de frais éventuels de 600 €.
- [ ] Remplacer la distance de livraison déclarative par un calcul / contrôle fiable.
- [ ] Compléter les visuels / galerie de menus et les opérations CRUD restantes si requises par l'énoncé.
- [ ] Exécuter des tests fonctionnels de tous les statuts et notifications.
- [ ] Vérifier le parcours de réinitialisation et la délivrabilité email sur plusieurs scénarios.
- [ ] Ajouter des tests automatisés pertinents si possible.

## Priorité 2 — Livrables documentaires (TODO)

- [x] README actualisé : installation, architecture, environnement, état réel, sécurité et accessibilité ouvertes.
- [x] Script SQL initial et migration des jetons de réinitialisation.
- [ ] Vérifier que l'URL GitHub Projects est accessible publiquement.
- [ ] Exporter un MCD / modèle de données et les diagrammes UML demandés.
- [ ] Réaliser les wireframes et maquettes desktop/mobile (formats et quantité selon l'énoncé).
- [ ] Produire la charte graphique PDF.
- [ ] Produire le manuel utilisateur PDF (visiteur, client, employé, administrateur).
- [ ] Mettre à jour `docs/ARCHITECTURE.md` pour refléter MongoDB et les emails.
- [ ] Finaliser les exigences documentaires de la copie Studi, avec liens et compte de démo.
- [ ] Vérifier les livrables en ouvrant leurs fichiers et liens depuis un autre navigateur.

## Rappel Git

- `main` : version stable à mettre à jour après tests.
- `develop` : intégration des dernières fonctionnalités.
- `feature/*` : développement isolé, puis fusion après vérification.
- Ne jamais committer un mot de passe, une URI MongoDB avec secret, un fichier `.env` réel ou des données personnelles de test.

## Priorité pratique avant le rendu

1. Terminer les contrôles de sécurité essentiels et vérifier les accès.
2. Réaliser l'audit d'accessibilité, corriger les problèmes les plus bloquants et documenter le résultat.
3. Livrer les maquettes, schémas, charte graphique et manuel PDF.
4. Compléter et relire la copie Studi, puis vérifier les liens GitHub / GitHub Projects / site et le compte administrateur de démonstration.

**Un résultat « non testé » ne doit pas être présenté comme « terminé ».**
