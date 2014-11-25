<?php

namespace KON\DB;

use DateTime;
use Exception;

use KON\App;
use KON\Cache;
use KON\Config;
use KON\Database;

abstract class Row {
	public $table;
	public $primary;
	public $cacheable;
	public $fields;

	abstract public function getAPIFields();
	abstract public function setRow($row);
	abstract public function setJSON($json);

	public function __construct($table, $primary, $cacheable = false) {
		$this->table = $table;
		$this->primary = $primary;
		$this->cacheable = $cacheable;
		$this->fields = [];
	}

	// fields

	public function getFields() {
		return $this->fields;
	}

	public function __get($key) {
		return isset($this->fields[$key]) ? $this->fields[$key] : null;
	}

	public function __set($key, $value) {
		if (!array_key_exists($key, $this->fields)) {
			throw new Exception('Invalid field "' . $key . '" for table "' . $this->table . '"');
		}

		$this->fields[$key] = $value;
	}

	// crud

	public function create($shutdown = false) {
		$success = Database::create($this->table, $this->fields, $shutdown);
		if ($success && $this->cacheable) {
			return Cache::set('db:' . $this->table, [$this->primary => $this->fields[$this->primary]], $this->fields, Config::LONGCACHE, $shutdown);
		}

		return $success;
	}

	public function read($primary, $shutdown = false) {
		if ($this->cacheable) {
			$cache = Cache::get('db:' . $this->table, [$this->primary => $primary]);
			if ($cache !== false) {
				$this->fields = $cache;
				return true;
			}
		}

		$row = Database::readOne($this->table, '`' . $this->primary . '` = :primary', [':primary' => $primary]);
		if ($row === null) {
			return false;
		}

		if ($this->cacheable) {
			Cache::set('db:' . $this->table, [$this->primary => $primary], $row, Config::LONGCACHE, $shutdown);
		}

		$this->fields = $row;
		return true;
	}

	public function update($shutdown = false) {
		if ($this->cacheable) {
			Cache::set('db:' . $this->table, [$this->primary => $this->fields[$this->primary]], $this->fields, Config::LONGCACHE, $shutdown);
		}

		return Database::update($this->table, $this->fields, '`' . $this->primary . '` = :primary', [':primary' => $this->fields[$this->primary]], $shutdown);
	}

	public function delete($shutdown = false) {
		// do nothing
	}
}
