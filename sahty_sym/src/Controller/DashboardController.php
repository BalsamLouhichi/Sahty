<?php

namespace App\Controller;

use App\Repository\RendezVousRepository;
use App\Repository\FicheMedicaleRepository;
use App\Repository\PatientRepository;
use App\Repository\MedecinRepository;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Component\HttpFoundation\Session\SessionInterface;

class DashboardController extends AbstractController
{
    #[Route('/dashboard', name: 'app_dashboard')]
    public function index(
        RendezVousRepository $rendezVousRepository,
        FicheMedicaleRepository $ficheMedicaleRepository,
        PatientRepository $patientRepository,
        MedecinRepository $medecinRepository,
        SessionInterface $session
    ): Response
    {
        // Vérifier le cache en session (5 minutes)
        $lastUpdate = $session->get('dashboard_last_update');
        $cachedData = $session->get('dashboard_data');
        
        if ($lastUpdate && $cachedData && (time() - $lastUpdate < 300)) {
            return $this->render('dashboard/index.html.twig', $cachedData);
        }
        
        // ================== COMPTAGES RAPIDES ==================
        $totalAppointments = $rendezVousRepository->count([]);
        $totalMedicalRecords = $ficheMedicaleRepository->count([]);
        $totalPatients = $patientRepository->count([]);
        $totalMedecins = $medecinRepository->count([]);
        
        // ================== RENDEZ-VOUS AUJOURD'HUI ==================
        $today = new \DateTime();
        $today->setTime(0, 0, 0);
        $tomorrow = clone $today;
        $tomorrow->modify('+1 day');
        
        $appointmentsToday = $rendezVousRepository->createQueryBuilder('r')
            ->select('COUNT(r.id)')
            ->where('r.dateRdv BETWEEN :start AND :end')
            ->setParameter('start', $today)
            ->setParameter('end', $tomorrow)
            ->getQuery()
            ->getSingleScalarResult();
        
        // ================== REQUÊTE UNIQUE POUR LES RDV ==================
        $appointments = $rendezVousRepository->createQueryBuilder('r')
            ->select('r.dateRdv, r.statut, r.raison, r.heureRdv')
            ->addSelect('p.id as patient_id, p.nom as patient_nom, p.prenom as patient_prenom')
            ->addSelect('m.id as medecin_id, m.nom as medecin_nom, m.prenom as medecin_prenom')
            ->leftJoin('r.patient', 'p')
            ->leftJoin('r.medecin', 'm')
            ->orderBy('r.dateRdv', 'DESC')
            ->addOrderBy('r.heureRdv', 'DESC')
            ->setMaxResults(50)
            ->getQuery()
            ->getResult();
        
        // ================== TRAITEMENT DES DONNÉES RDV ==================
        $appointmentsData = array_fill(0, 12, 0);
        $statusCounts = [];
        $medecinCounts = [];
        $patientCounts = [];
        $recentAppointments = [];
        
        foreach ($appointments as $app) {
            // Comptage par mois
            if ($app['dateRdv']) {
                $month = (int)$app['dateRdv']->format('n') - 1;
                $appointmentsData[$month]++;
            }
            
            // Comptage par statut
            if (!empty($app['statut'])) {
                $statusCounts[$app['statut']] = ($statusCounts[$app['statut']] ?? 0) + 1;
            }
            
            // Comptage par médecin
            if ($app['medecin_id']) {
                $medecinName = 'Dr. ' . trim(($app['medecin_prenom'] ?? '') . ' ' . ($app['medecin_nom'] ?? ''));
                $medecinCounts[$medecinName] = ($medecinCounts[$medecinName] ?? 0) + 1;
            }
            
            // Comptage par patient
            if ($app['patient_id']) {
                $patientName = trim(($app['patient_prenom'] ?? '') . ' ' . ($app['patient_nom'] ?? ''));
                $patientCounts[$patientName] = ($patientCounts[$patientName] ?? 0) + 1;
            }
            
            // Derniers rendez-vous (5 premiers)
            if (count($recentAppointments) < 5) {
                $recentAppointments[] = [
                    'patient' => [
                        'nom' => $app['patient_nom'] ?? '', 
                        'prenom' => $app['patient_prenom'] ?? ''
                    ],
                    'medecin' => [
                        'nom' => $app['medecin_nom'] ?? '', 
                        'prenom' => $app['medecin_prenom'] ?? ''
                    ],
                    'dateRdv' => $app['dateRdv'],
                    'heureRdv' => $app['heureRdv'] ?? null,
                    'statut' => $app['statut'],
                    'raison' => $app['raison']
                ];
            }
        }
        
        // ================== FICHES MÉDICALES ==================
        $fiches = $ficheMedicaleRepository->createQueryBuilder('f')
            ->select('f.categorieImc, f.statut, f.creeLe, f.imc')
            ->addSelect('p.id as patient_id, p.nom as patient_nom, p.prenom as patient_prenom')
            ->leftJoin('f.patient', 'p')
            ->orderBy('f.creeLe', 'DESC')
            ->setMaxResults(20)
            ->getQuery()
            ->getResult();
        
        $imcCounts = [];
        $ficheStatusCounts = [];
        $recentMedicalRecords = [];
        
        foreach ($fiches as $fiche) {
            // Comptage IMC
            if (!empty($fiche['categorieImc'])) {
                $imcCounts[$fiche['categorieImc']] = ($imcCounts[$fiche['categorieImc']] ?? 0) + 1;
            }
            
            // Comptage statut des fiches
            if (!empty($fiche['statut'])) {
                $ficheStatusCounts[$fiche['statut']] = ($ficheStatusCounts[$fiche['statut']] ?? 0) + 1;
            }
            
            // Dernières fiches (5 premiers)
            if (count($recentMedicalRecords) < 5) {
                $recentMedicalRecords[] = [
                    'patient' => [
                        'nom' => $fiche['patient_nom'] ?? '', 
                        'prenom' => $fiche['patient_prenom'] ?? ''
                    ],
                    'creeLe' => $fiche['creeLe'],
                    'imc' => $fiche['imc'] ?? null,
                    'categorieImc' => $fiche['categorieImc'] ?? 'N/A',
                    'statut' => $fiche['statut'] ?? 'Non défini'
                ];
            }
        }
        
        // ================== TRI ET LIMITATION ==================
        arsort($medecinCounts);
        arsort($patientCounts);
        arsort($imcCounts);
        arsort($ficheStatusCounts);
        
        $medecinNames = array_keys(array_slice($medecinCounts, 0, 5, true));
        $medecinCounts = array_values(array_slice($medecinCounts, 0, 5, true));
        
        $patientNames = array_keys(array_slice($patientCounts, 0, 5, true));
        $patientCounts = array_values(array_slice($patientCounts, 0, 5, true));
        
        $imcLabels = array_keys(array_slice($imcCounts, 0, 5, true));
        $imcCounts = array_values(array_slice($imcCounts, 0, 5, true));
        
        $statusLabels = array_keys($statusCounts);
        $statusCounts = array_values($statusCounts);
        
        $ficheStatusLabels = array_keys($ficheStatusCounts);
        $ficheStatusCounts = array_values($ficheStatusCounts);
        
        // Valeurs par défaut si aucune donnée
        if (empty($medecinNames)) {
            $medecinNames = ['Dr. Emily White', 'Dr. Karen Brown', 'Dr. Jane Doe', 'Dr. John Smith', 'Dr. Lisa Lee'];
            $medecinCounts = [25, 20, 18, 15, 12];
        }
        
        if (empty($statusLabels)) {
            $statusLabels = ['Confirmé', 'En attente', 'Annulé', 'Terminé'];
            $statusCounts = [45, 30, 15, 60];
        }
        
        if (empty($ficheStatusLabels)) {
            $ficheStatusLabels = ['Actif', 'Inactif'];
            $ficheStatusCounts = [30, 5];
        }
        
        if (empty($imcLabels)) {
            $imcLabels = ['Maigreur', 'Normal', 'Surpoids', 'Obésité'];
            $imcCounts = [10, 25, 15, 8];
        }
        
        if (empty($patientNames)) {
            $patientNames = ['Jean Dupont', 'Marie Martin', 'Pierre Durand', 'Sophie Bernard', 'Thomas Petit'];
            $patientCounts = [8, 7, 6, 5, 4];
        }
        
        // ================== MOIS ==================
        $months = ['Janvier', 'Février', 'Mars', 'Avril', 'Mai', 'Juin', 
                   'Juillet', 'Août', 'Septembre', 'Octobre', 'Novembre', 'Décembre'];
        
        // ================== PRÉPARATION DES DONNÉES ==================
        $data = [
            'stats' => [
                'total_appointments' => $totalAppointments,
                'total_medical_records' => $totalMedicalRecords,
                'total_patients' => $totalPatients,
                'total_medecins' => $totalMedecins,
                'appointments_today' => (int)$appointmentsToday,
            ],
            'charts' => [
                'months' => json_encode($months, JSON_UNESCAPED_UNICODE),
                'appointments_data' => json_encode($appointmentsData),
                'medecin_names' => json_encode($medecinNames, JSON_UNESCAPED_UNICODE),
                'medecin_counts' => json_encode($medecinCounts),
                'patient_names' => json_encode($patientNames, JSON_UNESCAPED_UNICODE),
                'patient_counts' => json_encode($patientCounts),
                'imc_labels' => json_encode($imcLabels, JSON_UNESCAPED_UNICODE),
                'imc_counts' => json_encode($imcCounts),
                'status_labels' => json_encode($statusLabels, JSON_UNESCAPED_UNICODE),
                'status_counts' => json_encode($statusCounts),
                'fiche_status_labels' => json_encode($ficheStatusLabels, JSON_UNESCAPED_UNICODE),
                'fiche_status_counts' => json_encode($ficheStatusCounts),
            ],
            'recent_appointments' => $recentAppointments,
            'recent_medical_records' => $recentMedicalRecords,
        ];
        
        // ================== MISE EN CACHE ==================
        $session->set('dashboard_last_update', time());
        $session->set('dashboard_data', $data);

        return $this->render('dashboard/index.html.twig', $data);
    }
}