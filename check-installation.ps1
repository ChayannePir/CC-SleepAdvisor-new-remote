# Script de vérification pour Windows PowerShell
# Usage: .\check-installation.ps1

Write-Host "================================"
Write-Host "SleepAdvisor Installation Check"
Write-Host "================================"
Write-Host ""

$checksPass = 0
$checksFail = 0

function Check-Condition {
    param(
        [bool]$condition,
        [string]$message
    )
    
    if ($condition) {
        Write-Host "✓ $message" -ForegroundColor Green
        $global:checksPass++
    } else {
        Write-Host "✗ $message" -ForegroundColor Red
        $global:checksFail++
    }
}

# Test PHP
Write-Host "1. Vérification de PHP..."
$phpExists = $null -ne (Get-Command php -ErrorAction SilentlyContinue)
Check-Condition $phpExists "PHP installé"

# Test Composer
Write-Host ""
Write-Host "2. Vérification de Composer..."
$composerExists = $null -ne (Get-Command composer -ErrorAction SilentlyContinue)
Check-Condition $composerExists "Composer installé"

# Test de la structure
Write-Host ""
Write-Host "3. Vérification de la structure..."
Check-Condition (Test-Path "src") "Dossier src existe"
Check-Condition (Test-Path "templates") "Dossier templates existe"
Check-Condition (Test-Path "public") "Dossier public existe"
Check-Condition (Test-Path "composer.json") "composer.json existe"

# Test des fichiers clés
Write-Host ""
Write-Host "4. Vérification des fichiers clés..."
Check-Condition (Test-Path "src\Entity\User.php") "Entité User existe"
Check-Condition (Test-Path "src\Entity\Client.php") "Entité Client existe"
Check-Condition (Test-Path "src\Entity\Reservation.php") "Entité Reservation existe"
Check-Condition (Test-Path "src\Controller\SecurityController.php") "SecurityController existe"
Check-Condition (Test-Path "templates\base_new.html.twig") "Template de base existe"

# Test des répertoires
Write-Host ""
Write-Host "5. Vérification des répertoires..."
Check-Condition (Test-Path "src\Service") "Dossier Service existe"
Check-Condition (Test-Path "src\Repository") "Dossier Repository existe"
Check-Condition (Test-Path "tests") "Dossier tests existe"

# Test des dépendances
Write-Host ""
Write-Host "6. Vérification des dépendances..."
Check-Condition (Test-Path "vendor") "Dépendances installées"

# Test de .env
Write-Host ""
Write-Host "7. Vérification de la configuration..."
if (Test-Path ".env") {
    Check-Condition $true "Fichier .env existe"
} else {
    Write-Host "⚠ Fichier .env N'EXISTE PAS - À créer!" -ForegroundColor Yellow
    $global:checksFail++
}

# Résumé
Write-Host ""
Write-Host "================================"
Write-Host "Résumé:"
Write-Host "  ✓ Réussites: $checksPass" -ForegroundColor Green
if ($checksFail -gt 0) {
    Write-Host "  ✗ Problèmes: $checksFail" -ForegroundColor Red
} else {
    Write-Host "  ✓ Aucun problème détecté!" -ForegroundColor Green
}
Write-Host "================================"

# Instructions
Write-Host ""
Write-Host "Prochaines étapes:"
Write-Host "1. Créer le fichier .env (copier .env.example)"
Write-Host "2. Configurer DATABASE_URL dans .env"
Write-Host "3. Exécuter: php bin/console doctrine:database:create"
Write-Host "4. Exécuter: php bin/console doctrine:migrations:migrate"
Write-Host "5. Lancer: symfony serve"
Write-Host ""
Write-Host "Pour l'aide complète, consulter INSTALLATION.md"
