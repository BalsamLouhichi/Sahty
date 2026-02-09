<?php

namespace App\Controller;

use App\Entity\Quiz;
use App\Entity\Recommandation;
use App\Form\QuizType;
use App\Repository\QuizRepository;
use App\Repository\RecommandationRepository;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;

class QuizController extends AbstractController
{
  #[Route('/quiz', name: 'app_quiz_index', methods: ['GET'])]
public function index(Request $request, QuizRepository $quizRepository): Response
{
    // Récupérer tous les paramètres GET
    $search = $request->query->get('search');
    $minQuestions = $request->query->getInt('min_questions', 0);
    $page = $request->query->getInt('page', 1);
    $limit = 6; // comme avant

    // Charger TOUS les quizzes (ou une requête optimisée sans filtre complexe)
    $quizzes = $quizRepository->createQueryBuilder('q')
        ->orderBy('q.id', 'DESC')
        ->getQuery()
        ->getResult();

    // Filtrer en PHP
    $filteredQuizzes = $quizzes;

    // Filtre recherche nom
    if ($search) {
        $search = strtolower($search);
        $filteredQuizzes = array_filter($filteredQuizzes, function ($quiz) use ($search) {
            return str_contains(strtolower($quiz->getName()), $search);
        });
    }

    // Filtre nombre de questions minimum
    if ($minQuestions > 0) {
        $filteredQuizzes = array_filter($filteredQuizzes, function ($quiz) use ($minQuestions) {
            return count($quiz->getQuestions()) >= $minQuestions;
        });
    }

    // Pagination manuelle en PHP
    $total = count($filteredQuizzes);
    $totalPages = ceil($total / $limit);
    $offset = ($page - 1) * $limit;

    $paginatedQuizzes = array_slice($filteredQuizzes, $offset, $limit);

    // Structure compatible avec ton template (comme avant)
    $pagination = [
        'results' => $paginatedQuizzes,
        'current_page' => $page,
        'max_per_page' => $limit,
        'total_pages' => $totalPages,
        'total_items' => $total
    ];

    return $this->render('quiz/index.html.twig', [
        'quizzes' => $pagination,
    ]);
}

    #[Route('/admin/quiz/new', name: 'app_quiz_new', methods: ['GET', 'POST'])]
    public function new(Request $request, EntityManagerInterface $em): Response
    {
        $quiz = new Quiz();
        $form = $this->createForm(QuizType::class, $quiz);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $em->persist($quiz);
            $em->flush();
            $this->addFlash('success', 'Quiz ajouté avec succès.');
            return $this->redirectToRoute('app_quiz_index');
        }

        return $this->render('quiz/new.html.twig', [
            'form' => $form->createView(),
        ]);
    }

    #[Route('/admin/quiz/edit/{id}', name: 'app_quiz_edit', methods: ['GET', 'POST'])]
    public function edit(Quiz $quiz, Request $request, EntityManagerInterface $em): Response
    {
        $form = $this->createForm(QuizType::class, $quiz);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $em->flush();
            $this->addFlash('success', 'Quiz modifié avec succès.');
            return $this->redirectToRoute('app_quiz_index');
        }

        return $this->render('quiz/edit.html.twig', [
            'form' => $form->createView(),
            'quiz' => $quiz,
        ]);
    }

    #[Route('/admin/quiz/delete/{id}', name: 'app_quiz_delete', methods: ['POST'])]
    public function delete(Quiz $quiz, Request $request, EntityManagerInterface $em): Response
    {
        if ($this->isCsrfTokenValid('delete'.$quiz->getId(), $request->request->get('_token'))) {
            $em->remove($quiz);
            $em->flush();
            $this->addFlash('success', 'Quiz supprimé avec succès.');
        }

        return $this->redirectToRoute('app_quiz_index');
    }

    #[Route('/quiz/{id}', name: 'app_quiz_show', methods: ['GET'])]
public function show(Quiz $quiz): Response
{
    $questions = $quiz->getQuestions();

    // Debug
    dump($questions);           // ← regarde dans la barre debug Symfony
    // ou
    // dd($questions);          // ← stoppe l'exécution

    return $this->render('quiz/show.html.twig', [
        'quiz'      => $quiz,
        'questions' => $questions,
    ]);
}

    #[Route('/quiz/{id}/submit', name: 'app_quiz_submit', methods: ['POST'])]
    public function submit(Request $request, Quiz $quiz, EntityManagerInterface $em): Response
    {
        $answers = $request->request->all('answers', []);

        // Calcul du score total
        $totalScore = 0;
        foreach ($answers as $questionIndex => $selectedScore) {
            $totalScore += (int) $selectedScore;
        }

        // Recherche des recommandations correspondantes
        $recommandations = $quiz->getRecommandations()
            ->filter(function (Recommandation $reco) use ($totalScore) {
                return $totalScore >= $reco->getMinScore() 
                    && $totalScore <= $reco->getMaxScore();
            });

        return $this->render('quiz/result.html.twig', [
            'quiz'            => $quiz,
            'score'           => $totalScore,
            'recommandations' => $recommandations,
            'total_questions' => count($quiz->getQuestions()),
        ]);
    }

    #[Route('/admin/quizzes', name: 'admin_quiz_list', methods: ['GET'])]
    public function adminList(QuizRepository $quizRepository): Response
    {
        return $this->render('quiz/admin_list.html.twig', [
            'quizzes' => $quizRepository->findAll(),
        ]);
    }
}