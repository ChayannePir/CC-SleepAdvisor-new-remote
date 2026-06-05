# 🚀 Guide d'Installation - SleepAdvisor

## Prérequis Système

- **PHP**: 8.2 ou supérieur
- **Composer**: Installation du gestionnaire de paquets PHP
- **MySQL/MariaDB**: Serveur de base de données (5.7+)
- **Symfony CLI**: (optionnel) Pour le serveur de développement

## ✅ Étapes d'Installation

### 1. Configuration de l'Environnement

#### Windows (PowerShell)

```powershell
# Accéder au répertoire du projet
cd "Documents\PHP-Symfony"

# Vérifier les versions
php --version
composer --version
```

#### Linux/MacOS (bash)

```bash
cd ~/Documents/PHP-Symfony

php --version
composer --version
```

### 2. Installation des Dépendances

```bash
composer install
```

**Temps estimé**: 2-3 minutes

### 3. Configuration de la Base de Données

#### Créer le fichier `.env`

Copier `.env.example` en `.env`:

```bash
# Windows (PowerShell)
Copy-Item ".env.example" ".env"

# Linux/MacOS
cp .env.example .env
```

#### Modifier `.env` pour votre base de données

```ini
# Dans .env, chercher la ligne DATABASE_URL et la modifier:

# Pour MySQL avec l'utilisateur root (sans mot de passe)
DATABASE_URL="mysql://root:@localhost:3306/hotelbooking"

# Pour MySQL avec un utilisateur et mot de passe
DATABASE_URL="mysql://utilisateur:motdepasse@localhost:3306/hotelbooking"

# Pour MariaDB
DATABASE_URL="mysql://root:@127.0.0.1:3306/hotelbooking"
```

### 4. Créer la Base de Données

```bash
php bin/console doctrine:database:create
```

**Résultat attendu**:

```
Created database `hotelbooking` for connection named default
```

### 5. Générer les Migrations (Important!)

```bash
# Créer les fichiers de migration basés sur les entités
php bin/console doctrine:migrations:migrate --no-interaction
```

**Ou si vous devez d'abord générer les fichiers de migration:**

```bash
php bin/console doctrine:migrations:generate
php bin/console doctrine:migrations:migrate --no-interaction
```

### 6. Charger les Données de Test (Optionnel)

Vous pouvez charger des données initiales:

```bash
# Si vous avez créé des fixtures
php bin/console doctrine:fixtures:load --no-interaction
```

### 7. Vérifier l'Installation

```bash
# Vérifier que la base de données est peuplée
php bin/console doctrine:query:dql "SELECT COUNT(u) FROM App\Entity\User u"
```

## 🏃 Lancer l'Application

### Option 1: Avec Symfony CLI (Recommandé)

```bash
# Installer Symfony CLI si ce n'est pas fait
curl -sS https://get.symfony.com/cli/installer | bash

# Lancer l'application
symfony serve
```

Accédez à `https://localhost:8000`

### Option 2: Avec PHP Builtin Server

```bash
php -S localhost:8000 -t public/
```

Accédez à `http://localhost:8000`

### Option 3: Avec Docker (Optionnel)

Si vous utilisez Docker Compose:

```bash
cd ..
docker-compose up -d
```

## 👤 Créer des Utilisateurs de Test

### Créer un client via console

```bash
php bin/console
```

Puis dans la console interactive:

```php
// Créer un client
$client = new App\Entity\Client();
$client->setEmail('client1@example.com');
$client->setPassword($passwordHasher->hashPassword($client, 'client123'));
$client->setTelephone('06 01 02 03 04');
$client->setNom('Alice Martin');
$client->setAdresse('123 Rue de Paris');

$entityManager->persist($client);
$entityManager->flush();

// Quitter
exit
```

### Ou via command personnalisée

À créer dans `src/Command/CreateUserCommand.php`:

```bash
php bin/console app:create-user
```

## ⚙️ Configuration Additionnelle

### Permissions des Répertoires

Si vous avez des erreurs de permissions:

```bash
# Windows (pas nécessaire généralement)

# Linux/MacOS
chmod -R 755 var/
chmod -R 755 public/
```

### Variable d'Environnement

```bash
# Pour le développement
APP_ENV=dev
APP_DEBUG=true

# Pour la production
APP_ENV=prod
APP_DEBUG=false
```

## 🔍 Vérifier l'Installation

### 1. Vérifier la base de données

```bash
# Afficher les tables créées
php bin/console doctrine:database:create --if-not-exists
php bin/console doctrine:migrations:status
```

### 2. Tester l'accès Web

Ouvrir navigateur:

- http://localhost:8000/
- http://localhost:8000/login (doit afficher le formulaire de connexion)

### 3. Consulter les logs

```bash
# Afficher les logs récents
tail -f var/log/dev.log
```

## 🐛 Dépannage

### Erreur: "Cannot find MySQL server"

```
Solution: Vérifier que MySQL/MariaDB est démarré
Windows: Services → MySQL → Démarrer
Linux: sudo systemctl start mysql
MacOS: brew services start mysql
```

### Erreur: "Access denied for user"

```
Solution: Vérifier DATABASE_URL dans .env
- Vérifier le nom d'utilisateur
- Vérifier le mot de passe
- Vérifier le port (3306 par défaut)
```

### Erreur: "Table doesn't exist"

```
Solution: Exécuter les migrations
php bin/console doctrine:migrations:migrate --force
```

### Erreur: "Class not found"

```
Solution: Regénérer l'autoloader
composer dump-autoload
```

### Application lente

```
Solution: Vider le cache
php bin/console cache:clear
```

## 📊 Commandes Utiles

### Base de Données

```bash
# Créer la BD
php bin/console doctrine:database:create

# Vider la BD
php bin/console doctrine:database:drop --force

# Afficher les migrations
php bin/console doctrine:migrations:list

# Exécuter une migration spécifique
php bin/console doctrine:migrations:execute Version202404010900

# Afficher le schéma de la BD
php bin/console doctrine:schema:update --dump-sql
```

### Cache et Compilation

```bash
# Vider tous les caches
php bin/console cache:clear

# Vider le cache de prod
php bin/console cache:clear --env=prod

# Réchauffer le cache
php bin/console cache:warmup
```

### Routing

```bash
# Afficher toutes les routes
php bin/console debug:router

# Tester une route
php bin/console router:match /admin
```

### Tests

```bash
# Exécuter tous les tests
php bin/phpunit

# Exécuter un test spécifique
php bin/phpunit tests/Unit/Entity/ClientTest.php

# Afficher la couverture de code
php bin/phpunit --coverage-html coverage/
```

## 🔐 Sécurité - Configuration Initiale

### Changer la clé secrète

```bash
# Générer une nouvelle clé
php bin/console random:int

# Dans .env
APP_SECRET=<valeur-générée>
```

### Mettre à jour les permissions

```bash
# Définir un utilisateur admin
php bin/console security:encode-password
```

## 📝 Première Utilisation

1. **Accueil**: http://localhost:8000/
2. **Inscription**: Cliquer sur "Inscription"
3. **Créer un compte**: Remplir le formulaire
4. **Rechercher une chambre**: Utiliser "Rechercher"
5. **Réserver**: Sélectionner une chambre et les dates
6. **Voir réservations**: Menu "Mes réservations"

## 🎓 Ressources Utiles

- [Documentation Symfony](https://symfony.com/doc/current/index.html)
- [Doctrine ORM](https://www.doctrine-project.org/)
- [Bootstrap 5](https://getbootstrap.com/)
- [Twig Templates](https://twig.symfony.com/)

## 📞 Support et Aide

En cas de problème:

1. Consulter les logs: `var/log/dev.log`
2. Vérifier `.env` configuration
3. Regénérer les migrations
4. Contrôler les permissions des fichiers
5. Vérifier la connexion base de données

---

**Installation réussie!** 🎉

Vous pouvez maintenant accéder à l'application et commencer à l'utiliser.
