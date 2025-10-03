<?php

namespace App\Entity;

use Doctrine\ORM\Mapping as ORM;
use Symfony\Component\Security\Core\User\PasswordAuthenticatedUserInterface;
use Symfony\Component\Security\Core\User\UserInterface;

/**
 * Représente un utilisateur du système.
 *
 * - Gère l’authentification et l’autorisation (rôles)
 * - Contient les informations affichées dans l’interface (nom complet)
 * - Référencé par les entités d’audit et les mouvements pour tracer qui agit
 */
#[ORM\Entity]
#[ORM\Table(name: 'user')]
class User implements UserInterface, PasswordAuthenticatedUserInterface
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column(type: 'integer')]
    /** Identifiant unique généré par la base de données */
    private ?int $id = null;

    #[ORM\Column(type: 'string', length: 180, unique: true)]
    /** Email de connexion (sert d’identifiant unique) */
    private string $email = '';

    #[ORM\Column(type: 'json')]
    /**
     * Ensemble des rôles (ROLE_USER est ajouté automatiquement).
     * Exemple: ["ROLE_ADMIN", "ROLE_USER"].
     */
    private array $roles = [];

    #[ORM\Column(type: 'string')]
    /** Mot de passe hashé (jamais en clair) */
    private string $password = '';

    #[ORM\Column(type: 'string', length: 100)]
    /** Nom complet affiché dans la barre de navigation */
    private string $fullName = '';

    #[ORM\Column(type: 'boolean')]
    /** Statut d’activation de l’utilisateur */
    private bool $isActive = true;

    public function getId(): ?int { return $this->id; }
    public function getEmail(): string { return $this->email; }
    public function setEmail(string $email): self { $this->email = $email; return $this; }

    public function getUserIdentifier(): string { return $this->email; }
    public function getRoles(): array { $roles = $this->roles; $roles[] = 'ROLE_USER'; return array_unique($roles); }
    public function setRoles(array $roles): self { $this->roles = $roles; return $this; }

    public function getPassword(): string { return $this->password; }
    public function setPassword(string $password): self { $this->password = $password; return $this; }

    /**
     * Méthode prévue pour effacer des données temporaires sensibles
     * après l’authentification. Non utilisée ici.
     */
    public function eraseCredentials(): void {}

    public function getFullName(): string { return $this->fullName; }
    public function setFullName(string $fullName): self { $this->fullName = $fullName; return $this; }

    public function isActive(): bool { return $this->isActive; }
    public function setIsActive(bool $active): self { $this->isActive = $active; return $this; }
}