<?php

declare(strict_types=1);

namespace App\AuthBundle\Authenticator;

use App\AuthBundle\Model\AuthStage;
use App\UserBundle\Entity\User;
use Doctrine\ORM\EntityManagerInterface;
use Exception;
use Symfony\Bundle\SecurityBundle\Security;
use Symfony\Component\HttpFoundation\RedirectResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\PasswordHasher\Hasher\UserPasswordHasherInterface;
use Symfony\Component\Routing\Generator\UrlGeneratorInterface;
use Symfony\Component\Security\Core\Authentication\Token\TokenInterface;
use Symfony\Component\Security\Core\Exception\AuthenticationException;
use Symfony\Component\Security\Http\Authenticator\AbstractLoginFormAuthenticator;
use Symfony\Component\Security\Http\Authenticator\Passport\Badge\CsrfTokenBadge;
use Symfony\Component\Security\Http\Authenticator\Passport\Badge\UserBadge;
use Symfony\Component\Security\Http\Authenticator\Passport\Credentials\PasswordCredentials;
use Symfony\Component\Security\Http\Authenticator\Passport\Passport;
use Symfony\Component\Security\Http\Util\TargetPathTrait;

class Login extends AbstractLoginFormAuthenticator
{
	use TargetPathTrait;

	public const LOGIN_ROUTE = 'app_login';

	private EntityManagerInterface $em;
	private UserPasswordHasherInterface $hasher;
	private UrlGeneratorInterface $url_generator;

	public function __construct(EntityManagerInterface $em, UserPasswordHasherInterface $hasher, UrlGeneratorInterface $url_generator)
	{
		$this->em = $em;
		$this->hasher = $hasher;
		$this->url_generator = $url_generator;
	}

	/** @throws Exception */
	public function authenticate(Request $request): Passport
	{
		$email = $request->request->get('email', '');
		$password = $request->request->get('password', '');
		$request->getSession()->set('_security.last_username', $email);

		return new Passport(
			new UserBadge($email, function (string $userIdentifier) use ($password)
			{
				$user = $this->em->getRepository(User::class)->findOneBy(['email' => $userIdentifier]);

				if (!$user || !$this->hasher->isPasswordValid($user, $password))
				{
					throw new AuthenticationException('Bad credentials');
				}

				if (!$user->isVerified())
				{
					throw new AuthenticationException('User is not verified');
				}

				return $user;
			}),
			new PasswordCredentials($request->request->get('password', '')),
			[
				new CsrfTokenBadge('authenticate', $request->request->get('_csrf_token')),
			]
		);
	}

	public function onAuthenticationSuccess(Request $request, TokenInterface $token, string $firewall_name): ?Response
	{
		if ($targetPath = $this->getTargetPath($request->getSession(), $firewall_name))
		{
			return new RedirectResponse($targetPath);
		}

		return new RedirectResponse($this->url_generator->generate('app_security'));
	}

	public function onAuthenticationFailure(Request $request, AuthenticationException $exception): Response
	{
		return new RedirectResponse($this->url_generator->generate('app_authenticate', [
			'error' => $exception->getMessage(),
			'stage' => AuthStage::AUTHORIZATION,
		]));
	}

	protected function getLoginUrl(Request $request): string
	{
		return $this->url_generator->generate(self::LOGIN_ROUTE);
	}
}