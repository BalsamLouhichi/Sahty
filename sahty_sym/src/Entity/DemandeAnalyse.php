<?php

namespace App\Entity;

use App\Repository\DemandeAnalyseRepository;
use Doctrine\ORM\Mapping as ORM;

#[ORM\Entity(repositoryClass: DemandeAnalyseRepository::class)]
#[ORM\Table(name: "demande_analyse")]
class DemandeAnalyse
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column]
    private ?int $id = null;

    #[ORM\ManyToOne(targetEntity: Patient::class, inversedBy: 'demandeAnalyses')]
    #[ORM\JoinColumn(name: 'patient_id', nullable: false)]
    private ?Patient $patient = null;

    #[ORM\ManyToOne(targetEntity: Medecin::class)]
    #[ORM\JoinColumn(name: 'medecin_id', referencedColumnName: 'id', nullable: true)]
    private ?Medecin $medecin = null;

    #[ORM\ManyToOne(targetEntity: Laboratoire::class, inversedBy: 'demandeAnalyses')]
    #[ORM\JoinColumn(name: 'laboratoire_id', nullable: false)]
    private ?Laboratoire $laboratoire = null;

    #[ORM\Column(length: 255)]
    private ?string $type_bilan = null;

    // CORRECTION : Utiliser 'en_attente' pour cohérence avec le contrôleur et FormType
    #[ORM\Column(length: 50)]
    private string $statut = 'en_attente';

    #[ORM\Column]
    private \DateTimeImmutable $date_demande;

    #[ORM\Column(type: 'datetime', nullable: true)]
    private ?\DateTimeInterface $programme_le = null;

    #[ORM\Column(type: 'datetime', nullable: true)]
    private ?\DateTimeInterface $envoye_le = null;

    #[ORM\Column(length: 255, nullable: true)]
    private ?string $notes = null;

    // CORRECTION : Utiliser 'normale' pour cohérence avec le FormType
    #[ORM\Column(length: 20)]
    private string $priorite = 'normale';

    #[ORM\Column(type: 'json', nullable: true)]
    private ?array $analyses = [];

    public function __construct()
    {
        $this->date_demande = new \DateTimeImmutable();
        $this->analyses = [];
    }

    public function getId(): ?int
    {
        return $this->id;
    }

    public function getPatient(): ?Patient
    {
        return $this->patient;
    }

    public function setPatient(?Patient $patient): static
    {
        $this->patient = $patient;
        return $this;
    }

    public function getMedecin(): ?Medecin
    {
        return $this->medecin;
    }

    public function setMedecin(?Medecin $medecin): static
    {
        $this->medecin = $medecin;
        return $this;
    }

    public function getLaboratoire(): ?Laboratoire
    {
        return $this->laboratoire;
    }

    public function setLaboratoire(?Laboratoire $laboratoire): static
    {
        $this->laboratoire = $laboratoire;
        return $this;
    }

    public function getTypeBilan(): ?string
    {
        return $this->type_bilan;
    }

    public function setTypeBilan(string $type_bilan): static
    {
        $this->type_bilan = $type_bilan;
        return $this;
    }

    public function getStatut(): string
    {
        return $this->statut;
    }

    public function setStatut(string $statut): static
    {
        $this->statut = $statut;
        return $this;
    }

    public function getDateDemande(): \DateTimeImmutable
    {
        return $this->date_demande;
    }

    public function setDateDemande(\DateTimeImmutable $date_demande): static
    {
        $this->date_demande = $date_demande;
        return $this;
    }

    public function getProgrammeLe(): ?\DateTimeInterface
    {
        return $this->programme_le;
    }

    public function setProgrammeLe(?\DateTimeInterface $programme_le): static
    {
        $this->programme_le = $programme_le;
        return $this;
    }

    public function getEnvoyeLe(): ?\DateTimeInterface
    {
        return $this->envoye_le;
    }

    public function setEnvoyeLe(?\DateTimeInterface $envoye_le): static
    {
        $this->envoye_le = $envoye_le;
        return $this;
    }

    public function getNotes(): ?string
    {
        return $this->notes;
    }

    public function setNotes(?string $notes): static
    {
        $this->notes = $notes;
        return $this;
    }

    public function getPriorite(): string
    {
        return $this->priorite;
    }

    public function setPriorite(string $priorite): static
    {
        $this->priorite = $priorite;
        return $this;
    }

    public function getAnalyses(): ?array
    {
        return $this->analyses;
    }

    public function setAnalyses(?array $analyses): static
    {
        $this->analyses = $analyses;
        return $this;
    }

    public function addAnalyse(string $analyse): static
    {
        if (!in_array($analyse, $this->analyses)) {
            $this->analyses[] = $analyse;
        }
        return $this;
    }

    public function removeAnalyse(string $analyse): static
    {
        if (($key = array_search($analyse, $this->analyses)) !== false) {
            unset($this->analyses[$key]);
            $this->analyses = array_values($this->analyses);
        }
        return $this;
    }

    public function getNbAnalyses(): int
    {
        return count($this->analyses);
    }

    public function __toString(): string
    {
        return sprintf('Demande #%d - %s', $this->id, $this->type_bilan);
    }
    
    // Méthode utilitaire pour afficher le statut de manière lisible
    public function getStatutLibelle(): string
    {
        $statuts = [
            'en_attente' => 'En attente',
            'programme' => 'Programmé',
            'envoye' => 'Envoyé',
            'annule' => 'Annulé'
        ];
        
        return $statuts[$this->statut] ?? $this->statut;
    }
    
    // Méthode utilitaire pour afficher la priorité de manière lisible
    public function getPrioriteLibelle(): string
    {
        $priorites = [
            'normale' => 'Normale',
            'haute' => 'Haute',
            'urgent' => 'Urgent'
        ];
        
        return $priorites[$this->priorite] ?? $this->priorite;
    }
}