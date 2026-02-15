<?php
// src/Controller/PanierController.php

namespace App\Controller;

use App\Entity\Produit;
use App\Entity\Commande;
use App\Entity\Parapharmacie;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpFoundation\Session\SessionInterface;
use Symfony\Component\Routing\Annotation\Route;

class PanierController extends AbstractController
{
    #[Route('/panier', name: 'app_panier')]
    public function index(SessionInterface $session): Response
    {
        $panier = $session->get('panier', []);
        $total = 0;
        
        foreach ($panier as $item) {
            $total += $item['prix'] * $item['quantite'];
        }
        
        return $this->render('panier/index.html.twig', [
            'panier' => $panier,
            'total' => $total
        ]);
    }
    
    #[Route('/panier/ajouter/{id}', name: 'app_panier_ajouter', methods: ['POST'])]
    public function ajouter(Request $request, Produit $produit, SessionInterface $session): Response
    {
        $quantite = $request->request->get('quantite', 1);
        $pharmacieId = $request->request->get('pharmacie');
        $prix = $produit->getPrix();
        
        // Récupérer le panier de la session
        $panier = $session->get('panier', []);
        
        // Vérifier si le produit est déjà dans le panier
        $found = false;
        foreach ($panier as &$item) {
            if ($item['produit_id'] == $produit->getId() && $item['pharmacie_id'] == $pharmacieId) {
                $item['quantite'] += $quantite;
                $found = true;
                break;
            }
        }
        
        if (!$found) {
            // Ajouter le produit au panier
            $panier[] = [
                'produit_id' => $produit->getId(),
                'produit_nom' => $produit->getNom(),
                'produit_image' => $produit->getImage(),
                'pharmacie_id' => $pharmacieId,
                'pharmacie_nom' => $pharmacieId ? $this->getParapharmacieNom($pharmacieId) : 'Non spécifiée',
                'quantite' => $quantite,
                'prix' => $prix
            ];
        }
        
        // Sauvegarder le panier dans la session
        $session->set('panier', $panier);
        
        $this->addFlash('success', 'Produit ajouté au panier avec succès !');
        
        // Rediriger vers la page précédente ou vers le panier
        $referer = $request->headers->get('referer');
        return $this->redirect($referer ?: $this->generateUrl('app_panier'));
    }
    
    #[Route('/panier/supprimer/{index}', name: 'app_panier_supprimer')]
    public function supprimer(int $index, SessionInterface $session): Response
    {
        $panier = $session->get('panier', []);
        
        if (isset($panier[$index])) {
            unset($panier[$index]);
            $panier = array_values($panier); // Réindexer le tableau
            $session->set('panier', $panier);
            $this->addFlash('success', 'Produit retiré du panier.');
        }
        
        return $this->redirectToRoute('app_panier');
    }
    
    #[Route('/panier/modifier/{index}', name: 'app_panier_modifier', methods: ['POST'])]
    public function modifier(int $index, Request $request, SessionInterface $session): Response
    {
        $panier = $session->get('panier', []);
        $quantite = $request->request->get('quantite');
        
        if (isset($panier[$index]) && $quantite > 0) {
            $panier[$index]['quantite'] = $quantite;
            $session->set('panier', $panier);
            $this->addFlash('success', 'Quantité mise à jour.');
        }
        
        return $this->redirectToRoute('app_panier');
    }
    
    #[Route('/panier/vider', name: 'app_panier_vider')]
    public function vider(SessionInterface $session): Response
    {
        $session->remove('panier');
        $this->addFlash('success', 'Panier vidé avec succès.');
        
        return $this->redirectToRoute('app_panier');
    }
    
    #[Route('/panier/commander', name: 'app_panier_commander')]
    public function commander(SessionInterface $session, EntityManagerInterface $entityManager): Response
    {
        $panier = $session->get('panier', []);
        
        if (empty($panier)) {
            $this->addFlash('error', 'Votre panier est vide.');
            return $this->redirectToRoute('app_panier');
        }
        
        // Rediriger vers le formulaire de commande avec les informations du panier
        return $this->redirectToRoute('app_commande_formulaire_panier');
    }
    
    private function getParapharmacieNom($id): string
    {
        // Cette méthode sera implémentée si nécessaire
        return 'Pharmacie';
    }
}