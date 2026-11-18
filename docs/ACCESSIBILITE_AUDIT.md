# Audit d'accessibilité — Vite & Gourmand

Date : 18 novembre 2026. **Statut : audit partiel, sans déclaration de conformité RGAA**.

## Périmètre et méthode

Relecture du HTML et des feuilles CSS de la branche `develop`, calcul des ratios de contraste selon la formule WCAG, puis correctifs isolés dans `feature/accessibilite-contrastes`. Le test clavier initial (Tab et Entrée) a été effectué par le développeur sur le site en ligne avant cette branche. Ce document n'est ni un audit exhaustif RGAA ni un rapport de test avec technologies d'assistance.

## Points vérifiés

- Langue principale du document : `<html lang="fr">`.
- Repères HTML : `header`, `nav` avec nom accessible, `main` et `footer`.
- Lien d'évitement « Aller au contenu ».
- Champs de formulaire majoritairement associés à un `label`.
- Filtres du catalogue : `role="status"` et `aria-live="polite"`.
- Statistiques administrateur : graphique doublé d'un tableau HTML.

## Anomalies relevées et corrections

| Point | Avant | Après | Vérification |
| --- | --- | --- | --- |
| Texte `.tag` sur fond blanc | 4,40:1 | ~5,26:1 | Contraste recalculé |
| Texte `.eyebrow` sur fond papier | 4,18:1 | ~4,97:1 | Contraste recalculé |
| Couleur dorée de certains textes | trop faible pour certaines utilisations | nuance assombrie | Relecture visuelle à faire |
| Focus des champs | contour pâle de ~1,57:1 sur blanc | contour vert foncé `#244c42` | Contraste calculé ; contrôle navigateur à faire |
| Focus des liens, boutons et `summary` | style navigateur variable | contour 3 px explicite | Contrôle clavier à faire |
| Motifs décoratifs des cartes | motif « ✳ » susceptible d'être annoncé | `aria-hidden="true"` | Contrôle lecteur d'écran à faire |
| Cible du lien d'évitement | `main` non focalisable explicitement | `tabindex="-1"` | Contrôle clavier à faire |
| Préférence d'animations réduites | défilement toujours animé | `prefers-reduced-motion:reduce` | Contrôle navigateur à faire |

## Scénarios de test à mener avant fusion dans `develop`

1. Sur la page d'accueil, appuyer sur Tab dès l'ouverture de la page : le lien « Aller au contenu » doit apparaître ; Entrée doit déplacer la navigation vers le contenu principal.
2. Parcourir liens, boutons, champs et accordéons `details` au clavier : le focus doit rester visible et l'ordre logique.
3. Vérifier les pages Accueil, Menus, Détail, Connexion, Inscription, Mot de passe oublié, Commande, Mon compte et Gestion (avec les rôles de test appropriés).
4. Tester le zoom à 200 %, puis à 400 % avec une fenêtre de 1280 px, sans perte de contenu ni défilement horizontal injustifié.
5. Vérifier les messages d'erreur et le guidage de correction des formulaires ; tester un lecteur d'écran (NVDA sur Windows) pour les champs et les notifications.
6. Comparer les graphiques administrateur et leur tableau alternatif ; confirmer que les chiffres restent lisibles quand Chart.js est indisponible.
7. Tester les couleurs et l'affichage des éléments interactifs sur smartphone ou fenêtre étroite (320 px CSS).

## Sujets encore ouverts

- Les messages d'erreur génériques des formulaires ne sont pas systématiquement rattachés aux champs concernés par `aria-describedby` ou `aria-invalid`.
- Les listes de filtres doivent être contrôlées avec un lecteur d'écran pour limiter les annonces excessives.
- Les changements de statut et messages flash nécessitent des tests d'annonce avec lecteur d'écran.
- Contrôler les noms accessibles de tous les boutons d'action en gestion ; « Détails » répété peut être insuffisamment explicite hors contexte.
- Vérifier la hiérarchie de titres dans chaque rendu dynamique, les légendes des champs groupés et la compatibilité avec les systèmes d'agrandissement.
- Repasser un audit complet critère par critère avant toute déclaration de conformité RGAA.

**Important :** ne pas cocher la tâche « audit RGAA complet » sur `docs/PLAN.md` tant que les tests manuels détaillés ne sont pas réalisés.
