<?php

namespace App\Controller;

use App\Entity\ResponsableLaboratoire;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Security\Http\Authentication\AuthenticationUtils;

class SecurityController extends AbstractController
{
    #[Route('/login', name: 'app_login', methods: ['GET', 'POST'])]
    public function login(AuthenticationUtils $authenticationUtils): Response
    {
        // If the user is already logged in
        if ($this->getUser()) {
            // If it's an admin, redirect to admin dashboard (from Balsam)
            if ($this->isGranted('ROLE_ADMIN')) {
                return $this->redirectToRoute('admin_index');
            }

            // Other users redirect to home (from you)
            return $this->redirectToRoute('app_home');
        }

        // Get the login error if there is one
        $error = $authenticationUtils->getLastAuthenticationError();
        
        // Last email entered by the user (from you - with trim)
        $lastEmail = trim($authenticationUtils->getLastUsername() ?? '');

        return $this->render('securityL/login.html.twig', [
            'last_email' => $lastEmail,  // Your variable name
            'error' => $error,
        ]);
    }

    

    

    #[Route('/logout', name: 'app_logout', methods: ['GET'])]
    public function logout(): void
    {
        // This method will be handled by Symfony's logout key in the firewall
        throw new \LogicException('This method can be blank - it will be intercepted by the logout key on your firewall.');
    }

    // ========== NEW ROUTES FROM BALSAM ==========

    #[Route('/profile', name: 'app_profile')]
    public function profile(): Response
    {
        // Security check
        $this->denyAccessUnlessGranted('IS_AUTHENTICATED_FULLY');

        return $this->render('profile/index.html.twig', [
            'user' => $this->getUser(),
        ]);
    }

    #[Route('/login/redirect', name: 'app_login_redirect')]
    public function loginRedirect(): Response
    {
        $this->denyAccessUnlessGranted('IS_AUTHENTICATED_FULLY');

        if ($this->isGranted('ROLE_ADMIN')) {
            return $this->redirectToRoute('admin');
        }

        if ($this->isGranted('ROLE_RESPONSABLE_LABO')) {
            $user = $this->getUser();
            if ($user instanceof ResponsableLaboratoire && !$user->getLaboratoire()) {
                return $this->redirectToRoute('app_labo_new');
            }

            return $this->redirectToRoute('app_demande_analyse_index');
        }

        if ($this->isGranted('ROLE_MEDECIN')) {
            return $this->redirectToRoute('app_demande_analyse_index');
        }

        if ($this->isGranted('ROLE_PATIENT')) {
            return $this->redirectToRoute('app_labo_index');
        }
           // MODIFICATION : Redirection pour responsable parapharmacie
        if ($this->isGranted('ROLE_RESPONSABLE_PARA')) {
            if ($user instanceof ResponsableParapharmacie) {
                // Si c'est la première connexion OU si la parapharmacie n'est pas configurée
                if ($user->isPremiereConnexion() || !$user->getParapharmacie()) {
                    return $this->redirectToRoute('app_responsable_para_configurer');
                }
                return $this->redirectToRoute('app_responsable_dashboard');
            }
        }

        return $this->redirectToRoute('app_profile');
    }
}