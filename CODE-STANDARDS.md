# 🎯 Code Standards & Best Practices - SleepAdvisor

Ce document décrit les conventions de code et les meilleures pratiques utilisées dans ce projet.

---

## 📋 Table des matières

1. [Structure du Projet](#structure-du-projet)
2. [Conventions de Nommage](#conventions-de-nommage)
3. [Patterns de Conception](#patterns-de-conception)
4. [SOLID Principles](#solid-principles)
5. [Validation et Sécurité](#validation-et-sécurité)
6. [Tests](#tests)
7. [Documentation](#documentation)

---

## 📁 Structure du Projet

```
src/
├── Controller/          # Contrôleurs (couche présentation)
│   ├── SecurityController.php
│   ├── Admin/
│   ├── Client/
│   └── Public/
├── Entity/             # Entités Doctrine (modèles)
├── Repository/         # Repositories (accès aux données)
├── Service/            # Services (logique métier)
├── Security/           # Configuration de sécurité
└── DataFixtures/       # Données de test

templates/              # Templates Twig
├── base_new.html.twig
├── security/
├── admin/
└── client/

tests/
├── Unit/              # Tests unitaires
├── Functional/        # Tests fonctionnels
└── bootstrap.php

config/
├── services.yaml      # Configuration des services DI
├── packages/
└── routes/
```

### Principe: Séparation des responsabilités

- **Controllers**: Gèrent les requêtes HTTP et les réponses
- **Entities**: Représentent la structure des données (ORM)
- **Repositories**: Requêtes spécialisées aux données
- **Services**: Logique métier et calculs
- **Templates**: Présentation (HTML/Twig)

---

## 📝 Conventions de Nommage

### Classes

```php
// ✓ PascalCase pour tous les noms de classe
class UserRepository { }
class ReservationService { }
class AdminChambreController { }

// Suffixes reconnus
class UserRepository { }        // Repository
class ReservationService { }    // Service
class UserController { }        // Controller
class User { }                  // Entity (pas de suffixe)
```

### Méthodes et Propriétés

```php
class Reservation {
    // ✓ camelCase pour les propriétés
    private string $numeroReservation;
    private \DateTime $dateDebut;

    // ✓ camelCase pour les méthodes
    public function getNumeroReservation(): string { }
    public function setDateDebut(\DateTime $date): self { }
    public function isConfirmed(): bool { }

    // ✓ getters, seters, et is/has pour booléens
    public function hasComments(): bool { }
}
```

### Variables

```php
// ✓ camelCase avec nom explicite
$totalPrice = 0;
$clientCount = 5;
$isAvailable = true;

// Variable dans boucle
foreach ($reservations as $reservation) { }
for ($i = 0; $i < 10; $i++) { }
```

### Constantes

```php
// ✓ SCREAMING_SNAKE_CASE
const RESERVATION_STATUS_PENDING = 'En attente';
const RESERVATION_STATUS_CONFIRMED = 'Confirmée';
const PAGINATION_LIMIT = 10;
```

### Fichiers

```
// Controllers
AdminChambreController.php
ClientReservationController.php

// Entities
User.php
Reservation.php

// Repositories
ChambreRepository.php

// Services
ReservationService.php

// Tests
ReservationServiceTest.php
ClientTest.php
```

---

## 🏗️ Patterns de Conception

### 1. Service Layer Pattern

Sépare la logique métier des contrôleurs:

```php
// ✓ BON: Logique métier dans le service
class ReservationService {
    public function createReservation(Client $client, Chambre $chambre, \DateTime $start, \DateTime $end): Reservation {
        // Vérifier les conflits
        $conflicts = $this->repository->findConflictingReservations(...);
        if (count($conflicts) > 0) {
            throw new \InvalidArgumentException('Chambre non disponible');
        }

        // Créer la réservation
        $reservation = new Reservation();
        // ... initialiser
        return $reservation;
    }
}

// ✗ MAUVAIS: Logique métier dans le contrôleur
public function reserve(Request $request): Response {
    // Logique métier ici = MAUVAIS!
    $conflicts = $this->getDoctrine()->...
}
```

### 2. Repository Pattern

Encapsule les requêtes aux données:

```php
// ✓ BON: Repository gère les requêtes
class ChambreRepository extends ServiceEntityRepository {
    public function findAvailableChambres(\DateTime $start, \DateTime $end): array {
        return $this->createQueryBuilder('c')
            ->leftJoin('c.reservations', 'r')
            ->where('r.dateDebut >= :end OR r.dateFin <= :start')
            ->setParameters(['end' => $end, 'start' => $start])
            ->getQuery()
            ->getResult();
    }
}

// Utilisation dans le service
$available = $this->chambreRepository->findAvailableChambres($start, $end);
```

### 3. Dependency Injection

Les dépendances sont injectées, não hardcodées:

```php
// ✓ BON: DI via constructeur
class ReservationService {
    public function __construct(
        private ReservationRepository $repository,
        private EntityManagerInterface $entityManager,
    ) { }
}

// ✗ MAUVAIS: Hardcoder les dépendances
class ReservationService {
    private $repository;

    public function __construct() {
        $this->repository = new ReservationRepository(); // MAUVAIS!
    }
}
```

### 4. Entity Inheritance

Utilise SINGLE_TABLE pour User:

```php
#[ORM\Entity]
#[ORM\InheritanceType('SINGLE_TABLE')]
#[ORM\DiscriminatorColumn(name: 'type', type: 'string')]
#[ORM\DiscriminatorMap(['user' => User::class, 'client' => Client::class, 'gestionnaire' => Gestionnaire::class])]
class User implements UserInterface {
    // Propriétés communes
}

class Client extends User {
    // Propriétés spécifiques à Client
}
```

---

## ✅ SOLID Principles

### Single Responsibility

Chaque classe a une seule raison de changer:

```php
// ✓ BON: Responsabilités séparées
class ReservationService { }          // Crée les réservations
class ReservationRepository { }       // Accède aux données
class ReservationController { }       // Gère les HTTP requests
```

### Open/Closed

Extensible sans modifier le code existant:

```php
// ✓ Utiliser des interfaces pour l'extensibilité
interface NotifierInterface {
    public function notify(string $message): void;
}

class EmailNotifier implements NotifierInterface { }
class SMSNotifier implements NotifierInterface { }
```

### Liskov Substitution

Les sous-classes doivent pouvoir remplacer les parents:

```php
// ✓ BON: Client peut remplacer User
class User implements UserInterface { }
class Client extends User { }

// Utilisation polymorphe
public function authenticate(User $user): void { }
authenticate(new Client()); // Fonctionne!
```

### Interface Segregation

Interfaces spécialisées, pas générales:

```php
// ✓ BON: Interfaces spécialisées
interface RepositoryInterface { }
interface NotifierInterface { }

// ✗ MAUVAIS: Grosse interface générale
interface ServiceInterface {
    public function create();
    public function notify();
    public function delete();
}
```

### Dependency Inversion

Dépendre des abstractions, pas des implémentations:

```php
// ✓ BON: Dépendre de l'interface
class ReservationService {
    public function __construct(
        private ReservationRepository $repository  // Interface/Base
    ) { }
}

// ✗ MAUVAIS: Hardcoder l'implémentation
class ReservationService {
    private $repository = new MySQLReservationRepository();
}
```

---

## 🔒 Validation et Sécurité

### Validation des Entités

```php
class Reservation {
    #[ORM\Column(length: 20)]
    #[Assert\NotBlank(message: 'Le numéro est requis')]
    #[Assert\Regex('/^RES-\d+/')]
    private string $numeroReservation;

    #[ORM\Column(type: 'datetime')]
    #[Assert\NotNull]
    #[Assert\GreaterThan(value: 'now')]
    private \DateTime $dateDebut;

    #[Assert\GreaterThan(propertyPath: 'dateDebut')]
    private \DateTime $dateFin;
}
```

### Authentification

```php
// ✓ Vérifier les rôles
#[IsGranted('ROLE_CLIENT')]
public function mesReservations(): Response { }

#[IsGranted('ROLE_ADMIN')]
public function adminDashboard(): Response { }

// ✓ Vérifier la propriété avant modification
public function detailReservation(Reservation $reservation): Response {
    if ($reservation->getClient() !== $this->getUser()) {
        throw $this->createAccessDeniedException();
    }
}
```

### Protection CSRF

```php
// Automatique avec Symfony FormType
class ReservationFormType extends AbstractType {
    public function buildForm(FormBuilderInterface $builder, array $options): void {
        $builder
            ->add('dateDebut', DateType::class)
            ->add('dateFin', DateType::class)
            // CSRF token inclus automatiquement
            ;
    }
}
```

---

## 🧪 Tests

### Tests Unitaires

```php
// ✓ Tester la logique métier isolée
class ReservationServiceTest extends TestCase {
    public function testCreateReservationWithConflict(): void {
        $service = new ReservationService($mockRepository);

        $this->expectException(\InvalidArgumentException::class);
        $service->createReservation($client, $chambre, $start, $end);
    }
}
```

### Tests Fonctionnels

```php
// ✓ Tester l'intégration HTTP
class SecurityControllerTest extends WebTestCase {
    public function testLoginPageIsAccessible(): void {
        $client = static::createClient();
        $client->request('GET', '/login');

        $this->assertResponseIsSuccessful();
        $this->assertSelectorExists('form');
    }
}
```

### Exécution des tests

```bash
# Tous les tests
php bin/phpunit

# Fichier spécifique
php bin/phpunit tests/Unit/Service/ReservationServiceTest.php

# Classe spécifique
php bin/phpunit tests/Unit/Service/ --filter ReservationServiceTest

# Avec couverture
php bin/phpunit --coverage-text
```

---

## 📚 Documentation

### PHPDoc pour les classes

```php
/**
 * ReservationService - Gère le cycle de vie des réservations
 *
 * Responsabilités:
 * - Créer de nouvelles réservations
 * - Vérifier la disponibilité
 * - Gérer les changements de statut
 *
 * @package App\Service
 */
class ReservationService {
}
```

### PHPDoc pour les méthodes

```php
/**
 * Crée une nouvelle réservation avec vérification de disponibilité
 *
 * @param Client $client Le client qui effectue la réservation
 * @param Chambre $chambre La chambre à réserver
 * @param \DateTime $dateDebut Date de début de séjour
 * @param \DateTime $dateFin Date de fin de séjour
 *
 * @return Reservation La réservation créée
 *
 * @throws \InvalidArgumentException Si la chambre n'est pas disponible
 * @throws \RuntimeException Si une erreur de base de données survient
 */
public function createReservation(
    Client $client,
    Chambre $chambre,
    \DateTime $dateDebut,
    \DateTime $dateFin
): Reservation {
}
```

### Commentaires inline

```php
// ✓ Expliquer le POURQUOI, pas le COMMENT
// Utiliser LEFT JOIN pour inclure les chambres sans réservations
$queryBuilder->leftJoin('c.reservations', 'r');

// ✗ MAUVAIS: Commentaire inutile
// Boucler sur les réservations
foreach ($reservations as $reservation) { }
```

---

## 🎨 Formatage du Code

### Indentation et espacage

```php
// ✓ 4 espaces d'indentation, une ligne blank entre les méthodes
class MyClass {
    private string $property;

    public function method1(): void {
        $var = 'value';
    }

    public function method2(): void {
        // ...
    }
}
```

### Longueur des lignes

```php
// ✓ Maximum 120 caractères
$result = $this->repository->findByComplexCondition(
    $param1,
    $param2,
    $param3
);

// ✗ MAUVAIS: Ligne trop longue
$result = $this->repository->findByComplexCondition($param1, $param2, $param3, $param4, $param5);
```

### Ordre des modifications

```php
// ✓ Ordre précommande: propriétés, getters, seters, logique métier
class User {
    private string $email;
    private string $password;

    // Getters
    public function getEmail(): string { }

    // Setters
    public function setEmail(string $email): self { }

    // Logique métier
    public function isAdmin(): bool { }
}
```

---

## 🚀 Checklist avant commit

- [ ] Code suit les conventions de nommage
- [ ] Pas de code dupliqué
- [ ] Services gèrent la logique métier
- [ ] Contrôleurs gèrent HTTP uniquement
- [ ] Entités ont les validations nécessaires
- [ ] Dépendances sont injectées
- [ ] Tests unitaires réussissent
- [ ] Pas de warnings PHP
- [ ] Code est documenté (PHPDoc)
- [ ] Pas de secrets en dur (mots de passe, etc.)

---

## 📖 Ressources

- **PSR-12**: https://www.php-fig.org/psr/psr-12/
- **Symfony Best Practices**: https://symfony.com/doc/current/best_practices.html
- **Doctrine Documentation**: https://www.doctrine-project.org/
- **Clean Code**: "Clean Code" par Robert C. Martin
