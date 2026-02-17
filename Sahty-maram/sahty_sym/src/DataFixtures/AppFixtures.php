<?php

namespace App\DataFixtures;

use App\Entity\Quiz;
use App\Entity\Question;
use App\Entity\Recommandation;
use App\Entity\Patient;
use Doctrine\Bundle\FixturesBundle\Fixture;
use Doctrine\Persistence\ObjectManager;

class AppFixtures extends Fixture
{
    public function load(ObjectManager $manager): void
    {
        // ─────────────────────────────────────────────┐
        // QUIZ 1: ÉVALUATION DU STRESS ET DE L'ANXIÉTÉ
        // ─────────────────────────────────────────────┘

        $quiz1 = new Quiz();
        $quiz1->setName('Échelle de Stress et d\'Anxiété (GAD-7)');
        $quiz1->setDescription('Évaluez votre niveau d\'anxiété sur les 2 dernières semaines. Ce questionnaire est basé sur l\'échelle GAD-7 validée médicalement.');

        // Questions pour Quiz 1
        $questions1 = [
            [
                'text' => 'Au cours des 2 dernières semaines, à quelle fréquence vous avez senti de la nervosité ou de la tension ?',
                'type' => 'likert_0_4',
                'category' => 'anxiete',
                'order' => 1,
                'reverse' => false,
            ],
            [
                'text' => 'À quelle fréquence avez-vous eu du mal à arrêter de vous inquiéter ou contrôler vos inquiétudes ?',
                'type' => 'likert_0_4',
                'category' => 'anxiete',
                'order' => 2,
                'reverse' => false,
            ],
            [
                'text' => 'À quelle fréquence vous vous êtes inquiété pour trop de choses différentes ?',
                'type' => 'likert_0_4',
                'category' => 'anxiete',
                'order' => 3,
                'reverse' => false,
            ],
            [
                'text' => 'À quelle fréquence avez-vous eu du mal à vous détendre ?',
                'type' => 'likert_0_4',
                'category' => 'stress',
                'order' => 4,
                'reverse' => false,
            ],
            [
                'text' => 'À quelle fréquence vous vous êtes senti si agité que c\'était difficile de rester immobile ?',
                'type' => 'likert_0_4',
                'category' => 'stress',
                'order' => 5,
                'reverse' => false,
            ],
            [
                'text' => 'À quelle fréquence vous vous êtes senti facilement irritable ou énervé ?',
                'type' => 'likert_0_4',
                'category' => 'humeur',
                'order' => 6,
                'reverse' => false,
            ],
            [
                'text' => 'À quelle fréquence avez-vous ressenti de la peur, comme si quelque chose de terrible allait se passer ?',
                'type' => 'likert_0_4',
                'category' => 'anxiete',
                'order' => 7,
                'reverse' => false,
            ],
            [
                'text' => 'Durant la pandémie, avez-vous senti une amélioration de votre bien-être par rapport à avant ?',
                'type' => 'likert_0_4',
                'category' => 'humeur',
                'order' => 8,
                'reverse' => true, // Inversé
            ],
        ];

        foreach ($questions1 as $qData) {
            $q = new Question();
            $q->setText($qData['text']);
            $q->setType($qData['type']);
            $q->setCategory($qData['category']);
            $q->setOrderInQuiz($qData['order']);
            $q->setReverse($qData['reverse']);
            $q->setQuiz($quiz1);
            $manager->persist($q);
        }

        // Recommandations pour Quiz 1
        $reco1_1 = new Recommandation();
        $reco1_1->setQuiz($quiz1);
        $reco1_1->setName('Techniques de relaxation simples');
        $reco1_1->setTitle('Pratiquez la respiration 4-7-8');
        $reco1_1->setDescription('Des exercices de respiration simples peuvent réduire l\'anxiété immédiatement.');
        $reco1_1->setTips("• Inspirez pendant 4 secondes\n• Retenez votre respiration 7 secondes\n• Expirez lentement pendant 8 secondes\n• Répétez 4-5 fois");
        $reco1_1->setMinScore(5);
        $reco1_1->setMaxScore(12);
        $reco1_1->setTargetCategories('anxiete,stress');
        $reco1_1->setSeverity('low');
        $manager->persist($reco1_1);

        $reco1_2 = new Recommandation();
        $reco1_2->setQuiz($quiz1);
        $reco1_2->setName('Activité physique régulière');
        $reco1_2->setTitle('Bougez pour réduire le stress');
        $reco1_2->setDescription('L\'exercice physique est très efficace contre l\'anxiété et le stress.');
        $reco1_2->setTips("• Marchez 30 minutes par jour\n• Essayez le yoga (5-10 min/jour)\n• Faites du sport que vous aimez\n• Augmentez progressivement");
        $reco1_2->setMinScore(10);
        $reco1_2->setMaxScore(18);
        $reco1_2->setTargetCategories('stress,anxiete,humeur');
        $reco1_2->setSeverity('medium');
        $manager->persist($reco1_2);

        $reco1_3 = new Recommandation();
        $reco1_3->setQuiz($quiz1);
        $reco1_3->setName('Consulter un professionnel de santé');
        $reco1_3->setTitle('Chercher une aide professionnelle');
        $reco1_3->setDescription('Votre score indique un niveau d\'anxiété qui nécessite une prise en charge professionnelle.');
        $reco1_3->setTips("• Consultez un médecin ou psychiatre\n• Envisagez une thérapie (TCC)\n• Un suivi régulier est recommandé\n• Ne restez pas seul");
        $reco1_3->setMinScore(15);
        $reco1_3->setMaxScore(32);
        $reco1_3->setTargetCategories('anxiete');
        $reco1_3->setSeverity('high');
        $manager->persist($reco1_3);

        $manager->persist($quiz1);

        // ─────────────────────────────────────────────┐
        // QUIZ 2: QUALITÉ DU SOMMEIL
        // ─────────────────────────────────────────────┘

        $quiz2 = new Quiz();
        $quiz2->setName('Indice de Qualité du Sommeil (PSQI)');
        $quiz2->setDescription('Évaluez la qualité de votre sommeil sur le dernier mois. Le PSQI est un questionnaire validé scientifiquement.');

        $questions2 = [
            [
                'text' => 'À quelle heure vous vous êtes généralement couché ? (heures qui passent avant l\'endormissement)',
                'type' => 'likert_0_4',
                'category' => 'sommeil',
                'order' => 1,
                'reverse' => false,
            ],
            [
                'text' => 'Combien de temps après vous être couché avez-vous généralement mis pour vous endormir ?',
                'type' => 'likert_0_4',
                'category' => 'sommeil',
                'order' => 2,
                'reverse' => false,
            ],
            [
                'text' => 'Combien d\'heures par nuit avez-vous dormi en moyenne ?',
                'type' => 'likert_0_4',
                'category' => 'sommeil',
                'order' => 3,
                'reverse' => true, // Plus on dort c'est mieux
            ],
            [
                'text' => 'À quelle fréquence avez-vous été incapable de dormir parce que vous aviez mal ?',
                'type' => 'likert_0_4',
                'category' => 'sommeil',
                'order' => 4,
                'reverse' => false,
            ],
            [
                'text' => 'À quelle fréquence avez-vous ressenti une mauvaise ambiance dans votre chambre ?',
                'type' => 'likert_0_4',
                'category' => 'sommeil',
                'order' => 5,
                'reverse' => false,
            ],
            [
                'text' => 'À quelle fréquence avez-vous consommé des médicaments pour mieux dormir ?',
                'type' => 'likert_0_4',
                'category' => 'sommeil',
                'order' => 6,
                'reverse' => false,
            ],
            [
                'text' => 'À quelle fréquence avez-vous senti que votre sommeil était réparateur ?',
                'type' => 'likert_0_4',
                'category' => 'sommeil',
                'order' => 7,
                'reverse' => true,
            ],
            [
                'text' => 'At what frequency did you feel drowsy during the day?',
                'type' => 'likert_0_4',
                'category' => 'sommeil',
                'order' => 8,
                'reverse' => false,
            ],
        ];

        foreach ($questions2 as $qData) {
            $q = new Question();
            $q->setText($qData['text']);
            $q->setType($qData['type']);
            $q->setCategory($qData['category']);
            $q->setOrderInQuiz($qData['order']);
            $q->setReverse($qData['reverse']);
            $q->setQuiz($quiz2);
            $manager->persist($q);
        }

        // Recommandations pour Quiz 2
        $reco2_1 = new Recommandation();
        $reco2_1->setQuiz($quiz2);
        $reco2_1->setName('Hygiène du sommeil');
        $reco2_1->setTitle('Améliorez vos habitudes de sommeil');
        $reco2_1->setDescription('Une bonne hygiène du sommeil peut améliorer significativement la qualité de votre repos.');
        $reco2_1->setTips("• Couchez-vous à heures régulières\n• Évitez écrans 1h avant lit\n• Température fraîche (16-19°C)\n• Environnement sombre et calme");
        $reco2_1->setMinScore(5);
        $reco2_1->setMaxScore(12);
        $reco2_1->setTargetCategories('sommeil');
        $reco2_1->setSeverity('low');
        $manager->persist($reco2_1);

        $reco2_2 = new Recommandation();
        $reco2_2->setQuiz($quiz2);
        $reco2_2->setName('Évitez stimulants');
        $reco2_2->setTitle('Réduisez café et alcool');
        $reco2_2->setDescription('La caféine et l\'alcool affectent fortement la qualité du sommeil.');
        $reco2_2->setTips("• Pas de café après 14h\n• Limitez l\'alcool (surtout le soir)\n• Évitez repas lourds la nuit\n• Pas d\'exercice 3h avant lit");
        $reco2_2->setMinScore(10);
        $reco2_2->setMaxScore(18);
        $reco2_2->setTargetCategories('sommeil');
        $reco2_2->setSeverity('medium');
        $manager->persist($reco2_2);

        $reco2_3 = new Recommandation();
        $reco2_3->setQuiz($quiz2);
        $reco2_3->setName('Consultation médicale - Insomnie');
        $reco2_3->setTitle('Recherchez une aide médicale');
        $reco2_3->setDescription('Votre qualité de sommeil affecte sérieusement votre santé. Une consultation est urgente.');
        $reco2_3->setTips("• Consultez votre médecin\n• Test sleep apnea: polysomnographie\n• Thérapie cognitivo-comportementale\n• Possibilité traitement médical");
        $reco2_3->setMinScore(16);
        $reco2_3->setMaxScore(32);
        $reco2_3->setTargetCategories('sommeil');
        $reco2_3->setSeverity('high');
        $manager->persist($reco2_3);

        $manager->persist($quiz2);

        // ─────────────────────────────────────────────┐
        // QUIZ 3: CONCENTRATION ET FOCUS
        // ─────────────────────────────────────────────┘

        $quiz3 = new Quiz();
        $quiz3->setName('Troubles de l\'Attention et de la Concentration');
        $quiz3->setDescription('Évaluez vos capacités de concentration et d\'attention.');

        $questions3 = [
            [
                'text' => 'À quelle fréquence avez-vous du mal à vous concentrer sur vos tâches ?',
                'type' => 'likert_0_4',
                'category' => 'concentration',
                'order' => 1,
                'reverse' => false,
            ],
            [
                'text' => 'Vous vous laissez facilement distraire par votre environnement ou les notifications ?',
                'type' => 'likert_0_4',
                'category' => 'concentration',
                'order' => 2,
                'reverse' => false,
            ],
            [
                'text' => 'À quelle fréquence vous avez oublié ce que vous faisiez au milieu d\'une tâche ?',
                'type' => 'likert_0_4',
                'category' => 'concentration',
                'order' => 3,
                'reverse' => false,
            ],
            [
                'text' => 'Combien de temps pouvez-vous vous concentrer sans pause ?',
                'type' => 'likert_0_4',
                'category' => 'concentration',
                'order' => 4,
                'reverse' => true,
            ],
            [
                'text' => 'À quelle fréquence vous changez de tâche sans les terminer ?',
                'type' => 'likert_0_4',
                'category' => 'concentration',
                'order' => 5,
                'reverse' => false,
            ],
            [
                'text' => 'Votre sommeil insuffisant affecte-t-il votre concentration ?',
                'type' => 'likert_0_4',
                'category' => 'concentration',
                'order' => 6,
                'reverse' => false,
            ],
            [
                'text' => 'À quelle fréquence vous ressentez du brouillard mental ?',
                'type' => 'likert_0_4',
                'category' => 'concentration',
                'order' => 7,
                'reverse' => false,
            ],
            [
                'text' => 'Utilisez-vous des techniques de productivité (Pomodoro, listes, etc.) ?',
                'type' => 'likert_0_4',
                'category' => 'concentration',
                'order' => 8,
                'reverse' => true,
            ],
        ];

        foreach ($questions3 as $qData) {
            $q = new Question();
            $q->setText($qData['text']);
            $q->setType($qData['type']);
            $q->setCategory($qData['category']);
            $q->setOrderInQuiz($qData['order']);
            $q->setReverse($qData['reverse']);
            $q->setQuiz($quiz3);
            $manager->persist($q);
        }

        // Recommandations pour Quiz 3
        $reco3_1 = new Recommandation();
        $reco3_1->setQuiz($quiz3);
        $reco3_1->setName('Techniques de productivité');
        $reco3_1->setTitle('Méthode Pomodoro et organisation');
        $reco3_1->setDescription('Des techniques simples peuvent améliorer significativement votre concentration.');
        $reco3_1->setTips("• Utilisez la technique Pomodoro (25min focalisés)\n• Éliminez les distractions (téléphone)\n• Prenez des pauses régulières\n• Créez un espace de travail calme");
        $reco3_1->setMinScore(4);
        $reco3_1->setMaxScore(10);
        $reco3_1->setTargetCategories('concentration');
        $reco3_1->setSeverity('low');
        $manager->persist($reco3_1);

        $reco3_2 = new Recommandation();
        $reco3_2->setQuiz($quiz3);
        $reco3_2->setName('Nutrition et hydration');
        $reco3_2->setTitle('Améliorez votre alimentation');
        $reco3_2->setDescription('Une bonne nutrition est essentielle pour la concentration.');
        $reco3_2->setTips("• Buvez 2L d\'eau par jour\n• Omega-3 (poisson, noix)\n• Évitez sucres raffinés\n• Petit-déjeuner sain = meilleure concentration");
        $reco3_2->setMinScore(10);
        $reco3_2->setMaxScore(18);
        $reco3_2->setTargetCategories('concentration');
        $reco3_2->setSeverity('medium');
        $manager->persist($reco3_2);

        $reco3_3 = new Recommandation();
        $reco3_3->setQuiz($quiz3);
        $reco3_3->setName('Évaluation neuropsychologique');
        $reco3_3->setTitle('Consultez un spécialiste');
        $reco3_3->setDescription('Vos troubles de concentration nécessitent une évaluation professionnelle.');
        $reco3_3->setTips("• Consultation neurologue ou neuropsychologue\n• Test TDAH (si applicable)\n• Évaluation complète du fonctionnement cognitif\n• Plan de traitement personnalisé");
        $reco3_3->setMinScore(15);
        $reco3_3->setMaxScore(32);
        $reco3_3->setTargetCategories('concentration');
        $reco3_3->setSeverity('high');
        $manager->persist($reco3_3);

        $manager->persist($quiz3);

        // ─────────────────────────────────────────────┐
        // QUIZ 4: DÉPRESSION ET HUMEUR (PHQ-9)
        // ─────────────────────────────────────────────┘

        $quiz4 = new Quiz();
        $quiz4->setName('Questionnaire de dépression (PHQ-9)');
        $quiz4->setDescription('Évaluez votre humeur et les symptômes possibles de dépression.');

        $questions4 = [
            [
                'text' => 'À quelle fréquence avez-vous eu peu d\'intérêt ou de plaisir à faire vos activités habituelles ?',
                'type' => 'likert_0_4',
                'category' => 'humeur',
                'order' => 1,
                'reverse' => false,
            ],
            [
                'text' => 'À quelle fréquence vous avez senti triste ou déprimé ?',
                'type' => 'likert_0_4',
                'category' => 'humeur',
                'order' => 2,
                'reverse' => false,
            ],
            [
                'text' => 'Avez-vous eu des problèmes d\'endormissement ou un sommeil excessif ?',
                'type' => 'likert_0_4',
                'category' => 'sommeil',
                'order' => 3,
                'reverse' => false,
            ],
            [
                'text' => 'Vous avez senti fatigue ou manque d\'énergie ?',
                'type' => 'likert_0_4',
                'category' => 'humeur',
                'order' => 4,
                'reverse' => false,
            ],
            [
                'text' => 'Avez-vous eu peu d\'appétit ou mangé trop ?',
                'type' => 'likert_0_4',
                'category' => 'humeur',
                'order' => 5,
                'reverse' => false,
            ],
            [
                'text' => 'À quelle fréquence vous avez eu un sentiment de dévalorisation ou de culpabilité excessif ?',
                'type' => 'likert_0_4',
                'category' => 'humeur',
                'order' => 6,
                'reverse' => false,
            ],
            [
                'text' => 'Avez-vous eu du mal à vous concentrer sur vos tâches ?',
                'type' => 'likert_0_4',
                'category' => 'concentration',
                'order' => 7,
                'reverse' => false,
            ],
            [
                'text' => 'Avez-vous des pensées suicidaires ou d\'automutilation ?',
                'type' => 'yes_no',
                'category' => 'humeur',
                'order' => 8,
                'reverse' => false,
            ],
        ];

        foreach ($questions4 as $qData) {
            $q = new Question();
            $q->setText($qData['text']);
            $q->setType($qData['type']);
            $q->setCategory($qData['category']);
            $q->setOrderInQuiz($qData['order']);
            $q->setReverse($qData['reverse']);
            $q->setQuiz($quiz4);
            $manager->persist($q);
        }

        // Recommandations pour Quiz 4
        $reco4_1 = new Recommandation();
        $reco4_1->setQuiz($quiz4);
        $reco4_1->setName('Activités et connexion sociale');
        $reco4_1->setTitle('Reconnectez-vous avec la vie');
        $reco4_1->setDescription('Les activités simples et la connexion sociale aident à l\'humeur.');
        $reco4_1->setTips("• Sortez 15-30 min par jour (lumière naturelle)\n• Appelez un ami ou famille\n• Faites une activité que vous aimiez\n• Envisagez un groupe d\'entraide");
        $reco4_1->setMinScore(5);
        $reco4_1->setMaxScore(10);
        $reco4_1->setTargetCategories('humeur');
        $reco4_1->setSeverity('low');
        $manager->persist($reco4_1);

        $reco4_2 = new Recommandation();
        $reco4_2->setQuiz($quiz4);
        $reco4_2->setName('Consultation psychiatrique');
        $reco4_2->setTitle('Cherchez une aide professionnelle');
        $reco4_2->setDescription('Votre humeur nécessite une prise en charge médicale.');
        $reco4_2->setTips("• Consultation psychologue ou psychiatre\n• Possibilité antidépresseur\n• Psychothérapie recommandée\n• Suivi régulier");
        $reco4_2->setMinScore(10);
        $reco4_2->setMaxScore(20);
        $reco4_2->setTargetCategories('humeur');
        $reco4_2->setSeverity('high');
        $manager->persist($reco4_2);

        $reco4_3 = new Recommandation();
        $reco4_3->setQuiz($quiz4);
        $reco4_3->setName('URGENCE - Ligne d\'écoute');
        $reco4_3->setTitle('Cherchez une aide immédiate');
        $reco4_3->setDescription('Si vous avez des pensées suicidaires, il est URGENT de chercher une aide immédiate.');
        $reco4_3->setTips("• Appelez le 3114 (France) ou 1-800-273-8255 (USA)\n• Allez aux urgences\n• Confiez-vous à quelqu\'un de confiance\n• Vous n\'êtes pas seul(e)");
        $reco4_3->setMinScore(15);
        $reco4_3->setMaxScore(32);
        $reco4_3->setTargetCategories('humeur');
        $reco4_3->setSeverity('high');
        $manager->persist($reco4_3);

        $manager->persist($quiz4);

        // ─────────────────────────────────────────────┐
        // QUIZ 5: BIEN-ÊTRE GLOBAL
        // ─────────────────────────────────────────────┘

        $quiz5 = new Quiz();
        $quiz5->setName('Indice de Bien-être Global');
        $quiz5->setDescription('Évaluation holistique de votre santé physique et mentale.');

        $questions5 = [
            [
                'text' => 'Comment évaluez-vous votre santé physique généralement ?',
                'type' => 'likert_0_4',
                'category' => 'humeur',
                'order' => 1,
                'reverse' => true,
            ],
            [
                'text' => 'À quelle fréquence avez-vous des douleurs chroniques ?',
                'type' => 'likert_0_4',
                'category' => 'stress',
                'order' => 2,
                'reverse' => false,
            ],
            [
                'text' => 'Êtes-vous satisfait de votre vie professionnelle ?',
                'type' => 'likert_0_4',
                'category' => 'humeur',
                'order' => 3,
                'reverse' => true,
            ],
            [
                'text' => 'À quelle fréquence vous exercez une activité physique ?',
                'type' => 'likert_0_4',
                'category' => 'concentration',
                'order' => 4,
                'reverse' => true,
            ],
            [
                'text' => 'Avez-vous des relations sociales satisfaisantes ?',
                'type' => 'likert_0_4',
                'category' => 'humeur',
                'order' => 5,
                'reverse' => true,
            ],
            [
                'text' => 'À quelle fréquence vous vous sentez isolé ou seul ?',
                'type' => 'likert_0_4',
                'category' => 'humeur',
                'order' => 6,
                'reverse' => false,
            ],
            [
                'text' => 'Êtes-vous satisfait de votre équilibre travail-vie personnelle ?',
                'type' => 'likert_0_4',
                'category' => 'stress',
                'order' => 7,
                'reverse' => true,
            ],
            [
                'text' => 'À quelle fréquence vous vous sentez optimiste pour l\'avenir ?',
                'type' => 'likert_0_4',
                'category' => 'humeur',
                'order' => 8,
                'reverse' => true,
            ],
        ];

        foreach ($questions5 as $qData) {
            $q = new Question();
            $q->setText($qData['text']);
            $q->setType($qData['type']);
            $q->setCategory($qData['category']);
            $q->setOrderInQuiz($qData['order']);
            $q->setReverse($qData['reverse']);
            $q->setQuiz($quiz5);
            $manager->persist($q);
        }

        // Recommandations pour Quiz 5
        $reco5_1 = new Recommandation();
        $reco5_1->setQuiz($quiz5);
        $reco5_1->setName('Programme holistique de bien-être');
        $reco5_1->setTitle('Adoptez une approche globale');
        $reco5_1->setDescription('Un bien-être durable nécessite une approche équilibrée de tous les domaines de votre vie.');
        $reco5_1->setTips("• Nutrition saine et équilibrée\n• 30min exercice 5x/semaine\n• 8h sommeil régulier\n• Connexions sociales fortes");
        $reco5_1->setMinScore(5);
        $reco5_1->setMaxScore(14);
        $reco5_1->setTargetCategories('humeur,stress,concentration,sommeil');
        $reco5_1->setSeverity('low');
        $manager->persist($reco5_1);

        $reco5_2 = new Recommandation();
        $reco5_2->setQuiz($quiz5);
        $reco5_2->setName('Thérapie personnalisée');
        $reco5_2->setTitle('Développement personnel et coaching');
        $reco5_2->setDescription('Un accompagnement professionnel peut vous aider à améliorer votre qualité de vie.');
        $reco5_2->setTips("• Coach de vie ou thérapeute\n• Programme de développement personnel\n• Méditation et pleine conscience\n• Travail sur les objectifs de vie");
        $reco5_2->setMinScore(12);
        $reco5_2->setMaxScore(22);
        $reco5_2->setTargetCategories('humeur,stress');
        $reco5_2->setSeverity('medium');
        $manager->persist($reco5_2);

        $reco5_3 = new Recommandation();
        $reco5_3->setQuiz($quiz5);
        $reco5_3->setName('Évaluation médicale complète');
        $reco5_3->setTitle('Consultation multidisciplinaire');
        $reco5_3->setDescription('Votre bien-être global est affecté. Une prise en charge complète est recommandée.');
        $reco5_3->setTips("• Bilan médical complet\n• Consultation gériatre/nutritionniste\n• Possibilité psychothérapie longue\n• Gestion des comorbidités");
        $reco5_3->setMinScore(18);
        $reco5_3->setMaxScore(32);
        $reco5_3->setTargetCategories('humeur,stress,concentration,sommeil');
        $reco5_3->setSeverity('high');
        $manager->persist($reco5_3);

        $manager->persist($quiz5);

        // Flush tous
        $manager->flush();
    }
}
