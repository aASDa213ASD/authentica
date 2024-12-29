<?php

declare(strict_types=1);

namespace App\UserBundle\Command;

use App\UserBundle\Entity\Repository\UserRepository;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;

#[AsCommand(name: 'user:verify')]
class Verify extends Command
{
	private UserRepository $user_repository;
	private EntityManagerInterface $em;

	public function __construct(
		UserRepository $user_repository,
		EntityManagerInterface $em
	) {
		parent::__construct();
		$this->user_repository = $user_repository;
		$this->em = $em;
	}

	protected function execute(InputInterface $input, OutputInterface $output): int
	{
		$email = $input->getArgument('email');
		$desired_verification_status = (bool)$input->getOption('verified');

		$output->writeln("Looking for user with email: <info>$email</info>...");

		$user = $this->user_repository->findOneBy(['email' => $email]);

		if (!$user)
		{
			$output->writeln("<error>Couldn't find a user with email $email</error>");
			return Command::FAILURE;
		}

		$user->setIsVerified($desired_verification_status);

		$this->em->persist($user);
		$this->em->flush();

		if ($desired_verification_status)
		{
			$output->writeln("<info>OK. User {$user->getLogin()}#{$user->getId()} has been verified successfully</info>");
		}
		else
		{
			$output->writeln("<comment>OK. User {$user->getLogin()}#{$user->getId()} has been unverified successfully</comment>");
		}
			return Command::SUCCESS;
	}

	protected function configure(): void
	{
		$this
			->setDescription('Verifies a user')
			->setHelp('This command allows you to verify a user identified by email');

		$this
			->addArgument('email', InputArgument::REQUIRED, 'The email address of the user')
			->addOption('verified', 'verify', InputOption::VALUE_NONE, 'Desired verification status (boolean)');
	}
}