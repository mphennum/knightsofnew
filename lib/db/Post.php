<?php

namespace KON\DB;

use DateTime;

use KON\App;
use KON\Config;
use KON\Database;

class Post extends Row {
	const TABLE = 'posts';

	public function __construct() {
		parent::__construct(self::TABLE, 'id');

		$this->fields = [
			'id' => null,
			'title' => null,
			'author' => null,
			'domain' => null,
			'url' => null,
			'sub' => null,
			'thumb' => null,
			'text' => null,
			'nsfw' => null,
			'created' => null
		];
	}

	public function isNew() {
		if ($this->fields['created'] === null) {
			return false;
		}

		$cdt = new DateTime($this->fields['created'], App::$utc);
		$cts = $cdt->getTimestamp();

		$ndt = new DateTime('now', App::$utc);
		$nts = $ndt->getTimestamp();

		return ($cts + Config::POSTTIME > $nts);
	}

	// fields

	public function getAPIFields() {
		$created = new DateTime($this->fields['created'], App::$utc);
		$created = $created->getTimestamp();

		return [
			'id' => $this->fields['id'],
			'title' => $this->fields['title'],
			'author' => $this->fields['author'],
			'domain' => $this->fields['domain'],
			'url' => $this->fields['url'],
			'sub' => $this->fields['sub'],
			'thumb' => $this->fields['thumb'],
			'text' => $this->fields['text'],
			'nsfw' => (bool) $this->fields['nsfw'],
			'created' => $created
		];
	}

	public function setRow($row) {
		$this->fields['id'] = $row['id'];
		$this->fields['title'] = $row['title'];
		$this->fields['author'] = $row['author'];
		$this->fields['domain'] = $row['domain'];
		$this->fields['url'] = $row['url'];
		$this->fields['sub'] = $row['sub'];
		$this->fields['thumb'] = $row['thumb'];
		$this->fields['text'] = $row['text'];
		$this->fields['nsfw'] = (int) $row['nsfw'];
		$this->fields['created'] = $row['created'];
	}

	public function setJSON($json) {
		$this->fields['id'] = $json['id'];
		$this->fields['title'] = $json['title'];
		$this->fields['author'] = strtolower($json['author']);
		$this->fields['domain'] = strtolower($json['domain']);
		$this->fields['url'] = $json['url'];
		$this->fields['sub'] = strtolower($json['subreddit']);
		$this->fields['thumb'] = preg_match('/^https?\/\//', $json['thumbnail']) ? $json['thumbnail'] : null;
		$this->fields['text'] = ($json['selftext'] === '') ? null : $json['selftext'];
		$this->fields['nsfw'] = $json['over_18'] ? 1 : 0;

		$dt = new DateTime('now', App::$utc);
		$dt->setTimestamp($json['created_utc']);
		$this->fields['created'] = $dt->format('Y-m-d H:i:s');
	}

	// crud

	static public function exists($id) {
		return (Database::readOne(self::TABLE, 'id = :id', [':id' => $id]) !== null);
	}
}
