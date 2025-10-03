<?php

namespace App\Entity;

use App\Repository\EquipmentRepository;
use App\Entity\Category;
use Doctrine\ORM\Mapping as ORM;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Symfony\Component\Validator\Constraints as Assert;

/**
 * Représente un matériel géré dans le stock.
 *
 * Champs principaux: nom, référence unique, catégorie, état, quantité en stock.
 * Liens: affectations vers des magasins; utilisé par les mouvements de stock.
 */
#[ORM\Entity(repositoryClass: EquipmentRepository::class)]
#[\Symfony\Bridge\Doctrine\Validator\Constraints\UniqueEntity('reference')]
class Equipment
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column(type: 'integer')]
    /** Identifiant du matériel */
    private ?int $id = null;

    #[ORM\Column(length: 255)]
    #[Assert\NotBlank]
    /** Nom du matériel (lisible par l’humain) */
    private string $name = '';

    #[ORM\Column(length: 255, unique: true)]
    #[Assert\NotBlank]
    /** Référence unique (utile pour les recherches et inventaires) */
    private string $reference = '';

    #[ORM\ManyToOne(inversedBy: 'equipment')]
    #[ORM\JoinColumn(nullable: false)]
    #[Assert\NotNull]
    /** Catégorie à laquelle appartient ce matériel */
    private ?Category $category = null;

    #[ORM\Column(length: 50)]
    #[Assert\Choice(choices: ['neuf', 'utilisé', 'endommagé'])]
    /** État du matériel (contrainte de choix) */
    private string $state = 'neuf';

    #[ORM\Column(type: 'integer')]
    #[Assert\GreaterThanOrEqual(0)]
    /** Quantité disponible en stock (>= 0) */
    private int $stockQuantity = 0;

    #[ORM\OneToMany(mappedBy: 'equipment', targetEntity: Assignment::class, orphanRemoval: false)]
    /** Affectations liant ce matériel à des magasins */
    private Collection $assignments;

    // Soft delete: date d’archivage (null si actif)
    #[ORM\Column(type: 'datetime_immutable', nullable: true)]
    private ?\DateTimeImmutable $deletedAt = null;

    public function __construct()
    {
        $this->assignments = new ArrayCollection();
    }

    public function getId(): ?int
    {
        return $this->id;
    }

    public function getName(): string
    {
        return $this->name;
    }

    public function setName(string $name): self
    {
        $this->name = $name;
        return $this;
    }

    public function getReference(): string
    {
        return $this->reference;
    }

    public function setReference(string $reference): self
    {
        $this->reference = $reference;
        return $this;
    }

    public function getCategory(): ?Category
    {
        return $this->category;
    }

    public function setCategory(?Category $category): self
    {
        $this->category = $category;
        return $this;
    }

    public function getState(): string
    {
        return $this->state;
    }

    public function setState(string $state): self
    {
        $this->state = $state;
        return $this;
    }

    public function getStockQuantity(): int
    {
        return $this->stockQuantity;
    }

    public function setStockQuantity(int $stockQuantity): self
    {
        $this->stockQuantity = $stockQuantity;
        return $this;
    }

    /** @return Collection<int, Assignment> */
    public function getAssignments(): Collection
    {
        return $this->assignments;
    }

    public function addAssignment(Assignment $assignment): self
    {
        if (!$this->assignments->contains($assignment)) {
            $this->assignments->add($assignment);
            $assignment->setEquipment($this);
        }
        return $this;
    }

    public function removeAssignment(Assignment $assignment): self
    {
        if ($this->assignments->removeElement($assignment)) {
            if ($assignment->getEquipment() === $this) {
                $assignment->setEquipment(null);
            }
        }
        return $this;
    }

    public function getDeletedAt(): ?\DateTimeImmutable
    {
        return $this->deletedAt;
    }

    public function setDeletedAt(?\DateTimeImmutable $deletedAt): self
    {
        $this->deletedAt = $deletedAt;
        return $this;
    }
}