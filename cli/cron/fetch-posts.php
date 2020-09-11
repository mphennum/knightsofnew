#!/usr/bin/php
<?php

namespace KON;

use KON\DB\Post;
use KON\DB\Sub;

require __DIR__ . '/../../sys/bootstrap.php';

$opts = getopt('', ['help']);

if (isset($opts['help'])) {
	CLI::message('Fetch new reddit posts / images');
	CLI::message('usage: ', 'fetch-posts.php');
	exit(0);
}

CLI::title('Fetch new reddit posts / images');

CLI::subtitle('Fetching new posts');

$limit = mt_rand(90, 100);
$url = 'https://www.reddit.com/domain/i.imgur.com/new.json?limit=' . $limit;

$ch = curl_init();
curl_setopt($ch, CURLOPT_URL, $url);
curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
CLI::notice('curling: ' . $url);
$resp = curl_exec($ch);
$info = curl_getinfo($ch);
curl_close($ch);

if ($resp === false || $info['http_code'] !== 200) {
	CLI::error('curl failed for: ' . $url);
}

$resp = json_decode($resp, true);
$resp = $resp['data']['children'];

$subs = [];
$seen = [];
foreach ($resp as $json) {
	$post = new Post();
	$post->setJSON($json['data']);

	if (isset($seen[$post->id])) {
		continue;
	}

	$subs[$post->sub] = true;
	$seen[$post->id] = true;

	if (!preg_match('/^https?:\/\/i\.imgur\.com\/[a-z0-9\-\_]+\.(?:jpe?g|gif|png)$/i', $post->url) || Post::exists($post->id) || !$post->isNew()) {
		continue;
	}

	CLI::message($post->title . ': ', $post->url . ' [' . $post->sub . ']');
	$post->create();
}

if (!empty($subs)) {
	$i = 0;
	$pdovars = [];
	$where = 'WHERE ';
	foreach ($subs as $name => $ignore) {
		$pdovars[':name' . $i] = $name;
		$where .= 'name = :name' . $i . ' OR ';
		++$i;
	}

	$where = substr($where, 0, -4);

	$rows = Database::fetch('SELECT `name` FROM `subs` ' . $where . ';', $pdovars);
	foreach ($rows as $row) {
		unset($subs[$row['name']]);
	}
}

CLI::subtitle('Creating new subs');

if (empty($subs)) {
	CLI::message('No new subs, complete');
	exit(0);
}

foreach ($subs as $name => $ignore) {
	$url = 'https://www.reddit.com/r/' . $name . '/about.json';
	$ch = curl_init();
	curl_setopt($ch, CURLOPT_URL, $url);
	curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
	CLI::notice('curling: ' . $url);
	$resp = curl_exec($ch);
	$info = curl_getinfo($ch);
	curl_close($ch);

	if ($resp === false || $info['http_code'] !== 200) {
		CLI::warning('curl failed for: ' . $url);
		continue;
	}

	$resp = json_decode($resp, true);

	$sub = new Sub();
	$sub->setJSON($resp['data']);

	CLI::message($sub->name, ' created');
	$sub->create();
}
