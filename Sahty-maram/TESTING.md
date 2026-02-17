# Guide Complet: Tests et Fixtures - Sahty Quiz Platform

## 📋 Vue d'Ensemble

Ce document explique comment exécuter les tests unitaires et charger les données de test dans votre application Sahty.

---

## 🚀 1. Charger les Fixtures (Données de Test)

Les fixtures créent automatiquement 5 quizzes complets avec:
- 8 questions par quiz
- Plusieurs recommandations par quiz
- Catégories variées (stress, sommeil, concentration, humeur, anxiété)

### Commande
```bash
php bin/console doctrine:fixtures:load
```

**Important**: Cette commande supprimera toutes les données existantes. Pour ajouter aux données existantes:
```bash
php bin/console doctrine:fixtures:load --append
```

### Quizzes Créés

1. **Échelle de Stress et d'Anxiété (GAD-7)**
   - 8 questions sur l'anxiété et le stress
   - 3 recommandations (faible, moyen, élevé)

2. **Indice de Qualité du Sommeil (PSQI)**
   - 8 questions sur la qualité du sommeil
   - 3 recommandations

3. **Troubles de l'Attention et de la Concentration**
   - 8 questions sur la concentration
   - 3 recommandations

4. **Questionnaire de Dépression (PHQ-9)**
   - 8 questions sur l'humeur et la dépression
   - 3 recommandations

5. **Indice de Bien-être Global**
   - 8 questions sur le bien-être général
   - 3 recommandations

---

## 🧪 2. Exécuter les Tests Unitaires

### 2.1 Tests du Service QuizResultService

```bash
php bin/phpunit tests/Service/QuizResultServiceTest.php
```

**Tests inclus:**
- Calcul du score total
- Scoring inversé (reverse scoring)
- Filtrage des recommandations par score
- Catégorisation par domaine
- Interprétation des résultats
- Gestion des réponses manquantes

### 2.2 Tests du Service RecommandationService

```bash
php bin/phpunit tests/Service/RecommandationServiceTest.php
```

**Tests inclus:**
- Filtrage par score
- Filtrage par catégories
- Tri par sévérité
- Regroupement par sévérité
- Comptage par sévérité
- Récupération des recommandations urgentes

### 2.3 Tests du Contrôleur Quiz

```bash
php bin/phpunit tests/Controller/QuizControllerTest.php
```

**Tests inclus:**
- Chargement de la liste des quizzes (front)
- Affichage détaillé d'un quiz
- Soumission de réponses et calcul des résultats
- Recherche avancée en admin
- Tri en admin

### 2.4 Exécuter Tous les Tests

```bash
php bin/phpunit
```

---

## ✨ 3. Features Implémentées

### A. CRUD Complet (Admin)

#### Quiz
- ✅ Créer un quiz avec questions dynamiques
- ✅ Modifier un quiz
- ✅ Supprimer un quiz
- ✅ Lister les quizzes avec pagination

#### Questions
- ✅ Ajouter/supprimer questions dans un quiz
- ✅ Types: Likert (0-4, 1-5), Oui/Non
- ✅ Catégories: stress, anxiété, concentration, sommeil, humeur
- ✅ Scoring inversé (reverse scoring)

#### Recommandations
- ✅ CRUD complet (Créer, Lire, Modifier, Supprimer)
- ✅ Lier à un quiz
- ✅ Définir les seuils de score
- ✅ Catégories cibles
- ✅ Niveaux de sévérité (low, medium, high)

### B. Interface Admin Avancée

- ✅ **Recherche Avancée**: Par nom et description
- ✅ **Tri Multi-critères**: Par date, nom, nombre de questions
- ✅ **Pagination**: 12 quizzes par page
- ✅ **Compteurs**: Nombre de questions et recommandations

### C. Frontend Utilisateur

- ✅ **Liste Paginée**: 9 quizzes par page
- ✅ **Questionnaire Interactif**: Réponses, validation
- ✅ **Résultats Personnalisés**:
  - Score global
  - Graphique Radar (par catégorie)
  - Recommandations filtrées et triées
  - Design premium

### D. Services Métier

#### QuizResultService
```php
$result = $quizResultService->calculate($quiz, $answers);
// Retourne: [
//   'totalScore' => int,
//   'maxScore' => int,
//   'categoryScores' => array,
//   'problems' => array (catégories problématiques),
//   'recommendations' => array,
//   'interpretation' => string
// ]
```

#### RecommandationService
```php
// Filtrer par score et catégories
$filtered = $recService->getFiltered($quiz, $score, $problems);

// Grouper par sévérité
$grouped = $recService->getGroupedBySeverity($quiz);

// Obtenir recommandations urgentes
$urgent = $recService->getUrgent($quiz);
```

### E. Templates Responsifs

- ✅ **Mobile-friendly**: Bootstrap 5
- ✅ **Grid adaptive**: 3 cols desktop, 2 cols tablet, 1 col mobile
- ✅ **Partials intégrés**: Navbar, footer réutilisables
- ✅ **Design moderne**: Cards, badges, accordions

---

## 🔧 4. Configuration des Fixtures

Fichier: `src/DataFixtures/AppFixtures.php`

### Modifier les Questions

```php
$questions1 = [
    [
        'text' => 'Votre question ici',
        'type' => 'likert_0_4',  // ou 'likert_1_5', 'yes_no'
        'category' => 'stress',   // ou autre
        'order' => 1,
        'reverse' => false,       // true pour inverser le scoring
    ],
    // ... plus de questions
];
```

### Modifier les Recommandations

```php
$reco = new Recommandation();
$reco->setQuiz($quiz);
$reco->setName('Nom');
$reco->setTitle('Titre court');
$reco->setDescription('Description longue');
$reco->setTips("• Conseil 1\n• Conseil 2"); // avec \n séparant les conseils
$reco->setMinScore(0);
$reco->setMaxScore(10);
$reco->setTargetCategories('stress,concentration'); // séparé par virgule
$reco->setSeverity('high'); // low, medium, high
```

---

## 📊 5. Architecture des Services

```
src/Service/
├── QuizResultService.php        # Calcule les résultats
└── RecommandationService.php    # Gère les recommandations

src/Controller/
├── QuizController.php           # Admin + Front
└── RecommandationController.php # Admin + Front

tests/
├── Service/
│   ├── QuizResultServiceTest.php
│   └── RecommandationServiceTest.php
└── Controller/
    └── QuizControllerTest.php
```

---

## 🚦 6. Commandes Utiles

### BASE DE DONNÉES

```bash
# Créer la base (si n'existe pas)
php bin/console doctrine:database:create

# Migrer le schéma
php bin/console doctrine:migrations:migrate

# Charger les fixtures
php bin/console doctrine:fixtures:load

# Vider la base et recharger
php bin/console doctrine:database:drop --force
php bin/console doctrine:database:create
php bin/console doctrine:migrations:migrate
php bin/console doctrine:fixtures:load
```

### TESTS

```bash
# Tous les tests
php bin/phpunit

# Test spécifique
php bin/phpunit tests/Service/QuizResultServiceTest.php

# Avec coverage
php bin/phpunit --coverage-html coverage/

# Test spécifique dans un fichier
php bin/phpunit tests/Service/QuizResultServiceTest.php::testCalculateTotalScore
```

### SERVEUR DE DÉVELOPPEMENT

```bash
php -S localhost:8000 -t public/
```

---

## 🎯 7. Workflow Typique de Test

### Pour Un Quiz Complet

```php
// 1. Créer un quiz avec questions
$quiz = new Quiz();
$quiz->setName('Mon Quiz');
// ... ajouter les questions

// 2. Créer les recommandations
$reco = new Recommandation();
// ... configurer la recommandation
$quiz->addRecommandation($reco);

// 3. Soumettre des réponses (frontend)
$responses = [1 => 2, 2 => 3, 3 => 1]; // question_id => score

// 4. Calculer les résultats
$result = $quizResultService->calculate($quiz, $responses);

// 5. Les recommandations sont déjà filtrées
foreach ($result['recommendations'] as $reco) {
    // Afficher les recommandations
}
```

---

## 📈 8. Métriques et Validation

### Validations Implémentées

- ✅ Score minimum/maximum doivent être cohérents
- ✅ Au moins une question par quiz
- ✅ Types de question valides
- ✅ Catégories reconnues
- ✅ Sévérité valide (low/medium/high)

### Cases de Test Couvertes

- Calcul du score avec questions inversées
- Recommandations par seuil de score
- Catégorisation multi-domaines
- Cas limites (réponses manquantes, etc.)
- Tri et filtrage avancé

---

## 🐛 9. Debugging

### Voir les requêtes SQL

```yaml
# config/packages/doctrine.yaml
doctrine:
    dbal:
        logging: true
```

### Logs

```
var/log/dev.log
```

### Twig Debug

```twig
{{ dump(variable) }}
{% %}
```

---

## 📝 10. Notes Importantes

1. **Fixtures**: À exécuter d'abord pour avoir des données de test
2. **Tests**: Doivent être exécutés dans un environnement test isolé
3. **Migrations**: Assurez-vous que les migrations sont à jour
4. **Bootstrap**: Le framework Bootstrap 5 est utilisé pour le design

---

## 📞 Support

Pour plus d'informations sur Symfony:
- [Symfony Documentation](https://symfony.com/doc/current/index.html)
- [Doctrine ORM](https://www.doctrine-project.org/)
- [PHPUnit](https://phpunit.de/)

---

**Dernière mise à jour**: Février 2025
**Version**: 1.0 - Complète
