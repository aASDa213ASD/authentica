<?php

declare(strict_types=1);

namespace App\UserBundle\Entity;

use App\UserBundle\Entity\Repository\UserRepository;
use Doctrine\ORM\Mapping as ORM;
use Symfony\Bridge\Doctrine\Validator\Constraints\UniqueEntity;
use Symfony\Component\Security\Core\User\UserInterface;
use Symfony\Component\Security\Core\User\PasswordAuthenticatedUserInterface;

#[ORM\HasLifecycleCallbacks]
#[ORM\Table(name: 'users')]
#[ORM\Entity(repositoryClass: UserRepository::class)]
#[UniqueEntity(fields: ['email'], message: 'Such email is already in use')]
class User implements UserInterface, PasswordAuthenticatedUserInterface
{
	#[ORM\Id]
	#[ORM\GeneratedValue]
	#[ORM\Column(type: 'integer')]
	private int $id;

	#[ORM\Column(type: 'string', unique: true)]
	private string $email;

	#[ORM\Column(type: 'string')]
	private string $login;

	#[ORM\Column(type: 'string')]
	private string $role = "ROLE_USER";

	#[ORM\Column(type: 'string')]
	private string $password;

	#[ORM\Column(type: 'boolean')]
	private bool $is_verified = false;

	#[ORM\Column(type: 'datetime', nullable: true)]
	private ?\DateTime $last_login_at;

	#[ORM\Column(type: 'datetime', nullable: true)]
	private ?\DateTime $last_activity_at;

	#[ORM\Column(type: 'integer', nullable: true)]
	private ?int $expires_confirmation_token;

	#[ORM\Column(type: 'string', nullable: true)]
	private ?string $confirmation_token;

	public function getId(): int
	{
		return $this->id;
	}

	public function getEmail(): string
	{
		return $this->email;
	}

	public function setEmail(string $email): self
	{
		$this->email = $email;
		return $this;
	}

	public function getLogin(): string
	{
		return $this->login;
	}

	public function setLogin(string $login): self
	{
		$this->login = $login;
		return $this;
	}

	public function getRoles(): array
	{
		return [$this->role];
	}

	public function getRole(): string
	{
		return $this->role;
	}

	public function setRole(string $new_role): self
	{
		$this->role = $new_role;
		return $this;
	}

	public function getPassword(): string
	{
		return $this->password;
	}

	public function setPassword(string $password): self
	{
		$this->password = $password;
		return $this;
	}

	public function getSalt(): ?string
	{
		return null;
	}

	public function eraseCredentials(): void
	{
		// If you store any temporary, sensitive data on the user, clear it here
		// $this->plainPassword = null;
	}

	public function isVerified(): bool
	{
		return $this->is_verified;
	}

	public function setIsVerified(bool $flag): self
	{
		$this->is_verified = $flag;
		return $this;
	}

	public function getLastActivityAt(): ?\DateTime
	{
		return $this->last_activity_at;
	}

	public function setLastActivityAt(?\DateTime $last_activity_at): self
	{
		$this->last_activity_at = $last_activity_at;
		return $this;
	}

	public function getLastLogin(): ?\DateTime
	{
		return $this->last_login_at;
	}

	public function setLastLogin(?\DateTime $time): self
	{
		$this->last_login_at = $time;
		return $this;
	}

	public function getConfirmationToken(): ?string
	{
		return $this->confirmation_token;
	}

	public function setConfirmationToken(?string $confirmation_token): self
	{
		$this->confirmation_token = $confirmation_token;
		return $this;
	}

	public function getExpiresConfirmationToken(): ?int
	{
		return $this->expires_confirmation_token;
	}

	public function setExpiresConfirmationToken(?int $expires_confirmation_token): self
	{
		$this->expires_confirmation_token = $expires_confirmation_token;
		return $this;
	}

	public function getUserIdentifier(): string
	{
		return $this->email;
	}

	public function getUserHash(): string
	{
		return substr(hash('fnv1a64', $this->getUserIdentifier()), 0, 16);
	}
}