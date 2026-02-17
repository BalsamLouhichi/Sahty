<?php

namespace App\Controller;

use App\Entity\Recommandation;
use App\Form\RecommandationType;
use App\Repository\RecommandationRepository;
use App\Repository\QuizRepository;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;

#[Route('/recommandation')]
class RecommandationController extends AbstractController
{
    // ROUTES SPÉCIFIQUES EN PREMIER (avant {id})
    
    /**
     * Page front-office publique : liste des recommandations
     */
    #[Route('/recommandations', name: 'app_recommandation_front_list', methods: ['GET'])]
    public function frontRecommandationList(RecommandationRepository $recommandationRepository, Request $request): Response
    {
        $page = max(1, $request->query->getInt('page', 1));
        $limit = 9; // 9 recommandations par page (3 par ligne)

        // Requête paginée pour les résultats
        $queryBuilder = $recommandationRepository->createQueryBuilder('r')
            ->orderBy('r.createdAt', 'DESC')
            ->setFirstResult(($page - 1) * $limit)
            ->setMaxResults($limit);

        $recommandations = $queryBuilder->getQuery()->getResult();

        // Requête pour le total SANS pagination
        $countQueryBuilder = $recommandationRepository->createQueryBuilder('r');
        $totalRecommandations = count($countQueryBuilder->getQuery()->getResult());

        $totalPages = ceil($totalRecommandations / $limit);

        return $this->render('recommandation/front/list.html.twig', [
            'recommandations'       => $recommandations,
            'current_page'          => $page,
            'total_pages'           => $totalPages,
            'total_recommandations' => $totalRecommandations,
        ]);
    }

    #[Route('/new', name: 'app_recommandation_new', methods: ['GET', 'POST'])]
    public function new(Request $request, EntityManagerInterface $em): Response
    {
        $recommandation = new Recommandation();
        $form = $this->createForm(RecommandationType::class, $recommandation);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $em->persist($recommandation);
            $em->flush();

            $this->addFlash('success', 'Recommandation créée avec succès.');
            return $this->redirectToRoute('admin_recommandation_index');
        }

        return $this->render('admin/recommandation_form.html.twig', [
            'form' => $form->createView(),
        ]);
    }

    #[Route('/get-questions/{quizId}', name: 'app_recommandation_get_questions', methods: ['GET'])]
    public function getQuestions(int $quizId, QuizRepository $quizRepository): JsonResponse
    {
        $quiz = $quizRepository->find($quizId);
        if (!$quiz) {
            return new JsonResponse([]);
        }

        $choices = [];
        foreach ($quiz->getQuestions() as $question) {
            $choices[] = [
                'id' => $question->getId(),
                'text' => $question->getText()
            ];
        }

        return new JsonResponse($choices);
    }

    // ROUTES GÉNÉRIQUES ({id}) EN DERNIER

    #[Route('', name: 'admin_recommandation_index', methods: ['GET'])]
    #[Route('', name: 'app_recommandation_index', methods: ['GET'])]
    public function index(RecommandationRepository $recommandationRepository): Response
    {
        $recommandations = $recommandationRepository->findAll();

        return $this->render('admin/recommandation_list.html.twig', [
            'recommandations' => $recommandations,
        ]);
    }

    #[Route('/{id}', name: 'app_recommandation_show', methods: ['GET'])]
    public function show(Recommandation $recommandation): Response
    {
        return $this->render('recommandation/show.html.twig', [
            'recommandation' => $recommandation,
        ]);
    }

    #[Route('/{id}/edit', name: 'app_recommandation_edit', methods: ['GET', 'POST'])]
    public function edit(Request $request, Recommandation $recommandation, EntityManagerInterface $em): Response
    {
        $form = $this->createForm(RecommandationType::class, $recommandation);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $em->flush();
            $this->addFlash('success', 'Recommandation modifiée avec succès.');
            return $this->redirectToRoute('admin_recommandation_index', [], Response::HTTP_SEE_OTHER);
        }

        return $this->render('admin/recommandation_form.html.twig', [
            'form' => $form->createView(),
        ]);
    }

    #[Route('/{id}', name: 'app_recommandation_delete', methods: ['POST'])]
    public function delete(Request $request, Recommandation $recommandation, EntityManagerInterface $em): Response
    {
        if ($this->isCsrfTokenValid('delete' . $recommandation->getId(), $request->request->get('_token'))) {
            $em->remove($recommandation);
            $em->flush();
            $this->addFlash('success', 'Recommandation supprimée avec succès.');
        }

        return $this->redirectToRoute('admin_recommandation_index', [], Response::HTTP_SEE_OTHER);
    }
}