<?php

declare(strict_types=1);

namespace App\AuthBundle\Service;

use GuzzleHttp\Client as Guzzle;

class RecaptchaValidator
{
	private string $secret;
	private Guzzle $guzzle;

	public function __construct(string $secret)
	{
		$this->secret = $secret;
		$this->guzzle = new Guzzle();
	}

	public function validate(string $recaptcha_response, ?string $remote_ip = null): bool
	{
		if (empty($recaptcha_response))
		{
			return false;
		}

		$params = [
			'secret' => $this->secret,
			'response' => $recaptcha_response,
		];

		if ($remote_ip)
		{
			$params['remoteip'] = $remote_ip;
		}

		$response = $this->guzzle->post(
			'https://www.google.com/recaptcha/api/siteverify', [
				'form_params' => $params
			]
		);

		$data = json_decode($response->getBody()->getContents(), true);

		if (isset($data['success']) && $data['success'] === true)
		{
			return true;
		}

		return false;
	}
}