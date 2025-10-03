<?php

namespace App\Entity;

use App\Repository\AuditRepository;
use Doctrine\ORM\Mapping as ORM;

/**
 * Journal d’audit minimaliste.
 *
 * Enregistre qui (user), quoi (action), sur quelle entité (entityClass, entityId)
 * et quand (occurredAt). Permet d’afficher l’historique sur les pages de détail.
 */
#[ORM\Entity(repositoryClass: AuditRepository::class)]
class Audit
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column(type: 'integer')]
    /** Identifiant de l’enregistrement d’audit */
    private ?int $id = null;

    #[ORM\ManyToOne(targetEntity: \App\Entity\User::class)]
    #[ORM\JoinColumn(nullable: true)]
    /** Utilisateur ayant effectué l’action (peut être null si système) */
    private ?User $user = null;

    #[ORM\Column(length: 50)]
    /** Type d’action: create, update, delete, return, adjust_stock, etc. */
    private string $action = '';

    #[ORM\Column(length: 128)]
    /** Classe de l’entité concernée (ex: App\Entity\Store) */
    private string $entityClass = '';

    #[ORM\Column(type: 'integer')]
    /** Identifiant de l’entité concernée */
    private int $entityId = 0;

    #[ORM\Column(type: 'datetime_immutable')]
    /** Date et heure de l’action */
    private \DateTimeImmutable $occurredAt;

    public function __construct()
    {
        $this->occurredAt = new \DateTimeImmutable();
    }

    public function getId(): ?int { return $this->id; }
    public function getUser(): ?User { return $this->user; }
    public function setUser(?User $user): self { $this->user = $user; return $this; }
    public function getAction(): string { return $this->action; }
    public function setAction(string $action): self { $this->action = $action; return $this; }
    public function getEntityClass(): string { return $this->entityClass; }
    public function setEntityClass(string $entityClass): self { $this->entityClass = $entityClass; return $this; }
    public function getEntityId(): int { return $this->entityId; }
    public function setEntityId(int $entityId): self { $this->entityId = $entityId; return $this; }
    public function getOccurredAt(): \DateTimeImmutable { return $this->occurredAt; }
    public function setOccurredAt(\DateTimeImmutable $d): self { $this->occurredAt = $d; return $this; }
}