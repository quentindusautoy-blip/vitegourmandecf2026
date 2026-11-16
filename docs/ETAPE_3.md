# Étape 3 — Gestion complète du compte et du back-office

Cette mise à jour enrichit le projet fourni pour l'ECF Studi « Vite & Gourmand ».

## Installation sans perdre ses données

1. **Sauvegarder** le dossier courant `C:\xampp\htdocs\SiteWeb\vite-gourmand` en le copiant ailleurs (ou en le dupliquant sous `vite-gourmand-sauvegarde`).
2. Décompresser `vite-gourmand-mise-a-jour-2.zip` dans un endroit temporaire.
3. Copier les fichiers `app/actions.php`, `app/extra_actions.php`, `public/index.php`, `public/assets/style.css` dans le dossier courant `C:\xampp\htdocs\SiteWeb\vite-gourmand`, en conservant leurs sous-dossiers. **Autoriser le remplacement des trois fichiers déjà présents.**
4. Facultatif : copier aussi `docs/ETAPE_3.md`, `docs/PLAN.md`, `docs/ARCHITECTURE.md` et `README.md` pour garder la documentation à jour.
5. Ne **pas** importer ou recréer la base de données : **aucune migration SQL n'est nécessaire** pour cette version.
6. Ouvrir `http://localhost/SiteWeb/vite-gourmand/public/index.php?page=account`, se connecter et tester.

## Scénarios de vérification manuelle

- Client : modifier son prénom et son téléphone dans `Mon compte` ; rafraîchir et vérifier la persistance.
- Client : créer une commande puis l'ouvrir. En statut `pending`, changer le nombre de personnes ; confirmer le recalcul du total et la possibilité de modifier la date, l'adresse et l'heure. Le menu choisi doit rester le même.
- Client : vérifier que la modification n'est plus proposée après acceptation par un employé.
- Employé : filtrer les commandes par statut et email ; changer les horaires du lundi ; vérifier le pied de page.
- Employé : créer un menu, lui associer un plat, consulter la fiche publique ; modifier son stock et son titre puis le masquer.
- Employé : créer un plat avec allergènes et l'associer à plusieurs menus.
- Client : vérifier que les anciennes commandes et comptes sont toujours présents.

## Limites connues et vigilance

- L'envoi SMTP, la réinitialisation de mot de passe par email, la galerie d'images et la base MongoDB sont **encore à réaliser**.
- La livraison hors Bordeaux utilise une distance déclarée par l'utilisateur, **non vérifiée** par un service cartographique.
- Les maquettes, PDF, graphiques NoSQL et le déploiement sont à fournir séparément.
- Le changement de tarif d'une commande en attente reflète le tarif **courant** du menu : hypothèse pédagogique à mentionner dans la documentation métier.
- Pas de test d'intégration sur le poste Windows de l'étudiant : effectuer les vérifications ci-dessus avant d'enregistrer cette mise à jour comme validée.
