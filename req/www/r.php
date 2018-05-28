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
		$pdovars = [];
		if ($this->action === null || $this->action === 'all') {
			$this->response->sub = 'all';
			$where = '`nsfw` = 0';
		} else {
			$where = [];
			$subs = explode('+', $this->action);
			for ($i = 0, $n = count($subs); $i < $n; ++$i) {
				$where[] = '`sub` = :sub' . $i;
				$pdovars[':sub' . $i] = $subs[$i];
			}

			$where = implode(' OR ', $where);

			$this->response->sub = $this->action;
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
