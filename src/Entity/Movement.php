<?php

namespace App\Entity;

use App\Repository\MovementRepository;
use Doctrine\ORM\Mapping as ORM;

/**
 * Trace un mouvement de stock.
 *
 * Type: 'ajout' (sortie du stock vers un magasin) ou 'retour' (retour au stock).
 * Lien possible vers Assignment pour contextualiser l’opération.
 */
#[ORM\Entity(repositoryClass: MovementRepository::class)]
class Movement
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column(type: 'integer')]
    /** Identifiant du mouvement */
    private ?int $id = null;

    #[ORM\ManyToOne(targetEntity: Assignment::class)]
    #[ORM\JoinColumn(nullable: true, onDelete: 'CASCADE')]
    /** Affectation liée (peut être null si mouvement global) */
    private ?Assignment $assignment = null;

    #[ORM\ManyToOne(targetEntity: Equipment::class)]
    #[ORM\JoinColumn(nullable: false)]
    /** Matériel impacté par le mouvement */
    private ?Equipment $equipment = null;

    #[ORM\ManyToOne(targetEntity: Store::class)]
    #[ORM\JoinColumn(nullable: true)]
    /** Magasin concerné (pour les ajouts vers un magasin) */
    private ?Store $store = null;

    // 'ajout' ou 'retour'
    #[ORM\Column(length: 20)]
    /** Type de mouvement (ajout/retour) */
    private string $type = 'ajout';

    #[ORM\Column(type: 'integer')]
    /** Quantité déplacée */
    private int $quantity = 0;

    #[ORM\Column(type: 'datetime_immutable')]
    /** Date et heure du mouvement */
    private \DateTimeImmutable $occurredAt;

    #[ORM\ManyToOne(targetEntity: \App\Entity\User::class)]
    #[ORM\JoinColumn(nullable: true)]
    /** Utilisateur ayant réalisé l’opération */
    private ?\App\Entity\User $performedBy = null;

    public function __construct()
    {
        $this->occurredAt = new \DateTimeImmutable();
    }

    public function getId(): ?int { return $this->id; }

    public function getAssignment(): ?Assignment { return $this->assignment; }
    public function setAssignment(?Assignment $assignment): self { $this->assignment = $assignment; return $this; }

    public function getEquipment(): ?Equipment { return $this->equipment; }
    public function setEquipment(?Equipment $equipment): self { $this->equipment = $equipment; return $this; }

    public function getStore(): ?Store { return $this->store; }
    public function setStore(?Store $store): self { $this->store = $store; return $this; }

    public function getType(): string { return $this->type; }
    public function setType(string $type): self { $this->type = $type; return $this; }

    public function getQuantity(): int { return $this->quantity; }
    public function setQuantity(int $quantity): self { $this->quantity = $quantity; return $this; }

    public function getOccurredAt(): \DateTimeImmutable { return $this->occurredAt; }
    public function setOccurredAt(\DateTimeImmutable $occurredAt): self { $this->occurredAt = $occurredAt; return $this; }

    public function getPerformedBy(): ?\App\Entity\User { return $this->performedBy; }
    public function setPerformedBy(?\App\Entity\User $user): self { $this->performedBy = $user; return $this; }
}