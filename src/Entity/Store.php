<?php

namespace App\Entity;

use App\Repository\StoreRepository;
use Doctrine\ORM\Mapping as ORM;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Symfony\Component\Validator\Constraints as Assert;

/**
 * Représente un magasin (lieu de stockage / point de distribution).
 *
 * Champs principaux: nom, adresse, responsable.
 * Liens: possède des affectations (Assignment) liées aux matériels.
 */
#[ORM\Entity(repositoryClass: StoreRepository::class)]
class Store
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column(type: 'integer')]
    /** Identifiant du magasin */
    private ?int $id = null;

    #[ORM\Column(length: 255)]
    #[Assert\NotBlank]
    /** Nom du magasin (obligatoire) */
    private string $name = '';

    #[ORM\Column(length: 255)]
    #[Assert\NotBlank]
    /** Adresse du magasin (obligatoire) */
    private string $address = '';

    #[ORM\Column(length: 255, nullable: true)]
    /** Nom du responsable (optionnel) */
    private ?string $sr = null;

    #[ORM\Column(length: 255, nullable: true)]
    /** Code FR du magasin */
    private ?string $codeFR = null;

    #[ORM\Column(length: 255, nullable: true)]
    /** Statut du magasin */
    private ?string $statut = null;

    #[ORM\Column(length: 255, nullable: true)]
    /** Type de projet pour le magasin */
    private ?string $typeDeProjet = null;

    #[ORM\Column(type: 'date', nullable: true)]
    /** Date d'ouverture du magasin */
    private ?\DateTimeInterface $dateOuverture = null;

    #[ORM\OneToMany(mappedBy: 'store', targetEntity: Assignment::class, orphanRemoval: false)]
    /** Affectations liées à ce magasin */
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

    public function getAddress(): string
    {
        return $this->address;
    }

    public function setAddress(string $address): self
    {
        $this->address = $address;
        return $this;
    }

    public function getSr(): ?string
    {
        return $this->sr;
    }

    public function setSr(?string $sr): self
    {
        $this->sr = $sr;
        return $this;
    }

    public function getCodeFR(): ?string
    {
        return $this->codeFR;
    }

    public function setCodeFR(?string $codeFR): self
    {
        $this->codeFR = $codeFR;
        return $this;
    }

    public function getStatut(): ?string
    {
        return $this->statut;
    }

    public function setStatut(?string $statut): self
    {
        $this->statut = $statut;
        return $this;
    }

    public function getTypeDeProjet(): ?string
    {
        return $this->typeDeProjet;
    }

    public function setTypeDeProjet(?string $typeDeProjet): self
    {
        $this->typeDeProjet = $typeDeProjet;
        return $this;
    }

    public function getDateOuverture(): ?\DateTimeInterface
    {
        return $this->dateOuverture;
    }

    public function setDateOuverture(?\DateTimeInterface $dateOuverture): self
    {
        $this->dateOuverture = $dateOuverture;
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
            $assignment->setStore($this);
        }
        return $this;
    }

    public function removeAssignment(Assignment $assignment): self
    {
        if ($this->assignments->removeElement($assignment)) {
            if ($assignment->getStore() === $this) {
                $assignment->setStore(null);
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