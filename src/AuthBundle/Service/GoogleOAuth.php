<?php

declare(strict_types=1);

namespace App\AuthBundle\Service;

use Predis\Client as Redis;
use GuzzleHttp\Client as Guzzle;

class GoogleOAuth
{
	private const REDIS_OAUTH_KEY = 'GOOGLE_OAUTH_ACCESS_TOKEN:';

	private Redis $redis;
	private Guzzle $guzzle;
	private string $refresh_token;
	private string $client_id;
	private string $client_secret;

	public function __construct(string $client_id, string $client_secret, string $refresh_token)
	{
		$this->redis = new Redis();
		$this->guzzle = new Guzzle();
		$this->client_id = $client_id;
		$this->client_secret = $client_secret;
		$this->refresh_token = $refresh_token;
	}

	public function getAccessToken(): string
	{
		if ($this->redis->exists(self::REDIS_OAUTH_KEY))
		{
			return $this->redis->get(self::REDIS_OAUTH_KEY);
		}

		return $this->requestGoogleForNewToken();
	}

	private function requestGoogleForNewToken(): string
	{
		$response = $this->guzzle->post('https://oauth2.googleapis.com/token', [
			'form_params' => [
			    'client_id'     => $this->client_id,
			    'client_secret' => $this->client_secret,
			    'refresh_token' => $this->refresh_token,
			    'grant_type'    => 'refresh_token',
			]
		])->getBody()->getContents();

		$access_token = json_decode($response, true)['access_token'];
		$this->redis->setex(self::REDIS_OAUTH_KEY, 1800, $access_token);

		return $access_token;
	}
}