<?php

namespace App\Entity;

use App\Repository\RecommandationRepository;
use Doctrine\ORM\Mapping as ORM;
use Symfony\Component\Validator\Constraints as Assert;

#[ORM\Entity(repositoryClass: RecommandationRepository::class)]
class Recommandation
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column]
    private ?int $id = null;

    #[ORM\Column(length: 150)]
    #[Assert\NotBlank(message: "Le nom est obligatoire.")]
    #[Assert\Length(
        min: 3,
        max: 150,
        minMessage: "Le nom doit contenir au moins {{ limit }} caractères.",
        maxMessage: "Le nom ne peut pas dépasser {{ limit }} caractères."
    )]
    private ?string $name = null;

    #[ORM\Column(type: 'text', nullable: true)]
    #[Assert\Length(
        max: 2000,
        maxMessage: "La description ne peut pas dépasser {{ limit }} caractères."
    )]
    private ?string $description = null;

    #[ORM\Column]
    #[Assert\NotBlank(message: "Le score minimum est obligatoire.")]
    #[Assert\PositiveOrZero(message: "Le score minimum doit être positif ou zéro.")]
    private ?int $min_score = null;

    #[ORM\Column]
    #[Assert\NotBlank(message: "Le score maximum est obligatoire.")]
    #[Assert\PositiveOrZero(message: "Le score maximum doit être positif ou zéro.")]
    #[Assert\Expression(
        "this.getMaxScore() >= this.getMinScore()",
        message: "Le score maximum doit être supérieur ou égal au score minimum."
    )]
    private ?int $max_score = null;

    #[ORM\Column(length: 500)]
    #[Assert\NotBlank(message: "La question / problème est obligatoire.")]
    #[Assert\Length(
        min: 5,
        max: 500,
        minMessage: "Minimum {{ limit }} caractères.",
        maxMessage: "Maximum {{ limit }} caractères."
    )]
    private ?string $type_probleme = null;

    #[ORM\Column(type: 'datetime')]
    private \DateTimeInterface $createdAt;

    #[ORM\ManyToOne]
    #[ORM\JoinColumn(nullable: false)]
    #[Assert\NotNull(message: "Veuillez sélectionner un quiz.")]
    private ?Quiz $quiz = null;

    public function __construct()
    {
        $this->createdAt = new \DateTime();
    }

    // --------------------
    // Getters et Setters
    // --------------------

    public function getId(): ?int
    {
        return $this->id;
    }

    public function getName(): ?string
    {
        return $this->name;
    }

    public function setName(?string $name): static
    {
        $this->name = $name;
        return $this;
    }

    public function getDescription(): ?string
    {
        return $this->description;
    }

    public function setDescription(?string $description): static
    {
        $this->description = $description;
        return $this;
    }

    public function getMinScore(): ?int
    {
        return $this->min_score;
    }

    public function setMinScore(?int $min_score): static
    {
        $this->min_score = $min_score;
        return $this;
    }

    public function getMaxScore(): ?int
    {
        return $this->max_score;
    }

    public function setMaxScore(?int $max_score): static
    {
        $this->max_score = $max_score;
        return $this;
    }

    public function getTypeProbleme(): ?string
    {
        return $this->type_probleme;
    }

    public function setTypeProbleme(?string $type_probleme): static
    {
        $this->type_probleme = $type_probleme;
        return $this;
    }

    public function getCreatedAt(): ?\DateTimeInterface
    {
        return $this->createdAt;
    }

    public function setCreatedAt(?\DateTimeInterface $createdAt): static
    {
        $this->createdAt = $createdAt;
        return $this;
    }

    public function getQuiz(): ?Quiz
    {
        return $this->quiz;
    }

    public function setQuiz(?Quiz $quiz): static
    {
        $this->quiz = $quiz;
        return $this;
    }
}
