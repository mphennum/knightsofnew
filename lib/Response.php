<?php

namespace KON;

class Response {
	static public $codes = [
		200 => 'OK',
		201 => 'Created',
		204 => 'No Content',

		304 => 'Not Modified',

		400 => 'Bad Request',
		401 => 'Unauthorized',
		404 => 'Not Found',
		405 => 'Method Not Allowed',
		406 => 'Not Acceptable',
		410 => 'Gone',
		411 => 'Length Required',
		412 => 'Precondition Failed',
		416 => 'Range Not Satisfiable',
		417 => 'Expectation Failed',
		429 => 'Too Many Requests',

		500 => 'Internal Server Error',
		501 => 'Not Implemented',
		502 => 'Bad Gateway',
		503 => 'Service Unavailable',
		504 => 'Gateway Timeout'
	];

	public $result;
	public $status;

	public function __construct($opts = []) {
		$this->result = [];
		$this->status = [
			'code' => 200,
			'message' => self::$codes[200],
			'ttl' => 0
		];
	}

	// result

	public function getResult() {
		return $this->result;
	}

	public function setResult($result) {
		$this->result = $result;
	}

	public function __get($key) {
		if (!isset($this->result[$key])) {
			return null;
		}

		return $this->result[$key];
	}

	public function __set($key, $value) {
		$this->result[$key] = $value;
	}

	// ttl

	public function getTTL() {
		return $this->status['ttl'];
	}

	public function setTTL($ttl) {
		$this->status['ttl'] = $ttl;
	}

	// status

	public function getStatus() {
		return $this->status;
	}

	public function setStatus($code, $reason = null) {
		if (!isset(self::$codes[$code])) {
			$this->internalServerError();
			return;
		}

		$this->status['code'] = $code;
		$this->status['message'] = self::$codes[$code];

		if ($reason !== null) {
			$this->status['reason'] = $reason;
		}

		if ($code > 299 || $code < 200) {
			$this->result = [];

			if ($code > 399) {
				$this->status['ttl'] = 0;
			}
		}
	}

	public function okay($reason = null) {
		$this->setStatus(200, $reason);
	}

	public function created($reason = null) {
		$this->setStatus(201, $reason);
	}

	public function noContent($reason = null) {
		$this->setStatus(204, $reason);
	}

	public function notModified($reason = null) {
		$this->setStatus(304, $reason);
	}

	public function badRequest($reason = null) {
		$this->setStatus(400, $reason);
	}

	public function unauthorized($reason = null) {
		$this->setStatus(401, $reason);
	}

	public function notFound($reason = null) {
		$this->setStatus(404, $reason);
	}

	public function methodNotAllowed($reason = null) {
		$this->setStatus(405, $reason);
	}

	public function notAcceptable($reason = null) {
		$this->setStatus(406, $reason);
	}

	public function lengthRequired($reason = null) {
		$this->setStatus(411, $reason);
	}

	public function preconditionFailed($reason = null) {
		$this->setStatus(412, $reason);
	}

	public function rangeNotSatisfiable($reason = null) {
		$this->setStatus(416, $reason);
	}

	public function expectationFailed($reason = null) {
		$this->setStatus(417, $reason);
	}

	public function tooManyRequests($reason = null) {
		$this->setStatus(429, $reason);
	}

	public function internalServerError($reason = null) {
		$this->setStatus(500, $reason);
	}

	public function notImplemented($reason = null) {
		$this->setStatus(501, $reason);
	}

	public function badGateway($reason = null) {
		$this->setStatus(502, $reason);
	}

	public function serviceUnavailable($reason = null) {
		$this->setStatus(503, $reason);
	}

	public function gatewayTimeout($reason = null) {
		$this->setStatus(504, $reason);
	}
}
