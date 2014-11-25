<?php

namespace KON;

require __DIR__ . '/../etc/Config.php';

require __DIR__ . '/../lib/App.php';
require __DIR__ . '/../lib/Cache.php';
require __DIR__ . '/../lib/Database.php';
require __DIR__ . '/../lib/Request.php';
require __DIR__ . '/../lib/Response.php';

require __DIR__ . '/../lib/db/Row.php';
require __DIR__ . '/../lib/db/Post.php';
require __DIR__ . '/../lib/db/Session.php';
require __DIR__ . '/../lib/db/Sub.php';

if (PHP_SAPI === 'cli') {
	require __DIR__ . '/../lib/CLI.php';
}

App::init();
