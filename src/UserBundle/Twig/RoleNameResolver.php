<?php

declare(strict_types=1);

namespace App\UserBundle\Twig;

use Twig\Extension\AbstractExtension;
use Twig\TwigFunction;

class RoleNameResolver extends AbstractExtension
{
	public function getFunctions(): array
	{
		return [
			new TwigFunction('resolve_role_name', [$this, 'resolve']),
		];
	}

	public function resolve(string $role): string
	{
		$role_mapping = [
			'ROLE_ADMIN' => 'Адміністратор',
			'ROLE_DEVELOPER' => 'Розробник',
		];

		return $role_mapping[$role] ?? $role;
	}
}