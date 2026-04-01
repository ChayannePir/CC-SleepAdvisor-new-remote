# API Documentation - SleepAdvisor

## 📍 Endpoints publics

### Accueil

```
GET /
Description: Page d'accueil du site
Authentification: Non requise
Response: HTML
```

### Recherche de chambres

```
GET /search
Description: Recherche de chambres disponibles
Authentification: Non requise
Paramètres:
  - date_debut: Date de début (YYYY-MM-DD)
  - date_fin: Date de fin (YYYY-MM-DD)
  - hotel: ID de l'hôtel (optionnel)
Response: HTML avec résultats
```

### Détail d'une chambre

```
GET /chambre/{id}
Description: Affiche les détails d'une chambre
Authentification: Non requise
Response: HTML
```

### Reserver une chambre

```
POST /reserver/{hotelId}/{chambreId}
Description: Crée une nouvelle réservation
Authentification: Requise (ROLE_CLIENT)
Body: JSON
{
  "date_debut": "2024-01-15",
  "date_fin": "2024-01-20"
}
Response: Redirect vers détail réservation
```

---

## 🔐 Endpoints d'authentification

### Se connecter

```
GET /login
Description: Formulaire de connexion
Authentification: Non requise
Response: HTML
```

### Traiter la connexion

```
POST /login
Description: Soumet le formulaire de connexion
Authentification: Non requise
Body: Form data
  - email: Adresse email
  - password: Mot de passe
  - _remember_me: Mémoriser (optionnel)
Response: Redirect vers dashboard ou page précédente
```

### S'enregistrer

```
GET /register
Description: Formulaire d'enregistrement
Authentification: Non requise
Response: HTML
```

### Traiter l'enregistrement

```
POST /register
Description: Crée un nouveau compte client
Authentification: Non requise
Body: JSON
{
  "email": "client@example.com",
  "nom": "Jean Dupont",
  "adresse": "123 rue de la Paix",
  "telephone": "0123456789",
  "password": {
    "first": "securepass123",
    "second": "securepass123"
  }
}
Response: Redirect vers login
```

### Se déconnecter

```
GET /logout
Description: Déconnecte l'utilisateur
Authentification: Requise
Response: Redirect vers home
```

### Mot de passe oublié

```
GET /forgot-password
Description: Formulaire de récupération de mot de passe
Authentification: Non requise
Response: HTML
```

---

## 👤 Endpoints Client

### Mes réservations

```
GET /client/reservations
Description: Liste toutes les réservations du client
Authentification: Requise (ROLE_CLIENT)
Response: HTML avec pagination
```

### Détail d'une réservation

```
GET /client/reservation/{id}
Description: Affiche les détails d'une réservation
Authentification: Requise (ROLE_CLIENT)
Response: HTML
```

### Ajouter un commentaire

```
POST /client/reservation/{id}/commentaire
Description: Ajoute un commentaire à une réservation
Authentification: Requise (ROLE_CLIENT)
Body: Form data
  - contenu: Texte du commentaire
  - type: Type (Demande spéciale/Réclamation/Remarque)
Response: Redirect vers détail réservation
```

### Annuler une réservation

```
POST /client/reservation/{id}/cancel
Description: Annule une réservation
Authentification: Requise (ROLE_CLIENT)
Response: Redirect vers mes réservations
```

---

## 🛠 Endpoints Admin

### Dashboard

```
GET /admin/dashboard
Description: Page d'accueil admin avec statistiques
Authentification: Requise (ROLE_ADMIN)
Response: HTML
```

### Gestion des chambres

```
GET /admin/chambres
Description: Liste toutes les chambres avec pagination
Authentification: Requise (ROLE_ADMIN)
Paramètres:
  - page: Numéro de page (optionnel)
  - type: Filtrer par type (optionnel)
Response: HTML
```

### Créer une chambre

```
GET /admin/chambre/new
Description: Formulaire de création de chambre
Authentification: Requise (ROLE_ADMIN)
Response: HTML

POST /admin/chambre/new
Description: Crée une nouvelle chambre
Authentification: Requise (ROLE_ADMIN)
Body: Form data
  - type: Type de chambre
  - etage: Étage
  - nombreLits: Nombre de lits
  - hotel: ID de l'hôtel
Response: Redirect vers liste chambres
```

### Éditer une chambre

```
GET /admin/chambre/{id}/edit
Description: Formulaire d'édition de chambre
Authentification: Requise (ROLE_ADMIN)
Response: HTML

POST /admin/chambre/{id}/edit
Description: Modifie une chambre existante
Authentification: Requise (ROLE_ADMIN)
Body: Form data (même que créer)
Response: Redirect vers liste chambres
```

### Supprimer une chambre

```
POST /admin/chambre/{id}/delete
Description: Supprime une chambre
Authentification: Requise (ROLE_ADMIN)
Response: Redirect vers liste chambres
```

### Gestion des réservations

```
GET /admin/reservations
Description: Liste toutes les réservations avec recherche
Authentification: Requise (ROLE_ADMIN)
Paramètres:
  - search: Numéro de réservation (optionnel)
  - page: Numéro de page (optionnel)
Response: HTML
```

### Détail d'une réservation

```
GET /admin/reservation/{id}
Description: Affiche les détails d'une réservation
Authentification: Requise (ROLE_ADMIN)
Response: HTML
```

### Confirmer une réservation

```
POST /admin/reservation/{id}/confirm
Description: Change le statut à "Confirmée"
Authentification: Requise (ROLE_ADMIN)
Response: Redirect vers détail
```

### Annuler une réservation

```
POST /admin/reservation/{id}/cancel
Description: Change le statut à "Annulée"
Authentification: Requise (ROLE_ADMIN)
Response: Redirect vers détail
```

### Gestion des clients

```
GET /admin/clients
Description: Liste tous les clients avec pagination
Authentification: Requise (ROLE_ADMIN)
Paramètres:
  - page: Numéro de page (optionnel)
  - search: Chercher par nom/email (optionnel)
Response: HTML
```

### Détail d'un client

```
GET /admin/client/{id}
Description: Affiche les détails d'un client
Authentification: Requise (ROLE_ADMIN)
Response: HTML
```

### Supprimer un client

```
POST /admin/client/{id}/delete
Description: Supprime un client et ses réservations
Authentification: Requise (ROLE_ADMIN)
Response: Redirect vers liste clients
```

---

## 📊 Codes HTTP

| Code | Signification                     |
| ---- | --------------------------------- |
| 200  | OK - Requête réussie              |
| 201  | Created - Ressource créée         |
| 302  | Redirect - Redirection            |
| 400  | Bad Request - Mauvaise requête    |
| 401  | Unauthorized - Non authentifié    |
| 403  | Forbidden - Non autorisé          |
| 404  | Not Found - Ressource non trouvée |
| 500  | Server Error - Erreur serveur     |

---

## 🔒 Authentification

Toutes les requêtes authentifiées sont en session HTTP.

### Flux d'authentification

1. **GET /login** → Obtenir le formulaire
2. **POST /login** → Soumettre email + password
3. **Réponse**: Cookie de session établi
4. Requêtes ultérieures avec le cookie pour maintenir la session

### Roles

- `ROLE_CLIENT`: Client normal (peut voir ses réservations)
- `ROLE_ADMIN`: Gestionnaire d'hôtel (accès admin complet)

```php
// Exemple de vérification de rôle dans le code
#[IsGranted('ROLE_CLIENT')]
public function mesReservations(): Response
{
    // Votre code ici
}
```

---

## 📝 Exemples cURL

### Rechercher des chambres

```bash
curl "http://localhost:8000/search?date_debut=2024-01-15&date_fin=2024-01-20"
```

### Se connecter

```bash
curl -X POST http://localhost:8000/login \
  -H "Content-Type: application/x-www-form-urlencoded" \
  -d "email=client@example.com&password=mypassword" \
  -c cookies.txt
```

### Accéder à une page protégée

```bash
curl -X GET http://localhost:8000/client/reservations \
  -b cookies.txt
```

### S'enregistrer

```bash
curl -X POST http://localhost:8000/register \
  -H "Content-Type: application/x-www-form-urlencoded" \
  -d "email=newclient@example.com&nom=Jean%20Dupont&adresse=123%20rue&telephone=0123456789&password[first]=secure123&password[second]=secure123"
```

---

## ✅ Tests

```bash
# Exécuter tous les tests
php bin/phpunit

# Exécuter les tests de sécurité
php bin/phpunit tests/Functional/SecurityControllerTest.php

# Exécuter les tests unitaires
php bin/phpunit tests/Unit/
```

---

## 📚 Ressources supplémentaires

- **Symfony Routing**: https://symfony.com/doc/current/routing.html
- **Symfony Security**: https://symfony.com/doc/current/security.html
- **Doctrine ORM**: https://www.doctrine-project.org/
- **Twig Templating**: https://twig.symfony.com/
