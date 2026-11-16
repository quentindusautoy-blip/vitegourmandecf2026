# Architecture technique — Vite & Gourmand (état de la version 2)

## Choix techniques

- HTML5/CSS3 responsive et JavaScript natif : peu de dépendances, faible coût d'hébergement et filtres de menus côté client.
- PHP 8.2 et PDO : intégration directe avec Apache/XAMPP, requêtes préparées, logique métier et autorisations côté serveur.
- MariaDB/MySQL : données structurées (menus, plats, relations N:N, commandes, comptes, suivi et avis).
- Session PHP et cookie HttpOnly / SameSite : authentification côté serveur, sans stocker de jeton dans le navigateur.
- MongoDB/NoSQL : **non intégré**, exigence à réaliser pour le graphique des commandes par menu.

## Entités principales et relations

```mermaid
erDiagram
    USERS ||--o{ ORDERS : passe
    MENUS ||--o{ ORDERS : concerne
    ORDERS ||--o{ ORDER_HISTORY : possede
    USERS ||--o{ REVIEWS : publie
    ORDERS ||--o| REVIEWS : recoit
    MENUS ||--o{ MENU_DISHES : comporte
    DISHES ||--o{ MENU_DISHES : figure_dans
    USERS {
      int id PK
      string email UK
      string password_hash
      string role
    }
    MENUS {
      int id PK
      string title
      int min_people
      int price_cents
      int stock
    }
    DISHES {
      int id PK
      string name
      string kind
      string allergens
    }
    MENU_DISHES {
      int menu_id FK
      int dish_id FK
    }
    ORDERS {
      int id PK
      int user_id FK
      int menu_id FK
      int total_cents
      string status
    }
    ORDER_HISTORY {
      int id PK
      int order_id FK
      string status
    }
    REVIEWS {
      int id PK
      int order_id FK
      int user_id FK
      int rating
    }
```

## Cas d'utilisation (extrait)

```mermaid
flowchart LR
  Visiteur --> Consulter[Consulter les menus]
  Visiteur --> Inscrire[Créer un compte]
  Client --> Commander[Passer commande]
  Client --> Modifier[Modifier ou annuler commande en attente]
  Client --> Suivi[Suivre les statuts]
  Employe --> Gerer[Gérer menus, plats, horaires]
  Employe --> Statut[Traiter les commandes]
  Admin --> EmployeCompte[Créer ou désactiver un employé]
```

## Séquence de commande (extrait)

```mermaid
sequenceDiagram
    actor Client
    participant Web as Interface web
    participant PHP as Contrôleur PHP
    participant SQL as MariaDB
    Client->>Web: Remplit le formulaire
    Web->>PHP: POST place_order + CSRF
    PHP->>PHP: Vérifie session, date, convives, livraison
    PHP->>SQL: BEGIN; SELECT menu FOR UPDATE
    SQL-->>PHP: Prix, préavis et stock
    PHP->>PHP: Calcule remise et total
    PHP->>SQL: INSERT commande + historique; décrémente stock
    PHP->>SQL: COMMIT
    PHP-->>Web: Redirection vers Mon compte
    Web-->>Client: Affiche le récapitulatif
```

## Sujets de sécurité

Les formulaires POST utilisent un jeton CSRF ; les données envoyées sont vérifiées côté PHP ; les écritures SQL utilisent des requêtes préparées ; les mots de passe sont hachés ; une session est régénérée à la connexion ; les pages de gestion et les actions vérifient le rôle ; les sorties HTML sont échappées.

**À approfondir avant déploiement** : anti-bruteforce, tests RGAA, mailer SMTP, récupération du mot de passe, stockage sécurisé des secrets, HTTPS obligatoire, protection contre l'énumération de comptes et revue des mentions légales.
