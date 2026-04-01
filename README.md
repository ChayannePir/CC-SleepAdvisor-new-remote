# SleepAdvisor - Système de Réservation d'Hôtel

## 📋 Description

Application web CRUD MVC développée avec Symfony 7.4 pour gérer un système de réservation d'hôtel. L'application permettent aux clients de rechercher et réserver des chambres, et aux administrateurs de gérer les chambres, réservations et clients.

## 🎯 Fonctionnalités

### Espace Public

- 🏠 Accueil avec présentation du service
- 🔍 Recherche de chambres disponibles par date
- 📝 Inscription/Connexion des clients
- 🔑 Récupération du mot de passe oublié

### Espace Client

- 📅 Visualisation des réservations
- 💬 Ajout de commentaires/demandes spéciales
- ❌ Annulation de réservations
- 👤 Gestion du profil

### Espace Admin

- 🏨 CRUD Chambres (Créer, Lire, Mettre à jour, Supprimer)
- 📋 CRUD Réservations avec recherche par numéro
- 👥 CRUD Clients avec recherche par nom/email
- 📊 Tableau de bord avec statistiques
- 📄 Pagination sur toutes les listes

## 🏗️ Architecture

### Couches du projet

```
src/
├── Entity/              # Entités Doctrine
│   ├── User.php
│   ├── Client.php
│   ├── Gestionnaire.php
│   ├── Hotel.php
│   ├── Chambre.php
│   ├── Reservation.php
│   └── CommentaireReservation.php
├── Repository/          # Repositories Doctrine
├── Service/             # Couche métier
│   ├── ClientService.php
│   ├── ChambreService.php
│   └── ReservationService.php
├── Controller/          # Contrôleurs
│   ├── SecurityController.php
│   ├── Public/SearchController.php
│   ├── Client/ReservationController.php
│   └── Admin/
│       ├── DashboardController.php
│       ├── ChambreController.php
│       ├── ReservationController.php
│       └── ClientController.php
├── Security/            # Authenticateurs
└── Kernel.php
```

## 📊 Modèle de Données

### Relations

- **User** (héritage SINGLE_TABLE)
    - Client et Gestionnaire héritent de User
    - Un client peut avoir plusieurs réservations
    - Un gestionnaire gère un hôtel

- **Hotel**
    - Contient plusieurs chambres
    - A un ou plusieurs gestionnaires
    - A plusieurs réservations

- **Chambre**
    - Appartient à un hôtel
    - Peut être réservée plusieurs fois à des dates différentes
    - Relation ManyToMany avec Reservation

- **Reservation** (relation ternaire)
    - Lie un client, un hôtel et des chambres
    - Contient les dates de début/fin
    - Peut avoir plusieurs commentaires

- **CommentaireReservation**
    - Permet les demandes spéciales sur une réservation

## 🚀 Installation

### Prérequis

- PHP 8.2+
- Composer
- MySQL/MariaDB
- Symfony CLI (optionnel)

### Étapes d'installation

1. **Cloner le projet**

```bash
cd PHP-Symfony
```

2. **Installer les dépendances**

```bash
composer install
```

3. **Configurer la base de données**

```bash
# Modifier le fichier .env
DATABASE_URL="mysql://user:password@localhost:3306/hotelbooking"
```

4. **Créer la base de données et exécuter les migrations**

```bash
php bin/console doctrine:database:create
php bin/console doctrine:migrations:migrate
```

5. **Charger les données de test (optionnel)**

```bash
php bin/console doctrine:fixtures:load --no-interaction
```

6. **Lancer le serveur de développement**

```bash
symfony serve
# ou
php -S localhost:8000 -t public/
```

Accédez à `http://localhost:8000` dans votre navigateur.

## 👤 Utilisation

### Comptes de test

#### Client

- Email: `client@example.com`
- Mot de passe: `password123`

#### Admin

- Email: `admin@example.com`
- Mot de passe: `admin123`

## 🔐 Authentification

Le projet utilise:

- **AppAuthenticator** - Authentification personnalisée
- **PasswordHasher** - Hachage sécurisé des mots de passe
- **Remember Me** - Fonctionnalité "Se souvenir de moi"
- **ROLE_CLIENT** - Rôle pour les clients
- **ROLE_ADMIN** - Rôle pour les administrateurs
- **ROLE_GESTIONNAIRE** - Rôle pour les gestionnaires d'hôtel

## ✅ Bonnes Pratiques Implémentées

### Code

- ✅ Architecture MVC - Séparation de responsabilités
- ✅ Couches (Entités, Repositories, Services, Contrôleurs)
- ✅ Principes SOLID:
    - Single Responsibility: chaque classe a une responsabilité unique
    - Dependency Injection: utilisation du conteneur Symfony
    - Interface Segregation: services bien définis
- ✅ Validation: constraints Symfony sur les entités
- ✅ Gestion d'exceptions: Try/catch et messages d'erreur
- ✅ Documentation: PHPDoc sur les classes et méthodes

### Sécurité

- ✅ Hachage des mots de passe avec bcrypt
- ✅ Protection CSRF sur les formulaires
- ✅ Contrôle d'accès par rôles (RBAC)
- ✅ Vérification d'autorisation par contrôleur

### Design

- ✅ Responsive design avec Bootstrap 5
- ✅ CSS personnalisé avec variables et gradients
- ✅ Mobile-first approach
- ✅ Icônes Font Awesome

### Tests

- ✅ Tests unitaires des services
- ✅ Tests des entités
- ✅ Configuration PHPUnit

## 🧪 Tests

Exécuter les tests:

```bash
php bin/phpunit
```

Exécuter les tests avec couverture de code:

```bash
php bin/phpunit --coverage-html coverage/
```

## 📱 Responsive Design

L'application est entièrement responsive avec breakpoints Bootstrap:

- **XS**: < 576px
- **SM**: ≥ 576px
- **MD**: ≥ 768px
- **LG**: ≥ 992px
- **XL**: ≥ 1200px
- **XXL**: ≥ 1400px

## 🔄 Flux de l'Application

### Pour un client:

1. Inscription sur l'accueil
2. Connexion
3. Recherche de chambres
4. Réservation
5. Visualisation des réservations
6. Ajout de commentaires
7. Annulation (optionnel)

### Pour un administrateur:

1. Connexion
2. Accès au tableau de bord
3. Gestion des chambres (CRUD)
4. Gestion des réservations
5. Gestion des clients

## 🌐 Routes Principales

| Route                  | Contrôleur                   | Rôle Requis |
| ---------------------- | ---------------------------- | ----------- |
| `/`                    | SecurityController::home     | -           |
| `/login`               | SecurityController::login    | -           |
| `/inscription`         | SecurityController::register | -           |
| `/recherche`           | SearchController::search     | -           |
| `/client/reservations` | ClientReservationController  | ROLE_CLIENT |
| `/admin`               | DashboardController          | ROLE_ADMIN  |
| `/admin/chambres`      | ChambreController            | ROLE_ADMIN  |
| `/admin/reservations`  | ReservationController        | ROLE_ADMIN  |
| `/admin/clients`       | ClientController             | ROLE_ADMIN  |

## 🛠️ Technologies Utilisées

- **Framework**: Symfony 7.4
- **ORM**: Doctrine
- **Template Engine**: Twig
- **CSS Framework**: Bootstrap 5
- **Icons**: Font Awesome 6
- **Validation**: Symfony Validator
- **Security**: Symfony Security
- **Testing**: PHPUnit

## 📝 Gestion de la Dette Technique

- ✅ Code documenté
- ✅ Validation des données
- ✅ Gestion des erreurs
- ✅ Requêtes SQL optimisées
- ✅ Pagination pour les listes volumineuses
- ✅ Utilisation des services réutilisables

## 🎓 Critères d'Évaluation Couverts

- ✅ Configuration du projet
- ✅ Organisation des packages
- ✅ Bonnes pratiques de codage
- ✅ Documentation du code
- ✅ Vues responsives design
- ✅ Couverture des exigences
- ✅ Gestion des exceptions
- ✅ Authentification et mot de passe oublié
- ✅ Tests

## 📞 Support

Pour toute question ou problème, consultez la documentation Symfony:

- [Documentation Symfony](https://symfony.com/doc/current/index.html)
- [Doctrine ORM](https://www.doctrine-project.org/)
- [Twig Template Engine](https://twig.symfony.com/)

## 📄 Licence

MIT License

---

**Développé pour l'expertise IT - Applications Intelligentes et Big Data**
