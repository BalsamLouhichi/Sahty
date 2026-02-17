<?php

namespace App\Controller;

use App\Entity\Quiz;
use App\Form\QuizType;
use App\Repository\QuizRepository;
use App\Service\QuizResultService;
use Doctrine\ORM\EntityManagerInterface;
use Knp\Component\Pager\PaginatorInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;

#[Route('/quiz')]
class QuizController extends AbstractController
{
    // ────────────────────────────────────────────────
    // PARTIE ADMIN ────────────────────────────────────
    // ────────────────────────────────────────────────

    #[Route('/admin', name: 'admin_quiz_index', methods: ['GET'])]
    public function adminIndex(
        QuizRepository $quizRepository,
        Request $request,
        PaginatorInterface $paginator
    ): Response {
        $queryBuilder = $quizRepository->createQueryBuilder('q');

        // Recherche avancée
        if ($search = $request->query->get('search')) {
            $queryBuilder->andWhere('(q.name LIKE :search OR q.description LIKE :search)')
                ->setParameter('search', '%' . $search . '%');
        }

        // Tri avancé
        $sortBy = $request->query->get('sortBy', 'createdAt');
        $sortOrder = strtoupper($request->query->get('sortOrder', 'DESC'));

        if (!in_array($sortOrder, ['ASC', 'DESC'])) {
            $sortOrder = 'DESC';
        }

        switch ($sortBy) {
            case 'name':
                $queryBuilder->orderBy('q.name', $sortOrder);
                break;
            case 'createdAt':
            default:
                $queryBuilder->orderBy('q.createdAt', $sortOrder);
        }

        $pagination = $paginator->paginate(
            $queryBuilder,
            $request->query->getInt('page', 1),
            12 // items per page
        );

        return $this->render('admin/quiz/index.html.twig', [
            'pagination' => $pagination,
            'searchTerm' => $request->query->get('search', ''),
            'sortBy' => $sortBy,
            'sortOrder' => strtolower($sortOrder),
        ]);
    }

    #[Route('/admin/new', name: 'admin_quiz_new', methods: ['GET', 'POST'])]
    public function new(Request $request, EntityManagerInterface $em): Response
    {
        $quiz = new Quiz();
        $form = $this->createForm(QuizType::class, $quiz);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $em->persist($quiz);
            $em->flush();

            $this->addFlash('success', 'Quiz créé avec succès !');
            return $this->redirectToRoute('admin_quiz_index');
        }

        return $this->render('admin/quiz/new.html.twig', [
            'form' => $form->createView(),
        ]);
    }

    #[Route('/admin/{id}/edit', name: 'admin_quiz_edit', methods: ['GET', 'POST'])]
    public function edit(Quiz $quiz, Request $request, EntityManagerInterface $em): Response
    {
        $form = $this->createForm(QuizType::class, $quiz);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $em->flush();

            $this->addFlash('success', 'Quiz modifié avec succès.');
            return $this->redirectToRoute('admin_quiz_index');
        }

        return $this->render('admin/quiz/edit.html.twig', [
            'form' => $form->createView(),
            'quiz' => $quiz,
        ]);
    }

    #[Route('/admin/{id}/delete', name: 'admin_quiz_delete', methods: ['POST'])]
    public function delete(Request $request, Quiz $quiz, EntityManagerInterface $em): Response
    {
        if ($this->isCsrfTokenValid('delete' . $quiz->getId(), $request->request->get('_token'))) {
            $em->remove($quiz);
            $em->flush();

            $this->addFlash('success', 'Quiz supprimé avec succès.');
        }

        return $this->redirectToRoute('admin_quiz_index');
    }

    // ────────────────────────────────────────────────
    // PARTIE FRONT PUBLIQUE ───────────────────────────
    // ────────────────────────────────────────────────

    #[Route('', name: 'app_quiz_front_list', methods: ['GET'])]
    public function frontList(
        QuizRepository $quizRepository,
        Request $request,
        PaginatorInterface $paginator // ← injection directe ici (corrige l'erreur)
    ): Response {
        $page = max(1, $request->query->getInt('page', 1));

        $query = $quizRepository->createQueryBuilder('q')
            ->orderBy('q.createdAt', 'DESC');

        $pagination = $paginator->paginate(
            $query,
            $page,
            9 // 3 × 3 cards
        );

        return $this->render('quiz/front/list.html.twig', [
            'pagination' => $pagination,
        ]);
    }

    #[Route('/{id}', name: 'app_quiz_show', methods: ['GET'])]
    public function show(Quiz $quiz): Response
    {
        if ($quiz->getQuestions()->isEmpty()) {
            $this->addFlash('warning', 'Ce quiz ne contient aucune question pour le moment.');
        }

        return $this->render('quiz/front/show.html.twig', [
            'quiz' => $quiz,
        ]);
    }

    #[Route('/{id}/submit', name: 'app_quiz_submit', methods: ['POST'])]
    public function submit(
        Quiz $quiz,
        Request $request,
        QuizResultService $quizResultService
    ): Response {
        $answers = $request->request->all('answers', );

        if (empty($answers)) {
            $this->addFlash('danger', 'Aucune réponse n\'a été soumise.');
            return $this->redirectToRoute('app_quiz_show', ['id' => $quiz->getId()]);
        }

        $result = $quizResultService->calculate($quiz, $answers);

        return $this->render('quiz/front/result.html.twig', [
            'quiz'   => $quiz,
            'result' => $result,
        ]);
    }
}