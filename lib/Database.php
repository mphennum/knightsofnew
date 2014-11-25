<?php

namespace KON;

use PDO;

abstract class Database {
	static private $queue;
	static private $pdo;

	// init

	static public function init() {
		self::$queue = [];
		self::$pdo = new PDO('mysql:dbname=' . Config::DBNAME . ';host=' . Config::DBHOST, Config::DBUSER, Config::DBPASS);
	}

	// crud

	static public function create($table, $params, $shutdown = false) {
		$fields = '';
		$values = '';
		$pdovars = [];
		foreach ($params as $key => $value) {
			$fields .= '`' . $key . '`,';
			$values .= ':' . $key . ',';
			$pdovars[':' . $key] = $value;
		}

		$fields = substr($fields, 0, -1);
		$values = substr($values, 0, -1);

		$sql = 'INSERT INTO `' . $table . '` (' . $fields . ') VALUES (' . $values . ');';

		if ($shutdown) {
			self::$queue[] = [
				'sql' => $sql,
				'pdovars' => $pdovars
			];

			return true;
		}

		$statement = self::$pdo->prepare($sql);
		return $statement->execute($pdovars);
	}

	static public function read($table, $where = null, $pdovars = [], $orderby = null, $limit = 10) {
		$sql =
			'SELECT * FROM `' . $table . '`' .
			($where === null ? '' : ' WHERE ' . $where) .
			($orderby === null ? '' : ' ORDER BY ' . $orderby) .
			' LIMIT ' . (int) $limit . ';'
		;

		return self::fetch($sql, $pdovars);
	}

	static public function readOne($table, $where = null, $pdovars = [], $orderby = null) {
		$sql =
			'SELECT * FROM `' . $table . '`' .
			($where === null ? '' : ' WHERE ' . $where) .
			($orderby === null ? '' : ' ORDER BY ' . $orderby) .
			' LIMIT 1;'
		;

		return self::fetchOne($sql, $pdovars);
	}

	static public function update($table, $params, $where = null, $pdovars = [], $shutdown = false) {
		$set = ' SET ';
		foreach ($params as $key => $value) {
			$set .= '`' . $key . '` = :_' . $key . ', ';
			$pdovars[':_' . $key] = $value;
		}

		$set = substr($set, 0, -2);

		$sql = 'UPDATE `' . $table . '`' . $set . ($where === null ? '' : ' WHERE ' . $where) . ';';

		if ($shutdown) {
			self::$queue[] = [
				'sql' => $sql,
				'pdovars' => $pdovars
			];

			return true;
		}

		$statement = self::$pdo->prepare($sql);
		return $statement->execute($pdovars);
	}

	static public function delete() {
		return false;
	}

	static public function count($table, $where = null, $pdovars = []) {
		$sql =
			'SELECT count(*) FROM `' . $table . '`' .
			(($where === null) ? '' : ' WHERE ' . $where) . ';';
		;

		$row = self::fetchOne($sql, $pdovars);
		return ($row === null) ? null : $row['count(*)'];
	}

	// pdo

	static public function execute($sql, $pdovars = [], $shutdown = false) {
		if ($shutdown) {
			self::$queue[] = [
				'sql' => $sql,
				'pdovars' => $pdovars
			];

			return null;
		}

		$statement = self::$pdo->prepare($sql);
		$statement->execute($pdovars);
		return $statement;
	}

	static public function fetch($sql, $pdovars = []) {
		$statement = self::execute($sql, $pdovars);
		return $statement->fetchAll(PDO::FETCH_ASSOC);
	}

	static public function fetchOne($sql, $pdovars = []) {
		$statement = self::execute($sql, $pdovars);

		if ($statement->rowCount() === 0) {
			return null;
		}

		return $statement->fetch(PDO::FETCH_ASSOC);
	}

	// shutdown

	static public function shutdown() {
		foreach (self::$queue as $item) {
			$statement = self::$pdo->prepare($item['sql']);
			$statement->execute($item['pdovars']);
		}
	}
}
