<?php

declare(strict_types=1);

namespace App\AuthBundle\Service;

use App\AuthBundle\Entity\VerificationCode;
use App\UserBundle\Entity\User;
use Google\Client as GmailClient;
use Google\Service\Gmail;
use Google\Service\Gmail\Message;
use Predis\Client as Redis;

class TwoFactorAuth
{
	private GoogleOAuth $google_oauth;
	private GmailClient $gmail_client;
	private Redis $redis;

	public function __construct()
	{
		$this->google_oauth = new GoogleOAuth(
			$_ENV['GOOGLE_CLIENT_ID'],
			$_ENV['GOOGLE_CLIENT_SECRET'],
			$_ENV['GOOGLE_REFRESH_TOKEN']
		);
		$this->gmail_client = new GmailClient();
		$this->redis = new Redis();
	}

	public function send2FA(User $user): void
	{
		$access_token = $this->google_oauth->getAccessToken();
		$this->gmail_client->setAccessToken($access_token);
		$gmail_service = new Gmail($this->gmail_client);

		$verification_code = new VerificationCode();
		$code = $verification_code->getCode();

		$this->redis->setex($code, 60, $user->getEmail());

		$message = $this->createMessage(
			$user->getEmail(), 'Authentica',
			"2FA Code - $code"
		);

		$gmail_service->users_messages->send('me', $message);
	}

	public function verify2FA(string $code): bool
	{
		if ($this->redis->exists($code))
		{
			return true;
		}

		return false;
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