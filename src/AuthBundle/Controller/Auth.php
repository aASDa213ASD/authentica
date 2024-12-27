<?php

declare(strict_types=1);

namespace App\AuthBundle\Controller;

use App\UserBundle\Entity\User;
use Doctrine\Persistence\ManagerRegistry;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Component\Security\Http\Authentication\AuthenticationUtils;

#[Route('/auth')]
class Auth extends AbstractController
{
	private ManagerRegistry $manager_registry;

	public function __construct(ManagerRegistry $manager_registry)
	{
		$this->manager_registry = $manager_registry;
	}

	#[Route('/login/invoke', name: 'app_login')]
	public function login(AuthenticationUtils $authenticationUtils): Response
	{
		return $this->render(
			'@Auth/login.html.twig', []
		);
	}

	#[Route('/login', name: 'app_authenticate')]
	public function authenticate(Request $request, AuthenticationUtils $authenticationUtils): Response
	{
		if ($this->getUser())
		{
			return $this->redirectToRoute('app_security');
		}

		$step = $request->get('step', 'email');

		if ($request->getMethod() === 'POST')
		{
			dd($step);

			if ($step === 'email')
			{
				$email = $request->request->get('email');

				if ($this->manager_registry->getRepository(User::class)->findOneBy(['email' => $email]))
				{
					return $this->redirectToRoute('app_login', ['step' => 'password', 'email' => $email]);
				}

				$this->addFlash('warning', 'Create your account');
				return $this->redirectToRoute('app_register');
			}
		}

		$error = $authenticationUtils->getLastAuthenticationError();
		$last_username = $authenticationUtils->getLastUsername();

		return $this->render(
			'@Auth/login.html.twig', [
				'last_username' => $last_username,
				'error' => $error,
				'step' => $step,
			]
		);
	}

	#[Route('/logout', name: 'app_logout')]
	public function logout(): void
	{
		throw new \LogicException('This method can be blank - it will be intercepted by the logout key on your firewall.');
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
}