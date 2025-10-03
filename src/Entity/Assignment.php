<?php

namespace App\Entity;

use App\Repository\AssignmentRepository;
use Doctrine\ORM\Mapping as ORM;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Symfony\Component\Validator\Constraints as Assert;

/**
 * Représente une affectation d’un matériel vers un magasin.
 *
 * Suivi: quantité affectée, quantité retournée, dates, créateur et retourneur.
 * Liens: mouvements (ajout/retour) pour tracer le stock dans le temps.
 */
#[ORM\Entity(repositoryClass: AssignmentRepository::class)]
class Assignment
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column(type: 'integer')]
    /** Identifiant de l’affectation */
    private ?int $id = null;

    #[ORM\ManyToOne(inversedBy: 'assignments')]
    #[ORM\JoinColumn(nullable: false)]
    #[Assert\NotNull]
    /** Matériel affecté */
    private ?Equipment $equipment = null;

    #[ORM\ManyToOne(inversedBy: 'assignments')]
    #[ORM\JoinColumn(nullable: false)]
    #[Assert\NotNull]
    /** Magasin concerné par l’affectation */
    private ?Store $store = null;

    #[ORM\Column(type: 'datetime_immutable')]
    /** Date de l’affectation */
    private \DateTimeImmutable $assignedAt;

    #[ORM\Column(type: 'integer')]
    #[Assert\GreaterThan(0)]
    /** Quantité affectée (strictement > 0) */
    private int $quantity = 1;

    #[ORM\Column(type: 'integer')]
    #[Assert\GreaterThanOrEqual(0)]
    /** Quantité déjà retournée (>= 0) */
    private int $returnedQuantity = 0;

    #[ORM\Column(type: 'datetime_immutable', nullable: true)]
    /** Date du retour (si l’affectation est totalement/partiellement retournée) */
    private ?\DateTimeImmutable $returnedAt = null;

    #[ORM\OneToMany(mappedBy: 'assignment', targetEntity: \App\Entity\Movement::class, orphanRemoval: true)]
    /** Mouvements (ajouts/retours) liés à cette affectation */
    private Collection $movements;

    #[ORM\ManyToOne(targetEntity: \App\Entity\User::class)]
    #[ORM\JoinColumn(nullable: true)]
    /** Utilisateur qui a créé l’affectation */
    private ?\App\Entity\User $createdBy = null;

    #[ORM\ManyToOne(targetEntity: \App\Entity\User::class)]
    #[ORM\JoinColumn(nullable: true)]
    /** Utilisateur qui a enregistré le retour */
    private ?\App\Entity\User $returnedBy = null;

    // Soft delete: date d’archivage (null si actif)
    #[ORM\Column(type: 'datetime_immutable', nullable: true)]
    private ?\DateTimeImmutable $deletedAt = null;

    public function __construct()
    {
        $this->assignedAt = new \DateTimeImmutable();
        $this->movements = new ArrayCollection();
    }

    public function getId(): ?int
    {
        return $this->id;
    }

    public function getEquipment(): ?Equipment
    {
        return $this->equipment;
    }

    public function setEquipment(?Equipment $equipment): self
    {
        $this->equipment = $equipment;
        return $this;
    }

    public function getStore(): ?Store
    {
        return $this->store;
    }

    public function setStore(?Store $store): self
    {
        $this->store = $store;
        return $this;
    }

    public function getAssignedAt(): \DateTimeImmutable
    {
        return $this->assignedAt;
    }

    public function setAssignedAt(\DateTimeImmutable $assignedAt): self
    {
        $this->assignedAt = $assignedAt;
        return $this;
    }

    public function getQuantity(): int
    {
        return $this->quantity;
    }

    public function setQuantity(int $quantity): self
    {
        $this->quantity = $quantity;
        return $this;
    }

    public function getReturnedQuantity(): int
    {
        return $this->returnedQuantity;
    }

    public function setReturnedQuantity(int $returnedQuantity): self
    {
        $this->returnedQuantity = $returnedQuantity;
        return $this;
    }

    public function getReturnedAt(): ?\DateTimeImmutable
    {
        return $this->returnedAt;
    }

    public function setReturnedAt(?\DateTimeImmutable $returnedAt): self
    {
        $this->returnedAt = $returnedAt;
        return $this;
    }

    public function getCreatedBy(): ?\App\Entity\User { return $this->createdBy; }
    public function setCreatedBy(?\App\Entity\User $user): self { $this->createdBy = $user; return $this; }
    public function getReturnedBy(): ?\App\Entity\User { return $this->returnedBy; }
    public function setReturnedBy(?\App\Entity\User $user): self { $this->returnedBy = $user; return $this; }

    /**
     * @return Collection<int, \App\Entity\Movement>
     */
    public function getMovements(): Collection
    {
        return $this->movements;
    }

    public function addMovement(\App\Entity\Movement $movement): self
    {
        if (!$this->movements->contains($movement)) {
            $this->movements->add($movement);
            $movement->setAssignment($this);
        }
        return $this;
    }

    public function removeMovement(\App\Entity\Movement $movement): self
    {
        if ($this->movements->removeElement($movement)) {
            if ($movement->getAssignment() === $this) {
                $movement->setAssignment(null);
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