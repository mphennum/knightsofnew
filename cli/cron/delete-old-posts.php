#!/usr/bin/php
<?php

namespace KON;

require __DIR__ . '/../../sys/bootstrap.php';

$opts = getopt('', ['help']);

if (isset($opts['help'])) {
	CLI::message('Delete old posts');
	CLI::message('usage: ', 'delete-old-posts.php');
	exit(0);
}

CLI::title('Delete old posts');

$time = Config::POSTTIME;
if ($time < 60) {
	$time = $time . ' SECOND';
} else if ($time < 3600) {
	$time = round($time / 60, 2) . ' MINUTE';
} else if ($time < 86400) {
	$time = round($time / 3600, 2) . ' HOUR';
} else {
	$time = round($time / 86400, 2) . ' DAY';
}

$where = '`created` < DATE_SUB(NOW(), INTERVAL ' . $time . ')';
$count = Database::count('posts', $where);
Database::fetch('DELETE FROM `posts` WHERE ' . $where . ';');
CLI::message($count, ' posts deleted');
