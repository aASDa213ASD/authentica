<?php

declare(strict_types=1);

namespace App\UserBundle\Entity\Repository;

use App\UserBundle\Entity\User;
use Doctrine\ORM\EntityManagerInterface;
use Doctrine\Persistence\ManagerRegistry;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Symfony\Component\Security\Core\Exception\UnsupportedUserException;
use Symfony\Component\Security\Core\User\PasswordAuthenticatedUserInterface;
use Symfony\Component\Security\Core\User\PasswordUpgraderInterface;
use function get_class;

class UserRepository extends ServiceEntityRepository implements PasswordUpgraderInterface
{
	private EntityManagerInterface $entity_manager;

	public function __construct(ManagerRegistry $registry, EntityManagerInterface $entity_manager)
	{
		parent::__construct($registry, User::class);
		$this->entity_manager = $entity_manager;
	}

	public function upgradePassword(PasswordAuthenticatedUserInterface $user, string $new_hashed_password): void
	{
		if (!$user instanceof User)
		{
			throw new UnsupportedUserException(sprintf('Instances of "%s" are not supported.', get_class($user)));
		}

		$user->setPassword($new_hashed_password);
		$this->entity_manager->persist($user);
		$this->entity_manager->flush();
	}
}