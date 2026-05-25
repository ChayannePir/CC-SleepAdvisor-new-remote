# ✅ CHECKLIST - Validation du Tunnel de Réservation

## 📋 Avant de commencer

- [ ] Vous avez exécuté `composer install`
- [ ] La base de données est créée et migrée
- [ ] Vous avez des données fixtures (hôtels, chambres)
- [ ] Vous êtes sur Symfony 7.4+

---

## 🔧 Étape 1: Vérifier les Fichiers

### Services
- [ ] ✅ `src/Service/BasketReservationService.php` existe
- [ ] ✅ `src/Service/ReservationService.php` est amélioré (findConfirmedConflictingReservations)

### Repositories
- [ ] ✅ `src/Repository/ReservationRepository.php` contient `findConfirmedConflictingReservations()`
- [ ] ✅ `src/Repository/ChambreRepository.php` LEFT JOIN optimisé sur "Confirmée"

### Controllers
- [ ] ✅ `src/Controller/Public/SearchController.php` a les 4 routes:
  - `GET|POST /recherche` → search()
  - `GET /recherche/chambre/{id}` → detail()
  - `GET|POST /recherche/chambre/{id}/reserver` → reserver()
  - `GET /recherche/panier` → basket()
  - `POST /recherche/panier/retirer/{chambreKey}` → removeFromBasket()
  - `GET|POST /recherche/panier/checkout` → checkout()

### Templates
- [ ] ✅ `templates/public/basket.html.twig` existe
- [ ] ✅ `templates/public/checkout.html.twig` existe
- [ ] ✅ `templates/public/home.html.twig` a formulaire POST

---

## 🚀 Étape 2: Tester le Flux

### Test 1: Accueil
```
1. Ouvrir http://localhost:8000
2. Voir le formulaire de recherche rapide
3. Saisir dates (ex: 2026-06-01 → 2026-06-05)
4. Cliquer "Rechercher"
5. ✓ Redirigé à /recherche avec résultats
```

### Test 2: Recherche de Chambres
```
1. Voir la liste des chambres disponibles
2. Si 0 chambre:
   - Vérifier les dates ne chevauchent pas de réservation CONFIRMÉE
   - Vérifier réservations existantes status
3. ✓ Affichage correct des chambres libres
```

### Test 3: Réserver une Chambre
```
1. Cliquer "Réserver" sur une chambre
2. Redirigé au formulaire de réservation
3. Saisir dates (obligatoire)
4. Cliquer "Ajouter au panier"
5. ✓ Message "Chambre ajoutée"
6. ✓ Redirigé à /recherche/panier
```

### Test 4: Visualiser le Panier
```
1. Voir la chambre ajoutée
2. Voir le résumé (nombre, hôtel, dates)
3. Bouton "Continuer" visible
4. Bouton "Ajouter une chambre" pour revenir chercher
5. ✓ Layout OK
```

### Test 5: Ajouter Une 2e Chambre (Optionnel)
```
1. Cliquer "Ajouter une chambre"
2. Rechercher une autre chambre
3. Réserver
4. Retour au panier → 2 chambres visibles
5. ✓ Multi-chambre OK
```

### Test 6: Checkout - Validation Données
```
1. Cliquer "Continuer" depuis panier
2. Voir formulaire avec Email + Téléphone pré-remplis
3. Vérifier les champs ne sont pas vides
4. Cliquer "Confirmer la réservation"
5. ✓ Message "Réservations créées avec succès"
6. ✓ Redirigé à /client/reservations
7. ✓ Nouvelles réservations visibles dans la liste
```

### Test 7: Vérifier Statut des Réservations
```
1. Ouvrir mes réservations
2. Cliquer sur une réservation
3. Voir statut = "En attente"
4. Voir les chambres sélectionnées
5. ✓ Toutes les infos correctes
```

---

## 🧪 Étape 3: Tests Avancés

### Test A: Conflits de Désaxilingilité
```
Scénario:
1. Admin crée réservation CONFIRMÉE pour Chambre X, dates J1→J5
2. Client cherche pour J2→J4
   → Chambre X ne doit PAS aparaître
3. Client cherche pour J5→J8
   → Chambre X DOIT aparaître (pas de chevauchement)

✓ Vérifier la logique de chevauchement correcte
```

### Test B: Données Client Modifiées
```
Scénario:
1. Client avec Email=client@ex.com
2. En checkout, change Email=nouveau@ex.com
3. Saisir Téléphone valide
4. Confirmer réservation
5. Vérifier dans BD: Client.email = "nouveau@ex.com"

✓ Update client en BD correct
```

### Test C: Panier Vide
```
Scénario:
1. Accéder directement à /recherche/panier/checkout
   (sans rien dans le panier)
2. Voir message d'erreur
3. Redirection automatique

✓ Gestion panier vide OK
```

### Test D: Multi-Hôtels
```
Scénario:
1. Ajouter Chambre du Hôtel A (dates J1→J5)
2. Ajouter Chambre du Hôtel B (dates J1→J5)
3. Panier: 2 chambres, 2 hôtels
4. Checkout + Confirmer
5. Vérifier: 2 réservations créées (1 par hôtel)

✓ Groupage par hôtel correct
```

---

## 🔍 Étape 4: Vérifier la BD

### Requête SQL pour Valider
```sql
-- Voir les nouvelles réservations
SELECT r.id, r.numeroReservation, r.statut, 
       c.nom, h.nom, r.dateDebut, r.dateFin
FROM reservation r
JOIN client c ON r.client_id = c.id
JOIN hotel h ON r.hotel_id = h.id
WHERE r.statut = 'En attente'
ORDER BY r.createdAt DESC;

-- Voir les chambres associées
SELECT rc.*, ch.type, ch.etage
FROM reservation_chambre rc
JOIN chambre ch ON rc.chambre_id = ch.id
WHERE rc.reservation_id = ?;
```

---

## 🐛 Debugging

### Si erreur "Panier vide"
```
1. Vérifier session.ini_set('session.use_cookies', true)
2. Vérifier config/packages/framework.yaml → session activée
3. Vérifier BasketReservationService injécté correctement
```

### Si chambres introuvables
```
1. Vérifier chambre existe en BD
2. Vérifier hotel_id sur chambre est correct
3. Vérifier requête LEFT JOIN LogicException
   → SELECT * FROM chambre c LEFT JOIN ...
```

### Si réservation ne se crée pas
```
1. Vérifier ReservationService::createReservation() validations
2. Vérifier pas de contrainte UNIQUE violation (numeroReservation)
3. Vérifier FormValidation sur Reservation entity
4. Voir logs: tail var/log/dev.log
```

---

## ✅ Validation Finale

Une fois tous les tests passés:

- [ ] Tunnel de recherche: ✓ OK
- [ ] Ajout au panier: ✓ OK
- [ ] Visualisation panier: ✓ OK
- [ ] Checkout avec données: ✓ OK
- [ ] Création réservations: ✓ OK
- [ ] Statut "En attente": ✓ OK
- [ ] Multi-chambres: ✓ OK
- [ ] Multi-hôtels: ✓ OK
- [ ] Disponibilités correctes: ✓ OK
- [ ] Données client sauvgardées: ✓ OK

**🎉 Phase Critique Complètement Fonctionnelle!**

---

## 📞 Support Technique

Si vous avez des problèmes, consultez:
1. `TUNNEL-RESERVATION-COMPLET.md` pour les détails techniques
2. `var/log/dev.log` pour les erreurs
3. `php bin/console debug:router` pour vérifier les routes

---

**Bonne chance avec le test! 🚀**

