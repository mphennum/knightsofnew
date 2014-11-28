<?php

namespace KON\DB;

use DateTime;

use KON\App;
use KON\Config;
use KON\Database;

class Session extends Row {
	const TABLE = 'sessions';

	public function __construct() {
		parent::__construct(self::TABLE, 'id', true);

		$this->fields = [
			'id' => null,
			'ip' => null,
			'nsfw' => false,
			'access' => null,
			'refresh' => null,
			'expires' => null,
			'requested' => null,
			'created' => null
		];
	}

	// login / logout

	public function isLoggedIn() {
		return ($this->fields['access'] !== null);
	}

	public function login($access, $refresh, $ttl) {
		$dt = new DateTime('now', App::$utc);
		$dt->setTimestamp($dt->getTimestamp() + (int) $ttl);

		$this->access = $access;
		$this->refresh = $refresh;
		$this->expires = $dt->format('Y-m-d H:i:s');
		$this->update(true);
	}

	public function logout() {
		$this->fields['access'] = null;
		$this->fields['refresh'] = null;
		$this->fields['expires'] = null;
		$this->update(true);
	}

	// fields

	public function getAPIFields() {
		return [
			'id' => $this->fields['id'],
			'nsfw' => !!$this->fields['nsfw'],
			'logged-in' => $this->isLoggedIn()
		];
	}

	public function setRow($row) {
		$this->fields['id'] = $row['id'];
		$this->fields['ip'] = $row['ip'];
		$this->fields['nsfw'] = $row['nsfw'];
		$this->fields['access'] = $row['access'];
		$this->fields['refresh'] = $row['refresh'];
		$this->fields['expires'] = $row['expires'];
		$this->fields['requested'] = $row['requested'];
		$this->fields['created'] = $row['created'];
	}

	public function setJSON($json) {
		// do nothing
	}

	static public function genUUID() {
		$exists = true;
		while ($exists) {
			$id = '';
			$maxchar = strlen(Config::$chars) - 1;
			for ($i = 0; $i < Config::SIDLEN; ++$i) {
				$id .= Config::$chars{mt_rand(0, $maxchar)};
			}

			$exists = self::exists($id);
		}

		return $id;
	}

	// crud

	public function refresh($access, $ttl = 3600) {
		$this->fields['access'] = $access;

		$dt = new DateTime('now', App::$utc);
		$dt->setTimestamp($dt->getTimestamp() + $ttl);
		$this->fields['expires'] = $dt->format('Y-m-d H:i:s');

		$this->update(true);
	}

	public function request() {
		$dt = new DateTime('now', App::$utc);
		$this->fields['requested'] = $dt->format('Y-m-d H:i:s');
		$this->update(true);
	}

	static public function exists($id) {
		return (Database::readOne(self::TABLE, 'id = :id', [':id' => $id]) !== null);
	}
}
