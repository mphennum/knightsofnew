<?php

namespace KON\Request\WWW;

use DateTime;

use KON\App;
use KON\Config;
use KON\Database;
use KON\Request;
use KON\DB\Session;

class Login extends Request {
	const CACHEABLE = false;
	const TEMPLATE = 'login';

	public function handle() {
		if (isset($this->params['error']) || !isset($this->params['state']) || !isset($this->params['code'])) {
			return;
		}

		$sid = $this->params['state'];
		$code = $this->params['code'];

		$session = new Session();
		if ($sid !== $this->opts['sid'] || !$session->read($sid, true)) {
			return;
		}

		$ch = curl_init();

		curl_setopt($ch, CURLOPT_URL, 'https://www.reddit.com/api/v1/access_token');
		curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
		curl_setopt($ch, CURLOPT_USERPWD, Config::REDDITKEY . ':' . Config::REDDITSECRET);
		curl_setopt($ch, CURLOPT_POST, true);
		curl_setopt($ch, CURLOPT_POSTFIELDS,
			'grant_type=authorization_code' .
			'&code=' . rawurlencode($code) .
			'&redirect_uri=' . rawurlencode('https://knightsofnew.mphennum.com/login')
		);

		$resp = curl_exec($ch);
		$info = curl_getinfo($ch);
		curl_close($ch);

		if ($resp === false || $info['http_code'] !== 200) {
			return;
		}

		$resp = json_decode($resp, true);

		if (!isset($resp['access_token']) || !isset($resp['expires_in']) || !isset($resp['refresh_token'])) {
			return;
		}

		$session->login($resp['access_token'], $resp['refresh_token'], $resp['expires_in']);
	}
}

Request::$map[__FILE__] = 'Login';
