<?php

namespace KON;

use DateTimeZone;

abstract class App {
	static public $utc;

	static public function init() {
		if (!Config::DEVMODE && PHP_SAPI !== 'cli') {
			error_reporting(E_ERROR);
			ob_start();
		}

		self::$utc = new DateTimeZone('UTC');

		Database::init();
		Cache::init();

		register_shutdown_function([__CLASS__, 'shutdown']);
	}

	static public function handle() {
		$opts = [];

		$opts['host'] = (isset($_SERVER['HTTP_HOST']) && $_SERVER['HTTP_HOST'] === Config::WWWHOST) ? 'www' : 'api';
		$opts['origin'] = isset($_SERVER['HTTP_ORIGIN']) ? $_SERVER['HTTP_ORIGIN'] : null;
		$opts['referer'] = isset($_SERVER['HTTP_REFERER']) ? $_SERVER['HTTP_REFERER'] : null;
		$opts['method'] = isset($_SERVER['REQUEST_METHOD']) ? $_SERVER['REQUEST_METHOD'] : null;

		if ($opts['host'] === 'api') {
			if (isset($opts['origin'])) {
				$origin = $opts['origin'];
			} else if (isset($opts['referer'])) {
				$origin = preg_replace('/((?:https?:\/\/)?[^\/]+).*$/', '$1', $opts['referer']);
			} else {
				$origin = '*';
			}

			header('Access-Control-Allow-Origin: ' . $origin);

			if ($opts['method'] === 'OPTIONS') {
				header('Access-Control-Max-Age: 86400'); // 1 day
				header('Access-Control-Allow-Methods: GET, POST, PUT, DELETE, OPTIONS');
				exit(0);
			}
		}

		$opts['sid'] = isset($_COOKIE['sid']) ? $_COOKIE['sid'] : null;
		$opts['ip'] = isset($_SERVER['REMOTE_ADDR']) ? $_SERVER['REMOTE_ADDR'] : null;
		$opts['agent'] = isset($_SERVER['HTTP_USER_AGENT']) ? $_SERVER['HTTP_USER_AGENT'] : null;

		if ($opts['method'] === 'GET') {
			$opts['params'] = [];
			foreach ($_GET as $key => $value) {
				$opts['params'][rawurldecode($key)] = self::decodeParam($value);
			}
		}

		$opts['format'] = null;
		$opts['resource'] = null;
		$opts['action'] = null;
		if (isset($_SERVER['REQUEST_URI'])) {
			$uri = explode('?', $_SERVER['REQUEST_URI']);
			$uri = $uri[0];
			$uri = trim($uri);
			$uri = trim($uri, '/');
			$uri = explode('.', $uri);

			$request = explode('/', $uri[0]);
			$resource = array_shift($request);
			$action = implode('/', $request);

			$n = count($uri);
			if ($n > 1) {
				$opts['format'] = $uri[$n - 1];
			}

			$opts['resource'] = ($resource === '') ? null : $resource;
			$opts['action'] = ($action === '') ? null : $action;
		}

		$request = Request::factory($opts);
		if (!$request->cached) {
			$request->handle();
		}

		$output = $request->view();
		while (ob_get_level() !== 0) {
			ob_end_clean();
		}

		echo $output;
	}

	static public function decodeParam($param) {
		$param = rawurldecode($param);

		if ($param === '' || $param === 'true') {
			return true;
		}

		if ($param === 'false') {
			return false;
		}

		if ($param === 'null') {
			return null;
		}

		if (preg_match('/^-?[0-9]+$/', $param)) {
			return (int) $param;
		}

		if (preg_match('/^-?[0-9]*\.[0-9]+$/', $param)) {
			return (float) $param;
		}

		if (preg_match('/^[^,]+(,[^,]+)+$/', $param)) {
			$params = explode(',', $param);
			foreach ($params as &$param) {
				$param = self::decodeParam($param);
			}

			return $params;
		}

		return $param;
	}

	static public function shutdown() {
		if (function_exists('fastcgi_finish_request')) {
			fastcgi_finish_request();
		}

		ignore_user_abort(true);
		set_time_limit(0);

		Database::shutdown();
		Cache::shutdown();

		gc_collect_cycles();
		exit(0);
	}
}
