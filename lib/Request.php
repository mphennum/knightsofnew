<?php

namespace KON;

use DateTime;
use DateInterval;

use KON\DB\Session;

class Request {
	static public $map = [];

	public $cached;

	public $opts;

	public $host;
	public $resource;
	public $action;
	public $params;
	public $format;
	public $template;

	public $response;

	public function __construct($opts = []) {
		$this->opts = $opts;

		$this->cached = false;

		$this->host = $opts['host'];
		$this->resource = $opts['resource'];
		$this->action = $opts['action'];
		$this->params = $opts['params'];
		$this->format = $opts['format'];
		$this->template = $opts['template'];

		$this->response = new Response($opts);
	}

	public function hasError() {
		$status = $this->response->getStatus();
		return ($status['code'] !== 200);
	}

	public function setCache($response) {
		if ($response === false) {
			$this->cached = false;
			return;
		}

		$this->cached = true;
		$this->response->setStatus($response['status']['code'], isset($response['status']['reason']) ? $response['status']['reason'] : null);
		if (isset($response['result'])) {
			$this->response->setResult($response['result']);
		}
	}

	public function handle() {
		// reserve for later use
	}

	public function view() {
		$response = [
			'request' => [
				'resource' => $this->resource,
				'action' => $this->action,
				'params' => $this->params,
				'format' => $this->format
			],
			'status' => $this->response->getStatus()
		];

		$result = $this->response->getResult();
		if (!empty($result)) {
			$response['result'] = $result;
		}

		ob_start('ob_gzhandler');
		include __DIR__ . '/../tmpl/' . $this->host . '/' . $this->template . '.php';
		$output = ob_get_contents();
		ob_end_clean();

		$ttl = $response['status']['ttl'];
		if (!$this->cached && $ttl !== 0) {
			Cache::set($this->host . ':' . $this->resource . ':' . $this->action, $this->params, $response, $ttl, true);
		}

		return $output;
	}

	static public function factory($opts = []) {
		return ($opts['host'] === 'www') ? self::wwwFactory($opts) : self::apiFactory($opts);
	}

	static private function apiFactory($opts = []) {
		$opts['resource'] = isset($opts['resource']) ? $opts['resource'] : null;
		$opts['format'] = isset($opts['format']) ? $opts['format'] : 'json';
		$opts['template'] = 'json';

		if ($opts['format'] !== 'json') {
			$request = new Request($opts);
			$request->response->notAcceptable('Format not supported');
			return $request;
		}

		if (!isset($opts['resource'])) {
			$request = new Request($opts);
			$request->response->expectationFailed('Missing resource');
			return $request;
		}

		if (!isset($opts['action'])) {
			$request = new Request($opts);
			$request->response->expectationFailed('Missing action');
			return $request;
		}

		$file = realpath(__DIR__ . '/../req/api/' . $opts['resource'] . '/' . $opts['action'] . '.php');

		if (!isset(self::$map[$file]) && file_exists($file)) {
			require $file;
		}

		if (isset(self::$map[$file])) {
			$class = 'KON\\Request\\API\\' . self::$map[$file];
			$request = new $class($opts);
			if ($class::CACHEABLE) {
				$cache = Cache::get('api:' . $request->resource . ':' . $request->action, $request->params);
				$request->setCache($cache);
			}

			return $request;
		}

		$request = new Request($opts);
		$request->response->badRequest('Invalid resource or action');
		return $request;
	}

	static private function wwwFactory($opts = []) {
		$opts['resource'] = isset($opts['resource']) ? $opts['resource'] : 'r';
		$opts['format'] = isset($opts['format']) ? $opts['format'] : 'html';
		$opts['template'] = $opts['format'];

		if ($opts['format'] !== 'html') {
			$request = new Request($opts);
			$request->template = 'error';
			$request->response->notAcceptable('Format not supported');
			return $request;
		}

		if (isset($opts['sid'])) {
			$session = new Session();
			if ($session->read($opts['sid'], true)) {
				$session->request();
			} else {
				$opts['sid'] = null;
			}
		}

		if (!isset($opts['sid'])) {
			$dt = new DateTime('now', App::$utc);
			$date = $dt->format('Y-m-d H:i:s');

			$opts['sid'] = Session::genUUID();

			$session = new Session();
			$session->id = $opts['sid'];
			$session->ip = $opts['ip'];
			$session->requested = $date;
			$session->created = $date;

			$dt->add(new DateInterval('P1Y'));
			$date = $dt->format('D, d M Y H:i:s') . ' GMT';

			header('Set-Cookie: sid=' . $opts['sid'] . '; Path=/; Domain=' . Config::WWWHOST . '; Expires=' . $date);

			$session->create(true);
		}

		$file = realpath(__DIR__ . '/../req/www/' . $opts['resource'] . '.php');

		if (!isset(self::$map[$file]) && file_exists($file)) {
			require $file;
		}

		if (isset(self::$map[$file])) {
			$class = 'KON\\Request\\WWW\\' . self::$map[$file];
			$request = new $class($opts);
			$request->template = $class::TEMPLATE;
			if ($class::CACHEABLE) {
				$cache = Cache::get('www:' . $request->resource . ':' . $request->action, $request->params);
				$request->setCache($cache);
			}

			return $request;
		}

		$request = new Request($opts);
		$request->response->notFound();
		$request->template = 'error';
		return $request;
	}
}
