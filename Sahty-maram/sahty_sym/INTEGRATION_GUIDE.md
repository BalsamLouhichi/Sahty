# 🚀 Sahty - Professional UI/UX Integration Guide

## Overview

This guide walks through integrating the new professional UI/UX templates into your Symfony application.

---

## 📋 Pre-Integration Checklist

Before starting, ensure you have:
- ✅ Symfony 6.4+ installed
- ✅ Bootstrap 5.3+ (CSS)
- ✅ Bootstrap Icons installed
- ✅ All database entities created (Quiz, Question, AnswerOption, etc.)
- ✅ Controllers for Quiz and Admin operations
- ✅ Routes configured in `config/routes.yaml`

---

## 🔧 Step 1: Update Bootstrap Configuration

### 1.1 Ensure Bootstrap 5.3 is installed
```bash
npm install bootstrap@5.3.0
npm install bootstrap-icons@1.11.0
```

### 1.2 Add to your main CSS/JavaScript file
**assets/app.js**:
```javascript
import './bootstrap.js';
```

**assets/styles/app.css** (or in your base template):
```html
<link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
<link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
<link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.0/font/bootstrap-icons.css">
```

### 1.3 Load Bootstrap JS
Add to end of `base.html.twig`:
```html
<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
```

---

## 📁 Step 2: Template File Structure

Ensure all templates are in correct locations:

```
templates/
├── base.html.twig                 ← Main frontend layout
├── home.html.twig                 ← NEW: Home page
├── admin/
│   ├── base.html.twig             ← NEW: Admin layout
│   ├── dashboard.html.twig        ← NEW: Admin dashboard
│   └── quiz/
│       ├── index.html.twig        ← UPDATED: Quiz list
│       ├── new.html.twig          ← Existing form
│       └── edit.html.twig         ← Existing form
├── quiz/
│   ├── list.html.twig             ← NEW: Public quiz list
│   ├── show.html.twig             ← UPDATED: Quiz taking
│   └── result.html.twig           ← UPDATED: Results page
└── recommandation/                ← Similar structure for recommendations
    └── index.html.twig
```

---

## 🔌 Step 3: Update Base Template

Your `templates/base.html.twig` should:
1. Load Bootstrap CSS/JS
2. Include navigation
3. Define `{% block content %}`
4. Load Font Awesome/Bootstrap Icons

**Minimal Example**:
```html
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>{% block title %}Sahty{% endblock %}</title>
    
    <!-- Bootstrap CSS -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <!-- Bootstrap Icons -->
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.0/font/bootstrap-icons.css">
</head>
<body>
    <!-- Navigation (if needed) -->
    {% block navbar %}{% endblock %}
    
    <!-- Main Content -->
    {% block content %}{% endblock %}
    
    <!-- Bootstrap JS -->
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    {% block javascripts %}{% endblock %}
</body>
</html>
```

---

## 🛣️ Step 4: Configure Routes

Add/update routes in `config/routes.yaml`:

```yaml
# Frontend Routes
home:
  path: /
  controller: App\Controller\HomeController::index
  methods: [GET]

quiz_list:
  path: /quizzes
  controller: App\Controller\QuizController::list
  methods: [GET]

quiz_show:
  path: /quizzes/{id}
  controller: App\Controller\QuizController::show
  methods: [GET]
  requirements:
    id: '\d+'

quiz_submit:
  path: /quizzes/{id}/submit
  controller: App\Controller\QuizController::submit
  methods: [POST]
  requirements:
    id: '\d+'

quiz_results:
  path: /quizzes/{id}/results
  controller: App\Controller\QuizController::result
  methods: [GET]
  requirements:
    id: '\d+'

# Admin Routes
admin_dashboard:
  path: /admin
  controller: App\Controller\HomeController::dashboard
  methods: [GET]

admin_quiz_index:
  path: /admin/quizzes
  controller: App\Controller\QuizController::adminIndex
  methods: [GET]

admin_quiz_new:
  path: /admin/quizzes/new
  controller: App\Controller\QuizController::adminNew
  methods: [GET, POST]

admin_quiz_edit:
  path: /admin/quizzes/{id}/edit
  controller: App\Controller\QuizController::adminEdit
  methods: [GET, POST]
  requirements:
    id: '\d+'

admin_quiz_delete:
  path: /admin/quizzes/{id}
  controller: App\Controller\QuizController::adminDelete
  methods: [POST, DELETE]
  requirements:
    id: '\d+'

admin_recommandation_index:
  path: /admin/recommandations
  controller: App\Controller\RecommandationController::adminIndex
  methods: [GET]
```

---

## 🎮 Step 5: Create/Update Controllers

### 5.1 HomeController

```php
<?php
namespace App\Controller;

use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;

class HomeController extends AbstractController
{
    #[Route('/', name: 'home')]
    public function index(): Response
    {
        return $this->render('home.html.twig');
    }

    #[Route('/admin', name: 'home')]
    #[IsGranted('ROLE_ADMIN')]
    public function dashboard(QuizRepository $quizRepo, RecommendationRepository $recoRepo): Response
    {
        return $this->render('admin/dashboard.html.twig', [
            'quizzes' => $quizRepo->findAll(),
            'recommendations' => $recoRepo->findAll(),
        ]);
    }
}
```

### 5.2 QuizController (Frontend Methods)

```php
public function list(QuizRepository $repo, PaginatorInterface $paginator, Request $request): Response
{
    $query = $repo->findAllQueryBuilder();
    $quizzes = $paginator->paginate($query, $request->query->getInt('page', 1), 9);
    
    return $this->render('quiz/list.html.twig', [
        'quizzes' => $quizzes,
    ]);
}

public function show(Quiz $quiz): Response
{
    return $this->render('quiz/show.html.twig', [
        'quiz' => $quiz,
    ]);
}

public function submit(Quiz $quiz, Request $request, QuizResultService $resultService): Response
{
    $answers = $request->request->all()['answers'] ?? [];
    $result = $resultService->calculateResult($quiz, $answers);
    
    return $this->render('quiz/result.html.twig', [
        'quiz' => $quiz,
        'score' => $result['score'],
        'total' => $result['maxScore'],
        'recommandations' => $result['recommendations'],
    ]);
}
```

### 5.3 QuizController (Admin Methods)

```php
#[Route('/admin/quizzes', name: 'admin_quiz_index')]
#[IsGranted('ROLE_ADMIN')]
public function adminIndex(QuizRepository $repo, PaginatorInterface $paginator, Request $request): Response
{
    $query = $repo->findAllQueryBuilder();
    $quizzes = $paginator->paginate($query, $request->query->getInt('page', 1), 12);
    
    return $this->render('admin/quiz/index.html.twig', [
        'quizzes' => $quizzes,
    ]);
}

#[Route('/admin/quizzes/new', name: 'admin_quiz_new', methods: ['GET', 'POST'])]
#[IsGranted('ROLE_ADMIN')]
public function adminNew(Request $request, EntityManagerInterface $em): Response
{
    // ... form handling ...
}

#[Route('/admin/quizzes/{id}/edit', name: 'admin_quiz_edit', methods: ['GET', 'POST'])]
#[IsGranted('ROLE_ADMIN')]
public function adminEdit(Quiz $quiz, Request $request, EntityManagerInterface $em): Response
{
    // ... form handling ...
}
```

---

## 🎨 Step 6: Update Frontend Base Template

Ensure your `templates/base.html.twig` is properly set up:

```html
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>{% block title %}Sahty - Your Health Companion{% endblock %}</title>
    
    <!-- Bootstrap 5 CSS -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <!-- Bootstrap Icons -->
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.0/font/bootstrap-icons.css">
    
    {% block stylesheets %}{% endblock %}
</head>
<body>
    <!-- Optional Navigation -->
    <nav class="navbar navbar-expand-lg navbar-light bg-white shadow-sm">
        <div class="container">
            <a class="navbar-brand" href="{{ path('home') }}">
                <i class="bi bi-heart-pulse"></i> Sahty
            </a>
            <button class="navbar-toggler" type="button" data-bs-toggle="collapse" data-bs-target="#navbarNav">
                <span class="navbar-toggler-icon"></span>
            </button>
            <div class="collapse navbar-collapse" id="navbarNav">
                <ul class="navbar-nav ms-auto">
                    <li class="nav-item">
                        <a class="nav-link" href="{{ path('quiz_list') }}">Quizzes</a>
                    </li>
                    {% if is_granted('ROLE_ADMIN') %}
                        <li class="nav-item">
                            <a class="nav-link" href="{{ path('home') }}">Admin</a>
                        </li>
                    {% endif %}
                </ul>
            </div>
        </div>
    </nav>

    <!-- Flash Messages -->
    {% for type, messages in app.flashes %}
        {% for message in messages %}
            <div class="alert alert-{{ type == 'success' ? 'success' : (type == 'error' ? 'danger' : 'info') }} 
                    alert-dismissible fade show m-3" role="alert">
                {{ message }}
                <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
            </div>
        {% endfor %}
    {% endfor %}

    <!-- Main Content -->
    {% block content %}{% endblock %}

    <!-- Bootstrap JS -->
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    {% block javascripts %}{% endblock %}
</body>
</html>
```

---

## ✅ Step 7: Verification Checklist

After integration, verify:

```bash
# 1. Check syntax of all Twig files
php bin/console lint:twig

# 2. Check routes are registered
php bin/console debug:router | grep quiz
php bin/console debug:router | grep admin

# 3. Check database entities exist
php bin/console list doctrine

# 4. Clear cache
php bin/console cache:clear

# 5. Build assets (if using Webpack)
npm run build
# or for development:
npm run dev
```

---

## 🌐 Step 8: Test in Browser

Visit each URL and verify:

| URL | Expected | Status |
|-----|----------|--------|
| `/` | Home page with hero | ☐ |
| `/quizzes` | Quiz list with grid | ☐ |
| `/quizzes/1` | Quiz with questions | ☐ |
| `/admin` | Admin dashboard (if admin) | ☐ |
| `/admin/quizzes` | Quiz management table | ☐ |

---

## 🐛 Common Issues & Solutions

### Issue: "Unable to find template 'quiz/list.html.twig'"
**Solution**: Ensure the template file exists at `templates/quiz/list.html.twig`

### Issue: "Variable 'quizzes' does not exist"
**Solution**: In controller, pass the variable:
```php
return $this->render('quiz/list.html.twig', [
    'quizzes' => $quizzes,  // ← Add this
]);
```

### Issue: Styles not loading
**Solution**: 
- Check Bootstrap CSS link in base template
- Clear browser cache (Ctrl+Shift+Delete)
- Verify CSS files are being served

### Issue: Icons not showing
**Solution**: 
- Verify Bootstrap Icons CSS is loaded
- Check browser console for 404 errors
- Update CDN links if needed

### Issue: Pagination not working
**Solution**: 
- Verify `PaginatorInterface` is injected
- Check that variable is iterable
- Ensure `knp/knp-paginator-bundle` is installed

---

## 🚀 Step 9: Deployment

### Pre-Deployment Checklist
- ✅ All routes configured
- ✅ All templates created
- ✅ Controllers updated
- ✅ Database migrations run
- ✅ Assets compiled
- ✅ CSS/JS minified
- ✅ Cache cleared

### Production Settings
```bash
# Set environment to production
export APP_ENV=prod

# Clear cache
php bin/console cache:clear --env=prod

# Build assets
npm run build --prod

# Warmup cache
php bin/console cache:warmup --env=prod
```

---

## 📊 Performance Tips

1. **Image Optimization**: Use WebP format for images
2. **Lazy Loading**: Add `loading="lazy"` to images
3. **CSS Minification**: Use production build
4. **JavaScript Bundling**: Combine JS files
5. **Caching**: Enable HTTP caching headers
6. **Compression**: Enable gzip compression

---

## 📚 Documentation Files

- `PROFESSIONAL_UI_GUIDE.md` - Design system details
- `UI_UX_IMPROVEMENTS_REPORT.md` - Feature overview
- `TEMPLATE_ROUTE_VERIFICATION.md` - Route checklist

---

## 🎯 Next Steps

1. ✅ Follow this integration guide
2. ✅ Verify all changes in browser
3. ✅ Test all user flows
4. ✅ Check mobile responsiveness
5. ✅ Deploy to production

---

## 📞 Support

If you encounter issues:
1. Check the browser console for errors
2. Review server logs: `tail -f var/log/dev.log`
3. Verify all routes with: `php bin/console debug:router`
4. Check templates with: `php bin/console lint:twig`

---

**Date**: February 2025  
**Version**: 2.0  
**Status**: Ready for Integration  
**Estimated Integration Time**: 2-3 hours
