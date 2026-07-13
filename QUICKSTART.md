# 🏨 SleepAdvisor - Démarrage Rapide

## 🚀 Démarrage en 5 minutes

### Prérequis

- PHP 8.2+
- MySQL 5.7+ ou MariaDB 10.3+
- Composer

### Étape 1: Cloner et installer

```bash
cd votre-dossier-projet
composer install
```

### Étape 2: Configurer la base de données

```bash
# Copier le template
cp .env.example .env

# Éditer .env et configurer DATABASE_URL
# Exemple:
# DATABASE_URL="mysql://root:password@127.0.0.1:3306/hotelbooking"
```

### Étape 3: Créer la base de données

```bash
# Créer la base
php bin/console doctrine:database:create

# Créer les tables
php bin/console doctrine:migrations:migrate --no-interaction

# (OPTIONNEL) Charger les données de test
php bin/console doctrine:fixtures:load --no-interaction
```

### Étape 4: Lancer l'application

```bash
# Méthode 1: Avec Symfony CLI
symfony serve

# Méthode 2: PHP natif
php -S localhost:8000 -t public/

# Accès: http://localhost:8000
```

## 📋 Comptes de test (si fixtures chargées)


| Email                       | Mot de passe | Rôle   |
| --------------------------- | ------------ | ------ |
| gestionnaire@hotelparis.com | admin123     | Admin  |
| admin.marseille@hotel.fr    | 123admin     | Admin  |
| alice.martin@dawan.com      | alice26      | Client |
| bob.dylan@yahoo.com         | bob26bob     | Client |
| camille.dubois@gmail.com    | camille99    | Client |


## 🔍 Vérifier l'installation

### Windows (PowerShell)

```powershell
.\check-installation.ps1
```

### Linux/Mac (Bash)

```bash
bash check-installation.sh
```

## 📚 Documentation complète

- **README.md** - Guide complet du projet
- **ARCHITECTURE.md** - Architecture technique
- **INSTALLATION.md** - Instructions d'installation détaillées
- **RESUME.md** - Résumé des accomplissements

## 🛠 Commandes utiles

```bash
# Vider le cache
php bin/console cache:clear

# Réinitialiser la base (danger!)
php bin/console doctrine:database:drop --force
php bin/console doctrine:database:create
php bin/console doctrine:migrations:migrate --no-interaction

# Exécuter les tests
php bin/phpunit

# Afficher les routes
php bin/console debug:router

# Vérifier les erreurs
php bin/console lint:yaml config/
php bin/console lint:twig templates/
```

## ❓ Dépannage

### "Command not found"

- Vérifiez que PHP et Composer sont dans le PATH
- Sur Windows, utilisez PowerShell en tant qu'administrateur

### "SQLSTATE[HY000]: General error: 2006"

- La base de données n'existe pas ou est déconnectée
- Exécutez `php bin/console doctrine:database:create`

### "Class not found"

- Exécutez `composer install`
- Exécutez `composer dump-autoload`

### Les migrations ne s'appliquent pas

```bash
# Vérifiez le statut
php bin/console doctrine:migrations:status

# Appliquer toutes les migrations
php bin/console doctrine:migrations:migrate --no-interaction
```

## 📞 Support

Pour plus d'aide, consultez:

- La documentation Symfony: https://symfony.com/doc/current/
- La documentation Doctrine: https://www.doctrine-project.org/
- Les comments dans le code (PHPDoc complet)

---
