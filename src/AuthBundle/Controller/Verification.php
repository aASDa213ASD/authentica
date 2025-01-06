<?php

declare(strict_types=1);

namespace App\AuthBundle\Controller;

use App\AuthBundle\Entity\AuthStage;
use App\AuthBundle\Entity\VerificationCode;
use App\AuthBundle\Form\PasswordResetType;
use App\AuthBundle\Form\VerificationRequestType;
use App\AuthBundle\Service\GoogleOAuth;
use App\UserBundle\Entity\User;
use Doctrine\ORM\EntityManagerInterface;
use Google\Service\Exception;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Mailer\Exception\TransportExceptionInterface;
use Symfony\Component\Mailer\MailerInterface;
use Symfony\Component\PasswordHasher\Hasher\UserPasswordHasherInterface;
use Symfony\Component\Routing\Annotation\Route;
use Google\Client as GmailClient;
use Google\Service\Gmail;
use Google\Service\Gmail\Message;
use Predis\Client as Redis;

#[Route('/verify')]
class Verification extends AbstractController
{
	private GoogleOAuth $google_oauth;
	private EntityManagerInterface $em;
	private UserPasswordHasherInterface $hasher;
	private GmailClient $gmail_client;
	private Redis $redis;

	public function __construct(EntityManagerInterface $em, UserPasswordHasherInterface $hasher)
	{
		$this->em = $em;
		$this->hasher = $hasher;
		$this->google_oauth = new GoogleOAuth(
			$_ENV['GOOGLE_CLIENT_ID'],
			$_ENV['GOOGLE_CLIENT_SECRET'],
			$_ENV['GOOGLE_REFRESH_TOKEN']
		);
		$this->gmail_client = new GmailClient();
		$this->redis = new Redis();
	}

	/** @throws TransportExceptionInterface
	 *  @throws Exception */
	#[Route('/user', name: 'user_verify')]
	public function verify(Request $request): Response
	{
		$verification_code = new VerificationCode();

		$form = $this->createForm(VerificationRequestType::class, $verification_code);
		$form->handleRequest($request);

		if ($form->isSubmitted() && $form->isValid())
		{
			$email = $form->get('email')->getData();
			$user = $this->em->getRepository(User::class)->findOneBy(['email' => $email]);

			if ($user instanceof User)
			{
				$access_token = $this->google_oauth->getAccessToken();
				$this->gmail_client->setAccessToken($access_token);
				$gmail_service = new Gmail($this->gmail_client);

				$activation_token = bin2hex($email); // generates activation token
				$this->redis->setex($activation_token, 300, $email); // stores the token for 5 minutes

				$message = $this->createMessage(
					$email, 'Authentica',
					"In order to verify your account go by this link (valid for 5 minutes) - http://localhost:5000/verify/token/{$activation_token}"
				);

				$gmail_service->users_messages->send('me', $message);
			}

			$this->addFlash('message', 'If such user exists the email with the verification link will be sent');

			return $this->redirectToRoute('user_verify', [
				'email' => $email,
				'stage' => AuthStage::AUTHENTICATION,
			]);
		}

		return $this->render(
			'@App/edit_form.html.twig', [
				'form'  => $form->createView(),
				'title' => 'User verification',
			]
		);
	}

	#[Route('/token/{token}', name: 'user_verify_by_token')]
	public function verifyByToken(Request $request, string $token): Response
	{
		if ($this->redis->exists($token))
		{
			$email = $this->redis->get($token);
			$user = $this->em->getRepository(User::class)->findOneBy(['email' => $email]);

			if ($user instanceof User)
			{
				$user->setIsVerified(true);
				$this->em->persist($user);
				$this->em->flush();

				$this->addFlash('message', "User $email is now verified");
			}

			return $this->redirectToRoute('app_authenticate');
		}

		$this->addFlash('message', 'Your token has expired');
		return $this->redirectToRoute('user_verify');
	}

	/** @throws TransportExceptionInterface
	 *  @throws Exception */
	#[Route('/reset', name: 'user_reset_password_request')]
	public function resetPasswordByRequest(Request $request): Response
	{
		$email = $request->query->get('email');
		$user = $this->em->getRepository(User::class)->findOneBy(['email' => $email]);

		if ($user instanceof User)
		{
			$access_token = $this->google_oauth->getAccessToken();
			$this->gmail_client->setAccessToken($access_token);
			$gmail_service = new Gmail($this->gmail_client);

			$password_reset_token = bin2hex($email);
			$this->redis->setex($password_reset_token, 300, $email);

			$message = $this->createMessage(
				$email, 'Authentica',
				"In order to reset your password click here (valid for 5 minutes) - http://localhost:5000/verify/reset/{$password_reset_token}"
			);

			$gmail_service->users_messages->send('me', $message);

			$this->addFlash('message', "Password reset link was sent");
		}

		return $this->redirectToRoute('app_authenticate');
	}

	#[Route('/reset/{token}', name: 'user_reset_password_by_token')]
	public function resetPasswordByToken(Request $request, string $token): Response
	{
		if (!$this->redis->exists($token))
		{
			$this->addFlash('message', "Unknown error occured");
			return $this->redirectToRoute('app_authenticate');
		}
		else
		{
			$email = $this->redis->get($token);
			$form = $this->createForm(PasswordResetType::class);
			$form->handleRequest($request);

			if ($form->isSubmitted() && $form->isValid())
			{
				$new_password = $form->get('password')->getData();
				$new_password_confirm = $form->get('confirm_password')->getData();

				if ($new_password !== $new_password_confirm)
				{
					$this->addFlash('message', "Passwords did not match");
					return $this->redirectToRoute('app_authenticate');
				}

				$user = $this->em->getRepository(User::class)->findOneBy(['email' => $email]);

				if ($user instanceof User)
				{
					$user->setPassword($this->hasher->hashPassword($user, $new_password));
					$this->em->persist($user);
					$this->em->flush();
				}

				$this->addFlash('message', "Password was reset");
				return $this->redirectToRoute('app_authenticate');
			}

			return $this->render(
				'@App/edit_form.html.twig', [
					'form'  => $form->createView(),
					'title' => 'User verification',
				]
			);
		}
	}

	private function createMessage(string $to, string $subject, string $body): Message
	{
		$headers = [
			'To' => $to,
			'Subject' => $subject,
			'From' => 'ragebladesamurai@gmail.com',
		];

		$mimeMessage = "From: {$headers['From']}\r\n";
		$mimeMessage .= "To: {$headers['To']}\r\n";
		$mimeMessage .= "Subject: {$headers['Subject']}\r\n";
		$mimeMessage .= "\r\n";
		$mimeMessage .= $body;

		$mimeMessage = $this->base64url_encode($mimeMessage);

		$message = new Message();
		$message->setRaw($mimeMessage);

		return $message;
	}

	private function base64url_encode($data): string
	{
		return rtrim(strtr(base64_encode($data), '+/', '-_'), '=');
	}
}