<?php

namespace KON;

use Memcached;

abstract class Cache {
	const SET = 0;
	const DELETE = 1;

	static private $queue;
	static private $memcached;

	static public function init() {
		self::$queue = [];

		self::$memcached = new Memcached('knightsofnew');
		self::$memcached->setOptions([
			//Memcached::OPT_TCP_NODELAY => true,
			//Memcached::OPT_RECV_TIMEOUT => 100000,
			//Memcached::OPT_SEND_TIMEOUT => 100000,
			//Memcached::OPT_SERVER_FAILURE_LIMIT => 25,
			//Memcached::OPT_CONNECT_TIMEOUT => 100,
			//Memcached::OPT_RETRY_TIMEOUT => 300,
			//Memcached::DISTRIBUTION => Memcached::DISTRIBUTION_CONSISTENT,
			//Memcached::OPT_REMOVE_FAILED_SERVERS => true,
			Memcached::OPT_HASH => Memcached::HASH_MURMUR,
			Memcached::OPT_SERIALIZER => Memcached::SERIALIZER_IGBINARY
		]);

		if (count(self::$memcached->getServerList()) === 0) {
			foreach (Config::$cacheServers as $host) {
				self::$memcached->addServer($host, 11211);
			}
		}
	}

	static public function get($key, $params = []) {
		return self::$memcached->get(self::getKey($key, $params));
	}

	static public function set($key, $params = [], $value, $ttl = Config::SHORTCACHE, $shutdown = false) {
		$key = self::getKey($key, $params);

		if ($shutdown) {
			self::$queue[] = [
				'type' => self::SET,
				'key' => $key,
				'value' => $value,
				'ttl' => $ttl
			];

			return true;
		}

		return self::$memcached->set($key, $value, $ttl - 1);
	}

	static public function delete($key, $params = [], $shutdown = false) {
		$key = self::getKey($key, $params);

		if ($shutdown) {
			self::$queue[] = [
				'type' => self::DELETE,
				'key' => $key
			];

			return true;
		}

		return self::$memcached->delete($key);
	}

	static public function getKey($key, $params = []) {
		$cacheKey = 'kon:' . $key;
		foreach ($params as $key => $value) {
			$cacheKey .= ':' . $key . '=' . (string) $value;
		}

		return $cacheKey;
	}

	// shutdown

	static public function shutdown() {
		foreach (self::$queue as $item) {
			if ($item['type'] === self::SET) {
				self::$memcached->set($item['key'], $item['value'], $item['ttl'] - 1);
			} else if ($item['type'] === self::DELETE) {
				self::$memcached->delete($item['key']);
			}
		}
	}
}
