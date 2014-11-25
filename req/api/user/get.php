<?php

namespace KON\Request\API;

use KON\Request;
use KON\DB\Session;

class UserGet extends Request {
	const CACHEABLE = false;

	public function handle() {
		if (!isset($this->params['sid'])) {
			$this->response->expectationFailed('Missing sid');
			return;
		}

		$session = new Session();
		if (!$session->read($this->params['sid'], true)) {
			$this->response->notFound();
			return;
		}

		$this->response->session = $session->getAPIFields();
	}
}

Request::$map[__FILE__] = 'UserGet';
