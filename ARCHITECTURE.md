# Architecture et Spécifications - SleepAdvisor

## 🏗️ Architecture Générale

### Couches d'Application

```
┌─────────────────────────────────────────────────────────────┐
│                    COUCHE PRÉSENTATION (Twig)              │
│              (Templates et Vues Responsives)                │
└──────────────────────────┬──────────────────────────────────┘
                           │
┌──────────────────────────▼──────────────────────────────────┐
│           COUCHE CONTRÔLEUR (Controllers)                   │
│ ┌──────────────────────────────────────────────────────┐   │
│ │ Security │ Public │ Client │ Admin                   │   │
│ └──────────────────────────────────────────────────────┘   │
└──────────────────────────┬──────────────────────────────────┘
                           │
┌──────────────────────────▼──────────────────────────────────┐
│           COUCHE MÉTIER (Services)                          │
│ ┌──────────────────────────────────────────────────────┐   │
│ │ ClientService │ ChambreService │ ReservationService │   │
│ └──────────────────────────────────────────────────────┘   │
└──────────────────────────┬──────────────────────────────────┘
                           │
┌──────────────────────────▼──────────────────────────────────┐
│        COUCHE PERSISTANCE (Repositories)                    │
│ ┌──────────────────────────────────────────────────────┐   │
│ │UserRepository│ClientRepository│ChambreRepository... │   │
│ └──────────────────────────────────────────────────────┘   │
└──────────────────────────┬──────────────────────────────────┘
                           │
┌──────────────────────────▼──────────────────────────────────┐
│         COUCHE ENTITÉS (Doctrine ORM)                       │
│ ┌──────────────────────────────────────────────────────┐   │
│ │ Entités mapées aux tables de la base de données      │   │
│ └──────────────────────────────────────────────────────┘   │
└──────────────────────────┬──────────────────────────────────┘
                           │
┌──────────────────────────▼──────────────────────────────────┐
│              COUCHE BASE DE DONNÉES                         │
│                    (MySQL/MariaDB)                          │
└─────────────────────────────────────────────────────────────┘
```

## 📊 Modèle de Données

### Entités et Relations

#### User (Admin et Clients)

```
User (Héritage SINGLE_TABLE)
├── id (PK)
├── email (unique)
├── password (hashé)
├── roles (JSON array)
├── telephone
├── isActive
├── type (Single Table Inheritance: client, gestionnaire)
├── createdAt
└── updatedAt
```

#### Client (hérite de User)

```
Client extends User
├── nom
├── adresse
└── reservations (OneToMany)
```

#### Hotel

```
Hotel
├── id (PK)
├── nom
├── adresse
├── categorie (*, **, ***, ****, *****)
├── chambres (OneToMany)
├── gestionnaires (OneToMany)
└── reservations (OneToMany)
```

#### Chambre

```
Chambre
├── id (PK)
├── type (Single, Double, Twin, Suite, Deluxe)
├── etage
├── nombreLits
├── hotel (ManyToOne)
└── reservations (ManyToMany)
```

#### Reservation

```
Reservation
├── id (PK)
├── numeroReservation (unique)
├── client (ManyToOne)
├── hotel (ManyToOne)
├── chambres (ManyToMany)
├── dateDebut
├── dateFin
├── statut (En attente, Confirmée, Annulée)
├── commentaires (OneToMany)
├── createdAt
└── updatedAt
```

#### CommentaireReservation

```
CommentaireReservation
├── id (PK)
├── contenu
├── type (Demande spéciale, Réclamation, Remarque)
├── reservation (ManyToOne)
└── createdAt
```

## 🔐 Sécurité

### Authentification

- **Méthode**: Form-based avec AppAuthenticator personnalisé
- **Hachage**: bcrypt via UserPasswordHasher
- **Sessions**: Session de 7 jours par défaut
- **Remember Me**: Disponible pour les clients

### Autorisations (RBAC)

```
ROLE_USER (rôle par défaut)
├── ROLE_CLIENT (clients)
│   └── Accès à /client/**
└── ROLE_ADMIN (administrateurs)
    └── Accès à /admin/**
```

### Contrôles d'Accès

```
route                      | contrôle
──────────────────────────┼──────────────────────────
/                         | public
/login                    | public
/inscription              | public
/mot-de-passe-perdu      | public
/recherche                | public
/client/**                | @IsGranted('ROLE_CLIENT')
/admin/**                 | @IsGranted('ROLE_ADMIN')
```

## 🎯 Flux de Réservation

### Parcours Client

```
1. Accueil
   ↓
2. Recherche de chambre (date début/fin, hôtel optionnel)
   ↓
3. Affichage des chambres disponibles
   ↓
4. Détail chambre
   ↓
5. Réservation
   ├─→ Non connecté → Inscription/Connexion
   └─→ Connecté → Confirmatiomer la réservation
   ↓
6. Réservation créée (statut: En attente)
   ↓
7. Espace Client: Mes réservations
   ├─→ Voir détails
   ├─→ Ajouter commentaires
   └─→ Annuler (si permis)
```

### Gestion Admin

```
1. Dashboard: Vue d'ensemble
   ├─→ Clients (statut)
   ├─→ Chambres (inventaire)
   └─→ Réservations (flux)

2. CRUD Chambres
   ├─→ Lister (avec pagination)
   ├─→ Créer
   ├─→ Modifier
   └─→ Supprimer

3. CRUD Réservations
   ├─→ Lister (avec recherche par numéro)
   ├─→ Voir détails (avec chambres, commentaires)
   ├─→ Confirmer
   ├─→ Annuler
   └─→ Supprimer

4. CRUD Clients
   ├─→ Lister (avec pagination, recherche)
   ├─→ Voir détails (historique réservations)
   └─→ Supprimer
```

## 📋 Validation des Données

### Entité User

- email: NotBlank, Email, unique
- password: NotBlank, Length(min: 8)
- telephone: NotBlank, Length(10-20)

### Entité Client

- nom: NotBlank, Length(2-100)
- adresse: NotBlank

### Entité Chambre

- type: NotBlank, Choice([Single, Double, Twin, Suite, Deluxe])
- etage: NotBlank, Positive
- nombreLits: NotBlank, Positive

### Entité Reservation

- chambres: Count(min: 1)
- dateDebut: NotBlank, Type(DateTimeInterface)
- dateFin: NotBlank, Type(DateTimeInterface), GreaterThan(dateDebut)

### Entité CommentaireReservation

- contenu: NotBlank, Length(5-1000)
- type: Choice([Demande spéciale, Réclamation, Remarque])

## 🔄 Cycle de Vie des Objets

### État d'une Réservation

```
Création
   ↓
En attente (admin doit confirmer)
   ├─→ Confirmée (client peut voir les détails)
   │   └─→ Annulée (par client ou admin)
   └─→ Annulée (par admin)
```

## 🧪 Tests

### Couverture de Test

- **Unités**: Services, Entités
- **Intégration**: Controllers, Repositories
- **E2E**: Scénarios complets (inscription → réservation → gestion)

### Exemples de Tests

```
ReservationServiceTest
├── testCreateReservationWithValidData()
├── testCreateReservationFailsWithoutChambre()
└── testCreateReservationFailsWithConflict()

ClientTest
├── testCreateClient()
└── testAddReservation()
```

## 📱 Design Responsive

### Points de Rupture Bootstrap

```
XS: < 576px     (téléphones)
SM: 576px       (petits téléphones)
MD: 768px       (tablettes)
LG: 992px       (petits ordinateurs)
XL: 1200px      (ordinateurs)
XXL: 1400px     (grands écrans)
```

### Adaptabilité

- Navbar collapse sur mobile
- Tables scrollables sur petits écrans
- Formulaires empilés verticalement
- Cartes responsives avec grid Bootstrap

## 💾 Base de Données

### Tables Principales

```
`user` (héritage SINGLE_TABLE)
├── PK: id
├── EMAIL: unique
├── TYPE: (client|gestionnaire)
└── Clés étrangères vers chambres, réservations

hotel
├── PK: id
├── Catégories étoiles: *, **, ***, ****, *****

chambre
├── PK: id
├── FK: hotel_id

reservation
├── PK: id
├── FK: client_id, hotel_id
├── Relation dénormalisée avec chambres

reservation_chambre (table de jointure)
├── FK: reservation_id
├── FK: chambre_id

commentaire_reservation
├── FK: reservation_id
```

## 🚀 Performance

### Optimisations

- Pagination sur listes (10 éléments par défaut)
- Requêtes DQL optimisées avec jointures
- Recherche par index sur email et nom
- Cache de session pour l'utilisateur connecté

### Requêtes Importantes

- FindAvailableChambres: LEFT JOIN avec exclusion des dates réservées
- FindByClientId: Requête simple pour les réservations du client
- FindConflictingReservations: Vérification des chevauchements de dates

## 📝 Conventions de Code

### Nommage

- **Classes**: PascalCase (ClientService)
- **Méthodes**: camelCase (getReservations())
- **Propriétés**: camelCase ($nomReservation)
- **Constantes**: UPPER_SNAKE_CASE (ROLE_ADMIN)

### Documentation

- PHPDoc sur les classes publiques
- @param et @return sur les méthodes
- Description des méthodes complexes

### Principes SOLID

- **S**ingle Responsibility: Services pour métier, Repositories pour données
- **O**pen/Closed: Héritage User pour Client et Gestionnaire
- **L**iskov Substitution: Repositories héritent de ServiceEntityRepository
- **I**nterface Segregation: Validateurs Symfony
- **D**ependency Injection: Injection via constructeur

## 🛠️ Maintenabilité

### Points d'Extension

- Services métier réutilisables
- Repositories extensibles pour nouvelles requêtes
- Templates Twig hérités de base.html.twig
- Validateurs centralisés sur entités

### Gestion de la Dette Technique

- ✅ Pas de code dupliqué
- ✅ Services désaccouplés
- ✅ Tests unitaires
- ✅ Documentation présente
- ✅ Erreurs gérées globalement

---

**Document Architectural - SleepAdvisor**
