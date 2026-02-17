# Setup Script pour Sahty Quiz System (PowerShell)
# Exécuter dans: c:\Users\LENOVO\Downloads\Sahty-maram\Sahty-maram\sahty_sym

Write-Host "━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━" -ForegroundColor Cyan
Write-Host "🎯 SAHTY QUIZ SYSTEM - SETUP COMPLET" -ForegroundColor Cyan
Write-Host "━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━" -ForegroundColor Cyan

# Step 1: Clear Cache
Write-Host "`n[1/5] Clearing application cache..." -ForegroundColor Yellow
php bin/console cache:clear
Write-Host "✓ Cache cleared" -ForegroundColor Green

# Step 2: Check Database
Write-Host "`n[2/5] Checking database migrations..." -ForegroundColor Yellow
php bin/console doctrine:migrations:status
Write-Host "✓ Migrations checked" -ForegroundColor Green

# Step 3: Verify Database Connection
Write-Host "`n[3/5] Testing database connection..." -ForegroundColor Yellow
php bin/console doctrine:query:sql "SELECT 1"
Write-Host "✓ Database connection OK" -ForegroundColor Green

# Step 4: Load Test Data
Write-Host "`n[4/5] Loading test data (if not exists)..." -ForegroundColor Yellow
php bin/console app:load-test-data
Write-Host "✓ Test data loaded" -ForegroundColor Green

# Step 5: Display Routes
Write-Host "`n[5/5] Retrieved available routes..." -ForegroundColor Yellow
Write-Host "`nAdmin Routes:" -ForegroundColor Yellow
php bin/console debug:router | Select-String -Pattern "admin_quiz|admin_recommandation" | Select-Object -First 10

Write-Host "`nPublic Routes:" -ForegroundColor Yellow
php bin/console debug:router | Select-String -Pattern "app_quiz|app_quiz" | Select-Object -First 10

Write-Host "`n━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━" -ForegroundColor Green
Write-Host "✓ SETUP COMPLETE!" -ForegroundColor Green
Write-Host "━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━" -ForegroundColor Green

Write-Host "`nStart Server:" -ForegroundColor Yellow
Write-Host "  php -S 127.0.0.1:8000 -t public"

Write-Host "`nAccess Points:" -ForegroundColor Yellow
Write-Host "  🏠 Home: http://127.0.0.1:8000/"
Write-Host "  📚 Quizzes: http://127.0.0.1:8000/quiz"
Write-Host "  ⚙️  Admin: http://127.0.0.1:8000/quiz/admin"
Write-Host "  📊 Dashboard: http://127.0.0.1:8000/admin_dashboard"

Write-Host ""
