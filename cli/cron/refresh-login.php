#!/usr/bin/php
<?php

namespace KON;

use KON\DB\Session;

require __DIR__ . '/../../sys/bootstrap.php';

$opts = getopt('', ['help']);

if (isset($opts['help'])) {
	CLI::message('Refresh login');
	CLI::message('usage: ', 'refresh-login.php');
	exit(0);
}

CLI::title('Refresh login');

// refresh recent sessions

$rows = Database::read(Session::TABLE, '`expires` > NOW() AND `expires` < DATE_ADD(NOW(), INTERVAL 10 MINUTE) AND `requested` > DATE_SUB(NOW(), INTERVAL 1 DAY)');
$success = 0;
for ($i = 0, $n = count($rows); $i < $n; ++$i) {
	$row = $rows[$i];

	$ch = curl_init();

	curl_setopt($ch, CURLOPT_URL, 'https://ssl.reddit.com/api/v1/access_token');
	curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
	curl_setopt($ch, CURLOPT_USERPWD, Config::REDDITKEY . ':' . Config::REDDITSECRET);
	curl_setopt($ch, CURLOPT_POST, true);
	curl_setopt($ch, CURLOPT_POSTFIELDS,
		'grant_type=refresh_token' .
		'&refresh_token=' . rawurlencode($row['refresh'])
	);

	$resp = curl_exec($ch);
	$info = curl_getinfo($ch);
	curl_close($ch);

	if ($resp === false || $info['http_code'] !== 200) {
		CLI::warning('Failed to refresh ' . $row['id']);
		continue;
	}

	$resp = json_decode($resp, true);

	if (!isset($resp['access_token']) || !isset($resp['expires_in'])) {
		CLI::warning('Failed to refresh ' . $row['id']);
		continue;
	}

	CLI::printr($resp);

	$session = new Session();
	$session->setRow($row);
	$session->refresh($resp['access_token'], (int) $resp['expires_in']);
	++$success;
}

CLI::notice($success . ' / ' . $i . ' logins refreshed');


// expire sessions

$where = '`expires` < NOW()';
$count = Database::count(Session::TABLE, $where);
Database::update(Session::TABLE, ['access' => null, 'expires' => null, 'refresh' => null], $where);

CLI::notice($count . ' logins expired');
