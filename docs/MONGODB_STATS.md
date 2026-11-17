# Vite & Gourmand — Statistiques MongoDB Atlas (ECF)

## Architecture
MariaDB reste la source de vérité pour les transactions clients. La collection MongoDB
`vite_gourmand_ecf.order_stats` conserve uniquement une **projection** des commandes :
identifiant, menu, total, statut et date de création. Aucune adresse de livraison, aucun email,
aucun mot de passe n’est stocké dans cette collection.

La projection se met à jour après une création de commande, une annulation, une modification
ou un changement de statut. Une panne MongoDB n’annule pas les opérations MariaDB. Le bouton
« Synchroniser les commandes depuis MariaDB » (administrateur seulement) récupère les données
historiques et resynchronise les données manquantes via un *upsert* idempotent (`_id = order_id`).

Le graphique et les totaux sont calculés **en lisant des documents MongoDB**, pas à partir de SQL.
Les commandes annulées sont exclues. Le chiffre d’affaires réalisé comprend les commandes
`delivered`, `awaiting_material`, `completed`. Les autres commandes non annulées sont
présentées comme « en cours », pas comme un CA déjà encaissé.

## Pré-requis
- Extension PHP `mongodb` active sur le serveur.
- MongoDB Atlas accessible depuis l’IP sortante d’alwaysdata.
- Utilisateur Atlas `vitegourmand_app` limité à la collection `order_stats` de la base
  `vite_gourmand_ecf` (ou à la base entière si des collections supplémentaires sont nécessaires).
- Variables d’environnement sur **le site Vite & Gourmand uniquement** :
  `MONGODB_URI` (URI d’Atlas avec mot de passe correctement encodé) et
  `MONGODB_DB=vite_gourmand_ecf`.
- Ne jamais committer l’URI et le mot de passe dans GitHub.

## Installation de la mise à jour
1. Sauvegarder les fichiers locaux et distants du projet Vite & Gourmand.
2. Copier `app/mongo_stats.php`, et remplacer `app/actions.php`, `app/extra_actions.php`,
   `public/index.php`, `public/assets/style.css` uniquement dans Vite & Gourmand.
3. **Ne pas réimporter SQL**. **Ne pas modifier Le Quai Antique**.
4. Ouvrir le site en ligne, se connecter comme administrateur, page Gestion.
5. Cliquer « Synchroniser les commandes depuis MariaDB » pour transférer les commandes
   existantes. Vérifier que les documents apparaissent dans Atlas > Data Explorer.
6. Vérifier les barres par menu, les filtres par menu / date de création et la colonne de CA réalisé.
7. Passer une commande de test puis rafraîchir Gestion : elle doit s’ajouter au graphique.
8. En cas d’échec, la synchronisation affiche une erreur neutre ; contrôler les journaux serveur
   et la configuration Atlas. Les commandes MariaDB continuent de fonctionner.

## Limites / prochaines améliorations
- Cette projection est à synchronisation « best effort » (écriture SQL puis MongoDB sans transaction distribuée).
  Cliquer « Synchroniser » garantit une remise en cohérence à la demande.
- Une charge importante nécessiterait une file de messages / job asynchrone.
- Pas de comptabilité ni de preuve d’encaissement : le « CA réalisé » est défini ici
  par les statuts de livraison et de clôture.
- Les requêtes de démonstration lisent au maximum 20 000 documents ; un véritable
  reporting à grande échelle devrait utiliser les pipelines d’agrégation MongoDB.
