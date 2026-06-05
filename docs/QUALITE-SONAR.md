# Qualité & SonarQube

## Analyse locale

```bash
# Tests + couverture (optionnel pour Sonar)
php bin/phpunit --coverage-clover coverage.xml

# Scanner SonarQube (CLI installé)
sonar-scanner
```

## Code smells traités dans cette phase

| Smell | Action |
|-------|--------|
| TODO mot de passe oublié | Implémentation complète `PasswordResetService` |
| `InvalidArgumentException` générique réservation | Exceptions métier `ReservationException` |
| Pagination affichant toutes les pages | `PaginationHelper::pageRange()` |
| Messages d'erreur techniques exposés | `getUserMessage()` + catch ciblés |
| PHPDoc manquant | Docblocks sur services, exceptions, entités clés |

## PHPDoc

Chaque classe sous `src/Exception/`, `src/Service/PasswordResetService.php`, `src/Service/ReservationService.php` et les repositories admin disposent d'une description de responsabilité.
