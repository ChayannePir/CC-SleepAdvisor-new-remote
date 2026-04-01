# 📊 Diagramme MCD - SleepAdvisor

## Relations entre entités

```
┌─────────────────────────────────────────────────────────────┐
│                                                               │
│                      USER (Table unique)                      │
│                                                               │
│  ┌─────────────────────────────────────────────────────────┐ │
│  │ id (PK)                                                 │ │
│  │ type (SINGLE_TABLE inheritance: user/client/gestionnaire)│ │
│  │ email (UNIQUE)                                          │ │
│  │ password                                                │ │
│  │ telephone                                               │ │
│  │ nom                                                     │ │
│  │ adresse (pour Client)                                   │ │
│  │ createdAt                                               │ │
│  │ updatedAt                                               │ │
│  └─────────────────────────────────────────────────────────┘ │
│         │                      │                              │
│         ├─ extends ────────────┼─ extends                    │
│         │                      │                              │
└─────────┼──────────────────────┼─────────────────────────────┘
          │                      │
    ┌─────▼────────┐      ┌──────▼────────────┐
    │   CLIENT      │      │  GESTIONNAIRE      │
    │               │      │                    │
    │ (Hérite)      │      │ (Hérite)           │
    │               │      │                    │
    │ + adresse     │      │ + hotel_id (FK)    │
    │ + reservations◄──────┼────────────────────┼──► HOTEL
    └───────────────┘      │                    │
          │                └────────────────────┘
          │
          │ one-to-many
          │
    ┌─────▼──────────────────┐
    │   RESERVATION           │
    │                         │
    │ ┌─────────────────────┐ │
    │ │ id (PK)             │ │
    │ │ numeroReservation   │ │
    │ │   (UNIQUE)          │ │
    │ │ dateDebut           │ │
    │ │ dateFin             │ │
    │ │ statut              │ │
    │ │ client_id (FK)      │ │
    │ │ hotel_id (FK)       │ │
    │ │ createdAt           │ │
    │ │ updatedAt           │ │
    │ └─────────────────────┘ │
    │         │        │       │
    │         │        │       │
    │    ┌────▼────────▼──┐    │
    │    │  many-to-many  │    │
    │    │  junction table │    │
    │    │ reservation_    │    │
    │    │ chambre         │    │
    │    └────┬────────┬──┘    │
    │         │        │       │
    │         │        │       │
    └─────────┼────────┼───────┘
              │        │
              │    ┌───▼──────────────────┐
              │    │     HOTEL            │
              │    │                      │
              │    │ ┌──────────────────┐ │
              │    │ │ id (PK)          │ │
              │    │ │ nom              │ │
              │    │ │ adresse          │ │
              │    │ │ categorie (★-★★★★★)│ │
              │    │ │ chambres[]       │ │
              │    │ │ gestionnaires[]  │ │
              │    │ │ reservations[]   │ │
              │    │ └──────────────────┘ │
              │    └────────┬──────────────┘
              │             │
              │    ┌────────▼────────┐
              └───►│     CHAMBRE      │
                   │                  │
                   │ ┌─────────────┐  │
                   │ │ id (PK)     │  │
                   │ │ type        │  │ (Single, Double, Twin, Suite, Deluxe)
                   │ │ etage       │  │
                   │ │ nombreLits  │  │
                   │ │ hotel_id(FK)│  │
                   │ │ reservations│  │ (many-to-many)
                   │ └─────────────┘  │
                   └────────┬─────────┘
                            │
                    ┌───────▼──────────────┐
                    │ COMMENTAIRE_         │
                    │ RESERVATION          │
                    │                      │
                    │ ┌──────────────────┐ │
                    │ │ id (PK)          │ │
                    │ │ contenu          │ │
                    │ │ type             │ │ (Demande spéciale, Réclamation, Remarque)
                    │ │ reservation_id   │ │ (FK - one-to-many)
                    │ │ createdAt        │ │
                    │ └──────────────────┘ │
                    └──────────────────────┘
```

## Flux d'interactions

```
┌──────────────────────────────────────────────────────────────────┐
│                         APPLICATION FLOW                          │
└──────────────────────────────────────────────────────────────────┘

ÉTAPE 1: Authentification
─────────────────────────
    Utilisateur               Navigateur              Application
         │                         │                        │
         │──────────POST /login───►│                        │
         │                         │──► Validation email    │
         │                         │─── et password         │
         │                         │    [Vérifier dans DB]  │
         │                         │◄─── OK / Erreur        │
         │                         │                        │
         │◄───── Cookie Session ───│                        │
         │     (PHPSESSID)         │                        │
         │                         │                        │

ÉTAPE 2: Recherche de chambres
──────────────────────────────
    Client             Navigateur              Application
         │                  │                        │
         │──── GET /search ─►│                        │
         │                   │──► findAvailableChambres│
         │                   │    (dates, hôtel)      │
         │                   │    [Requête DQL]       │
         │                   │◄─── Liste chambres     │
         │◄──── HTML page ───│                        │
         │  (avec filtres)   │                        │

ÉTAPE 3: Créer une réservation
───────────────────────────────
    Client             Navigateur              Application
         │                  │                        │
         │─ POST /reserver ─►│                        │
         │ (dates + chambre) │──► createReservation   │
         │                   │    * check conflicts   │
         │                   │    * generate numéro   │
         │                   │    * set statut        │
         │                   │    * save to DB        │
         │                   │◄─── Reservation saved  │
         │◄──── Redirect ────│                        │
         │  (details page)   │                        │

ÉTAPE 4: Gestion admin
──────────────────────
  Admin              Navigateur              Application
     │                   │                        │
     │ GET /admin/...────►│                        │
     │                    │──► Check ROLE_ADMIN   │
     │                    │    generateList       │
     │                    │    (avec pagination)  │
     │                    │◄─── HTML (tableau)    │
     │◄───── Page admin ───│                        │
     │                    │                        │
     │ POST /admin/.../­ ─►│                        │
     │ (edit/delete)      │──► updateDatabase     │
     │                    │    saveChanges        │
     │                    │◄─── OK                │
     │◄──── Redirect ─────│                        │
```

## État des réservations

```
┌─────────────────────────────────┐
│   État des réservations          │
└─────────────────────────────────┘

    ┌──────────────────┐
    │   En attente     │ (Nouvelle réservation)
    └────────┬─────────┘
             │
             ├─────────────────┐
             │                 │
       ┌─────▼────────┐   ┌────▼─────────┐
       │  Confirmée   │   │   Annulée    │
       │ (Admin/Auto) │   │ (Client/Admin)│
       └──────────────┘   └──────────────┘

Transitions:
• En attente → Confirmée (via admin)
• En attente → Annulée (via client/admin)
• Confirmée → Annulée (avant date début)
```

## Diagramme d'authentification

```
┌─────────────────────────────────────────────┐
│        SYSTÈME D'AUTHENTIFICATION            │
└─────────────────────────────────────────────┘

    ┌────────────────────────────┐
    │  Formulaire Login          │
    │ [email] [password] [button]│
    └────────┬────────────────────┘
             │
             ▼
    ┌───────────────────────────┐
    │  AppAuthenticator         │
    │  (Custom Authenticator)   │
    └────────┬──────────────────┘
             │
             ├─ Email existe? ──────► NO ──► Erreur
             │
             ├─ Password OK? ────────► NO ──► Erreur
             │
             └─ YES ──┐
                      │
                      ▼
              ┌──────────────────────┐
              │  Check Roles:        │
              │  • ROLE_CLIENT       │
              │  • ROLE_ADMIN        │
              └────┬─────────┬───────┘
                   │         │
            ┌──────▼──┐   ┌──▼────────┐
            │  Client  │   │   Admin    │
            │ Redirect │   │ Redirect   │
            │ /client/ │   │ /admin/    │
            └──────────┘   └────────────┘
```

## Pagination et recherche

```
┌────────────────────────────────────────────┐
│         PAGINATION & RECHERCHE              │
└────────────────────────────────────────────┘

Pagination par défaut: 10 items par page

Exemple:
    Page 1: items 1-10      (offset: 0)
    Page 2: items 11-20     (offset: 10)
    Page 3: items 21-30     (offset: 20)

    offset = (page - 1) * limit
    offset = (2 - 1) * 10 = 10

Recherche:
    • Chambres: par type ou étage
    • Clients: par nom ou email (LIKE %query%)
    • Réservations: par numéro (exact ou LIKE)
```

## Convention des types de données

```sql
id                  BIGINT AUTO_INCREMENT PRIMARY KEY
email               VARCHAR(180) UNIQUE NOT NULL
password            VARCHAR(255) NOT NULL
nom                 VARCHAR(255) NOT NULL
adresse             VARCHAR(500)
telephone           VARCHAR(20)
type (enum)         VARCHAR(50)
statut (enum)       VARCHAR(50)
categorie (enum)    VARCHAR(10)
nombreLits          INT
etage               INT
dateDebut           DATETIME NOT NULL
dateFin             DATETIME NOT NULL
contenu             TEXT
createdAt           DATETIME DEFAULT CURRENT_TIMESTAMP
updatedAt           DATETIME DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
```

---

## Quelques statistiques du projet

- **7 Entités** créées
- **7 Repositories** avec requêtes optimisées
- **3 Services** pour la logique métier
- **8 Contrôleurs** pour les routes
- **17 Templates Twig** responsives
- **2+ Fichiers de tests** unitaires
- **5 Documentation** complètes
- **Zéro warnings** PHP strict
