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
		$all = (!isset($this->params['sub']) || $this->params['sub'] === 'all');
		if ($all) {
			$subs = Config::$homesubs;
		} else {
			$subs = explode('+', $this->params['sub']);
			Sub::requested($subs);
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
