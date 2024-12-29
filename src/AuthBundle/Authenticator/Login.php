<?php

declare(strict_types=1);

namespace App\AuthBundle\Authenticator;

use App\AuthBundle\Entity\AuthStage;
use App\UserBundle\Entity\User;
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
use Symfony\Component\DependencyInjection\ParameterBag\ParameterBagInterface;
use Doctrine\ORM\EntityManagerInterface;
use Psr\Log\LoggerInterface;
use Predis\Client as Redis;
use Exception;

class Login extends AbstractLoginFormAuthenticator
{
	use TargetPathTrait;

	public const LOGIN_ROUTE = 'app_login';

	private Redis $redis;
	private LoggerInterface $logger;
	private EntityManagerInterface $em;
	private UserPasswordHasherInterface $hasher;
	private UrlGeneratorInterface $url_generator;

	private bool $password_fatigue_enabled = false;
	private int $password_fatigue_time_seconds = 180;

	public function __construct(
		LoggerInterface $logger,
		EntityManagerInterface $em,
		UserPasswordHasherInterface $hasher,
		UrlGeneratorInterface $url_generator,
	)
	{
		$this->logger = $logger;
		$this->em = $em;
		$this->hasher = $hasher;
		$this->url_generator = $url_generator;
		$this->redis = new Redis();
	}

	/** @throws Exception */
	public function authenticate(Request $request): Passport
	{
		$email = $request->request->get('email', '');
		$request->getSession()->set('_security.last_username', $email);

		return new Passport(
			new UserBadge($email, function (string $userIdentifier) use ($request)
			{
				return $this->resolveAuthorizationByRequest($request);
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

	private function resolveAuthorizationByRequest(Request $request): User
	{
		$email = $request->request->get('email', '');
		$password = $request->request->get('password', '');
		$user = $this->em->getRepository(User::class)->findOneBy(['email' => $email]);

		if ($this->password_fatigue_enabled && $this->hasAuthorizationFatigue($email))
		{
			throw new AuthenticationException("Bad credentials");
		}

		if (!$user || !$this->hasher->isPasswordValid($user, $password))
		{
			$this->logger->warning("Failed authorization attempt for user {$user->getEmail()}", [
				'ip_address' => $request->getClientIp(),
				'time' => date('d-m-Y H:i:s'),
			]);

			$this->addAuthorizationFatigue($email);

			//throw new AuthenticationException('Bad credentials');
			throw new AuthenticationException("Bad credentials");
		}

		if (!$user->isVerified())
		{
			throw new AuthenticationException('User is not verified');
		}

		return $user;
	}

	private function hasAuthorizationFatigue(string $email): bool
	{
		$key = 'failed_attempts:' . $email;

		if ((int) $this->redis->get($key) >= 3)
		{
			return true;
		}

		return false;
	}

	private function addAuthorizationFatigue(string $email): void
	{
		$key = 'failed_attempts:' . $email;
		$this->redis->incr($key);
		$this->redis->expire($key, $this->password_fatigue_time_seconds);
	}

	private function removeAuthorizationFatigue(string $email): void
	{
		$key = 'failed_attempts:' . $email;
		$this->redis->del($key);
	}
}