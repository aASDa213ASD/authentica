<?php

declare(strict_types=1);

namespace App\UserBundle\Command;

use App\UserBundle\Entity\Repository\UserRepository;
use Doctrine\ORM\EntityManagerInterface;
use Random\RandomException;
use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\PasswordHasher\Hasher\UserPasswordHasherInterface;

#[AsCommand(name: 'user:reset-password')]
class ResetPassword extends Command
{
	private UserRepository $user_repository;
	private UserPasswordHasherInterface $hasher;
	private EntityManagerInterface $em;

	public function __construct(
		UserRepository $user_repository,
		UserPasswordHasherInterface $hasher,
		EntityManagerInterface $em
	) {
		parent::__construct();
		$this->user_repository = $user_repository;
		$this->hasher = $hasher;
		$this->em = $em;
	}

	/** @throws RandomException */
	protected function execute(InputInterface $input, OutputInterface $output): int
	{
		$email = $input->getArgument('email');
		$password = $input->getArgument('password') ?? $this->generatePassword();

		$output->writeln("Looking for user with email: <info>$email</info>...");

		$user = $this->user_repository->findOneBy(['email' => $email]);

		if (!$user)
		{
			$output->writeln("<error>Couldn't find a user with email $email</error>");
			return Command::FAILURE;
		}
		$hashed_password = $this->hasher->hashPassword($user, $password);
		$user->setPassword($hashed_password);

		$this->em->persist($user);
		$this->em->flush();

		$output->writeln("<info>OK. Password has been successfully reset for {$user->getLogin()}#{$user->getId()}, new password - $password</info>");
		return Command::SUCCESS;
	}

	protected function configure(): void
	{
		$this
			->setDescription('Resets user password')
			->setHelp('This command allows you to reset the password for a user identified by email');

		$this
			->addArgument('email', InputArgument::REQUIRED, 'The email address of the user')
			->addArgument('password', InputArgument::OPTIONAL, 'The new password for the user');
	}

	/** @throws RandomException */
	private function generatePassword(int $length = 16): string
	{
		$letters = 'abcdefghijklmnopqrstuvwxyzABCDEFGHIJKLMNOPQRSTUVWXYZ';
		$numbers = '0123456789';
		$symbols = '!@#$%&*';

		$password = $letters[random_int(0, strlen($letters) - 1)];
		$password .= $numbers[random_int(0, strlen($numbers) - 1)];
		$password .= $symbols[random_int(0, strlen($symbols) - 1)];

		$all_characters = $letters . $numbers . $symbols;
		$remainingLength = $length - 3;

		for ($i = 0; $i < $remainingLength; $i++)
		{
			$password .= $all_characters[random_int(0, strlen($all_characters) - 1)];
		}

		return str_shuffle($password);
	}
}