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
use Symfony\Component\Routing\Annotation\Route;

class SignupController extends AbstractController
{
    #[Route('/signup', name: 'app_sign')] // Correction du nom de route
    public function signup(
        Request $request,
        EntityManagerInterface $em,
        UserPasswordHasherInterface $passwordHasher
    ): Response {
        // Créer le formulaire sans entité spécifique
        $form = $this->createForm(SignupType::class);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            // Récupération du rôle choisi
            $roleSelected = $form->get('role')->getData();

            // Validation de la confirmation du mot de passe
            $confirmPassword = $request->request->get('confirm_password');
            $password = $form->get('password')->getData();
            
            if ($password !== $confirmPassword) {
                $this->addFlash('error', 'Les mots de passe ne correspondent pas.');
                return $this->render('signup/signup.html.twig', [
                    'form' => $form->createView(),
                ]);
            }

            // Instanciation dynamique selon le rôle choisi
            switch ($roleSelected) {
                case 'admin':
                    $user = new Administrateur();
                    break;
                case 'medecin':
                    $user = new Medecin();
                    
                    // Champs spécifiques au médecin
                    $user->setSpecialite($request->request->get('specialite', ''));
                    $user->setAnneeExperience((int)$request->request->get('annee_experience', 0));
                    $user->setGrade($request->request->get('grade', ''));
                    $user->setAdresseCabinet($request->request->get('adresse_cabinet', ''));
                    $user->setTelephoneCabinet($request->request->get('telephone_cabinet', ''));
                    $user->setNomEtablissement($request->request->get('nom_etablissement', ''));
                    $user->setNumeroUrgence($request->request->get('numero_urgence', ''));
                    $user->setDisponibilite($request->request->get('disponibilite', ''));
                    
                    // Gestion du document PDF
                    $documentPdfFile = $request->files->get('document_pdf');
                    if ($documentPdfFile) {
                        $newFilename = uniqid().'.'.$documentPdfFile->guessExtension();
                        $documentPdfFile->move(
                            $this->getParameter('kernel.project_dir').'/public/uploads/documents',
                            $newFilename
                        );
                        $user->setDocumentPdf('/uploads/documents/'.$newFilename);
                    }
                    break;
                    
                case 'responsable_labo':
                    $user = new ResponsableLaboratoire();
                    $user->setLaboratoireId((int)$request->request->get('laboratoire_id', 0));
                    break;
                    
                case 'responsable_para':
                    $user = new ResponsableParapharmacie();
                    $user->setParapharmacieId((int)$request->request->get('parapharmacie_id', 0));
                    break;
                    
                case 'patient':
                default:
                    $user = new Patient();
                    
                    // Champs spécifiques au patient
                    $user->setSexe($request->request->get('sexe', ''));
                    $user->setGroupeSanguin($request->request->get('groupe_sanguin', ''));
                    $user->setContactUrgence($request->request->get('contact_urgence', ''));
                    break;
            }

            // Remplissage des champs communs depuis le formulaire
            $user->setPrenom($form->get('prenom')->getData())
                 ->setNom($form->get('nom')->getData())
                 ->setEmail($form->get('email')->getData())
                 ->setTelephone($form->get('telephone')->getData())
                 ->setRole($roleSelected);

            // Date de naissance
            if ($form->get('dateNaissance')->getData()) {
                $user->setDateNaissance($form->get('dateNaissance')->getData());
            }

            // Hash du mot de passe
            $hashedPassword = $passwordHasher->hashPassword($user, $password);
            $user->setPassword($hashedPassword);

            // Gestion de la photo de profil
            $photoProfilFile = $form->get('photoProfil')->getData();
            if ($photoProfilFile) {
                $newFilename = uniqid().'.'.$photoProfilFile->guessExtension();
                $photoProfilFile->move(
                    $this->getParameter('kernel.project_dir').'/public/uploads/photos',
                    $newFilename
                );
                $user->setPhotoProfil('/uploads/photos/'.$newFilename);
            }

            try {
                $em->persist($user);
                $em->flush();

                $this->addFlash('success', 'Votre compte a été créé avec succès !');
                return $this->redirectToRoute('app_login');
                
            } catch (\Exception $e) {
                // Gestion des erreurs (email déjà existant, etc.)
                if (strpos($e->getMessage(), 'UNIQ_EMAIL') !== false) {
                    $this->addFlash('error', 'Cet email est déjà utilisé. Veuillez en choisir un autre.');
                } else {
                    $this->addFlash('error', 'Une erreur est survenue lors de la création du compte.');
                }
                
                return $this->render('signup/signup.html.twig', [
                    'form' => $form->createView(),
                ]);
            }
        }

        return $this->render('signup/signup.html.twig', [
            'form' => $form->createView(),
        ]);
    }
}