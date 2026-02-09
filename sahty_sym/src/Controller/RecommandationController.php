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
    #[Route('', name: 'app_recommandation_index', methods: ['GET'])]
public function index(Request $request, RecommandationRepository $recommandationRepository): Response
{
    $page = $request->query->getInt('page', 1);
    $recommandations = $recommandationRepository->findPaginated($page, 10); // 10 par page par exemple

    return $this->render('recommandation/index.html.twig', [
        'recommandations' => $recommandations,
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
            return $this->redirectToRoute('app_recommandation_index');
        }

        return $this->render('recommandation/new.html.twig', [
            'form' => $form->createView(),
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
            return $this->redirectToRoute('app_recommandation_index', [], Response::HTTP_SEE_OTHER);
        }

        return $this->render('recommandation/edit.html.twig', [
            'recommandation' => $recommandation,
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

        return $this->redirectToRoute('app_recommandation_index', [], Response::HTTP_SEE_OTHER);
    }


    #[Route('/get-questions/{quizId}', name: 'app_recommandation_get_questions', methods: ['GET'])]
    public function getQuestions(int $quizId, QuizRepository $quizRepository): JsonResponse
    {
        $quiz = $quizRepository->find($quizId);
        if (!$quiz) {
            return new JsonResponse([]);
        }

        $choices = [];
        foreach ($quiz->getQuestions() ?? [] as $index => $question) {
            $choices[] = [
                'text' => $question['question'] ?? "Question #$index"
            ];
        }

        return new JsonResponse($choices);
    }
    
}