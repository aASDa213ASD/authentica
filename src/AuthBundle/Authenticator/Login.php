<?php

declare(strict_types=1);

namespace App\AuthBundle\Authenticator;

use Symfony\Component\HttpFoundation\RedirectResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Generator\UrlGeneratorInterface;
use Symfony\Component\Security\Core\Authentication\Token\TokenInterface;
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

	private UrlGeneratorInterface $url_generator;

	public function __construct(UrlGeneratorInterface $url_generator)
	{
		$this->url_generator = $url_generator;
	}

	public function authenticate(Request $request): Passport
	{
		$email = $request->request->get('email', '');

		$request->getSession()->set('_security.last_username', $email);

		return new Passport(
			new UserBadge($email),
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

	protected function getLoginUrl(Request $request): string
	{
		return $this->url_generator->generate(self::LOGIN_ROUTE);
	}
}