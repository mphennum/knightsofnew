<?php

namespace KON\Request\API;

use KON\Config;
use KON\Database;
use KON\Request;
use KON\DB\Post;
use KON\DB\Sub;

class PostList extends Request {
	const CACHEABLE = true;

	public function handle() {
		$pdovars = [];
		if (!isset($this->params['sub']) || $this->params['sub'] === 'all') {
			$where = '`nsfw` = 0';
		} else {
			$where = '';
			$subs = explode('+', $this->params['sub']);
			Sub::requested($subs);
			for ($i = 0, $n = count($subs); $i < $n; ++$i) {
				$where .= '`sub` = :sub' . $i . ' OR ';
				$pdovars[':sub' . $i] = $subs[$i];
			}

			$where = substr($where, 0, -4);
		}

		$rows = Database::read(Post::TABLE, $where, $pdovars, '`created` DESC', 100);

		$posts = [];
		if (isset($this->params['since'])) {
			$since = $this->params['since'];
			foreach ($rows as $row) {
				if ($row['id'] === $this->params['since']) {
					break;
				}

				$post = new Post();
				$post->setRow($row);
				$posts[] = $post->getAPIFields();
			}
		} else {
			foreach ($rows as $row) {
				$post = new Post();
				$post->setRow($row);
				$posts[] = $post->getAPIFields();
			}
		}

		$this->response->posts = $posts;
		$this->response->setTTL(Config::MICROCACHE);
	}
}

Request::$map[__FILE__] = 'PostList';
