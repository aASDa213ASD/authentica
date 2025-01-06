<?php

declare(strict_types=1);

namespace App\AuthBundle\Entity;

use Random\RandomException;

class VerificationCode
{
	private string $email;
	private string $code;

	/** @throws RandomException */

	public function __construct()
	{
		$this->code = strtoupper(bin2hex(random_bytes(3)));
	}

	public function getCode(): string
	{
		return $this->code;
	}

	public function setCode(string $code): self
	{
		$this->code = $code;
		return $this;
	}

	public function getEmail(): string
	{
		return $this->email;
	}

	public function setEmail(string $email): self
	{
		$this->email = $email;
		return $this;
	}
}