<?php

namespace KON\Request\API;

use KON\Config;
use KON\Request;
use KON\DB\Session;

class PostVote extends Request {
	const CACHEABLE = false;

	public function handle() {
		if (!isset($this->params['sid']) || !isset($this->params['id']) || !isset($this->params['dir'])) {
			$this->response->expectationFailed('Required params: sid, id, dir');
			return;
		}

		$dir = $this->params['dir'];
		if ($dir !== 1 && $dir !== -1 && $dir !== 0) {
			$this->response->notAcceptable('dir must be: 1 (upvote), -1 (downvote), or 0 (unvote)');
			return;
		}

		$session = new Session();
		if (!$session->read($this->params['sid'], true) || !$session->isLoggedIn()) {
			$this->response->unauthorized();
			return;
		}

		$ch = curl_init();
		curl_setopt($ch, CURLOPT_URL, 'https://oauth.reddit.com/api/vote');
		curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
		curl_setopt($ch, CURLOPT_POST, true);

		curl_setopt($ch, CURLOPT_POSTFIELDS,
			'dir=' . rawurlencode($dir) .
			'&id=' . rawurlencode('t3_' . $this->params['id'])
		);

		curl_setopt($ch, CURLOPT_HTTPHEADER, [
			'Authorization: bearer ' . $session->access,
			'User-Agent: ' . Config::REDDITUSERAGENT
		]);

		$resp = curl_exec($ch);
		$info = curl_getinfo($ch);
		curl_close($ch);

		if ($resp === false || $info['http_code'] !== 200) {
			$this->response->serviceUnavailable('Vote request failed');
			return;
		}

		//$resp = json_decode($resp, true);
	}
}

Request::$map[__FILE__] = 'PostVote';
