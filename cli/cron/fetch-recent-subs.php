#!/usr/bin/php
<?php

namespace KON;

use KON\DB\Post;

require __DIR__ . '/../../sys/bootstrap.php';

$opts = getopt('t:', ['help']);

if (isset($opts['help'])) {
	CLI::message('Fetch new posts / images from recently requested subs');
	CLI::message('usage: ', 'fetch-recent-subs.php');
	CLI::message('-t     ', 'number of seconds, default 3600');
	exit(0);
}

CLI::title('Fetch new posts / images from recently requested subs');

$time = isset($opts['t']) ? (int) $opts['t'] : 3600;
if ($time < 60) {
	$time = $time . ' SECOND';
} else if ($time < 3600) {
	$time = round($time / 60, 2) . ' MINUTE';
} else if ($time < 86400) {
	$time = round($time / 3600, 2) . ' HOUR';
} else {
	$time = round($time / 86400, 2) . ' DAY';
}

$rows = Database::fetch('SELECT `name` FROM `subs` WHERE `requested` > DATE_SUB(NOW(), INTERVAL ' . $time . ') ORDER BY `requested` DESC LIMIT 25;');

if (empty($rows)) {
	CLI::message('No recent subs');
}

$seen = [];
foreach ($rows as $row) {
	$limit = mt_rand(95, 100);
	$url = 'https://www.reddit.com/r/' . $row['name'] . '/new.json?limit=' . $limit;

	$ch = curl_init();
	curl_setopt($ch, CURLOPT_URL, $url);
	curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
	CLI::notice('curling: ' . $url);
	$resp = curl_exec($ch);
	$info = curl_getinfo($ch);
	curl_close($ch);

	if ($resp === false || $info['http_code'] !== 200) {
		CLI::warning('curl failed for: ' . $url);
	}

	$resp = json_decode($resp, true);
	if (!isset($resp['data']['children'])) {
		continue;
	}

	$resp = $resp['data']['children'];

	$i = 0;
	foreach ($resp as $json) {
		$post = new Post();
		$post->setJSON($json['data']);

		if (isset($seen[$post->id])) {
			continue;
		}

		$seen[$post->id] = true;

		if (!preg_match('/^https?:\/\/i\.imgur\.com\/[a-z0-9\-\_]+\.(?:jpe?g|gif|png)$/i', $post->url) || Post::exists($post->id) || !$post->isNew()) {
			continue;
		}

		CLI::message($post->title . ': ', $post->url . ' [' . $post->sub . ']');
		$post->create();

		++$i;
	}

	if ($i === 0) {
		CLI::message('No new posts');
	}
}
