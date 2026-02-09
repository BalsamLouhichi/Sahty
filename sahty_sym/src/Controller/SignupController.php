<?php

namespace App\Controller;

use App\Entity\Administrateur;
use App\Entity\Patient;
use App\Entity\Medecin;
use App\Entity\ResponsableLaboratoire;
use App\Entity\ResponsableParapharmacie;
use App\Form\SignupType;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\PasswordHasher\Hasher\UserPasswordHasherInterface;
use Symfony\Component\Routing\Attribute\Route;

class SignupController extends AbstractController
{
    #[Route('/signup', name: 'app_signup', methods: ['GET', 'POST'])]
    public function signup(
        Request $request,
        EntityManagerInterface $em,
        UserPasswordHasherInterface $passwordHasher
    ): Response {
        // If user is already logged in, redirect to home
        if ($this->getUser()) {
            return $this->redirectToRoute('app_home');
        }

        $form = $this->createForm(SignupType::class);
        $form->handleRequest($request);

        if ($form->isSubmitted()) {
            if ($form->isValid()) {
                try {
                    // Get selected role
                    $roleSelected = $form->get('role')->getData();
                    
                    if (!$roleSelected) {
                        $this->addFlash('error', 'Veuillez sélectionner un rôle.');
                        return $this->render('signup/signup.html.twig', ['form' => $form]);
                    }

                    // Get password and password confirmation
                    $password = $form->get('password')->getData();
                    $confirmPassword = $request->request->get('confirm_password', '');
                    
                    if (empty($password)) {
                        $this->addFlash('error', 'Le mot de passe est obligatoire.');
                        return $this->render('signup/signup.html.twig', ['form' => $form]);
                    }
                    
                    if ($password !== $confirmPassword) {
                        $this->addFlash('error', 'Les mots de passe ne correspondent pas.');
                        return $this->render('signup/signup.html.twig', ['form' => $form]);
                    }

                    // Dynamically instantiate user based on role
                    $user = match($roleSelected) {
                        'admin' => new Administrateur(),
                        'medecin' => $this->createMedecin($request),
                        'responsable_labo' => new ResponsableLaboratoire(),
                        'responsable_para' => new ResponsableParapharmacie(),
                        'patient' => $this->createPatient($request),
                        default => throw new \InvalidArgumentException("Role invalide: {$roleSelected}")
                    };

                    // Fill common fields from form
                    $user->setPrenom($form->get('prenom')->getData())
                         ->setNom($form->get('nom')->getData())
                         ->setEmail($form->get('email')->getData())
                         ->setTelephone($form->get('telephone')->getData())
                         ->setRole($roleSelected);

                    // Set birth date if provided
                    if ($form->get('dateNaissance')->getData()) {
                        $user->setDateNaissance($form->get('dateNaissance')->getData());
                    }

                    // Hash password
                    $hashedPassword = $passwordHasher->hashPassword($user, $password);
                    $user->setPassword($hashedPassword);

                    // Handle profile photo if provided
                    $photoProfil = $form->get('photoProfil')->getData();
                    if ($photoProfil) {
                        try {
                            $newFilename = uniqid() . '.' . $photoProfil->guessExtension();
                            $uploadDir = $this->getParameter('kernel.project_dir') . '/public/uploads/profiles';
                            
                            if (!is_dir($uploadDir)) {
                                mkdir($uploadDir, 0755, true);
                            }
                            
                            $photoProfil->move($uploadDir, $newFilename);
                            $user->setPhotoProfil('/uploads/profiles/' . $newFilename);
                        } catch (\Exception $e) {
                            $this->addFlash('warning', 'La photo de profil n\'a pas pu être téléchargée.');
                        }
                    }

                    // Persist and flush
                    $em->persist($user);
                    $em->flush();

                    $this->addFlash('success', 'Votre compte a été créé avec succès! Vous pouvez maintenant vous connecter.');
                    return $this->redirectToRoute('app_login');
                    
                } catch (\Exception $e) {
                    // Log the error for debugging
                    error_log('Signup error: ' . $e->getMessage());
                    
                    if (str_contains($e->getMessage(), 'UNIQ_EMAIL') || str_contains($e->getMessage(), 'unique')) {
                        $this->addFlash('error', 'Cet email est déjà utilisé. Veuillez en choisir un autre.');
                    } else {
                        $this->addFlash('error', 'Une erreur est survenue lors de la création du compte. Veuillez réessayer.');
                    }
                }
            } else {
                // Collect form errors
                $errors = [];
                foreach ($form->getErrors(true) as $error) {
                    $errors[] = $error->getMessage();
                }
                
                if (!empty($errors)) {
                    foreach ($errors as $error) {
                        $this->addFlash('error', $error);
                    }
                } else {
                    $this->addFlash('error', 'Veuillez corriger les erreurs du formulaire.');
                }
            }
        }

        return $this->render('signup/signup.html.twig', ['form' => $form]);
    }

    /**
     * Create a Medecin user with medical-specific fields
     */
    private function createMedecin(Request $request): Medecin
    {
        $user = new Medecin();
        
        // Medical specific fields
        $user->setSpecialite($request->request->get('signup[specialite]', ''));
        $user->setAnneeExperience((int)$request->request->get('signup[annee_experience]', 0));
        $user->setGrade($request->request->get('signup[grade]', ''));
        $user->setAdresseCabinet($request->request->get('signup[adresse_cabinet]', ''));
        $user->setTelephoneCabinet($request->request->get('signup[telephone_cabinet]', ''));
        
        // Handle document PDF if provided
        $documentPdf = $request->files->get('signup[document_pdf]');
        if ($documentPdf) {
            try {
                $newFilename = uniqid() . '.' . $documentPdf->guessExtension();
                $uploadDir = $this->getParameter('kernel.project_dir') . '/public/uploads/documents';
                
                if (!is_dir($uploadDir)) {
                    mkdir($uploadDir, 0755, true);
                }
                
                $documentPdf->move($uploadDir, $newFilename);
                $user->setDocumentPdf('/uploads/documents/' . $newFilename);
            } catch (\Exception $e) {
                error_log('Document upload error: ' . $e->getMessage());
            }
        }
        
        return $user;
    }

    /**
     * Create a Patient user with patient-specific fields
     */
    private function createPatient(Request $request): Patient
    {
        $user = new Patient();
        
        // Patient specific fields
        $user->setSexe($request->request->get('signup[sexe]', ''));
        $user->setGroupeSanguin($request->request->get('signup[groupe_sanguin]', ''));
        $user->setContactUrgence($request->request->get('signup[contact_urgence]', ''));
        
        return $user;
    }
}
