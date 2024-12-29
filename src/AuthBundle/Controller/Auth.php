<?php

declare(strict_types=1);

namespace App\AuthBundle\Controller;

use App\AuthBundle\Entity\AuthStage;
use App\UserBundle\Entity\User;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\RedirectResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Component\Security\Http\Authentication\AuthenticationUtils;
use DateTime;

#[Route('/auth')]
class Auth extends AbstractController
{
	private EntityManagerInterface $em;

	public function __construct(EntityManagerInterface $em)
	{
		$this->em = $em;
	}

	#[Route('/logout', name: 'app_logout')]
	public function logout(): void
	{
		throw new \LogicException('INTERCEPTED_BY_FIREWALL');
	}

	#[Route('/login/invoke', name: 'app_login')]
	public function login(AuthenticationUtils $authenticationUtils): JsonResponse
	{
		return new JsonResponse(['message' => 'Login request received']);
	}

	#[Route('/login', name: 'app_authenticate')]
	public function authenticate(Request $request, AuthenticationUtils $authenticationUtils): Response
	{
		if ($this->getUser())
		{
			return $this->redirectToRoute('app_security');
		}

		$stage = $request->get('stage', AuthStage::AUTHENTICATION);
		$error = $request->get('error') ?? $authenticationUtils->getLastAuthenticationError();
		$email = $request->get('email') ?? $request->query->get('email');
		$last_username = $authenticationUtils->getLastUsername();

		if (!empty($email))
		{
			$last_username = $email;
		}

		if ($request->getMethod() === 'POST')
		{
			if ($stage === AuthStage::AUTHENTICATION)
			{
				$email = $request->request->get('email');
				$user = $this->em->getRepository(User::class)->findOneBy(['email' => $email]);

				if ($user instanceof User)
				{
					$stage = AuthStage::AUTHORIZATION;
				}
				else
				{
					$this->addFlash('message', "<span>Email $email seems to be available, you may create your account <a href='{$this->generateUrl('app_register')}' class='font-bold underline'>here</a></span>");
					return $this->redirectToRoute('app_authenticate');
				}
			}
		}

		return $this->render(
			'@Auth/login.html.twig', [
				'last_username' => $last_username,
				'error' => $error,
				'stage' => $stage,
			]
		);
	}

	#[Route('/registration', name: 'app_register')]
	public function register(Request $request, AuthenticationUtils $authenticationUtils): RedirectResponse
	{
		return new RedirectResponse($this->generateUrl('user_create'));
	}

	#[Route('/security', name: 'app_security')]
	public function security(): Response
	{
		$user = $this->getUser();

		if (!$user instanceof User)
		{
			return $this->redirect($this->generateUrl('app_login'));
		}

		$user->setLastLogin(new DateTime());
		$this->em->persist($user);
		$this->em->flush();

		return $this->redirect($this->generateUrl('system_user_list'));
	}
}