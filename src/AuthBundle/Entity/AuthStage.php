<?php

declare(strict_types=1);

namespace App\AuthBundle\Entity;

class AuthStage
{
	public const AUTHENTICATION = 'AUTHENTICATION';
	public const AUTHORIZATION = 'AUTHORIZATION';
	public const AUTHORIZATION_WITH_2FA = 'AUTHORIZATION_WITH_2FA';
}