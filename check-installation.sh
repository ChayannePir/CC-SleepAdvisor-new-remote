#!/bin/bash
# Script de vérification de l'installation SleepAdvisor
# Usage: bash check-installation.sh (ou powershell sur Windows)

echo "================================"
echo "SleepAdvisor Installation Check"
echo "================================"
echo ""

# Couleurs pour le terminal
GREEN='\033[0;32m'
RED='\033[0;31m'
YELLOW='\033[1;33m'
NC='\033[0m' # No Color

# Compteur
CHECKS_PASSED=0
CHECKS_FAILED=0

# Fonction pour vérifier
check() {
    if [ $? -eq 0 ]; then
        echo -e "${GREEN}✓${NC} $1"
        ((CHECKS_PASSED++))
    else
        echo -e "${RED}✗${NC} $1"
        ((CHECKS_FAILED++))
    fi
}

# Test PHP
echo "1. Vérification de PHP..."
php --version > /dev/null 2>&1
check "PHP installé"

# Test Composer
echo ""
echo "2. Vérification de Composer..."
composer --version > /dev/null 2>&1
check "Composer installé"

# Test de la structure du projet
echo ""
echo "3. Vérification de la structure..."
[ -d "src" ] && check "Dossier src existe" || echo -e "${RED}✗${NC} Dossier src existe"
[ -d "templates" ] && check "Dossier templates existe" || echo -e "${RED}✗${NC} Dossier templates existe"
[ -d "public" ] && check "Dossier public existe" || echo -e "${RED}✗${NC} Dossier public existe"
[ -f "composer.json" ] && check "composer.json existe" || echo -e "${RED}✗${NC} composer.json existe"

# Test des fichiers clés
echo ""
echo "4. Vérification des fichiers clés..."
[ -f "src/Entity/User.php" ] && check "Entité User existe" || echo -e "${RED}✗${NC} Entité User existe"
[ -f "src/Entity/Client.php" ] && check "Entité Client existe" || echo -e "${RED}✗${NC} Entité Client existe"
[ -f "src/Entity/Reservation.php" ] && check "Entité Reservation existe" || echo -e "${RED}✗${NC} Entité Reservation existe"
[ -f "src/Controller/SecurityController.php" ] && check "SecurityController existe" || echo -e "${RED}✗${NC} SecurityController existe"
[ -f "templates/base_new.html.twig" ] && check "Template de base existe" || echo -e "${RED}✗${NC} Template de base existe"

# Test des répertoires
echo ""
echo "5. Vérification des répertoires importants..."
[ -d "src/Service" ] && check "Dossier Service existe" || echo -e "${RED}✗${NC} Dossier Service existe"
[ -d "src/Repository" ] && check "Dossier Repository existe" || echo -e "${RED}✗${NC} Dossier Repository existe"
[ -d "tests" ] && check "Dossier tests existe" || echo -e "${RED}✗${NC} Dossier tests existe"

# Test de dépendances Composer
echo ""
echo "6. Vérification des dépendances..."
[ -d "vendor" ] && check "Dépendances installées" || echo -e "${RED}✗${NC} Dépendances installées"

# Test du fichier .env
echo ""
echo "7. Vérification de la configuration..."
[ -f ".env" ] && check "Fichier .env existe" || echo -e "${YELLOW}⚠${NC} Fichier .env N'EXISTE PAS - À créer!"

# Résumé
echo ""
echo "================================"
echo "Résumé:"
echo -e "  ${GREEN}✓ Réussites: $CHECKS_PASSED${NC}"
if [ $CHECKS_FAILED -gt 0 ]; then
    echo -e "  ${RED}✗ Problèmes: $CHECKS_FAILED${NC}"
else
    echo -e "  ${GREEN}✓ Aucun problème détecté!${NC}"
fi
echo "================================"

# Instructions de suivi
echo ""
echo "Prochaines étapes:"
echo "1. Créer le fichier .env (copier .env.example)"
echo "2. Configurer DATABASE_URL dans .env"
echo "3. Exécuter: php bin/console doctrine:database:create"
echo "4. Exécuter: php bin/console doctrine:migrations:migrate"
echo "5. Lancer: symfony serve"
echo ""
echo "Pour l'aide complète, consulter INSTALLATION.md"
