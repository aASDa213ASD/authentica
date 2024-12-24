<?php

declare(strict_types=1);

namespace App\AppBundle\Controller;

use App\UserBundle\Entity\User;
use Doctrine\Persistence\ManagerRegistry;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Security\Http\Authentication\AuthenticationUtils;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;

class Auth extends AbstractController
{
	private ManagerRegistry $manager_registry;

	public function __construct(ManagerRegistry $manager_registry)
	{
		$this->manager_registry = $manager_registry;
	}

	#[Route('/login', name: 'app_login')]
	public function login(AuthenticationUtils $authenticationUtils): Response
	{
		if ($this->getUser())
		{
			return $this->redirectToRoute('app_security');
		}

		$error = $authenticationUtils->getLastAuthenticationError();
		$last_username = $authenticationUtils->getLastUsername();

		return $this->render(
			'login.html.twig', [
				'last_username' => $last_username,
				'error' => $error
			]
		);
	}

	#[Route('/security', name: 'app_security')]
	public function security(): Response
	{
		$user = $this->getUser();

		if (!$user instanceof User)
		{
			return $this->redirect($this->generateUrl('app_login'));
		}

		$user->setLastLogin(new \DateTime());
		$this->manager_registry->getManager()->persist($user);
		$this->manager_registry->getManager()->flush();

		return $this->redirect($this->generateUrl('system_user_list'));
	}

	#[Route('/logout', name: 'app_logout')]
	public function logout(): void
	{
		throw new \LogicException('This method can be blank - it will be intercepted by the logout key on your firewall.');
	}
}