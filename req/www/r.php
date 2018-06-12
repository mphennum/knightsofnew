<?php

namespace KON\Request\WWW;

use KON\Config;
use KON\Database;
use KON\DB\Post;
use KON\Request;

class R extends Request {
	const CACHEABLE = true;
	const TEMPLATE = 'sub';

	public function handle() {
		$all = ($this->action === null || $this->action === 'all');
		if ($all) {
			$this->response->sub = 'all';
			$subs = Config::$homesubs;
		} else {
			$this->response->sub = $this->action;
			$subs = explode('+', $this->action);
		}

		$where = [];
		$pdovars = [];
		for ($i = 0, $n = count($subs); $i < $n; ++$i) {
			$where[] = '`sub` = :sub' . $i;
			$pdovars[':sub' . $i] = $subs[$i];
		}

		$where = implode(' OR ', $where);

		if ($all) {
			$where .= ' AND `nsfw` = 0';
		}

		$rows = Database::read(Post::TABLE, $where, $pdovars, '`created` DESC', 100);

		$posts = [];
		for ($i = 0, $n = count($rows); $i < $n; ++$i) {
			$post = new Post();
			$post->setRow($rows[$i]);
			$posts[] = $post->getAPIFields();
		}

		$this->response->posts = $posts;
		$this->response->setTTL(Config::SHORTCACHE);
	}
}

Request::$map[__FILE__] = 'R';
