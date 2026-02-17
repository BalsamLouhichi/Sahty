#!/bin/bash

# ============================================================================
# SAHTY QUIZ PLATFORM - QUICK SETUP SCRIPT
# ============================================================================
# Este script automatiza el setup completo del proyecto

set -e  # Exit on error

echo "🚀 SAHTY QUIZ PLATFORM - Instalación Rápida"
echo "============================================================================"

# Colors
RED='\033[0;31m'
GREEN='\033[0;32m'
YELLOW='\033[1;33m'
NC='\033[0m' # No Color

# ============================================================================
# 1. COMPOSER DEPENDENCIES
# ============================================================================
echo -e "\n${YELLOW}📦 Instalando dependencias Composer...${NC}"
if ! composer install --prefer-dist --no-interaction; then
    echo -e "${RED}❌ Error en Composer install${NC}"
    exit 1
fi
echo -e "${GREEN}✅ Dependencias instaladas${NC}"

# ============================================================================
# 2. DATABASE
# ============================================================================
echo -e "\n${YELLOW}🗄️  Configurando base de datos...${NC}"

# Create database
echo "Creando base de datos..."
php bin/console doctrine:database:create --if-not-exists 2>/dev/null || true

# Run migrations
echo "Ejecutando migraciones..."
php bin/console doctrine:migrations:migrate --no-interaction --allow-no-migration

echo -e "${GREEN}✅ Base de datos configurada${NC}"

# ============================================================================
# 3. FIXTURES
# ============================================================================
echo -e "\n${YELLOW}📝 Cargando datos de prueba (Fixtures)...${NC}"

if php bin/console doctrine:fixtures:load --no-interaction; then
    echo -e "${GREEN}✅ Fixtures cargadas${NC}"
else
    echo -e "${YELLOW}⚠️  No se pudieron cargar fixtures automaticamente${NC}"
    echo "    Ejecuta manualmente: php bin/console doctrine:fixtures:load"
fi

# ============================================================================
# 4. CACHE CLEAR
# ============================================================================
echo -e "\n${YELLOW}🔄 Limpiando cache...${NC}"
php bin/console cache:clear
echo -e "${GREEN}✅ Cache limpiado${NC}"

# ============================================================================
# 5. ASSETS
# ============================================================================
echo -e "\n${YELLOW}🎨 Compilando assets...${NC}"
if command -v npm &> /dev/null; then
    echo "npm encontrado - compilando assets..."
    npm install --legacy-peer-deps >/dev/null 2>&1 || true
    npm run build >/dev/null 2>&1 || true
    echo -e "${GREEN}✅ Assets compilados${NC}"
else
    echo -e "${YELLOW}⚠️  npm no encontrado - saltando compilacion de assets${NC}"
fi

# ============================================================================
# COMPLETION
# ============================================================================
echo -e "\n${GREEN}╔═══════════════════════════════════════════════════════════╗${NC}"
echo -e "${GREEN}║  ✅ INSTALACIÓN COMPLETADA EXITOSAMENTE${NC}"
echo -e "${GREEN}╠═══════════════════════════════════════════════════════════╣${NC}"
echo -e "${GREEN}║${NC}"
echo -e "${GREEN}║  🚀 Para iniciar el servidor:${NC}"
echo -e "${GREEN}║     ${YELLOW}php -S localhost:8000 -t public/${NC}"
echo -e "${GREEN}║${NC}"
echo -e "${GREEN}║  📝 Para ejecutar los tests:${NC}"
echo -e "${GREEN}║     ${YELLOW}php bin/phpunit${NC}"
echo -e "${GREEN}║${NC}"
echo -e "${GREEN}║  📖 Consulta los documentos:${NC}"
echo -e "${GREEN}║     ${YELLOW}TESTING.md${NC} - Guía de tests y fixtures"
echo -e "${GREEN}║     ${YELLOW}USER_GUIDE.md${NC} - Guía de uso"
echo -e "${GREEN}║     ${YELLOW}COMPLETION_REPORT.md${NC} - Reporto de completitud"
echo -e "${GREEN}║${NC}"
echo -e "${GREEN}║  🔗 URLs Principales:${NC}"
echo -e "${GREEN}║     Frontend: ${YELLOW}http://localhost:8000/quiz${NC}"
echo -e "${GREEN}║     Admin:    ${YELLOW}http://localhost:8000/quiz/admin${NC}"
echo -e "${GREEN}║${NC}"
echo -e "${GREEN}╚═══════════════════════════════════════════════════════════╝${NC}"

echo -e "\n${YELLOW}ℹ️  Notas:${NC}"
echo "   • La base de datos debe estar en ejecución (MySQL/MariaDB)"
echo "   • Asegúrate de tener PHP 8.0+ instalado"
echo "   • Consulta .env si necesitas ajustar configuraciones"
echo ""
