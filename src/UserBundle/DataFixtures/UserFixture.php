<?php

declare(strict_types=1);

namespace App\UserBundle\DataFixtures;

use App\UserBundle\Entity\User;
use Doctrine\Persistence\ObjectManager;
use Doctrine\Bundle\FixturesBundle\Fixture;
use Symfony\Component\PasswordHasher\Hasher\UserPasswordHasherInterface;

class UserFixture extends Fixture
{
	private UserPasswordHasherInterface $user_password_encoder;

	public function __construct(UserPasswordHasherInterface $user_password_encoder)
	{
		$this->user_password_encoder = $user_password_encoder;
	}

	public function load(ObjectManager $manager): void
	{
		$user = new User();
		$user->setEmail('root@gmail.com');
		$user->setLogin('root');
		$user->setRole('ROLE_DEVELOPER');
		$user->setIsVerified(true);
		$user->setPassword(
			$this->user_password_encoder->hashPassword(
				$user, 'root'
			));
		$manager->persist($user);

		$user = new User();
		$user->setEmail('admin@gmail.com');
		$user->setLogin('admin');
		$user->setRole('ROLE_ADMIN');
		$user->setIsVerified(true);
		$user->setPassword(
			$this->user_password_encoder->hashPassword(
				$user, 'admin'
			));
		$manager->persist($user);

		$user = new User();
		$user->setEmail('user@gmail.com');
		$user->setLogin('user');
		$user->setRole('ROLE_USER');
		$user->setIsVerified(true);
		$user->setPassword(
			$this->user_password_encoder->hashPassword(
				$user, 'user'
			));
		$manager->persist($user);

		$manager->flush();
	}
}