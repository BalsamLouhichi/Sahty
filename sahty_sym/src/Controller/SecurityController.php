<?php

namespace App\Controller;

use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Component\Security\Http\Authentication\AuthenticationUtils;

class SecurityController extends AbstractController
{
    #[Route('/login', name: 'app_login')]
    public function login(AuthenticationUtils $authenticationUtils): Response
    {
        // Si l'utilisateur est déjà connecté, redirigez-le vers son profil
        if ($this->getUser()) {
            return $this->redirectToRoute('app_profile');
        }

        // Récupérer l'erreur de login s'il y en a une
        $error = $authenticationUtils->getLastAuthenticationError();
        
        // Dernier email saisi par l'utilisateur
        $lastUsername = $authenticationUtils->getLastUsername();

        return $this->render('securityL/login.html.twig', [
            'last_username' => $lastUsername,
            'error' => $error,
        ]);
    }

    #[Route('/logout', name: 'app_logout')]
    public function logout(): void
    {
        // Cette méthode est gérée par Symfony
        throw new \LogicException('This method will be intercepted by the logout key on your firewall.');
    }

    #[Route('/profile', name: 'app_profile')]
    public function profile(): Response
    {
        // S'assurer que l'utilisateur est connecté
        $this->denyAccessUnlessGranted('IS_AUTHENTICATED_FULLY');
        
        $user = $this->getUser();
        
        return $this->render('profile/index.html.twig', [
            'user' => $user,
        ]);
    }
}