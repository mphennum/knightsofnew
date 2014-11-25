<?php

namespace KON\DB;

use DateTime;

use KON\App;
use KON\Database;

class Sub extends Row {
	const TABLE = 'subs';

	public function __construct() {
		parent::__construct(self::TABLE, 'name');

		$this->fields = [
			'name' => null,
			'nsfw' => null,
			'requested' => null,
			'created' => null
		];
	}

	// fields

	public function getAPIFields() {
		return [];
	}

	public function setRow($row) {
		$this->fields['name'] = $row['name'];
		$this->fields['nsfw'] = (int) $row['nsfw'];
		$this->fields['requested'] = $row['requested'];
		$this->fields['created'] = $row['created'];
	}

	public function setJSON($json) {
		$this->fields['name'] = strtolower($json['display_name']);
		$this->fields['nsfw'] = $json['over18'] ? 1 : 0;

		$dt = new DateTime('now', App::$utc);
		$ts = $dt->format('Y-m-d H:i:s');

		$this->fields['requested'] = $ts;
		$this->fields['created'] = $ts;
	}

	// crud

	static public function exists($name) {
		return (Database::readOne(self::TABLE, 'name = :name', [':name' => $name]) !== null);
	}

	static public function requested($names = []) {
		$i = 0;
		$pdovars = [];
		$where = 'WHERE ';
		foreach ($names as $name) {
			$pdovars[':name' . $i] = $name;
			$where .= 'name = :name' . $i . ' OR ';
			++$i;
		}

		$where = substr($where, 0, -4);

		Database::execute('UPDATE `' . self::TABLE . '` SET `requested` = NOW() ' . $where . ';', $pdovars, true);
	}
}
