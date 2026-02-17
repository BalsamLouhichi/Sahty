#!/bin/bash
# Installation & Setup Script pour Sahty Quiz System
# Exécuter depuis: c:\Users\LENOVO\Downloads\Sahty-maram\Sahty-maram\sahty_sym

echo "━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━"
echo "🎯 SAHTY QUIZ SYSTEM - SETUP COMPLET"
echo "━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━"

# Colors
GREEN='\033[0;32m'
YELLOW='\033[1;33m'
RED='\033[0;31m'
NC='\033[0m' # No Color

# Step 1: Clear Cache
echo -e "\n${YELLOW}[1/5] Clearing application cache...${NC}"
php bin/console cache:clear
echo -e "${GREEN}✓ Cache cleared${NC}"

# Step 2: Check Database
echo -e "\n${YELLOW}[2/5] Checking database migrations...${NC}"
php bin/console doctrine:migrations:status
echo -e "${GREEN}✓ Migrations checked${NC}"

# Step 3: Verify Database Connection
echo -e "\n${YELLOW}[3/5] Testing database connection...${NC}"
php bin/console doctrine:query:sql "SELECT 1"
echo -e "${GREEN}✓ Database connection OK${NC}"

# Step 4: Load Test Data
echo -e "\n${YELLOW}[4/5] Loading test data (if not exists)...${NC}"
php bin/console app:load-test-data
echo -e "${GREEN}✓ Test data loaded${NC}"

# Step 5: Display Routes
echo -e "\n${YELLOW}[5/5] Retrieved available routes...${NC}"
echo -e "\n${YELLOW}Admin Routes:${NC}"
php bin/console debug:router | grep -E "admin_quiz|admin_recommandation|admin_" | head -10

echo -e "\n${YELLOW}Public Routes:${NC}"
php bin/console debug:router | grep -E "app_quiz|quiz" | head -10

echo -e "\n${GREEN}━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━${NC}"
echo -e "${GREEN}✓ SETUP COMPLETE!${NC}"
echo -e "${GREEN}━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━${NC}"

echo -e "\n${YELLOW}Start Server:${NC}"
echo "  php -S 127.0.0.1:8000 -t public"

echo -e "\n${YELLOW}Access Points:${NC}"
echo "  🏠 Home: http://127.0.0.1:8000/"
echo "  📚 Quizzes: http://127.0.0.1:8000/quiz"
echo "  ⚙️  Admin: http://127.0.0.1:8000/quiz/admin"
echo "  📊 Dashboard: http://127.0.0.1:8000/admin_dashboard"

echo ""
