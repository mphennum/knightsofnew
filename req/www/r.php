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
		if ($this->action === null || $this->action === 'all') {
			$this->response->sub = 'all';
			$rows = Database::read(Post::TABLE, '`nsfw` = 0', [], '`created` DESC', 50);
		} else {
			$subs = explode('+', $this->action);
			$where = '';
			$pdovars = [];

			for ($i = 0, $n = count($subs); $i < $n; ++$i) {
				$where .= '`sub` = :sub' . $i . ' OR ';
				$pdovars[':sub' . $i] = $subs[$i];
			}

			$where = substr($where, 0, -4);

			$this->response->sub = $this->action;
			$rows = Database::read(Post::TABLE, $where, $pdovars, '`created` DESC', 10);
		}

		$posts = [];
		for ($n = count($rows), $i = $n > 5 ? $n - 6 : 0; $i < $n; ++$i) {
			$post = new Post();
			$post->setRow($rows[$i]);
			$posts[] = $post->getAPIFields();
		}

		$this->response->posts = $posts;
		$this->response->setTTL(Config::SHORTCACHE);
	}
}

Request::$map[__FILE__] = 'R';
